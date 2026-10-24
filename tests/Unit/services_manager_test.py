import importlib.util
import json
import os
from pathlib import Path
import plistlib
import subprocess
import tempfile
import unittest
from unittest.mock import patch

SOURCE = Path(__file__).resolve().parents[2] / 'services-manager.py'
spec = importlib.util.spec_from_file_location('service_manager', SOURCE)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class ServiceManagerTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.root = Path(self.temporary.name).resolve()
        self.environment = patch.dict(os.environ, {'SERVICES_ROOT': str(self.root), 'SERVICES_PHP': '/bin/sh', 'SERVICES_USER': 'www', 'SERVICES_ENV': 'local'}, clear=True)
        self.environment.start()
        self.platform = patch.object(module.platform, 'system', return_value='Darwin')
        self.platform.start()
        self.manager = module.Manager()

    def tearDown(self):
        self.platform.stop()
        self.environment.stop()
        self.temporary.cleanup()

    def test_launchd_definition_and_restart_policy(self):
        job = plistlib.loads(self.manager.definition('betfair', self.manager.instances['betfair']))
        self.assertEqual(['/bin/sh', str(self.root / 'artisan'), 'betfair:listen'], job['ProgramArguments'])
        self.assertTrue(job['KeepAlive'])
        self.assertEqual(120, job['ExitTimeOut'])
        self.assertEqual(10, job['ThrottleInterval'])
        self.assertTrue(job['RunAtLoad'])

    def test_production_scheduler_disabled_and_realtime_workers_configured(self):
        with patch.object(module.platform, 'system', return_value='Linux'), patch.dict(os.environ, {'SERVICES_ENV': 'production'}):
            manager = module.Manager()
            self.assertFalse(manager.instances['scheduler']['enabled'])
            self.assertEqual(9, len([name for name in manager.instances if name.startswith('queue-realtime-')]))
            unit = manager.definition('betfair', manager.instances['betfair']).decode()
            self.assertIn('Restart=always', unit)
            self.assertIn('KillSignal=SIGTERM', unit)
            self.assertIn('WantedBy=multi-user.target', unit)

    def test_queue_arguments_match_each_dispatched_sport_queue(self):
        for sport in ['football', 'cricket', 'icehockey', 'americanfootball', 'baseball', 'basketball', 'tennis', 'greyhounds', 'horseracing']:
            command = self.manager.instances['queue-realtime-' + sport]['command']
            self.assertEqual(['queue:work', 'sports-realtime', '--queue=realtime-' + sport, '--sleep=0', '--timeout=15', '--tries=6'], command)
        self.assertIn('--timeout=60', self.manager.instances['queue-default']['command'])
        self.assertIn('--tries=3', self.manager.instances['queue-default']['command'])
        self.assertIn('--timeout=30', self.manager.instances['queue-sports-images']['command'])
        self.assertNotIn('queue-realtime-1', self.manager.instances)

    def test_loaded_service_start_is_idempotent(self):
        with patch.object(self.manager, 'inspect', return_value=(True, 'running pid=123')), patch.object(self.manager, 'verify_existing'), patch.object(module, 'run') as run:
            self.manager.execute('start', 'betfair')
            run.assert_not_called()

    def test_disabled_services_never_start(self):
        self.manager.instances["pinnodds"]["enabled"] = False
        with patch.object(self.manager, 'inspect', return_value=(False, 'stopped')), patch.object(module, 'run') as run:
            self.manager.execute('start', 'pinnodds')
            self.assertTrue(all(call.args[0][1] != 'bootstrap' for call in run.call_args_list))

    def test_restart_stops_then_starts_only_selected_service(self):
        destination = self.root / 'betfair.plist'
        destination.write_bytes(self.manager.definition('betfair', self.manager.instances['betfair']))
        with patch.object(self.manager, 'destination', return_value=destination), patch.object(self.manager, 'inspect', return_value=(True, 'running pid=123')), patch.object(self.manager, 'verify_existing'), patch.object(self.manager, 'reject_unmanaged'), patch.object(module, 'run') as run:
            self.manager.execute('restart', 'betfair')
            calls = [call.args[0] for call in run.call_args_list]
            self.assertEqual('bootout', calls[0][1])
            self.assertEqual('bootstrap', calls[-1][1])
            self.assertTrue(all('sports-api-pro' not in ' '.join(call) for call in calls))

    def test_pending_unmanaged_process_blocks_duplicate_start(self):
        with patch.object(module, 'run', return_value=subprocess.CompletedProcess([], 0, '123 php artisan betfair:listen\n', '')):
            with self.assertRaisesRegex(ValueError, 'Unmanaged'):
                self.manager.reject_unmanaged(self.manager.instances['betfair'])

    def test_status_comes_from_supervisor_not_pid_file(self):
        with patch.object(module, 'run', return_value=subprocess.CompletedProcess([], 0, 'state = running\npid = 123\n', '')):
            self.assertEqual((True, 'running pid=123'), self.manager.inspect(self.manager.instances['betfair']))

    def test_reload_stops_removed_owned_service(self):
        self.manager.state.mkdir(parents=True)
        old = dict(self.manager.instances['betfair'], label='com.bfmarkets.removed')
        registry = self.manager.state / 'managed-local.json'
        registry.write_text(json.dumps({'removed': old}))
        with patch.object(self.manager, 'inspect', return_value=(True, 'running')), patch.object(self.manager, 'verify_existing'), patch.object(self.manager, 'destination', return_value=self.root / 'test.plist'), patch.object(self.manager, 'stop') as stop, patch.object(module, 'run'):
            (self.root / 'test.plist').write_bytes(self.manager.definition('betfair', self.manager.instances['betfair']))
            # Only one enabled service to isolate removed-service reconciliation.
            self.manager.instances = {'betfair': self.manager.instances['betfair']}
            self.manager.execute('reload')
            stop.assert_called_once_with(old, disable=True)
            self.assertNotIn('removed', json.loads(registry.read_text()))

    def test_docker_units_require_explicit_container_and_stop_definition(self):
        self.manager.mac = False
        unit = '[Service]\nExecStart=docker exec app php /srv/app/artisan betfair:listen\nExecStop=docker exec app stop\n'
        with patch.object(module, 'run', return_value=subprocess.CompletedProcess([], 0, unit, '')):
            with self.assertRaisesRegex(ValueError, 'SERVICES_CONTAINER'):
                self.manager.verify_existing(self.manager.instances['betfair'])
            with patch.dict(os.environ, {'SERVICES_CONTAINER': 'app', 'SERVICES_CONTAINER_ROOT': '/srv/app'}):
                self.manager.verify_existing(self.manager.instances['betfair'])

    def test_stop_uses_graceful_supervisor_stop(self):
        with patch.object(self.manager, 'inspect', return_value=(True, 'running')), patch.object(self.manager, 'verify_existing'), patch.object(module, 'run') as run:
            self.manager.execute('stop', 'betfair')
            self.assertEqual(['launchctl', 'bootout', self.manager.domain + '/com.bfmarkets.betfair'], run.call_args.args[0])

    def test_reload_updates_only_changed_owned_definition(self):
        self.manager.state.mkdir(parents=True)
        previous = dict(self.manager.instances['betfair'])
        content = self.manager.definition('betfair', previous)
        previous['definition_hash'] = module.hashlib.sha256(content).hexdigest()
        destination = self.root / 'betfair.plist'
        destination.write_bytes(content)
        (self.manager.state / 'managed-local.json').write_text(json.dumps({'betfair': previous}))
        self.manager.instances['betfair']['command'] = ['betfair:listen', '--no-interaction']
        with patch.object(self.manager, 'inspect', return_value=(True, 'running')), patch.object(self.manager, 'verify_existing'), patch.object(self.manager, 'destination', return_value=destination), patch.object(self.manager, 'reject_unmanaged'), patch.object(module, 'run') as run:
            self.manager.execute('reload', 'betfair')
            self.assertEqual('bootout', run.call_args_list[0].args[0][1])
            self.assertIn('--no-interaction', plistlib.loads(destination.read_bytes())['ProgramArguments'])
            self.assertEqual('bootstrap', run.call_args_list[-1].args[0][1])

    def test_prepare_does_not_start_or_install_services(self):
        with patch.object(module, 'run') as run:
            self.manager.execute('prepare', 'betfair')
            run.assert_not_called()
            self.assertTrue((self.manager.state / 'com.bfmarkets.betfair.plist').exists())

    def test_production_install_preserves_existing_units(self):
        self.manager.mac = False
        with patch.object(self.manager, 'inspect', return_value=(True, 'active/running')), patch.object(self.manager, 'verify_existing'), patch.object(self.manager, 'definition') as definition, patch.object(module, 'run') as run:
            self.manager.execute('install', 'betfair')
            definition.assert_not_called()
            run.assert_not_called()


if __name__ == '__main__':
    unittest.main()

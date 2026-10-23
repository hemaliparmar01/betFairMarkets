#!/usr/bin/env python3
"""Native launchd/systemd adapter. Does not run Laravel or supervise children itself."""
import argparse
import fcntl
import json
import hashlib
import os
from pathlib import Path
import platform
import plistlib
import re
import shutil
import subprocess
import sys

ROOT = Path(__file__).resolve().parent


def run(args, check=True):
    return subprocess.run(args, check=check, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)


def systemd_quote(value):
    return '"' + str(value).replace('\\', '\\\\').replace('"', '\\"').replace('%', '%%').replace('$', '$$') + '"'


class Manager:
    def __init__(self):
        self.mac = platform.system() == 'Darwin'
        self.environment = os.environ.get('SERVICES_ENV', 'local' if self.mac else 'production')
        if self.environment not in ('local', 'production'):
            raise ValueError('SERVICES_ENV must be local or production')
        self.root = Path(os.environ.get('SERVICES_ROOT', ROOT)).resolve()
        self.config = json.loads(Path(os.environ.get('SERVICES_CONFIG', ROOT / 'config/services-manager.json')).read_text())
        self.state = self.root / 'storage/app/service-manager'
        self.prefix = self.config.get('prefix', 'bfmarkets')
        if not re.fullmatch(r'[a-z][a-z0-9-]*', self.prefix):
            raise ValueError('Invalid service prefix')
        self.php = os.environ.get('SERVICES_PHP') or shutil.which('php')
        if not self.php and os.environ.get('SERVICES_CONTAINER'):
            self.php = 'php'
        if not self.php or (not os.environ.get('SERVICES_CONTAINER') and not Path(self.php).is_file()):
            raise ValueError('Set SERVICES_PHP to the PHP executable (Herd PHP locally)')
        self.domain = 'gui/' + str(os.getuid())
        self.instances = {}
        for name, original in self.config['services'].items():
            if not re.fullmatch(r'[a-z][a-z0-9-]*', name):
                raise ValueError('Invalid service name')
            spec = dict(original)
            spec.update(original.get('overrides', {}).get(self.environment, {}))
            command = spec['command']
            if not isinstance(command, list) or not command or not all(isinstance(x, str) and '\n' not in x and '\x00' not in x for x in command):
                raise ValueError('Commands must be Artisan argument arrays')
            if command[0] in ('serve', 'schedule:run'):
                raise ValueError('Web servers and cron schedule:run cannot be supervised here')
            if command[0] == 'schedule:work' and self.environment == 'production':
                spec['enabled'] = False
            spec['enabled'] = spec.get('enabled', True) and self.environment in spec.get('environments', ['local', 'production'])
            count = spec.get('workers', 1)
            if not isinstance(count, int) or not 1 <= count <= 32:
                raise ValueError('workers must be between 1 and 32')
            if spec.get('restart', 'always') not in ('always', 'on-failure', 'no'):
                raise ValueError('restart must be always, on-failure or no')
            for index in range(count):
                instance = name if count == 1 else name + '-' + str(index + 1)
                unit = spec.get('unit', self.prefix + '-' + instance + '.service')
                if not re.fullmatch(r'[a-zA-Z0-9@_.-]+\.service', unit):
                    raise ValueError('Invalid systemd unit')
                self.instances[instance] = dict(spec, name=name, unit=unit, label='com.' + self.prefix + '.' + instance)

    def selected(self, name):
        result = {k: v for k, v in self.instances.items() if name is None or name in (k, v['name'])}
        if not result:
            raise ValueError('Unknown service: ' + str(name))
        return result

    def definition(self, name, spec):
        directory = Path(spec.get('working_directory', self.root))
        cwd = str((directory if directory.is_absolute() else self.root / directory).resolve())
        args = [self.php, str(Path(cwd) / 'artisan')] + spec['command']
        logs = Path(spec.get('log_directory', self.state / 'logs')).resolve()
        restart = spec.get('restart', 'always')
        if self.mac:
            return plistlib.dumps({
                'Label': spec['label'], 'ProgramArguments': args, 'WorkingDirectory': cwd,
                'RunAtLoad': True, 'KeepAlive': {'SuccessfulExit': False} if restart == 'on-failure' else restart == 'always',
                'ThrottleInterval': self.config.get('restart_delay', 10),
                'ExitTimeOut': spec.get('stop_timeout', self.config.get('stop_timeout', 120)),
                'StandardOutPath': str(logs / (name + '.log')), 'StandardErrorPath': str(logs / (name + '.error.log')),
                'EnvironmentVariables': {'PATH': str(Path(self.php).parent) + ':' + os.environ.get('PATH', '/usr/bin:/bin')},
            })
        user = os.environ.get('SERVICES_USER')
        if not user:
            raise ValueError('Set SERVICES_USER to the application OS user before preparing new systemd units')
        if not re.fullmatch(r'[a-zA-Z_][a-zA-Z0-9_-]*', user):
            raise ValueError('Invalid SERVICES_USER')
        return ('[Unit]\nDescription=BF Markets ' + name + '\nAfter=network-online.target\nWants=network-online.target\n'
                'StartLimitIntervalSec=300\nStartLimitBurst=20\n\n[Service]\nType=simple\nUser=' + user + '\n'
                'WorkingDirectory=' + systemd_quote(cwd) + '\nExecStart=' + ' '.join(map(systemd_quote, args)) + '\n'
                'Restart=' + restart + '\nRestartSec=' + str(self.config.get('restart_delay', 10)) + '\n'
                'KillSignal=SIGTERM\nKillMode=control-group\nTimeoutStopSec=' + str(spec.get('stop_timeout', self.config.get('stop_timeout', 120))) + '\n'
                'StandardOutput=journal\nStandardError=journal\n\n[Install]\nWantedBy=multi-user.target\n').encode()

    def destination(self, spec):
        return (Path.home() / 'Library/LaunchAgents' / (spec['label'] + '.plist') if self.mac
                else Path('/etc/systemd/system') / spec['unit'])

    def inspect(self, spec):
        if self.mac:
            result = run(['launchctl', 'print', self.domain + '/' + spec['label']], False)
            state = re.search(r'\bstate = (.+)', result.stdout)
            pid = re.search(r'\bpid = (\d+)', result.stdout)
            return result.returncode == 0, (state.group(1) if state else 'stopped') + (' pid=' + pid.group(1) if pid else '')
        result = run(['systemctl', 'show', spec['unit'], '--property=LoadState,ActiveState,SubState,MainPID,FragmentPath,ExecStart,ExecStop,WorkingDirectory'])
        data = dict(line.split('=', 1) for line in result.stdout.splitlines() if '=' in line)
        return data.get('LoadState') == 'loaded', data.get('ActiveState', 'unknown') + '/' + data.get('SubState', '') + ' pid=' + data.get('MainPID', '0')

    def verify_existing(self, spec):
        if self.mac:
            path = self.destination(spec)
            if not path.exists():
                raise ValueError('Loaded launchd service has no managed definition: ' + spec['label'])
            definition = plistlib.loads(path.read_bytes())
            arguments = definition.get('ProgramArguments', [])
            if str(self.root / 'artisan') not in arguments or spec['command'][0] not in arguments:
                raise ValueError('launchd label belongs to another service/project: ' + spec['label'])
            return
        result = run(['systemctl', 'cat', spec['unit']]).stdout
        # Inspect and reuse existing Docker units, never overwrite their container/path configuration.
        if spec['command'][0] not in result or (str(self.root) not in result and 'docker' not in result):
            raise ValueError('Existing unit does not match configured service/project: ' + spec['unit'])
        if 'docker' in result:
            container = os.environ.get('SERVICES_CONTAINER')
            container_root = os.environ.get('SERVICES_CONTAINER_ROOT')
            if not container or not container_root or container not in result or container_root not in result:
                raise ValueError('Set SERVICES_CONTAINER and SERVICES_CONTAINER_ROOT to verify the existing Docker unit: ' + spec['unit'])
            if 'ExecStop=' not in result:
                raise ValueError('Docker unit needs a verified container-side graceful ExecStop: ' + spec['unit'])

    def reject_unmanaged(self, spec):
        # Conservative preflight: an unregistered Artisan instance must be migrated manually.
        processes = run(['ps', '-axo', 'pid=,command=']).stdout
        for line in processes.splitlines():
            if re.search(r'\bartisan\s+' + re.escape(spec['command'][0]) + r'(?:\s|$)', line):
                if spec['command'][0] != 'queue:work' or all(arg in line for arg in spec['command'][1:] if arg.startswith('--queue=')):
                    # Sibling configured workers are safe only when already owned by this supervisor.
                    if spec.get('workers', 1) > 1 and any(self.inspect(other)[1].split('pid=')[-1] == line.strip().split(None, 1)[0] for other in self.instances.values() if other['name'] == spec['name']):
                        continue
                    raise ValueError('Unmanaged matching Artisan process exists; inspect/migrate it before starting ' + spec['name'])

    def execute(self, action, name=None):
        selected = self.selected(name)
        if action == 'validate':
            for key, spec in selected.items():
                if self.mac or os.environ.get('SERVICES_USER'):
                    self.definition(key, spec)
            print('Configuration valid; ' + str(len(selected)) + ' service instances; environment=' + self.environment)
            return
        if action == 'prepare':
            self.state.mkdir(parents=True, exist_ok=True)
            for key, spec in selected.items():
                path = self.state / (spec['label'] + '.plist' if self.mac else spec['unit'])
                path.write_bytes(self.definition(key, spec))
                print(path)
            return
        if action == 'logs':
            if not self.mac:
                os.execvp('journalctl', ['journalctl', '-f', '-n', '100'] + [part for spec in selected.values() for part in ['-u', spec['unit']]])
            files = [str(Path(spec.get('log_directory', self.state / 'logs')).resolve() / (key + suffix)) for key, spec in selected.items() for suffix in ('.log', '.error.log')]
            files = [path for path in files if Path(path).exists()]
            if not files:
                print('No logs yet')
                return
            os.execvp('tail', ['tail', '-n', '100', '-F'] + files)
        if action == 'status':
            for key, spec in selected.items():
                loaded, status = self.inspect(spec)
                print(key + ': ' + status + (' [disabled in configuration]' if not spec['enabled'] else '') + (' [not installed/loaded]' if not loaded else ''))
            return
        self.reconcile(action, selected, name)

    def reconcile(self, action, selected, name):
        self.state.mkdir(parents=True, exist_ok=True)
        (self.state / 'logs').mkdir(exist_ok=True)
        registry = self.state / ('managed-' + self.environment + '.json')
        known = json.loads(registry.read_text()) if registry.exists() else {}
        # Validate all selected units before performing any service mutations.
        for key, spec in selected.items():
            loaded, _ = self.inspect(spec)
            if loaded:
                previous = known.get(key, {})
                self.verify_existing(previous if previous.get('definition_hash') else spec)
                if previous.get('definition_hash') and self.destination(spec).exists():
                    if hashlib.sha256(self.destination(spec).read_bytes()).hexdigest() != previous['definition_hash']:
                        raise ValueError('Managed definition was changed externally; review before applying: ' + key)
            if action in ('start', 'restart', 'reload') and spec['enabled'] and not loaded and not self.destination(spec).exists():
                raise ValueError('Missing unit for ' + key + '; run prepare, review, then install explicitly')
        if action == 'reload' and name is None:
            for key, old in known.items():
                if key not in self.instances:
                    self.stop(old, disable=True)
            known = {key: value for key, value in known.items() if key in self.instances}
        for key, spec in selected.items():
            loaded, status = self.inspect(spec)
            if action == 'install':
                if not spec['enabled']:
                    print('Skipped disabled service: ' + key)
                    continue
                if loaded and not self.mac:
                    print('Reusing existing unit (unchanged): ' + spec['unit'])
                    known[key] = spec
                    continue
                if os.environ.get('SERVICES_CONTAINER') and not self.mac:
                    raise ValueError('Docker: provide an existing verified systemd unit with container-side ExecStop; native units will not be installed')
                path = self.destination(spec)
                content = self.definition(key, spec)
                if path.exists() and path.read_bytes() != content:
                    raise ValueError('Existing definition differs; review manually: ' + str(path))
                path.parent.mkdir(parents=True, exist_ok=True)
                if self.mac:
                    Path(spec.get('log_directory', self.state / 'logs')).resolve().mkdir(parents=True, exist_ok=True)
                path.write_bytes(content)
                spec['definition_hash'] = hashlib.sha256(content).hexdigest()
                if not self.mac:
                    run(['systemctl', 'daemon-reload'])
                print('Installed (not started): ' + key)
            elif action == 'stop' or not spec['enabled']:
                self.stop(spec, disable=not spec['enabled'])
            else:
                if action == 'restart':
                    self.stop(spec)
                    loaded = False
                previous = known.get(key, {})
                if action == 'reload' and previous.get('definition_hash'):
                    content = self.definition(key, spec)
                    if self.destination(spec).read_bytes() != content:
                        self.stop(spec)
                        self.destination(spec).write_bytes(content)
                        loaded = False
                        if not self.mac:
                            run(['systemctl', 'daemon-reload'])
                    spec['definition_hash'] = hashlib.sha256(content).hexdigest()
                elif previous.get('definition_hash'):
                    spec['definition_hash'] = previous['definition_hash']
                elif action == 'reload' and not self.mac:
                    # Adopted production units remain authoritative and are never rewritten.
                    run(['systemctl', 'daemon-reload'])
                if not loaded or (not self.mac and not status.startswith('active/')):
                    self.reject_unmanaged(spec)
                    if self.mac:
                        run(['launchctl', 'enable', self.domain + '/' + spec['label']])
                        run(['launchctl', 'bootstrap', self.domain, str(self.destination(spec))])
                    else:
                        run(['systemctl', 'enable', '--now', spec['unit']])
                elif not self.mac:
                    run(['systemctl', 'enable', spec['unit']])
                print('Managed: ' + key)
            known[key] = spec
        temporary = registry.with_suffix('.tmp')
        temporary.write_text(json.dumps(known, indent=2))
        temporary.replace(registry)

    def stop(self, spec, disable=False):
        loaded, _ = self.inspect(spec)
        if self.mac:
            if loaded:
                run(['launchctl', 'bootout', self.domain + '/' + spec['label']])
            if disable:
                run(['launchctl', 'disable', self.domain + '/' + spec['label']])
        elif loaded:
            run(['systemctl', 'disable' if disable else 'stop', spec['unit']])
            if disable:
                run(['systemctl', 'stop', spec['unit']])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('action', choices=['start', 'stop', 'restart', 'status', 'logs', 'reload', 'validate', 'prepare', 'install'])
    parser.add_argument('service', nargs='?')
    args = parser.parse_args()
    manager = Manager()
    manager.state.mkdir(parents=True, exist_ok=True)
    with (manager.state / 'manager.lock').open('w') as lock:
        if args.action not in ('logs', 'status', 'validate'):
            fcntl.flock(lock, fcntl.LOCK_EX)
        manager.execute(args.action, args.service)


if __name__ == '__main__':
    try:
        main()
    except (ValueError, OSError, subprocess.CalledProcessError) as error:
        print('Service manager: ' + str(error), file=sys.stderr)
        sys.exit(1)

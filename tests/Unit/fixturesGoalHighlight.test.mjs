import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/users/js/sports/football.js', import.meta.url), 'utf8');
const highlightCode = source.slice(source.indexOf('const footballGoalState ='), source.indexOf('let openLiveStatsMatchId'));

function row(id, home = 0, away = 0, seconds = 40) {
    const classes = () => {
        const values = new Set();
        return { add: (...names) => names.forEach(name => values.add(name)), remove: (...names) => names.forEach(name => values.delete(name)), contains: name => values.has(name) };
    };
    const labels = [];
    const teams = Object.fromEntries(['home', 'away'].map(side => [side, { classList: classes(), append(label) { labels.push({ side, label }); label.remove = () => labels.splice(labels.findIndex(item => item.label === label), 1); } }]));
    return {
        dataset: { homeScore: String(home), awayScore: String(away), matchStatus: 'Live', goalHighlightSeconds: String(seconds) },
        classList: classes(), teams, labels,
        getAttribute: () => id, removeAttribute() {},
        querySelector: selector => teams[selector.includes('"home"') ? 'home' : 'away'],
        querySelectorAll: selector => selector === '[data-goal-indicator]' ? labels.map(item => item.label) : Object.values(teams).filter(team => team.classList.contains('fixture-goal-team')),
    };
}

function harness(initialRows) {
    let now = 100000;
    let rows = initialRows;
    let timerId = 0;
    const timers = new Map();
    const context = vm.createContext({
        Date: { now: () => now },
        document: { querySelectorAll: () => rows, createElement: () => ({ dataset: {} }) },
        window: {
            clearTimeout: id => timers.delete(id),
            setTimeout: (callback, delay) => { const id = ++timerId; timers.set(id, { callback, at: now + delay }); return id; },
        },
    });
    vm.runInContext(highlightCode, context);
    return {
        refresh: changed => vm.runInContext(`scheduleGoalHighlights(${Boolean(changed)})`, context),
        rows: value => { rows = value; },
        advance: milliseconds => { now += milliseconds; for (const [id, timer] of [...timers]) if (timer.at <= now) { timers.delete(id); timer.callback(); } },
        timers,
    };
}

test('initial render and initial snapshot do not flash; changed team flashes for 40 seconds', () => {
    const match = row('1'); const ui = harness([match]); ui.refresh(false);
    match.dataset.homeScore = '1'; ui.refresh(false);
    assert.equal(match.labels.length, 0);
    match.dataset.homeScore = '2'; ui.refresh(true);
    assert(match.classList.contains('fixture-goal-active'));
    assert(match.teams.home.classList.contains('fixture-goal-team'));
    assert.equal(match.labels[0].label.textContent, 'Goal');
    assert.equal(match.labels[0].side, 'home');
    ui.advance(39999); assert.equal(match.labels.length, 1);
    ui.advance(20); assert.equal(match.labels.length, 0);
    assert.equal(ui.timers.size, 0);
});

test('identical events do not extend the effect; a subsequent goal restarts one timer', () => {
    const match = row('1'); const other = row('2'); const ui = harness([match, other]); ui.refresh(false);
    match.dataset.homeScore = '1'; ui.refresh(true);
    ui.advance(20000); ui.refresh(true);
    assert.equal(ui.timers.size, 1);
    ui.advance(20020); assert.equal(match.labels.length, 0);
    match.dataset.homeScore = '2'; ui.refresh(true);
    ui.advance(20000); match.dataset.awayScore = '1'; ui.refresh(true);
    assert.equal(match.labels[0].side, 'away');
    assert.equal(ui.timers.size, 1);
    assert.equal(other.labels.length, 0);
    ui.advance(39999); assert.equal(match.labels.length, 1);
    ui.advance(20); assert.equal(match.labels.length, 0);
});

test('effect survives row replacement; corrections, finished status and removed rows clean up', () => {
    const match = row('1'); const ui = harness([match]); ui.refresh(false);
    match.dataset.homeScore = '1'; ui.refresh(true);
    const replaced = row('1', 1); ui.rows([replaced]); ui.refresh(false);
    assert.equal(replaced.labels.length, 1);
    replaced.dataset.homeScore = '0'; ui.refresh(true);
    assert.equal(replaced.labels.length, 0);
    replaced.dataset.awayScore = '1'; ui.refresh(true);
    replaced.dataset.matchStatus = 'Finished'; ui.refresh(true);
    assert.equal(replaced.labels.length, 0);
    ui.rows([]); ui.refresh(false); assert.equal(ui.timers.size, 0);
});

test('duration is configurable and missing scores never manufacture a goal', () => {
    const match = row('1', 0, 0, 5); match.dataset.homeScore = '';
    const ui = harness([match]); ui.refresh(false);
    match.dataset.homeScore = '1'; ui.refresh(true); assert.equal(match.labels.length, 0);
    match.dataset.awayScore = '1'; ui.refresh(true); assert.equal(match.labels.length, 1);
    ui.advance(5020); assert.equal(match.labels.length, 0);
});


test('a verified provider goal signal can highlight the first actual update without flashing snapshots', () => {
    const match = row('1'); match.dataset.goalSignal = '0';
    const ui = harness([match]); ui.refresh(false);
    match.dataset.homeScore = '1'; match.dataset.goalSignal = '999'; ui.refresh(false);
    assert.equal(match.labels.length, 1);
    ui.advance(20000); ui.refresh(false);
    ui.advance(20020); assert.equal(match.labels.length, 0);
});

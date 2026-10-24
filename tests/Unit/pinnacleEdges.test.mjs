import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
const source = readFileSync(new URL('../../resources/views/users/sports/football/marketActivity.blade.php', import.meta.url), 'utf8');
const helpers = source.slice(source.indexOf('                const sportKey ='), source.indexOf('                const filtered ='));
const renderCode = source.slice(source.indexOf('                const filtered ='), source.indexOf('                const openModal ='));
function harness(alerts = []) {
    const target = { innerHTML: '', textContent: '' };
    const state = { alerts, sport:'all', mode:'prematch', view:'simple', collapsedGroups:new Set() };
    const context = vm.createContext({ state, root: { dataset:{}, querySelectorAll:()=>[], querySelector:()=>target }, document:{createElement:()=>({set textContent(value){ this.innerHTML=String(value); }})} });
    vm.runInContext(helpers + renderCode + '\nglobalThis.api = { edge, pinnacleEdge, edgeText, edgeClass, render, changedDrops, runnerKey, selectionLabel, displayMarket };', context);
    return {api:context.api,state,target};
}
test('valid matching Betfair and NVP produce the expected positive/negative edge',()=>{
    const {api}=harness();
    assert.equal(api.edgeText(api.edge({bf_back:1.54,nvp:1.52})), '+1.3%');
    assert.equal(api.edgeText(api.edge({bf_back:1.50,nvp:1.52})), '-1.3%');
    assert.equal(api.edgeClass(-1), 'pinnacle-edge-neg');
    assert.equal(api.edgeClass(1), 'pinnacle-edge-pos');
    assert.equal(api.edgeText(api.edge({bf_back:4,nvp:2})), '+100.0%');
});
test('missing and invalid data never fabricate an edge',()=>{
    const {api}=harness();
    for(const value of [null,undefined,'',0,1,-2,'bad',Infinity]) {
        assert.equal(api.edge({bf_back:value,nvp:1.52}),null);
        assert.equal(api.edge({bf_back:1.54,nvp:value}),null);
    }
    assert.equal(api.edgeText(null),'N/A');
});
test('pinnacle comparison retains existing current price versus NVP semantics',()=>{
    const {api}=harness();
    assert.equal(api.edgeText(api.pinnacleEdge({to_price:1.47,nvp:1.52})), '-3.3%');
});
test('one renderer preserves grouping, counts, mode, and live changes',()=>{
    const alert = {id:1,home:'Home',away:'Away',sport:'soccer',league:'League',sect:'Moneyline',outcome:'Home',from_price:1.63,to_price:1.47,nvp:1.52,bf_back:1.54};
    const {api,state,target}=harness([alert]);
    api.render(); assert.match(target.innerHTML,/BF: \+1.3%/); assert.match(target.innerHTML,/data-group-body="0"/);
    state.view='advanced'; state.collapsedGroups.add('soccer|1|Home|Away|false'); api.render();
    assert.match(target.innerHTML,/aria-expanded="false"/); assert.match(target.innerHTML,/About NVP/); assert.match(target.innerHTML,/About Edge percentage/);
    alert.bf_back=1.60; api.render(); assert.match(target.innerHTML,/BF: \+5.3%/); assert.equal(state.view,'advanced');
});

test('exact total and handicap positions remain visible',()=>{
    const {api}=harness();
    assert.equal(api.displayMarket({sect:'total',point:2.5}), 'Over/Under 2.5 Goals');
    assert.equal(api.selectionLabel({sect:'total',point:2.5,outcome:'Over'}), 'Over 2.5 Goals');
    assert.equal(api.selectionLabel({sect:'spread',point:-1.25,outcome:'Home'}), 'Home (-1.25)');
});
test('flash is limited to an existing matching runner with a fresh genuine decrease',()=>{
    const {api}=harness();
    const before={id:1,home:'Home',away:'Away',sport:'soccer',sect:'total',point:2.5,outcome:'Over',from_price:2,to_price:1.8,alerted:100};
    assert.equal(api.changedDrops([], [before]).size,0);
    assert.equal(api.changedDrops([before], [{...before}]).size,0);
    assert.equal(api.changedDrops([before], [{...before,to_price:1.7,alerted:101}]).size,1);
    assert.equal(api.changedDrops([before], [{...before,to_price:1.7,alerted:99}]).size,0);
    assert.equal(api.changedDrops([before], [{...before,to_price:1.9,alerted:101}]).size,0);
    assert.equal(api.changedDrops([before], [{...before,outcome:'Under',to_price:1.7,alerted:101}]).size,0);
});

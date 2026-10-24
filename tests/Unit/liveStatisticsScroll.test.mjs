import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
const source=readFileSync(new URL('../../resources/users/js/sports/football.js',import.meta.url),'utf8');
const helpers=source.slice(source.indexOf('let liveStatsScrollState ='),source.indexOf('const closeLiveStatsPanel ='));
function panel(top, open){
    const periods=[{dataset:{statisticsPeriod:'ALL'},open},{dataset:{statisticsPeriod:'1ST'},open:!open}];
    const content={scrollTop:top,querySelectorAll:()=>periods};
    return {content,periods,querySelector:()=>content};
}
test('live refresh preserves internal scroll and expanded periods',()=>{
    const before=panel(640,true); const after=panel(0,false);
    const context=vm.createContext({openLiveStatsMatchId:'123',CSS:{escape:String},document:{querySelector:()=>before}});
    vm.runInContext(helpers+'\nglobalThis.api={captureLiveStatsScroll,applyLiveStatsScroll};',context);
    context.api.captureLiveStatsScroll(); context.api.applyLiveStatsScroll(after);
    assert.equal(after.content.scrollTop,640);
    assert.equal(after.periods[0].open,true);
    assert.equal(after.periods[1].open,false);
    context.openLiveStatsMatchId='456';
    const other=panel(0,false);context.api.applyLiveStatsScroll(other);assert.equal(other.content.scrollTop,0);
});
test('initial opening does not inherit scroll from another modal',()=>{
    const target=panel(20,true);
    const context=vm.createContext({openLiveStatsMatchId:null,CSS:{escape:String},document:{querySelector:()=>target}});
    vm.runInContext(helpers+'\nglobalThis.api={captureLiveStatsScroll,applyLiveStatsScroll};',context);
    context.api.captureLiveStatsScroll(); context.api.applyLiveStatsScroll(target);
    assert.equal(target.content.scrollTop,20);
});

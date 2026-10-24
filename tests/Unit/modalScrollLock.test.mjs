import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
const source=readFileSync(new URL('../../resources/users/js/modalScrollLock.js',import.meta.url),'utf8').replace('export function','function');
function harness(){
    let overlays=[]; const classes=new Set(); const variables=new Map(); const scrolls=[];
    const context=vm.createContext({
        window:{scrollX:7,scrollY:420,innerWidth:1000,addEventListener(){},scrollTo(options){scrolls.push(options);}},
        document:{body:{classList:{add:x=>classes.add(x),remove:x=>classes.delete(x)},style:{setProperty:(k,v)=>variables.set(k,v),removeProperty:k=>variables.delete(k)}},documentElement:{clientWidth:985},querySelectorAll:()=>overlays,addEventListener(){}},
        getComputedStyle:()=>({visibility:'visible',paddingRight:'16px'}),MutationObserver:class{observe(){}},
    });
    vm.runInContext(source+'\nglobalThis.check=synchronize;',context);
    return {classes,variables,scrolls,check(items){overlays=items;context.check();}};
}
const overlay=()=>({hidden:false,getAttribute:()=>null,getClientRects:()=>[{}]});
test('nested overlays retain lock until last close and restore exact scroll',()=>{
    const h=harness(); const a=overlay(); const b=overlay();
    h.check([a]); assert.equal(h.classes.has('modal-open'),true); assert.equal(h.variables.get('--modal-scroll-top'),'-420px');
    h.check([a,b]); a.hidden=true; h.check([a,b]); assert.equal(h.classes.has('modal-open'),true); assert.equal(h.scrolls.length,0);
    b.hidden=true; h.check([a,b]); assert.equal(h.classes.has('modal-open'),false); assert.equal(h.scrolls[0].top,420); assert.equal(h.scrolls[0].left,7);
});
test('removing a blocking overlay releases lock; hidden dialogs never lock',()=>{
    const h=harness(); const a=overlay(); a.hidden=true; h.check([a]); assert.equal(h.classes.has('modal-open'),false);
    a.hidden=false;h.check([a]);h.check([]);assert.equal(h.classes.has('modal-open'),false);
});

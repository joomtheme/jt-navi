/** JT Navi DOM regression checks. Run with jsdom available to Node (see docs/TESTING.md). */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const strings = {loading:'Loading', error:'Error', ai_result:'AI summary', local_result:'Source guide', sources:'Sources', show_more:'More', show_less:'Less', new_tab:'opens in a new tab'};
const moduleHtml = (id) => `<section class="jtnavi" id="${id}" data-endpoint="/index.php?option=com_jtnavi&task=assistant.ask&format=json" data-strings='${JSON.stringify(strings)}'><div class="jtnavi__body"><form class="jtnavi__form"><input name="question" value="Joomla"><button type="submit">Find</button></form><div class="jtnavi__status"></div><div class="jtnavi__results" tabindex="-1" hidden></div></div></section>`;
const dom = new JSDOM(moduleHtml('one') + moduleHtml('two'), {url:'https://example.org/subdir/',runScripts:'outside-only'});
const {window:w} = dom;
const sources = [
 {title:'Official guide',url:'https://manual.joomla.org/docs/',summary:'Guide'},
 {title:'Site article',url:'/article',summary:'Local'},
 {title:'<img src=x onerror=alert(1)>',url:'https://example.net/third',summary:'<script>alert(1)</script>'},
 {title:'Invalid script',url:'javascript:alert(1)'},
 {title:'Invalid data',url:'data:text/html,hello'},
 {title:'Credentials',url:'https://user:pass@example.net/'},
];
let payload = {mode:'local',answer:'Answer',sources,choices:[{label:'Hosting',query:'Hosting Joomla installation'}]};
const requests=[];
w.fetch = async (url,options={}) => {
 if (!options.method) return {ok:true,json:async()=>({success:true,data:{token:'a'.repeat(32)}})};
 requests.push(new URLSearchParams(options.body));
 return {ok:true,json:async()=>({success:true,data:payload})};
};
w.eval(fs.readFileSync(process.env.NAVI_SCRIPT || path.join(__dirname,'../extensions/mod_jtnavi/media/js/navi.js'),'utf8'));
w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
const roots = [...w.document.querySelectorAll('.jtnavi')];
async function submit(root) {
 root.querySelector('form').dispatchEvent(new w.Event('submit',{cancelable:true,bubbles:true}));
 for(let i=0;i<20 && root.hasAttribute('aria-busy');i++) await new Promise(setImmediate);
 assert.equal(root.hasAttribute('aria-busy'),false);
}
(async()=>{
 await submit(roots[0]);
 const links=[...roots[0].querySelectorAll('.jtnavi__source a')];
 assert.equal(links.length,3,'Unsafe protocols and credential URLs must be rejected');
 for(const link of links){
  assert.equal(link.target,'_blank','Every result must open a new tab');
  assert.equal(link.rel,'noopener noreferrer');
  assert.match(link.textContent,/opens in a new tab/);
 }
 assert.equal(links[1].href,'https://example.org/article');
 assert.equal(roots[0].querySelectorAll('img,script').length,0,'Source text must not become HTML');
 const items=[...roots[0].querySelectorAll('.jtnavi__source')];
 assert.equal(items[2].hidden,true);
 const more=roots[0].querySelector('.jtnavi__more');
 more.click(); assert.equal(items[2].hidden,false); assert.equal(more.getAttribute('aria-expanded'),'true');
 more.click(); assert.equal(items[2].hidden,true);
 assert.equal(roots[1].querySelector('.jtnavi__results').hidden,true,'Module state must stay independent');
 roots[0].querySelector('[data-query]').click();
 for(let i=0;i<20 && roots[0].hasAttribute('aria-busy');i++) await new Promise(setImmediate);
 assert.equal(requests.at(-1).get('question'),'Hosting Joomla installation');
 assert.equal(w.location.href,'https://example.org/subdir/','Choices must preserve the page');
 payload={mode:'ai',answer:'<b>Plain AI text</b>',sources};
 await submit(roots[1]);
 assert.equal(roots[1].querySelector('.jtnavi__badge').textContent,'AI summary');
 assert.equal(roots[1].querySelectorAll('b').length,0);
 assert.equal(roots[1].querySelector('a').target,'_blank');
 assert.equal(roots[0].querySelector('.jtnavi__badge').textContent,'Source guide');
 assert.equal(requests[0].get('a'.repeat(32)),'1');
 assert.equal(requests[0].get('ai'),'0');
 console.log('PASS: local/AI source links, safe URLs, text escaping, more/less, choices, tokens and independent modules');
 dom.window.close();
})().catch(e=>{console.error(e);dom.window.close();process.exitCode=1;});

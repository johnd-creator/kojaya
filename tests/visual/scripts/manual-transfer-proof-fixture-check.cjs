const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const assert = require('node:assert/strict');
const { createRequire } = require('node:module');
const { chromium } = createRequire(path.join(__dirname,'../../../package.json'))('@playwright/test');

const out = path.resolve(process.argv[2] || (()=>{throw new Error('Pass an isolated output directory');})());
fs.mkdirSync(out,{recursive:true});
const publicDir = path.resolve(__dirname,'../../../public');
const manifest = JSON.parse(fs.readFileSync(publicDir+'/build/manifest.json','utf8'));
const app = manifest['resources/js/app.ts'];
const css = [...new Set(Object.values(manifest).flatMap(v=>v.css||[]))];
const pdf = fs.readFileSync(path.resolve(__dirname,'../../Fixtures/manual-transfer-multipage.pdf'));
const results = [];
const requests = [];

function page(canApprove=true, pageNo=1) {
 const row=(id,proof,permission)=>({id,status:id===72?'APPROVED':id===73?'REJECTED':'PENDING',paid_at:'2026-10-23',payment_method:id===72?'QRIS':'TRANSFER',amount:id===72?'9876543210.00':'100000.00',notes:'FIXTURE SINTETIS - BUKAN TRANSAKSI NYATA',has_proof:proof,can_view_proof:permission,member:{name:'Anggota Sintetis '+id,member_no:'SYN-'+id},invoice:{contribution_type:{name:'Simpanan Wajib'}}});
 return {component:'Cooperative/Payments/Index',url:'/cooperative/payments',version:'synthetic-only',clearHistory:false,encryptHistory:false,
 props:{name:'Kojaya - PREVIEW SINTETIS',csrf_token:'synthetic-only',appearance:'light',sidebarOpen:true,flash:{},notifications:{unreadCount:0},contextualHelp:null,active_organization:null,user_organizations:[],
 auth:{user:{id:999999,name:'Admin Sintetis',email:'synthetic@example.test',roles:['Admin Koperasi'],organization_id:999999},roles:['Admin Koperasi'],permissions:['manage_cooperative_payments'],primary_role:'cooperative_admin',member_access:null},
 payments:{data:pageNo===1?[row(71,true,true),row(72,false,true),row(73,true,false)]:[row(74,true,true)],current_page:pageNo,from:pageNo===1?1:21,to:pageNo===1?20:21,last_page:2,per_page:20,total:21,links:[{label:'Previous',url:pageNo===1?null:'/cooperative/payments?page=1&approve='+Number(canApprove),active:false},{label:'1',url:'/cooperative/payments?page=1&approve='+Number(canApprove),active:pageNo===1},{label:'2',url:'/cooperative/payments?page=2&approve='+Number(canApprove),active:pageNo===2},{label:'Next',url:pageNo===2?null:'/cooperative/payments?page=2&approve='+Number(canApprove),active:false}],path:'/cooperative/payments'},members:[],contributionTypes:[],filters:{status:'PENDING',search:'',period:'',payment_method:'',sort_field:'paid_at',sort_direction:'desc'},canApprovePayments:canApprove}};
}

const mime={'.js':'text/javascript','.css':'text/css','.svg':'image/svg+xml','.png':'image/png','.woff2':'font/woff2','.ico':'image/x-icon'};
const server = http.createServer((req,res)=>{
 requests.push({method:req.method,url:req.url});
 if(req.method!=='GET'){res.writeHead(405);res.end('Synthetic fixture rejects writes');return;}
 const url=new URL(req.url,'http://127.0.0.1');
 if(url.pathname==='/cooperative/payments') {
  const payload=page(url.searchParams.get('approve')!=='0',Number(url.searchParams.get('page')||1));
  if(req.headers['x-inertia']){res.writeHead(200,{'Content-Type':'application/json','X-Inertia':'true'});res.end(JSON.stringify(payload));return;}
  const data=JSON.stringify(payload).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
  res.writeHead(200,{'Content-Type':'text/html'});
  res.end(`<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="synthetic-only">${css.map(v=>`<link rel="stylesheet" href="/build/${v}">`).join('')}<script type="module" src="/build/${app.file}"></script></head><body><div style="position:fixed;z-index:99999;left:5px;bottom:5px;background:#fff2bd;color:#493309;padding:6px;font:12px sans-serif">PREVIEW SINTETIS • TANPA AKUN/TRANSAKSI NYATA</div><div id="app" data-page="${data}"></div></body></html>`);return;
 }
 if(url.pathname==='/cooperative/payments/71/proof') {
  res.writeHead(200,{'Content-Type':'application/pdf','Content-Disposition':'attachment; filename="synthetic-proof.pdf"','Cache-Control':'private, no-store'});res.end(pdf);return;
 }
 if(url.pathname.includes('notification')) {res.writeHead(200,{'Content-Type':'application/json'});res.end(JSON.stringify({unread_count:0,data:[],meta:{unread_count:0}}));return;}
 const file=path.resolve(publicDir,'.'+decodeURIComponent(url.pathname));
 if(file.startsWith(path.resolve(publicDir)+path.sep)&&fs.existsSync(file)&&fs.statSync(file).isFile()){res.writeHead(200,{'Content-Type':mime[path.extname(file)]||'application/octet-stream'});fs.createReadStream(file).pipe(res);return;}
 res.writeHead(404);res.end('Missing fixture resource');
});

(async()=>{
 let browser;
 try {
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
  const origin=`http://127.0.0.1:${server.address().port}`;
  browser=await chromium.launch({headless:true});
  for(const [name,width,height] of [['desktop',1440,1000],['narrow',768,1024],['mobile',390,844]]) {
   for(const approve of [true,false]) {
    const context=await browser.newContext({viewport:{width,height},locale:'id-ID',acceptDownloads:true});
    const errors=[];
    await context.route('**/*',route=>new URL(route.request().url()).origin===origin?route.continue():route.abort());
    const p=await context.newPage();p.on('pageerror',e=>errors.push(e.message));
    await p.goto(origin+'/cooperative/payments?approve='+(approve?'1':'0'));
    const proof=p.locator('a[href="/cooperative/payments/71/proof"]:visible');
    await proof.waitFor({state:'visible',timeout:15000});
    assert.equal(await proof.count(),1);
    assert.equal(await p.locator('a[href="/cooperative/payments/72/proof"]').count(),0);
    assert.equal(await p.locator('a[href="/cooperative/payments/73/proof"]').count(),0);
    const amountMetrics = [];
    const amounts = p.locator('[data-testid="payment-amount"]:visible, [data-testid="payment-method"]:visible, [data-testid="payment-status"]:visible');
    assert.equal(
      await amounts.count(),
      width >= 640 ? 9 : 3,
      "Every synthetic payment must expose its full amount and desktop method/status",
    );
    for (
      let amountIndex = 0;
      amountIndex < (await amounts.count());
      amountIndex++
    ) {
      const amount = amounts.nth(amountIndex);
      await amount.scrollIntoViewIfNeeded();
      const metrics = await amount.evaluate((element) => {
        const range = document.createRange();
        range.selectNodeContents(element);
        const text = range.getBoundingClientRect();
        const cell = (
          element.closest("td") || element.closest("article")
        ).getBoundingClientRect();
        const label = element.getBoundingClientRect();
        return {
          value: element.textContent.trim(),
          left: text.left,
          right: text.right,
          top: text.top,
          bottom: text.bottom,
          cellLeft: Math.max(cell.left, label.left),
          cellRight: Math.min(cell.right, label.right),
          viewportWidth: innerWidth,
          viewportHeight: innerHeight,
        };
      });
      assert(
        metrics.left >= metrics.cellLeft - 1 &&
          metrics.right <= metrics.cellRight + 1,
        "Financial digits are clipped inside their cell: " +
          JSON.stringify(metrics),
      );
      assert(
        metrics.left >= -1 &&
          metrics.right <= width + 1 &&
          metrics.top >= -1 &&
          metrics.bottom <= height + 1,
        "Full amount cannot be brought into the viewport",
      );
      amountMetrics.push(metrics);
    }
    assert(
      amountMetrics.some(
        (m) => m.value.replace(/\s/g, "") === "Rp9.876.543.210",
      ),
      "Large synthetic amount must remain complete",
    );
    await proof.scrollIntoViewIfNeeded();
    const box=await proof.boundingBox();
    const overflow=await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
    if(!(box && box.x>=0 && box.x+box.width<=width+1)) {
      await p.screenshot({path:path.join(out,`synthetic-admin-proof-${name}-clipped.png`),fullPage:true});
      console.log(JSON.stringify({viewport:name,approve,box,width,overflow}));
    }
    assert(box && box.x>=0 && box.x+box.width<=width+1,'Proof button is clipped off viewport');
    assert(!overflow,'Page overflows horizontally');
    const screenshot=path.join(out,`synthetic-admin-proof-${name}-${approve?'approver':'viewer'}.png`);
    await p.screenshot({path:screenshot,fullPage:true});
    const before=requests.filter(x=>x.url==='/cooperative/payments/71/proof').length;
    await proof.click();
    await p.waitForTimeout(400);
    const after=requests.filter(x=>x.url==='/cooperative/payments/71/proof').length;
    assert(after>before,'Proof click did not request the existing download route');
    if (width >= 640) {
      const beforeSort = requests.length;
      const amountHeader = p.locator("th").filter({ hasText: "Nominal" });
      await amountHeader.scrollIntoViewIfNeeded();
      await Promise.all([
        p.waitForResponse((r) => r.url().includes("sort_field=amount")),
        amountHeader.click(),
      ]);
      assert(
        requests
          .slice(beforeSort)
          .some((r) => r.url.includes("sort_field=amount")),
        "Amount sorting must remain available",
      );
    }
    const pageTwo = p.locator('a:visible').filter({hasText:/^2$/});
    assert.equal(await pageTwo.count(),1,'Page 2 must remain accessible at every viewport');
    await pageTwo.click();
    await p.getByText('Anggota Sintetis 74',{exact:true}).filter({visible:true}).first().waitFor({state:'visible'});
    assert(requests.some(r=>r.url.includes('page=2')),'Pagination did not request page 2');
    assert.equal(errors.length,0,errors.join('\n'));
    results.push({viewport:name,width,approve,visibleProofLinks:1,unauthorizedAndMissingProofLinks:0,clickRequestedDownload:true,paginationPageTwo:true,horizontalOverflow:overflow,amountMetrics,screenshot,errors});
    await context.close();
   }
  }
  assert(!requests.some(x=>x.method!=='GET'));
  fs.writeFileSync(path.join(out,'admin-proof-browser-results.json'),JSON.stringify({fixtureOnly:true,actualBuiltVue:true,noLaravelServer:true,noDatabase:true,results,requests},null,2));
  console.log(JSON.stringify({pass:true,checks:results.length,viewports:['1440','768','390'],writes:0}));
 }catch(e){console.error(e.stack);fs.writeFileSync(path.join(out,'admin-proof-browser-failure.json'),JSON.stringify({message:e.message,results,requests},null,2));process.exitCode=1;}
 finally {if(browser)await browser.close();await new Promise(resolve=>server.close(resolve));}
})();

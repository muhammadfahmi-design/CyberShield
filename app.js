const $ = s => document.querySelector(s);
let chartData = [];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function toast(msg, bad=false){const t=$('#toast');t.textContent=msg;t.className='toast show'+(bad?' bad':'');setTimeout(()=>t.className='toast',2600)}
async function api(action,data={}) {
 const r=await fetch('api.php?action='+action,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
 const j=await r.json(); if(!r.ok || j.error) throw new Error(j.error||'Permintaan gagal'); return j;
}
function renderRows(id, rows, kind){
 const body=$(id);
 if(!rows.length){body.innerHTML='<tr><td colspan="8" class="empty">Belum ada data.</td></tr>';return}
 if(kind==='rules') body.innerHTML=rows.map(r=>`<tr><td>${esc(r.rule_name)}</td><td class="mono">${esc(r.ip_address)}</td><td>${r.port}</td><td><span class="pill ${r.action.toLowerCase()}">${r.action}</span></td><td>${r.status?'<span class="status-on">Aktif</span>':'<span class="status-off">Nonaktif</span>'}</td><td><button class="btn small" onclick="toggleRule(${r.id})">Aktif/Nonaktif</button> <button class="btn small danger" onclick="deleteRule(${r.id})">Hapus</button></td></tr>`).join('');
 if(kind==='logs') body.innerHTML=rows.map(l=>`<tr><td class="mono">${esc(l.event_time)}</td><td class="mono">${esc(l.source_ip)}</td><td>${l.destination_port}</td><td>${esc(l.activity)}</td><td><span class="pill ${l.action_taken.toLowerCase()}">${l.action_taken}</span></td><td>${esc(l.details)}</td></tr>`).join('');
 if(kind==='blacklist') body.innerHTML=rows.map(b=>`<tr><td class="mono">${esc(b.ip_address)}</td><td>${esc(b.reason)}</td><td>${esc(b.created_at)}</td><td><button class="btn small danger" onclick="removeBlacklist(${b.id})">Hapus</button></td></tr>`).join('');
}
function drawChart(history){
 const c=$('#trafficChart'); if(!c)return;
 const dpr=window.devicePixelRatio||1, w=c.clientWidth, h=155;
 c.width=w*dpr;c.height=h*dpr;const x=c.getContext('2d');x.scale(dpr,dpr);x.clearRect(0,0,w,h);
 const max=Math.max(3,...history.map(v=>+v.allowed_count + +v.blocked_count));
 x.strokeStyle='rgba(150,170,190,.13)';x.lineWidth=1;
 for(let i=0;i<4;i++){let y=12+i*(h-28)/3;x.beginPath();x.moveTo(30,y);x.lineTo(w-8,y);x.stroke()}
 const step=(w-48)/Math.max(1,history.length-1);
 history.forEach((v,i)=>{let xx=32+i*step, a=+v.allowed_count, b=+v.blocked_count;
   let ya=h-18-(a/max)*(h-40), yb=h-18-(b/max)*(h-40);
   x.fillStyle='#48a9ef';x.fillRect(xx-3,ya,6,(a/max)*(h-40));
   x.fillStyle='#f06475';x.fillRect(xx+4,yb,4,(b/max)*(h-40));
 });
}
function renderPorts(ports){const el=$('#portStats');if(!ports.length){el.innerHTML='<div class="empty">Belum ada data blokir.</div>';return}
 const max=Math.max(...ports.map(p=>+p.total));el.innerHTML=ports.map(p=>`<div class="port-row"><span>Port ${p.destination_port}</span><div class="port-meter"><i style="width:${(+p.total/max)*100}%"></i></div><b>${p.total}</b></div>`).join('')}
async function loadData(){
 try{const d=await (await fetch('api.php?action=stats')).json();
 $('#packets').textContent=Number(d.stats.packets).toLocaleString('id-ID');$('#allowed').textContent=Number(d.stats.allowed_count).toLocaleString('id-ID');
 $('#blocked').textContent=Number(d.stats.blocked_count).toLocaleString('id-ID');$('#activeRules').textContent=Number(d.stats.active_rules).toLocaleString('id-ID');
 renderRows('#rulesBody',d.rules,'rules');renderRows('#logsBody',d.logs,'logs');renderRows('#blacklistBody',d.blacklist,'blacklist');renderPorts(d.ports);drawChart(d.history);
 }catch(e){toast('Gagal memuat data: '+e.message,true)}
}
async function toggleRule(id){try{await api('rule_toggle',{id});await loadData();toast('Status aturan diperbarui')}catch(e){toast(e.message,true)}}
async function deleteRule(id){if(!confirm('Hapus aturan ini?'))return;try{await api('rule_delete',{id});await loadData();toast('Aturan dihapus')}catch(e){toast(e.message,true)}}
async function removeBlacklist(id){if(!confirm('Hapus IP dari blacklist?'))return;try{await api('blacklist_delete',{id});await loadData();toast('IP dihapus dari blacklist')}catch(e){toast(e.message,true)}}
$('#ruleForm').addEventListener('submit',async e=>{e.preventDefault();const data=Object.fromEntries(new FormData(e.target));data.port=+data.port;try{await api('rule_add',data);e.target.reset();await loadData();toast('Aturan firewall ditambahkan')}catch(err){toast(err.message,true)}});
$('#blacklistForm').addEventListener('submit',async e=>{e.preventDefault();const data=Object.fromEntries(new FormData(e.target));try{await api('blacklist_add',data);e.target.reset();await loadData();toast('IP masuk blacklist')}catch(err){toast(err.message,true)}});
async function simulate(mode){const m=$('#simMessage');m.textContent='Memproses event simulasi...';try{const d=await api('simulate',{mode});m.textContent=d.message+' '+d.created.map(x=>`${x.ip}:${x.port} → ${x.decision}`).join(' | ');toast(d.message);await loadData()}catch(e){m.textContent=e.message;toast(e.message,true)}}
function tick(){const d=new Date();$('#clock').textContent=d.toLocaleTimeString('id-ID')}
document.querySelectorAll('.nav').forEach(a=>a.addEventListener('click',()=>{document.querySelectorAll('.nav').forEach(n=>n.classList.remove('active'));a.classList.add('active')}));
tick();setInterval(tick,1000);loadData();setInterval(loadData,4000);window.addEventListener('resize',()=>drawChart(chartData));

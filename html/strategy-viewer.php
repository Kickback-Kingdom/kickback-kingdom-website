<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Strategy Flow — Working App (Backend‑Ready)</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    :root {
      --bg: #0f1115; --panel: #171a20; --muted: #8b94a7; --text: #e7ecf3; --accent: #7aa2f7; --ok: #7ee787; --line:#2a2f3a;
    }
    *{ box-sizing:border-box; }
    html, body { height: 100%; }
    body { margin:0; background:var(--bg); color:var(--text); font:14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, "Helvetica Neue", Arial; overflow:hidden; }

    .app { display:grid; grid-template-columns:320px 1fr; grid-template-rows:auto 1fr; grid-template-areas:"toolbar toolbar" "sidebar stage"; height:100vh; }
    header.toolbar { grid-area:toolbar; display:flex; gap:8px; align-items:center; padding:10px 12px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#151922,#11141b); position:sticky; top:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.25); }
    header .title { font-weight:700; letter-spacing:.3px; margin-right:8px; }
    .btn { border:1px solid var(--line); background:var(--panel); color:var(--text); padding:8px 10px; border-radius:12px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .btn:hover{ background:#1b1f28; border-color:#32384a; }
    .btn.primary{ background:#1d2230; border-color:#33405e; }
    .btn.danger{ background:#2a1818; border-color:#4a2a2a; color:#ffc9c9; }
    label.btn input{ display:none; }

    aside.sidebar{ grid-area:sidebar; border-right:1px solid var(--line); background:linear-gradient(180deg,#12161e,#0f141c); padding:12px; overflow:auto; }
    .group{ margin-bottom:14px; padding:12px; background:var(--panel); border:1px solid var(--line); border-radius:14px; }
    .group h3{ margin:0 0 8px; font-size:13px; color:var(--muted); font-weight:600; letter-spacing:.4px; }

    .stage{ grid-area:stage; position:relative; }
    canvas{ display:block; width:100%; height:100%; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSczMicgaGVpZ2h0PSczMic+PHJlY3Qgd2lkdGg9JzMyJyBoZWlnaHQ9JzMyJyBmaWxsPScjM2Y3ZTNmJy8+PHJlY3Qgd2lkdGg9JzE2JyBoZWlnaHQ9JzE2JyBmaWxsPScjMzU2YzM1Jy8+PHJlY3QgeD0nMTYnIHk9JzE2JyB3aWR0aD0nMTYnIGhlaWdodD0nMTYnIGZpbGw9JyMzNTZjMzUnLz48L3N2Zz4=') repeat; }
    .hint{ position:absolute; left:12px; bottom:8px; color:var(--muted); font-size:12px; opacity:.9; background:rgba(0,0,0,.25); padding:6px 8px; border-radius:10px; border:1px solid var(--line); }
    .toast{ position:absolute; top:12px; right:12px; background:#141925; border:1px solid #2a344a; padding:8px 12px; border-radius:10px; color:#cdd7eb; box-shadow:0 6px 22px rgba(0,0,0,.35); opacity:0; transform:translateY(-8px); pointer-events:none; transition:.25s ease; }
    .toast.show{ opacity:1; transform:translateY(0); }

    /* Modal */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: none; align-items: center; justify-content: center; z-index: 50; }
    .modal { width: 420px; max-width: 92vw; background: #121723; border: 1px solid #2b3449; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.45); overflow: hidden; }
    .modal header { padding: 12px 14px; font-weight: 700; background: #161c2d; border-bottom: 1px solid #2b3449; }
    .modal .content { padding: 12px 14px; }
    .modal .actions { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 14px; border-top: 1px solid #2b3449; }
    .field { margin-bottom: 10px; }
    .field label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
    .field input, .field select { width: 100%; padding: 8px 10px; border-radius: 10px; border: 1px solid #2a3040; background: #0f1420; color: var(--text); }

    /* Dev test bar (shows when test=1) */
    #testbar { position: fixed; left: 12px; top: 12px; background:#111824; border:1px solid #2a344a; color:#d2dcf3; padding:8px 10px; border-radius:10px; font:12px/1.4 system-ui; display:none; z-index: 100; }
    #testbar.ok { border-color:#1f3a28; color:#c8f7d0; }
    #testbar.fail { border-color:#4a2a2a; color:#ffc9c9; }
  </style>
</head>
<body>
  <div class="app">
    <header class="toolbar">
      <div class="title">Strategy Flow</div>
      <button class="btn" id="addMilestone">+ Milestone</button>
      <button class="btn" id="addTicket">+ Ticket</button>
      <button class="btn" id="addGoal">+ Goal</button>
      <button class="btn" id="connectMode">Connect</button>
      <button class="btn" id="autoLayout">Auto‑Layout</button>
      <button class="btn" id="exportBtn">Export JSON</button>
      <label class="btn" title="Import JSON">Import<input id="importFile" type="file" accept="application/json"></label>
      <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <button class="btn" id="help">Help</button>
        <button class="btn danger" id="deleteSelected">Delete</button>
      </div>
    </header>

    <aside class="sidebar">
      <div class="group"><h3>Legend</h3>
        <div style="display:grid; grid-template-columns:20px 1fr; gap:8px; align-items:center;">
          <div style="height:14px;border-radius:8px;background:#2b3243;border:1px solid var(--line);"></div><div>Milestone</div>
          <div style="height:14px;border-radius:8px;background:#2b3a33;border:1px solid var(--line);"></div><div>Ticket</div>
          <div style="height:14px;border-radius:8px;background:#3a2b33;border:1px solid var(--line);"></div><div>Goal</div>
        </div>
      </div>
      <div class="group"><h3>Selection</h3>
        <div id="selectionInfo" style="font-size:13px;color:#cfd6e6">—</div>
      </div>
      <div class="group"><h3>Tips</h3>
        <ul style="margin:0 0 0 16px; color:var(--muted);">
          <li>Connect: click Connect, then source → target.</li>
          <li>Pan: right‑drag or Space+drag. Zoom: wheel.</li>
          <li>Double‑click a node to edit details.</li>
        </ul>
      </div>
    </aside>

    <div class="stage">
      <canvas id="canvas"></canvas>
      <div class="hint" id="modeHint">Mode: Select/Drag</div>
      <div class="toast" id="toast">Saved</div>
    </div>
  </div>

  <div id="testbar"></div>

  <!-- Modal -->
  <div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal" role="document">
      <header id="modalTitle">Edit Node</header>
      <div class="content">
        <div class="field"><label for="fTitle">Title</label><input id="fTitle" placeholder="e.g., Launch Beta" /></div>
        <div class="field"><label for="fIcon">Icon</label><input id="fIcon" placeholder="fa-solid fa-flag or \\uf024" /></div>
        <div class="field"><label for="fType">Type</label>
          <select id="fType">
            <option value="milestone">Milestone</option>
            <option value="ticket">Ticket</option>
            <option value="goal">Goal</option>
          </select>
        </div>
        <div class="field" id="ticketFields" style="display:none;">
          <label for="fTicketId">Ticket ID</label>
          <input id="fTicketId" placeholder="e.g., PROJ-123" />
          <label for="fStatus" style="margin-top:8px;">Status</label>
          <select id="fStatus">
            <option>todo</option>
            <option>in-progress</option>
            <option>blocked</option>
            <option>done</option>
          </select>
        </div>
        <div class="field" id="goalFields" style="display:none;">
          <label for="fTarget">Target</label>
          <input id="fTarget" type="number" min="0" step="1" placeholder="e.g., 100" />
          <label for="fProgress" style="margin-top:8px;">Current</label>
          <input id="fProgress" type="number" min="0" step="1" placeholder="e.g., 42" />
        </div>
      </div>
      <div class="actions">
        <button class="btn" id="cancelEdit">Cancel</button>
        <button class="btn primary" id="saveEdit">Save</button>
      </div>
    </div>
  </div>

  <script>
  // =========================
  // Backend Plug‑in Adapters
  // =========================
  /** StorageAdapter interface (JSDoc)
   * @typedef {Object} StorageAdapter
   * @property {(diagramId:string)=>Promise<Diagram>} loadDiagram
   * @property {(diagram:Diagram)=>Promise<{version:string}>} saveDiagram
   */
  /** @typedef {{id:string,title?:string,icon?:string,x:number,y:number,w?:number,h?:number,type:'milestone'|'ticket'|'goal',date?:string,ticketId?:string,status?:'todo'|'in-progress'|'blocked'|'done',current?:number,target?:number}} Node */
  /** @typedef {{id:string, from:string, to:string}} Edge */
  /** @typedef {{id:string, nodes:Node[], edges:Edge[], version?:string}} Diagram */

  function createAdapters() {
    // Working default: persist to localStorage. Swap with real REST/GraphQL later.
    /** @type {StorageAdapter} */
    const storage = {
      async loadDiagram(diagramId){
        const raw = localStorage.getItem(key(diagramId));
        if (raw) { try { return JSON.parse(raw); } catch (e) { console.warn('Bad JSON, reseeding'); } }
        return { id: diagramId, ...seedData() };
      },
      async saveDiagram(diagram){
        const version = `v${Date.now()}`; const payload = { ...diagram, version };
        localStorage.setItem(key(diagram.id), JSON.stringify(payload));
        return { version };
      }
    };
    function key(id){ return `strategy-flow:${id}`; }
    return { storage };
  }

  // ===============
  // App State & UI
  // ===============
  const adapters = createAdapters();
  const params = new URLSearchParams(location.search);
  const diagramId = params.get('diagram') || 'local';

  const canvas = document.getElementById('canvas');
  const ctx = canvas.getContext('2d');
  const dpr = window.devicePixelRatio || 1;

  const images = {
    castle: new Image(),
    swordsBlue: new Image(),
    swordsRed: new Image()
  };
  images.castle.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAzMiAzMic+PHBhdGggZmlsbD0nI2IwYjBiMCcgZD0nTTQgMTRWNmg0djJoMlY2aDR2MmgyVjZoNHY4aDJ2MTJoLTZ2LThoLTR2OEg0VjE0aDJ6Jy8+PC9zdmc+';
  images.swordsBlue.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAzMiAzMic+PGcgc3Ryb2tlPScjNWZhMGZmJyBzdHJva2Utd2lkdGg9JzQnIHN0cm9rZS1saW5lY2FwPSdyb3VuZCc+PGxpbmUgeDE9JzYnIHkxPScyNicgeDI9JzI2JyB5Mj0nNicvPjxsaW5lIHgxPSc2JyB5MT0nNicgeDI9JzI2JyB5Mj0nMjYnLz48L2c+PC9zdmc+';
  images.swordsRed.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAzMiAzMic+PGcgc3Ryb2tlPScjZmY1ZjVmJyBzdHJva2Utd2lkdGg9JzQnIHN0cm9rZS1saW5lY2FwPSdyb3VuZCc+PGxpbmUgeDE9JzYnIHkxPScyNicgeDI9JzI2JyB5Mj0nNicvPjxsaW5lIHgxPSc2JyB5MT0nNicgeDI9JzI2JyB5Mj0nMjYnLz48L2c+PC9zdmc+';

  const iconCache={};
  function getIconGlyph(icon){
    if(!icon) return '';
    if(icon.includes('fa-')){
      if(iconCache[icon]!==undefined) return iconCache[icon];
      const i=document.createElement('i');
      i.className=icon;
      i.style.display='none';
      document.body.appendChild(i);
      let glyph=getComputedStyle(i,'::before').content.replace(/['"]/g,'');
      document.body.removeChild(i);
      if(!glyph || glyph==='none') glyph='';
      iconCache[icon]=glyph;
      return glyph;
    }
    if(icon.startsWith('\\u')) return String.fromCharCode(parseInt(icon.slice(2),16));
    if(/^&#x[0-9a-fA-F]+;?$/.test(icon)) return String.fromCharCode(parseInt(icon.replace(/^&#x|;$/g,''),16));
    if(/^0x[0-9a-fA-F]+$/.test(icon)) return String.fromCharCode(parseInt(icon.slice(2),16));
    if(/^[0-9a-fA-F]{4,6}$/.test(icon)) return String.fromCharCode(parseInt(icon,16));
    return icon;
  }
  function getIconFont(icon,size){
    if(icon.includes('fa-brands')) return `400 ${size}px 'Font Awesome 6 Brands'`;
    if(icon.includes('fa-regular')) return `400 ${size}px 'Font Awesome 6 Free'`;
    return `900 ${size}px 'Font Awesome 6 Free'`;
  }

  /** @type {Diagram} */
  let diagram = { id: diagramId, nodes: [], edges: [], version: undefined };

  const state = {
    camera: { x: 0, y: 0, z: 1 },
    selection: null, // Node | null
    drag: null, // {node, off:{x,y}} | null
    mode: 'select', // 'select' | 'connect'
    connectFrom: null, // nodeId | null
  };

  const clamp = (v,a,b)=>Math.max(a,Math.min(b,v));
  const uid = ()=> Math.random().toString(36).slice(2,10);

  // ---------- Canvas Sizing (FIX for ResizeObserver loop) ----------
  /**
   * The classic RO error happens if our resize callback causes layout changes
   * synchronously, which schedules another RO in the same cycle. We prevent
   * this by (1) watching the stable parent ".stage", not the canvas itself,
   * and (2) batching canvas size updates in rAF with guards.
   */
  const stageEl = document.querySelector('.stage');
  let roPending = false;
  function resizeCanvasIfNeeded(){
    const w = canvas.clientWidth, h = canvas.clientHeight;
    const targetW = Math.floor(w * dpr), targetH = Math.floor(h * dpr);
    if (canvas.width !== targetW || canvas.height !== targetH) {
      canvas.width = targetW; canvas.height = targetH; ctx.setTransform(dpr,0,0,dpr,0,0);
    }
  }
  function scheduleResizeFromRO(){
    if (roPending) return; roPending = true;
    requestAnimationFrame(()=>{ roPending = false; resizeCanvasIfNeeded(); });
  }
  const ro = new ResizeObserver(scheduleResizeFromRO);
  ro.observe(stageEl);
  window.addEventListener('resize', scheduleResizeFromRO);
  // Initial size
  scheduleResizeFromRO();

  // ---------- Coordinate Helpers ----------
  function worldToScreen(x,y){ const {x:cx,y:cy,z}=state.camera; return { x:(x-cx)*z, y:(y-cy)*z }; }
  function screenToWorld(x,y){ const {x:cx,y:cy,z}=state.camera; return { x:x/z+cx, y:y/z+cy }; }

  // ---------- Drawing ----------
  function drawGrid(){
    const { z, x:cx, y:cy } = state.camera;
    const spacing = 40;
    const w = canvas.clientWidth / z;
    const h = canvas.clientHeight / z;
    const startX = Math.floor((cx-50)/spacing)*spacing - spacing*2;
    const endX   = Math.ceil((cx + w + 50)/spacing)*spacing + spacing*2;
    const startY = Math.floor((cy-50)/spacing)*spacing - spacing*2;
    const endY   = Math.ceil((cy + h + 50)/spacing)*spacing + spacing*2;
    ctx.save(); ctx.lineWidth = 1 / z; ctx.strokeStyle = '#17202e'; ctx.beginPath();
    for(let x=startX; x<=endX; x+=spacing){ ctx.moveTo(x,startY); ctx.lineTo(x,endY); }
    for(let y=startY; y<=endY; y+=spacing){ ctx.moveTo(startX,y); ctx.lineTo(endX,y); }
    ctx.stroke(); ctx.restore();
  }

  function roundRect(x, y, w, h, r){ const rr=Math.min(r,w/2,h/2); ctx.beginPath(); ctx.moveTo(x+rr,y); ctx.arcTo(x+w,y,x+w,y+h,rr); ctx.arcTo(x+w,y+h,x,y+h,rr); ctx.arcTo(x,y+h,x,y,rr); ctx.arcTo(x,y,x+w,y,rr); ctx.closePath(); }
  function wrapText(text,maxWidth){ ctx.save(); ctx.font='600 13px system-ui'; const words=String(text||'').split(/\s+/); const lines=[]; let line=''; for(const w of words){ const test=line?line+' '+w:w; if(ctx.measureText(test).width<=maxWidth||!line) line=test; else { lines.push(line); line=w; } } if(line) lines.push(line); ctx.restore(); return lines.slice(0,3); }

  function drawArrow(from,to){ const ang=Math.atan2(to.y-from.y,to.x-from.x); const head=8; ctx.save(); ctx.lineWidth=2/state.camera.z; ctx.strokeStyle='#39445a'; ctx.fillStyle='#39445a'; ctx.beginPath(); ctx.moveTo(from.x,from.y); ctx.lineTo(to.x,to.y); ctx.stroke(); ctx.beginPath(); ctx.moveTo(to.x,to.y); ctx.lineTo(to.x-head*Math.cos(ang-Math.PI/6), to.y-head*Math.sin(ang-Math.PI/6)); ctx.lineTo(to.x-head*Math.cos(ang+Math.PI/6), to.y-head*Math.sin(ang+Math.PI/6)); ctx.closePath(); ctx.fill(); ctx.restore(); }

  function drawBadge(x,y,text,color){ const padX=8, height=20; ctx.save(); ctx.font='12px system-ui'; const width=ctx.measureText(text).width+padX*2; ctx.fillStyle='#0f1420'; roundRect(x-width/2,y,width,height,10); ctx.fill(); ctx.strokeStyle='#1f2a3d'; ctx.lineWidth=1/state.camera.z; ctx.stroke(); ctx.fillStyle=color||'#cbd5e1'; ctx.textAlign='center'; ctx.textBaseline='middle'; ctx.fillText(text,x,y+height/2); ctx.restore(); }

  function drawNode(n){ const {x,y,w=200,h=90,type}=n; const z=state.camera.z; ctx.save(); ctx.translate(x,y);
    // shadow
    ctx.save(); ctx.translate(3/z,6/z); ctx.fillStyle='rgba(0,0,0,.35)'; roundRect(-w/2,-h/2,w,h,14); ctx.fill(); ctx.restore();
    ctx.fillStyle='#1d2230'; roundRect(-w/2,-h/2,w,h,14); ctx.fill();
    ctx.strokeStyle='#2e3a52'; ctx.lineWidth=2/z; roundRect(-w/2,-h/2,w,h,14); ctx.stroke(); ctx.strokeStyle='rgba(255,255,255,.06)'; ctx.stroke();
    // clip for content
    ctx.save(); roundRect(-w/2+4,-h/2+4,w-8,h-8,12); ctx.clip();
    const iconSize = 40;
    const glyph = n.icon ? getIconGlyph(n.icon) : '';
    if(glyph){
      ctx.fillStyle='#e6edf3';
      ctx.textAlign='center';
      ctx.textBaseline='top';
      ctx.font=getIconFont(n.icon, iconSize);
      ctx.fillText(glyph,0,-h/2+8);
    } else {
      const icon = type==='milestone' ? images.castle : type==='ticket' ? images.swordsBlue : images.swordsRed;
      ctx.drawImage(icon,-iconSize/2,-h/2+8,iconSize,iconSize);
    }
    // title
    ctx.fillStyle='#e6edf3'; ctx.textAlign='center'; ctx.textBaseline='top'; ctx.font='600 13px system-ui'; const lines=wrapText(n.title||defaultTitle(n), w-24); let yCursor=-h/2+8+iconSize+4; for(const line of lines){ ctx.fillText(line,0,yCursor); yCursor+=15.6; }
    // sub‑info
    ctx.font='12px system-ui'; ctx.fillStyle='#9fb3d1';
    if(n.type==='goal'){
      const target=Number(n.target)||0; const current=clamp(Number(n.current)||0,0,target||1);
      ctx.fillText(`${current} / ${target}`,0,yCursor+6);
      const pad=16, yBar=h/2-18; ctx.strokeStyle='#2e3546'; ctx.lineWidth=8/z; ctx.lineCap='round'; ctx.beginPath(); ctx.moveTo(-w/2+pad,yBar); ctx.lineTo(w/2-pad,yBar); ctx.stroke(); const pct=target>0?current/target:0; ctx.strokeStyle='#7ee787'; ctx.beginPath(); ctx.moveTo(-w/2+pad,yBar); ctx.lineTo(-w/2+pad+(w-2*pad)*pct,yBar); ctx.stroke();
    } else if(n.type==='ticket'){
      const color=({"todo":"#8b94a7","in-progress":"#7aa2f7","blocked":"#ff8080","done":"#7ee787"})[n.status||'todo'];
      drawBadge(0,yCursor+6,n.ticketId||'Ticket',color); drawBadge(0,yCursor+28,'Status: '+(n.status||'todo'),color);
    } else if(n.type==='milestone'){
      ctx.fillText(n.date||'Milestone',0,yCursor+6);
    }
    ctx.restore();
    if(state.selection && state.selection.id===n.id){ ctx.strokeStyle='#7aa2f7'; ctx.lineWidth=2.5/z; roundRect(-w/2-3,-h/2-3,w+6,h+6,16); ctx.stroke(); }
    ctx.restore(); }

  function defaultTitle(n){ if(n.type==='milestone') return 'Milestone'; if(n.type==='ticket') return 'Ticket'; if(n.type==='goal') return 'Goal'; return 'Node'; }

  function draw(){ const wCSS=canvas.clientWidth, hCSS=canvas.clientHeight; ctx.setTransform(dpr,0,0,dpr,0,0); ctx.clearRect(0,0,wCSS,hCSS);
    const {x:cx,y:cy,z}=state.camera; ctx.save(); ctx.scale(z,z); ctx.translate(-cx,-cy);
    drawGrid();
    // edges
    for(const e of diagram.edges){ const a=diagram.nodes.find(n=>n.id===e.from); const b=diagram.nodes.find(n=>n.id===e.to); if(!a||!b) continue; drawArrow({x:a.x,y:a.y},{x:b.x,y:b.y}); }
    // preview during connect
    if(state.mode==='connect' && state.connectFrom){ const a=diagram.nodes.find(n=>n.id===state.connectFrom); if(a){ const mw=screenToWorld(mouse.x, mouse.y); drawArrow({x:a.x,y:a.y}, mw); } }
    // nodes
    for(const n of diagram.nodes) drawNode(n);
    ctx.restore(); requestAnimationFrame(draw); }

  // ---------- Interaction ----------
  let mouse={x:0,y:0}; let panning=false; let panStart={x:0,y:0}; let camStart={x:0,y:0}; let spaceDown=false;

  canvas.addEventListener('mousemove', (e)=>{ const r=canvas.getBoundingClientRect(); mouse={x:e.clientX-r.left, y:e.clientY-r.top}; if(panning){ const dx=(mouse.x-panStart.x)/state.camera.z; const dy=(mouse.y-panStart.y)/state.camera.z; state.camera.x = camStart.x - dx; state.camera.y = camStart.y - dy; }
    if(state.drag){ const w=screenToWorld(mouse.x,mouse.y); const n=state.drag.node; const snap=10; n.x=Math.round((w.x-state.drag.off.x)/snap)*snap; n.y=Math.round((w.y-state.drag.off.y)/snap)*snap; updateSelectionInfo(); }
  });
  canvas.addEventListener('mousedown',(e)=>{ const isRight=e.button===2; const isPanKey=e.button===0 && (e.ctrlKey||e.metaKey||e.shiftKey||spaceDown); if(isRight||isPanKey){ panning=true; panStart={...mouse}; camStart={...state.camera}; return; }
    const n = getNodeAt(mouse);
    if(state.mode==='connect'){
      if(!state.connectFrom && n){ state.connectFrom = n.id; showToast('Pick a target'); }
      else if(state.connectFrom && n){ if(state.connectFrom!==n.id){ addEdge(state.connectFrom, n.id); } state.connectFrom=null; state.mode='select'; setModeHint('Mode: Select/Drag'); }
      return;
    }
    if(n){ state.selection=n; updateSelectionInfo(); const w=screenToWorld(mouse.x,mouse.y); state.drag={ node:n, off:{x:w.x-n.x,y:w.y-n.y} };
    } else { state.selection=null; updateSelectionInfo(); }
  });
  canvas.addEventListener('mouseup', ()=>{ if(state.drag){ scheduleSave(); } state.drag=null; panning=false; });
  canvas.addEventListener('mouseleave', ()=>{ state.drag=null; panning=false; });
  canvas.addEventListener('contextmenu', e=> e.preventDefault());
  canvas.addEventListener('dblclick', ()=>{ const n=getNodeAt(mouse); if(n) openEditModal(n); });

  window.addEventListener('keydown', (e)=>{
    if(e.target.tagName==='INPUT' || e.target.tagName==='TEXTAREA') return;
    if(e.code==='Space') spaceDown=true;
    if(e.key==='Delete'||e.key==='Backspace') deleteSelected();
    if(e.key==='Escape'){ state.mode='select'; state.connectFrom=null; setModeHint('Mode: Select/Drag'); }
  });
  window.addEventListener('keyup', (e)=>{
    if(e.target.tagName==='INPUT' || e.target.tagName==='TEXTAREA') return;
    if(e.code==='Space') spaceDown=false;
  });

  canvas.addEventListener('wheel', (e)=>{ e.preventDefault(); const delta=-e.deltaY; const factor=Math.exp(delta*0.001); const before=screenToWorld(mouse.x,mouse.y); state.camera.z = clamp(state.camera.z * factor, 0.3, 2.5); const after=screenToWorld(mouse.x,mouse.y); state.camera.x += (before.x-after.x); state.camera.y += (before.y-after.y); }, { passive:false });

  function getNodeAt(pt){ const p=screenToWorld(pt.x,pt.y); for(let i=diagram.nodes.length-1;i>=0;i--){ const n=diagram.nodes[i]; const w=n.w||200, h=n.h||90; if(p.x>=n.x-w/2 && p.x<=n.x+w/2 && p.y>=n.y-h/2 && p.y<=n.y+h/2) return n; } return null; }

  // ---------- Controls & Mutations ----------
  document.getElementById('addMilestone').onclick=()=> addNode('milestone');
  document.getElementById('addTicket').onclick =()=> addNode('ticket');
  document.getElementById('addGoal').onclick  =()=> addNode('goal');
  document.getElementById('connectMode').onclick =()=>{ state.mode='connect'; state.connectFrom=null; setModeHint('Mode: Connect — click source then target'); };
  document.getElementById('autoLayout').onclick =()=>{ autoLayout(); scheduleSave(); showToast('Auto‑layout applied'); };
  document.getElementById('exportBtn').onclick = exportJSON;
  document.getElementById('importFile').addEventListener('change', (e)=>{ if(e.target.files && e.target.files[0]) importJSON(e.target.files[0]); e.target.value=''; });
  document.getElementById('deleteSelected').onclick = deleteSelected;
  document.getElementById('help').onclick = ()=> alert('Strategy Flow\n\n• Add Milestones, Tickets, Goals.\n• Connect (source → target).\n• Drag to move; Right‑drag/Space+Drag to pan; Wheel to zoom.\n• Double‑click a node to edit.\n• Export/Import JSON to save.');

  function addNode(type){ const center=screenToWorld(canvas.clientWidth/2, canvas.clientHeight/2); const n={ id:uid(), type, title:'', x:center.x, y:center.y, w:200, h:90 }; if(type==='ticket'){ n.status='todo'; } if(type==='goal'){ n.target=100; n.current=0; } diagram.nodes.push(n); state.selection=n; updateSelectionInfo(); scheduleSave(); }
  function addEdge(from,to){ if(from===to) return; if(diagram.edges.some(e=>e.from===from && e.to===to)) return; diagram.edges.push({ id:uid(), from, to }); scheduleSave(); }
  function deleteSelected(){ if(!state.selection) return; const id=state.selection.id; diagram.nodes = diagram.nodes.filter(n=>n.id!==id); diagram.edges = diagram.edges.filter(e=>e.from!==id && e.to!==id); state.selection=null; updateSelectionInfo(); scheduleSave(); }

  function autoLayout(){ const milestones=diagram.nodes.filter(n=>n.type==='milestone'); const deps=diagram.nodes.filter(n=>n.type!=='milestone'); milestones.sort((a,b)=>a.x-b.x); const startX=100, gap=260, yTop=120, yDeps=260; milestones.forEach((m,i)=>{ m.x=startX+i*gap; m.y=yTop; }); deps.forEach((d,idx)=>{ const toMs = diagram.edges.map(e=> (e.from===d.id? e.to: e.to===d.id? e.from: null)).filter(Boolean).map(id=> diagram.nodes.find(n=>n.id===id)).filter(n=>n && n.type==='milestone'); let segment = Math.min(Math.max(0, (toMs.length? milestones.indexOf(toMs[0]): Math.floor(idx%(Math.max(1,milestones.length-1))))), Math.max(0,milestones.length-2)); const x=startX+(segment+0.5)*gap; d.x=x+((idx%3)-1)*110; d.y=yDeps+(idx%2)*110; }); }

  // ---------- Import/Export ----------
  function exportJSON(){ const payload=JSON.stringify({ id:diagram.id, nodes:diagram.nodes, edges:diagram.edges, version:diagram.version }, null, 2); const blob=new Blob([payload], {type:'application/json'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=`${diagram.id}-strategy-flow.json`; a.click(); URL.revokeObjectURL(url); }
  function importJSON(file){ const reader=new FileReader(); reader.onload=()=>{ try{ const data=JSON.parse(reader.result); if(!data||!Array.isArray(data.nodes)||!Array.isArray(data.edges)) throw new Error('Invalid file'); diagram.id = data.id || diagram.id; diagram.nodes=data.nodes; diagram.edges=data.edges; diagram.version=data.version; state.selection=null; updateSelectionInfo(); scheduleSave(true); showToast('Imported diagram'); } catch(err){ console.error(err); alert('Import failed'); } }; reader.readAsText(file); }

  // ---------- Save (debounced) ----------
  let saveTimer=null; function scheduleSave(immediate){ if(saveTimer) clearTimeout(saveTimer); const doSave=async()=>{ const res = await adapters.storage.saveDiagram(diagram); diagram.version=res.version; showToast('Saved'); }; saveTimer = setTimeout(doSave, immediate?0:300); }

  // ---------- Selection UI ----------
  function setModeHint(t){ document.getElementById('modeHint').textContent=t; }
  function updateSelectionInfo(){ const el=document.getElementById('selectionInfo'); if(!state.selection){ el.textContent='—'; return; } const n=state.selection; const parts=[`Type: ${n.type}`, `Title: ${n.title||defaultTitle(n)}`, `Pos: (${Math.round(n.x)}, ${Math.round(n.y)})`]; if(n.type==='ticket'){ parts.push(`Ticket: ${n.ticketId||'—'}`, `Status: ${n.status||'todo'}`); } if(n.type==='goal'){ parts.push(`Progress: ${(n.current||0)} / ${(n.target||0)}`); } el.innerHTML = parts.join('<br>'); }
  function showToast(msg){ const t=document.getElementById('toast'); t.textContent=msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'), 1200); }

  // ---------- Modal ----------
  const modalBackdrop=document.getElementById('modalBackdrop');
  const fTitle=document.getElementById('fTitle');
  const fIcon=document.getElementById('fIcon');
  const fType=document.getElementById('fType');
  const fTicketId=document.getElementById('fTicketId');
  const fStatus=document.getElementById('fStatus');
  const fTarget=document.getElementById('fTarget');
  const fProgress=document.getElementById('fProgress');
  const ticketFields=document.getElementById('ticketFields');
  const goalFields=document.getElementById('goalFields');

  let editNodeRef=null;
  function openEditModal(n){ editNodeRef=n; document.getElementById('modalTitle').textContent=`Edit ${n.type}`; fTitle.value=n.title||''; fIcon.value=n.icon||''; fType.value=n.type; fTicketId.value=n.ticketId||''; fStatus.value=n.status||'todo'; fTarget.value=n.target||''; fProgress.value=n.current||''; updateTypeFields(); modalBackdrop.style.display='flex'; modalBackdrop.setAttribute('aria-hidden','false'); }
  function closeEditModal(){ modalBackdrop.style.display='none'; modalBackdrop.setAttribute('aria-hidden','true'); editNodeRef=null; }
  function updateTypeFields(){ const t=fType.value; ticketFields.style.display = (t==='ticket')? 'block':'none'; goalFields.style.display=(t==='goal')? 'block':'none'; }
  fType.addEventListener('change', updateTypeFields);
  document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
  document.getElementById('saveEdit').addEventListener('click', ()=>{ if(!editNodeRef) return; const n=editNodeRef; n.title=fTitle.value.trim(); n.icon=fIcon.value.trim(); if(!n.icon) delete n.icon; n.type=fType.value; if(n.type==='ticket'){ n.ticketId=fTicketId.value.trim(); n.status=fStatus.value; } else { delete n.ticketId; delete n.status; } if(n.type==='goal'){ n.target=Number(fTarget.value)||0; n.current=Number(fProgress.value)||0; } else { delete n.target; delete n.current; } closeEditModal(); updateSelectionInfo(); scheduleSave(); });
  modalBackdrop.addEventListener('click', (e)=>{ if(e.target===modalBackdrop) closeEditModal(); });

  // ---------- Seed & Load ----------
  function seedData(){ const m1={id:uid(), type:'milestone', title:'M1: Design', x:200, y:140, w:200, h:90, date:'Sprint 1'}; const m2={id:uid(), type:'milestone', title:'M2: Beta', x:460, y:140, w:200, h:90, date:'Sprint 2'}; const m3={id:uid(), type:'milestone', title:'M3: Launch', x:720, y:140, w:200, h:90, date:'Sprint 3'}; const t1={id:uid(), type:'ticket', title:'Implement auth', x:330, y:260, w:220, h:90, ticketId:'APP-101', status:'in-progress'}; const g1={id:uid(), type:'goal', title:'Get 100 users', x:590, y:260, w:220, h:90, target:100, current:32}; return { nodes:[m1,m2,m3,t1,g1], edges:[ {id:uid(), from:m1.id, to:t1.id}, {id:uid(), from:t1.id, to:m2.id}, {id:uid(), from:m2.id, to:g1.id}, {id:uid(), from:g1.id, to:m3.id} ] }; }

  (async function boot(){ diagram = await adapters.storage.loadDiagram(diagramId); if(!diagram || !Array.isArray(diagram.nodes)) diagram = { id: diagramId, ...seedData() }; draw(); updateSelectionInfo(); })();

  // ---------- Dev Tests (run with ?test=1) ----------
  (function devTests(){
    const run = new URLSearchParams(location.search).get('test') === '1';
    if (!run) return;
    const bar = document.getElementById('testbar'); bar.style.display='block'; bar.textContent='Running tests…';

    let pass = 0, fail = 0;

    // Test 1: scheduleResizeFromRO should coalesce multiple calls per frame
    let calls = 0; const orig = scheduleResizeFromRO; window.__rf = requestAnimationFrame;
    window.requestAnimationFrame = (cb)=> __rf(()=>{ calls++; cb(); });
    orig(); orig(); orig(); // spam 3 times
    setTimeout(()=>{
      if (calls === 1) { pass++; } else { fail++; }

      // Test 2: resizeCanvasIfNeeded should not trigger RO loop when dims unchanged
      const cw = canvas.width, ch = canvas.height;
      resizeCanvasIfNeeded(); // same size
      // If no error thrown and canvas dims unchanged, count as pass
      if (canvas.width === cw && canvas.height === ch) pass++; else fail++;

      // Show results
      bar.classList.toggle('ok', fail===0); bar.classList.toggle('fail', fail>0);
      bar.textContent = `Tests: ${pass+fail}, Passed: ${pass}, Failed: ${fail}`;
      // restore rAF
      window.requestAnimationFrame = window.__rf;
    }, 60);
  })();

  // Surface RO error messages to the user (for debugging)
  window.addEventListener('error', (e)=>{
    if (String(e.message||'').includes('ResizeObserver loop completed')) {
      showToast('ResizeObserver loop detected — throttled.');
    }
  });

  </script>
</body>
</html>

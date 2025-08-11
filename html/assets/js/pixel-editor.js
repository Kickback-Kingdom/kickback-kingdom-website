'use strict';

// Utility
function clamp8(v){ return v<0?0:(v>255?255:v)|0; }
function debounced(ms, fn){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }
function throttled(ms, fn){ let last=0, timer; return (...a)=>{ const now=Date.now(); const remain=ms-(now-last); if(remain<=0){ last=now; fn(...a); } else { clearTimeout(timer); timer=setTimeout(()=>{ last=Date.now(); fn(...a); }, remain); } }; }

  // Adjustments
  function applyAdjustments(ctx, w, h, bri, con, sat){
    const img = ctx.getImageData(0,0,w,h);
    const d = img.data;
    const b = bri/100 * 255;
    const c = (con/100)+1;
    const intercept = 128*(1-c);
    const s = sat/100;
    for(let i=0;i<d.length;i+=4){
      let r=d[i], g=d[i+1], bl=d[i+2];
      r = c*r + intercept + b;
      g = c*g + intercept + b;
      bl = c*bl + intercept + b;
      const avg=(r+g+bl)/3;
      r = avg + (r-avg)*s;
      g = avg + (g-avg)*s;
      bl = avg + (bl-avg)*s;
      d[i]=clamp8(r); d[i+1]=clamp8(g); d[i+2]=clamp8(bl);
    }
    ctx.putImageData(img,0,0);
  }

  function rgbToHsl(r,g,b){
    r/=255; g/=255; b/=255;
    const max=Math.max(r,g,b), min=Math.min(r,g,b);
    let h=0,s=0; const l=(max+min)/2;
    if(max!==min){
      const d=max-min;
      s = l>0.5 ? d/(2-max-min) : d/(max+min);
      switch(max){
        case r: h=(g-b)/d+(g<b?6:0); break;
        case g: h=(b-r)/d+2; break;
        case b: h=(r-g)/d+4; break;
      }
      h*=60;
    }
    return {h, s, l};
  }

  function hslToRgb(h,s,l){
    const c=(1-Math.abs(2*l-1))*s, hp=h/60, x=c*(1-Math.abs((hp%2)-1));
    let r1=0,g1=0,b1=0;
    if(0<=hp&&hp<1){r1=c;g1=x;} else if(1<=hp&&hp<2){r1=x;g1=c;}
    else if(2<=hp&&hp<3){g1=c;b1=x;} else if(3<=hp&&hp<4){g1=x;b1=c;}
    else if(4<=hp&&hp<5){r1=x;b1=c;} else if(5<=hp&&hp<6){r1=c;b1=x;}
    const m=l-c/2;
    return {r:clamp8((r1+m)*255), g:clamp8((g1+m)*255), b:clamp8((b1+m)*255)};
  }

  const BUCKETS = [
    {k:'R',h:0},
    {k:'Y',h:60},
    {k:'G',h:120},
    {k:'C',h:180},
    {k:'B',h:240},
    {k:'M',h:300}
  ];

  function nearestBucket(hh){
    let bk='R', bd=1e9;
    for(const b of BUCKETS){
      const diff=Math.min(Math.abs(hh-b.h), 360-Math.abs(hh-b.h));
      if(diff<bd){ bd=diff; bk=b.k; }
    }
    return bk;
  }

  const BAND_HUE=[0,0,60,120,180,240,300];

  function applyColorTuning(ctx, w, h, gains){
    const img = ctx.getImageData(0,0,w,h);
    const d=img.data;
    for(let i=0;i<d.length;i+=4){
      const r=d[i], g=d[i+1], bl=d[i+2];
      const hsl=rgbToHsl(r,g,bl);
      const band=nearestBucket(hsl.h);
      const gain=(gains[band]||0)/100;
      const sat= Math.max(0, Math.min(1, hsl.s*(1+gain)));
      const rgb = hslToRgb(hsl.h, sat, hsl.l);
      d[i]=rgb.r; d[i+1]=rgb.g; d[i+2]=rgb.b;
    }
    ctx.putImageData(img,0,0);
  }

  function applyHueRemap(ctx, w, h, mapping, globalStrength){
    const img = ctx.getImageData(0,0,w,h);
    const d=img.data;
    for(let i=0;i<d.length;i+=4){
      const r=d[i], g=d[i+1], bl=d[i+2];
      const hsl=rgbToHsl(r,g,bl);
      const srcBand = nearestBucket(hsl.h);
      const cfg = mapping[srcBand];
      if(!cfg) continue;
      const targetSel = cfg.t;
      const eff = Math.max(0, Math.min(1, globalStrength * cfg.s));
      if(targetSel && targetSel>0 && eff>0){
        const tgtHue = BAND_HUE[targetSel];
        let dh = ((tgtHue - hsl.h + 540) % 360) - 180;
        const newH = (hsl.h + dh*eff + 360) % 360;
        const rgb = hslToRgb(newH, hsl.s, hsl.l);
        d[i]=rgb.r; d[i+1]=rgb.g; d[i+2]=rgb.b;
      }
    }
    ctx.putImageData(img,0,0);
  }

  /**
   * Apply hue-based glow to bright areas.
   * @param {CanvasRenderingContext2D} ctx
   * @param {number} w - canvas width
   * @param {number} h - canvas height
   * @param {Object} glows - map of hue buckets to strength/range
  * @param {number} [threshold=0.6] - minimum lightness [0-1] required for glow
  * @param {number} [globalMult=1] - multiplier applied to all band strengths
   */
  function applyColorGlow(ctx, w, h, glows, threshold=0.6, globalMult=1){
    if(!glows || !Object.values(glows).some(v=>v.s>0)) return;
    for(const [bandKey, cfg] of Object.entries(glows)){
      const strength=(cfg.s||0)*globalMult;
      if(strength<=0) continue;
      const range=cfg.r||0;
      const off=document.createElement('canvas');
      off.width=w; off.height=h;
      const octx=off.getContext('2d');
      octx.drawImage(ctx.canvas,0,0);
      const img=octx.getImageData(0,0,w,h);
      const d=img.data;
      for(let i=0;i<d.length;i+=4){
        const r=d[i], g=d[i+1], bl=d[i+2];
        const hsl=rgbToHsl(r,g,bl);
        const b=nearestBucket(hsl.h);
        if(b!==bandKey || hsl.l<=threshold){ d[i]=d[i+1]=d[i+2]=0; continue; }
        const eff=strength*strength;
        const newL=Math.min(1, hsl.l+eff);
        const newS=Math.min(1, hsl.s+eff);
        const rgb=hslToRgb(hsl.h,newS,newL);
        d[i]=rgb.r; d[i+1]=rgb.g; d[i+2]=rgb.b;
      }
      octx.putImageData(img,0,0);
      ctx.save();
      if(range>0) ctx.filter=`blur(${range}px)`;
      ctx.globalCompositeOperation='lighter';
      ctx.drawImage(off,0,0);
      ctx.restore();
    }
  }

  /**
   * Apply a bloom effect by blurring bright pixels.
   * @param {CanvasRenderingContext2D} ctx
   * @param {number} w - canvas width
   * @param {number} h - canvas height
   * @param {number} [threshold=200] - luminance cutoff [0-255] for bloom
   * @param {number} [blurRadius=0] - blur radius in pixels
   * @param {number} [alpha=0] - output alpha [0-1] for the bloom overlay
   */
  function applyBloom(ctx, w, h, threshold=200, blurRadius=0, alpha=0){
    if(alpha<=0) return;
    const off=document.createElement('canvas');
    off.width=w; off.height=h;
    const octx=off.getContext('2d');
    octx.drawImage(ctx.canvas,0,0);
    const img=octx.getImageData(0,0,w,h);
    const d=img.data;
    for(let i=0;i<d.length;i+=4){
      const r=d[i], g=d[i+1], b=d[i+2];
      const lum=0.2126*r + 0.7152*g + 0.0722*b;
      if(lum<threshold){ d[i]=d[i+1]=d[i+2]=0; }
    }
    octx.putImageData(img,0,0);
    ctx.save();
    if(blurRadius>0) ctx.filter=`blur(${blurRadius}px)`;
    ctx.globalCompositeOperation='lighter';
    ctx.globalAlpha=Math.min(1, alpha);
    ctx.drawImage(off,0,0);
    ctx.restore();
  }

  function kmeansRGB(rgba, count, k){
    const pts=new Array(count);
    for(let i=0;i<count;i++){ const j=i*4; pts[i]=[rgba[j],rgba[j+1],rgba[j+2]]; }
    const cents=[]; const rand=m=>Math.floor(Math.random()*m);
    cents.push(pts[rand(count)]);
    while(cents.length<k){
      let farI=0,farV=-1;
      for(let i=0;i<count;i++){
        const p=pts[i]; let best=Infinity;
        for(const c of cents){ const dx=p[0]-c[0],dy=p[1]-c[1],dz=p[2]-c[2]; const d=dx*dx+dy*dy+dz*dz; if(d<best) best=d; }
        if(best>farV){farV=best; farI=i;}
      }
      cents.push(pts[farI]);
    }
    const labels=new Uint16Array(count);
    for(let it=0; it<10; it++){
      for(let i=0;i<count;i++){
        const p=pts[i]; let b=0,bd=Infinity;
        for(let c=0;c<k;c++){
          const ct=cents[c]; const dx=p[0]-ct[0],dy=p[1]-ct[1],dz=p[2]-ct[2];
          const d=dx*dx+dy*dy+dz*dz; if(d<bd){bd=d;b=c;}
        }
        labels[i]=b;
      }
      const sums=new Array(k).fill(0).map(()=>[0,0,0,0]);
      for(let i=0;i<count;i++){ const s=sums[labels[i]], p=pts[i]; s[0]+=p[0]; s[1]+=p[1]; s[2]+=p[2]; s[3]++; }
      for(let c=0;c<k;c++){ const s=sums[c]; cents[c]= s[3]>0 ? [(s[0]/s[3])|0,(s[1]/s[3])|0,(s[2]/s[3])|0] : pts[rand(count)]; }
    }
    return {palette:cents, labels};
  }

  function floydSteinbergQuantize(ctx, w, h, levels){
    const img = ctx.getImageData(0,0,w,h);
    const d=img.data;
    const q=v=>Math.round(v*(levels-1)/255)*(255/(levels-1));
    const at=(x,y)=>(y*w+x)*4;
    for(let y=0;y<h;y++){
      for(let x=0;x<w;x++){
        const i=at(x,y);
        const oldR=d[i], oldG=d[i+1], oldB=d[i+2];
        const newR=q(oldR), newG=q(oldG), newB=q(oldB);
        d[i]=newR; d[i+1]=newG; d[i+2]=newB;
        const er=oldR-newR, eg=oldG-newG, eb=oldB-newB;
        distribute(x+1,y,7/16,er,eg,eb);
        distribute(x-1,y+1,3/16,er,eg,eb);
        distribute(x,y+1,5/16,er,eg,eb);
        distribute(x+1,y+1,1/16,er,eg,eb);
      }
    }
    ctx.putImageData(img,0,0);

    function distribute(x,y,c,er,eg,eb){
      if(x<0||x>=w||y<0||y>=h) return;
      const j=at(x,y);
      d[j]+=er*c; d[j+1]+=eg*c; d[j+2]+=eb*c;
    }
  }

  /**
   * Initialize the pixel editor UI within the given container.
   *
   * The returned object exposes a `destroy()` function that must be
   * called when the editor is no longer needed so that all event
   * listeners are removed and memory can be reclaimed.
   */
  export function initPixelEditor(container, sourceImage, settings){
    const pixelWidth = container.querySelector('[data-pixel-width]');
    const method = container.querySelector('[data-method]');
    const paletteSize = container.querySelector('[data-palette-size]');
    const ditherCk = container.querySelector('[data-dither]');
    const autoRenderCk = container.querySelector('[data-auto-render]');
    const autoFitCk = container.querySelector('[data-auto-fit]');
    const renderBtn = container.querySelector('[data-render]');
    const resetBtn = container.querySelector('[data-reset]');
    const bri = container.querySelector('[data-brightness]');
    const con = container.querySelector('[data-contrast]');
    const sat = container.querySelector('[data-saturation]');
    const glowThreshold = container.querySelector('[data-glow-threshold]');
    const bloomAlpha = container.querySelector('[data-bloom-alpha]');
    const bloomBlur = container.querySelector('[data-bloom-blur]');
    const bloomThreshold = container.querySelector('[data-bloom-threshold]');
    const gAll = container.querySelector('[data-glow-all]');
    const gR = container.querySelector('[data-glow-r]');
    const gY = container.querySelector('[data-glow-y]');
    const gG = container.querySelector('[data-glow-g]');
    const gC = container.querySelector('[data-glow-c]');
    const gB = container.querySelector('[data-glow-b]');
    const gM = container.querySelector('[data-glow-m]');
    const gRRange = container.querySelector('[data-glow-r-range]');
    const gYRange = container.querySelector('[data-glow-y-range]');
    const gGRange = container.querySelector('[data-glow-g-range]');
    const gCRange = container.querySelector('[data-glow-c-range]');
    const gBRange = container.querySelector('[data-glow-b-range]');
    const gMRange = container.querySelector('[data-glow-m-range]');
    const enableGlow = container.querySelector('[data-enable-glow]');
    const enableTune = container.querySelector('[data-enable-tune]');
    const tR = container.querySelector('[data-tune-red]');
    const tY = container.querySelector('[data-tune-yellow]');
    const tG = container.querySelector('[data-tune-green]');
    const tC = container.querySelector('[data-tune-cyan]');
    const tB = container.querySelector('[data-tune-blue]');
    const tM = container.querySelector('[data-tune-magenta]');
    const enableRemap = container.querySelector('[data-enable-remap]');
    const remapStrength = container.querySelector('[data-remap-strength]');
    const mapR = container.querySelector('[data-map-r]');
    const mapY = container.querySelector('[data-map-y]');
    const mapG = container.querySelector('[data-map-g]');
    const mapC = container.querySelector('[data-map-c]');
    const mapB = container.querySelector('[data-map-b]');
    const mapM = container.querySelector('[data-map-m]');
    const mapRStr = container.querySelector('[data-map-r-str]');
    const mapYStr = container.querySelector('[data-map-y-str]');
    const mapGStr = container.querySelector('[data-map-g-str]');
    const mapCStr = container.querySelector('[data-map-c-str]');
    const mapBStr = container.querySelector('[data-map-b-str]');
    const mapMStr = container.querySelector('[data-map-m-str]');
    const pixMeta = container.querySelector('[data-pix-meta]');
    const statusEl = container.querySelector('[data-status]');
    const viewport = container.querySelector('[data-viewport]');
    const wrap = container.querySelector('[data-wrap]');
    const canvas = container.querySelector('canvas');
    const ctx = canvas.getContext('2d');

    const opts = settings || {};
    if(pixelWidth && opts.pixelWidth !== undefined) pixelWidth.value = opts.pixelWidth;
    if(method && opts.method !== undefined) method.value = opts.method;
    if(paletteSize && opts.paletteSize !== undefined) paletteSize.value = opts.paletteSize;
    if(ditherCk && opts.dither !== undefined) ditherCk.checked = opts.dither;
    if(autoRenderCk && opts.autoRender !== undefined) autoRenderCk.checked = opts.autoRender;
    if(autoFitCk && opts.autoFit !== undefined) autoFitCk.checked = opts.autoFit;
    if(bri && opts.brightness !== undefined) bri.value = opts.brightness;
    if(con && opts.contrast !== undefined) con.value = opts.contrast;
    if(sat && opts.saturation !== undefined) sat.value = opts.saturation;
    if(enableGlow && opts.enableGlow !== undefined) enableGlow.checked = opts.enableGlow;
    if(glowThreshold && opts.glowThreshold !== undefined) glowThreshold.value = opts.glowThreshold;
    if(bloomAlpha && opts.bloomAlpha !== undefined) bloomAlpha.value = opts.bloomAlpha;
    if(bloomBlur && opts.bloomBlur !== undefined) bloomBlur.value = opts.bloomBlur;
    if(bloomThreshold && opts.bloomThreshold !== undefined) bloomThreshold.value = opts.bloomThreshold;
    if(opts.glow){
      if(gAll) gAll.value = opts.glow.global ?? 100;
      if(gR) gR.value = opts.glow.R?.strength ?? 0;
      if(gY) gY.value = opts.glow.Y?.strength ?? 0;
      if(gG) gG.value = opts.glow.G?.strength ?? 0;
      if(gC) gC.value = opts.glow.C?.strength ?? 0;
      if(gB) gB.value = opts.glow.B?.strength ?? 0;
      if(gM) gM.value = opts.glow.M?.strength ?? 0;
      if(gRRange) gRRange.value = opts.glow.R?.range ?? 0;
      if(gYRange) gYRange.value = opts.glow.Y?.range ?? 0;
      if(gGRange) gGRange.value = opts.glow.G?.range ?? 0;
      if(gCRange) gCRange.value = opts.glow.C?.range ?? 0;
      if(gBRange) gBRange.value = opts.glow.B?.range ?? 0;
      if(gMRange) gMRange.value = opts.glow.M?.range ?? 0;
    }
    if(enableTune && opts.enableTune !== undefined) enableTune.checked = opts.enableTune;
    if(opts.tune){
      if(tR) tR.value = opts.tune.R;
      if(tY) tY.value = opts.tune.Y;
      if(tG) tG.value = opts.tune.G;
      if(tC) tC.value = opts.tune.C;
      if(tB) tB.value = opts.tune.B;
      if(tM) tM.value = opts.tune.M;
    }
    if(enableRemap && opts.enableRemap !== undefined) enableRemap.checked = opts.enableRemap;
    if(remapStrength && opts.remapStrength !== undefined) remapStrength.value = opts.remapStrength;

    let img = sourceImage;
    let zoom = 1;

    // Cached canvases used for processing. Reuse them instead of creating
    // new elements on every render to cut down on DOM churn.
    const useOffscreen = typeof OffscreenCanvas !== 'undefined';
    const small = useOffscreen ? new OffscreenCanvas(0,0) : document.createElement('canvas');
    const src   = useOffscreen ? new OffscreenCanvas(0,0) : document.createElement('canvas');
    const worker = opts.worker;

  let currentSettings = {};
  function collectSettings(){
      currentSettings = {
        pixelWidth: Number(pixelWidth?.value||64),
        method: method?.value||'neighbor',
        paletteSize: Number(paletteSize?.value||16),
        dither: ditherCk?.checked||false,
        autoRender: autoRenderCk?.checked||false,
        autoFit: autoFitCk?.checked||false,
        brightness: Number(bri?.value||0),
        contrast: Number(con?.value||0),
        saturation: Number(sat?.value||100),
        enableGlow: enableGlow?.checked||false,
        glowThreshold: Number(glowThreshold?.value||60),
        bloomAlpha: Number(bloomAlpha?.value||0),
        bloomBlur: Number(bloomBlur?.value||0),
        bloomThreshold: Number(bloomThreshold?.value||200),
        glow:{
          global:Number(gAll?.value||100),
          R:{strength:Number(gR?.value||0), range:Number(gRRange?.value||0)},
          Y:{strength:Number(gY?.value||0), range:Number(gYRange?.value||0)},
          G:{strength:Number(gG?.value||0), range:Number(gGRange?.value||0)},
          C:{strength:Number(gC?.value||0), range:Number(gCRange?.value||0)},
          B:{strength:Number(gB?.value||0), range:Number(gBRange?.value||0)},
          M:{strength:Number(gM?.value||0), range:Number(gMRange?.value||0)}
        },
        enableTune: enableTune?.checked||false,
        tune:{
          R:Number(tR?.value||0),
          Y:Number(tY?.value||0),
          G:Number(tG?.value||0),
          C:Number(tC?.value||0),
          B:Number(tB?.value||0),
          M:Number(tM?.value||0)
        },
        enableRemap: enableRemap?.checked||false,
        remapStrength: Number(remapStrength?.value||100),
        map:{
          R:mapR?.value||'0',
          Y:mapY?.value||'0',
          G:mapG?.value||'0',
          C:mapC?.value||'0',
          B:mapB?.value||'0',
          M:mapM?.value||'0'
        },
        mapStr:{
          R:Number(mapRStr?.value||100),
          Y:Number(mapYStr?.value||100),
          G:Number(mapGStr?.value||100),
          C:Number(mapCStr?.value||100),
          B:Number(mapBStr?.value||100),
          M:Number(mapMStr?.value||100)
        }
      };
    }

    function status(msg){ if(statusEl) statusEl.textContent = msg; }
    function setZoom(z){ zoom=Math.max(0.1,z); if(wrap) wrap.style.transform=`scale(${zoom})`; }
    function fitToViewport(){ if(!canvas.width||!canvas.height||!viewport) return; const availW=viewport.clientWidth-16; const availH=viewport.clientHeight-16; const fit=Math.min(availW/canvas.width, availH/canvas.height); setZoom(fit); }

    const listeners=[];
    function listen(target, event, handler, options){
      if(!target) return;
      target.addEventListener(event, handler, options);
      listeners.push({target, event, handler, options});
    }

    const onResize = ()=>{ if(autoFitCk?.checked) fitToViewport(); };
    listen(window, 'resize', onResize);
    const onAutoFitChange = ()=>{ if(autoFitCk.checked) fitToViewport(); onChange(); };
    listen(autoFitCk, 'change', onAutoFitChange);

    function render(){
      if(!img) return;
      status('Rendering…');
      const targetW = Math.max(8, Math.min(1024, Number(pixelWidth?.value)||64));
      const targetH = Math.round((img.naturalHeight/img.naturalWidth) * targetW);

      // Reinitialize dimensions on the cached canvases
      small.width = targetW; small.height = targetH;
      const sctx = small.getContext('2d', { willReadFrequently:true });

      const m = method?.value || 'neighbor';
      const k = Math.max(2, Math.min(64, Number(paletteSize?.value)||16));

      if(m==='neighbor'){
        sctx.imageSmoothingEnabled = false;
        sctx.drawImage(img,0,0,targetW,targetH);
      } else {
        src.width = img.naturalWidth; src.height = img.naturalHeight;
        const sfull = src.getContext('2d', { willReadFrequently:true });
        sfull.drawImage(img,0,0);
        const data = sfull.getImageData(0,0,src.width,src.height).data;
        if(m==='average'){
          const outImg = sctx.createImageData(targetW, targetH);
          const bw = src.width/targetW, bh = src.height/targetH;
          for(let py=0; py<targetH; py++){
            for(let px=0; px<targetW; px++){
              const x0=Math.floor(px*bw), x1=Math.floor((px+1)*bw);
              const y0=Math.floor(py*bh), y1=Math.floor((py+1)*bh);
              let r=0,g=0,b=0,c=0;
              for(let y=y0;y<y1;y++){
                let idx=(y*src.width + x0)*4;
                for(let x=x0;x<x1;x++){ r+=data[idx]; g+=data[idx+1]; b+=data[idx+2]; c++; idx+=4; }
              }
              if(c===0) c=1;
              const i=(py*targetW+px)*4;
              outImg.data[i]=(r/c)|0;
              outImg.data[i+1]=(g/c)|0;
              outImg.data[i+2]=(b/c)|0;
              outImg.data[i+3]=255;
            }
          }
          sctx.putImageData(outImg,0,0);
        } else if(m==='palette'){
          const bw = src.width/targetW, bh = src.height/targetH;
          const grid = new Uint8ClampedArray(targetW*targetH*4);
          for(let py=0; py<targetH; py++){
            for(let px=0; px<targetW; px++){
              const x0=Math.floor(px*bw), x1=Math.floor((px+1)*bw);
              const y0=Math.floor(py*bh), y1=Math.floor((py+1)*bh);
              let r=0,g=0,b=0,c=0;
              for(let y=y0;y<y1;y++){
                let idx=(y*src.width + x0)*4;
                for(let x=x0;x<x1;x++){ r+=data[idx]; g+=data[idx+1]; b+=data[idx+2]; c++; idx+=4; }
              }
              const i=(py*targetW+px)*4;
              if(c===0) c=1;
              grid[i]=(r/c)|0; grid[i+1]=(g/c)|0; grid[i+2]=(b/c)|0; grid[i+3]=255;
            }
          }
          const {palette, labels} = kmeansRGB(grid, targetW*targetH, k);
          const outImg = new ImageData(targetW, targetH);
          for(let i=0;i<labels.length;i++){ const j=i*4, p=palette[labels[i]]; outImg.data[j]=p[0]; outImg.data[j+1]=p[1]; outImg.data[j+2]=p[2]; outImg.data[j+3]=255; }
          sctx.putImageData(outImg,0,0);
        }
      }

      // If a worker was supplied, provide the offscreen canvases so it can
      // operate on them without additional DOM allocations.
      if(worker){
        try{ worker.postMessage({small, src}); } catch(e){}
      }

      applyAdjustments(sctx, targetW, targetH, Number(bri?.value||0), Number(con?.value||0), Number(sat?.value||100));

      if(enableTune?.checked){
        const gains={R:Number(tR.value),Y:Number(tY.value),G:Number(tG.value),C:Number(tC.value),B:Number(tB.value),M:Number(tM.value)};
        applyColorTuning(sctx,targetW,targetH,gains);
      }

      if(enableRemap?.checked){
        const mapping={
          R:{t:parseInt(mapR.value,10),s:Number(mapRStr.value)/100},
          Y:{t:parseInt(mapY.value,10),s:Number(mapYStr.value)/100},
          G:{t:parseInt(mapG.value,10),s:Number(mapGStr.value)/100},
          C:{t:parseInt(mapC.value,10),s:Number(mapCStr.value)/100},
          B:{t:parseInt(mapB.value,10),s:Number(mapBStr.value)/100},
          M:{t:parseInt(mapM.value,10),s:Number(mapMStr.value)/100},
        };
        const globalStrength=Number(remapStrength.value)/100;
        applyHueRemap(sctx,targetW,targetH,mapping,globalStrength);
      }

      if(enableGlow?.checked){
        const glowMap={
          R:{s:Number(gR?.value||0)/100, r:Number(gRRange?.value||0)},
          Y:{s:Number(gY?.value||0)/100, r:Number(gYRange?.value||0)},
          G:{s:Number(gG?.value||0)/100, r:Number(gGRange?.value||0)},
          C:{s:Number(gC?.value||0)/100, r:Number(gCRange?.value||0)},
          B:{s:Number(gB?.value||0)/100, r:Number(gBRange?.value||0)},
          M:{s:Number(gM?.value||0)/100, r:Number(gMRange?.value||0)},
        };
        const glowThreshVal = Number(glowThreshold?.value||60)/100;
        const bloomThreshVal = Number(bloomThreshold?.value||200);
        const bloomBlurVal = Number(bloomBlur?.value||0);
        const bloomAlphaVal = Number(bloomAlpha?.value||0)/100;
        const glowGlobalVal = Number(gAll?.value||100)/100;
        applyColorGlow(sctx, targetW, targetH, glowMap, glowThreshVal, glowGlobalVal);
        applyBloom(sctx, targetW, targetH, bloomThreshVal, bloomBlurVal, bloomAlphaVal);
      }

      if(ditherCk?.checked){
        floydSteinbergQuantize(sctx, targetW, targetH, 16);
      }

      canvas.width = targetW; canvas.height = targetH;
      ctx.imageSmoothingEnabled = false;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.drawImage(small,0,0);

      if(pixMeta) pixMeta.textContent = `${targetW}×${targetH}`;
      if(autoFitCk?.checked) fitToViewport();
      status('Done.');
    }

    const maybeRender = throttled(120, ()=>{ if(autoRenderCk?.checked) render(); });

    function onInput(){ collectSettings(); maybeRender(); }
    function onChange(){ collectSettings(); maybeRender(); }

    [pixelWidth, method, paletteSize, bri, con, sat, bloomAlpha, bloomBlur, bloomThreshold, glowThreshold, gAll, gR, gY, gG, gC, gB, gM, gRRange, gYRange, gGRange, gCRange, gBRange, gMRange, tR, tY, tG, tC, tB, tM, remapStrength, mapRStr, mapYStr, mapGStr, mapCStr, mapBStr, mapMStr].forEach(el=>{ listen(el,'input', onInput); });
    [ditherCk, enableGlow, enableTune, enableRemap, mapR, mapY, mapG, mapC, mapB, mapM, autoRenderCk].forEach(el=>{ listen(el,'change', onChange); });

    const onRenderClick = ()=>{ collectSettings(); render(); };
    listen(renderBtn,'click', onRenderClick);

    const onResetClick = ()=>{
      if(pixelWidth) pixelWidth.value=64;
      if(method) method.value='neighbor';
      if(paletteSize) paletteSize.value=16;
      if(ditherCk) ditherCk.checked=false;
      if(autoRenderCk) autoRenderCk.checked=true;
      if(autoFitCk) autoFitCk.checked=true;
      if(bri) bri.value=0;
      if(con) con.value=0;
      if(sat) sat.value=100;
      if(enableGlow) enableGlow.checked=false;
      if(glowThreshold) glowThreshold.value=60;
      if(bloomAlpha) bloomAlpha.value=0;
      if(bloomBlur) bloomBlur.value=0;
      if(bloomThreshold) bloomThreshold.value=200;
      if(gAll) gAll.value=100;
      [gR,gY,gG,gC,gB,gM,gRRange,gYRange,gGRange,gCRange,gBRange,gMRange].forEach(el=>{ if(el) el.value=0; });
      if(enableTune) enableTune.checked=false;
      if(tR) tR.value=0; if(tY) tY.value=0; if(tG) tG.value=0; if(tC) tC.value=0; if(tB) tB.value=0; if(tM) tM.value=0;
      if(enableRemap) enableRemap.checked=false;
      if(remapStrength) remapStrength.value=100;
      [mapR,mapY,mapG,mapC,mapB,mapM].forEach(sel=>{ if(sel) sel.value='0'; });
      [mapRStr,mapYStr,mapGStr,mapCStr,mapBStr,mapMStr].forEach(el=>{ if(el) el.value=100; });
      status('Settings reset.');
      collectSettings();
      render();
    };
    listen(resetBtn,'click', onResetClick);

    const remapBands=['— keep —','Red','Yellow','Green','Cyan','Blue','Magenta'];
    function buildOptions(sel){ sel.innerHTML=''; remapBands.forEach((name,i)=>{ const opt=document.createElement('option'); opt.textContent=name; opt.value=String(i); sel.appendChild(opt); }); sel.value='0'; }
    [mapR,mapY,mapG,mapC,mapB,mapM].forEach(sel=>{ if(sel) buildOptions(sel); });

    if(opts.map){
      if(mapR) mapR.value = opts.map.R;
      if(mapY) mapY.value = opts.map.Y;
      if(mapG) mapG.value = opts.map.G;
      if(mapC) mapC.value = opts.map.C;
      if(mapB) mapB.value = opts.map.B;
      if(mapM) mapM.value = opts.map.M;
    }
    if(opts.mapStr){
      if(mapRStr) mapRStr.value = opts.mapStr.R;
      if(mapYStr) mapYStr.value = opts.mapStr.Y;
      if(mapGStr) mapGStr.value = opts.mapStr.G;
      if(mapCStr) mapCStr.value = opts.mapStr.C;
      if(mapBStr) mapBStr.value = opts.mapStr.B;
      if(mapMStr) mapMStr.value = opts.mapStr.M;
    }

    collectSettings();
    render();

    function destroy(){
      listeners.forEach(({target,event,handler,options})=>{
        target.removeEventListener(event, handler, options);
      });
    }

    return {
      render,
      setImage: (image)=>{ img=image; collectSettings(); render(); },
      destroy,
      getSettings: () => { collectSettings(); return currentSettings; }
    };
  }

export { applyAdjustments, applyColorTuning, applyHueRemap, applyColorGlow, applyBloom, kmeansRGB, floydSteinbergQuantize };
export const pixelEditorUtils = {
  applyAdjustments,
  applyColorTuning,
  applyHueRemap,
  applyColorGlow,
  applyBloom,
  kmeansRGB,
  floydSteinbergQuantize
};


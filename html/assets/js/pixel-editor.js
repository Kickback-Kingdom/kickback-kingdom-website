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
        const eff=strength;
        const newL=Math.min(1, hsl.l+eff);
        const newS=Math.min(1, hsl.s+eff);
        const rgb=hslToRgb(hsl.h,newS,newL);
        d[i]=rgb.r; d[i+1]=rgb.g; d[i+2]=rgb.b;
      }
      octx.putImageData(img,0,0);
      ctx.save();
      if(range>0) ctx.filter=`blur(${range}px)`;
      ctx.globalCompositeOperation='lighter';
      ctx.globalAlpha=Math.min(1, strength);
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

  class Layer {
    constructor(type, options){
      this.type = type;
      this.options = options;
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

    function legacySettingsToLayers(opts){
      const ls = [];
      const adj = {
        brightness: opts.brightness ?? 0,
        contrast: opts.contrast ?? 0,
        saturation: opts.saturation ?? 100
      };
      ls.push(new Layer('adjustments', adj));
      if(opts.enableTune || opts.tune){
        ls.push(new Layer('tune', {
          R: opts.tune?.R ?? 0,
          Y: opts.tune?.Y ?? 0,
          G: opts.tune?.G ?? 0,
          C: opts.tune?.C ?? 0,
          B: opts.tune?.B ?? 0,
          M: opts.tune?.M ?? 0
        }));
      }
      if(opts.enableRemap){
        const mapping = {
          R: {t: parseInt(opts.map?.R ?? '0',10), s: (opts.mapStr?.R ?? 100)/100},
          Y: {t: parseInt(opts.map?.Y ?? '0',10), s: (opts.mapStr?.Y ?? 100)/100},
          G: {t: parseInt(opts.map?.G ?? '0',10), s: (opts.mapStr?.G ?? 100)/100},
          C: {t: parseInt(opts.map?.C ?? '0',10), s: (opts.mapStr?.C ?? 100)/100},
          B: {t: parseInt(opts.map?.B ?? '0',10), s: (opts.mapStr?.B ?? 100)/100},
          M: {t: parseInt(opts.map?.M ?? '0',10), s: (opts.mapStr?.M ?? 100)/100}
        };
        const globalStrength = (opts.remapStrength ?? 100)/100;
        ls.push(new Layer('remap', {mapping, globalStrength}));
      }
      if(opts.enableGlow){
        const glowMap = {
          R:{s:(opts.glow?.R?.strength ?? 0)/100, r:opts.glow?.R?.range ?? 0},
          Y:{s:(opts.glow?.Y?.strength ?? 0)/100, r:opts.glow?.Y?.range ?? 0},
          G:{s:(opts.glow?.G?.strength ?? 0)/100, r:opts.glow?.G?.range ?? 0},
          C:{s:(opts.glow?.C?.strength ?? 0)/100, r:opts.glow?.C?.range ?? 0},
          B:{s:(opts.glow?.B?.strength ?? 0)/100, r:opts.glow?.B?.range ?? 0},
          M:{s:(opts.glow?.M?.strength ?? 0)/100, r:opts.glow?.M?.range ?? 0}
        };
        const threshold = (opts.glowThreshold ?? 60)/100;
        const global = (opts.glow?.global ?? 100)/100;
        ls.push(new Layer('colorGlow', {glowMap, threshold, global}));
        ls.push(new Layer('bloom', {
          threshold: opts.bloomThreshold ?? 200,
          blur: opts.bloomBlur ?? 0,
          alpha: (opts.bloomAlpha ?? 0)/100
        }));
      }
      return ls;
    }

    function applyLayersToUI(ls){
      const adj = ls.find(l=>l.type==='adjustments');
      if(adj){
        if(bri) bri.value = adj.options.brightness;
        if(con) con.value = adj.options.contrast;
        if(sat) sat.value = adj.options.saturation;
      }
      const tune = ls.find(l=>l.type==='tune');
      if(tune){
        if(enableTune) enableTune.checked = true;
        if(tR) tR.value = tune.options.R;
        if(tY) tY.value = tune.options.Y;
        if(tG) tG.value = tune.options.G;
        if(tC) tC.value = tune.options.C;
        if(tB) tB.value = tune.options.B;
        if(tM) tM.value = tune.options.M;
      }
      const remap = ls.find(l=>l.type==='remap');
      if(remap){
        if(enableRemap) enableRemap.checked = true;
        if(remapStrength) remapStrength.value = remap.options.globalStrength*100;
        if(mapR) mapR.value = String(remap.options.mapping.R.t);
        if(mapY) mapY.value = String(remap.options.mapping.Y.t);
        if(mapG) mapG.value = String(remap.options.mapping.G.t);
        if(mapC) mapC.value = String(remap.options.mapping.C.t);
        if(mapB) mapB.value = String(remap.options.mapping.B.t);
        if(mapM) mapM.value = String(remap.options.mapping.M.t);
        if(mapRStr) mapRStr.value = remap.options.mapping.R.s*100;
        if(mapYStr) mapYStr.value = remap.options.mapping.Y.s*100;
        if(mapGStr) mapGStr.value = remap.options.mapping.G.s*100;
        if(mapCStr) mapCStr.value = remap.options.mapping.C.s*100;
        if(mapBStr) mapBStr.value = remap.options.mapping.B.s*100;
        if(mapMStr) mapMStr.value = remap.options.mapping.M.s*100;
      }
      const glow = ls.find(l=>l.type==='colorGlow');
      if(glow){
        if(enableGlow) enableGlow.checked = true;
        if(glowThreshold) glowThreshold.value = glow.options.threshold*100;
        if(gAll) gAll.value = glow.options.global*100;
        if(gR) gR.value = glow.options.glowMap.R.s*100;
        if(gY) gY.value = glow.options.glowMap.Y.s*100;
        if(gG) gG.value = glow.options.glowMap.G.s*100;
        if(gC) gC.value = glow.options.glowMap.C.s*100;
        if(gB) gB.value = glow.options.glowMap.B.s*100;
        if(gM) gM.value = glow.options.glowMap.M.s*100;
        if(gRRange) gRRange.value = glow.options.glowMap.R.r;
        if(gYRange) gYRange.value = glow.options.glowMap.Y.r;
        if(gGRange) gGRange.value = glow.options.glowMap.G.r;
        if(gCRange) gCRange.value = glow.options.glowMap.C.r;
        if(gBRange) gBRange.value = glow.options.glowMap.B.r;
        if(gMRange) gMRange.value = glow.options.glowMap.M.r;
      }
      const bloom = ls.find(l=>l.type==='bloom');
      if(bloom){
        if(enableGlow) enableGlow.checked = true;
        if(bloomThreshold) bloomThreshold.value = bloom.options.threshold;
        if(bloomBlur) bloomBlur.value = bloom.options.blur;
        if(bloomAlpha) bloomAlpha.value = bloom.options.alpha*100;
      }
    }

    const base = settings?.baseSettings || settings || {};
    let layers = settings?.layers ? JSON.parse(JSON.stringify(settings.layers)) : legacySettingsToLayers(settings||{});
    if(pixelWidth && base.pixelWidth !== undefined) pixelWidth.value = base.pixelWidth;
    if(method && base.method !== undefined) method.value = base.method;
    if(paletteSize && base.paletteSize !== undefined) paletteSize.value = base.paletteSize;
    if(ditherCk && base.dither !== undefined) ditherCk.checked = base.dither;
    if(autoRenderCk && base.autoRender !== undefined) autoRenderCk.checked = base.autoRender;
    if(autoFitCk && base.autoFit !== undefined) autoFitCk.checked = base.autoFit;

    let img = sourceImage;
    let zoom = 1;

    // Cached canvases used for processing. Reuse them instead of creating
    // new elements on every render to cut down on DOM churn.
    const useOffscreen = typeof OffscreenCanvas !== 'undefined';
    const small = useOffscreen ? new OffscreenCanvas(0,0) : document.createElement('canvas');
    const src   = useOffscreen ? new OffscreenCanvas(0,0) : document.createElement('canvas');
    const worker = settings?.worker;

    // Helpers for reading current UI state
    function readPixelWidth(){ return Number(pixelWidth?.value||64); }
    function readMethod(){ return method?.value||'neighbor'; }
    function readPaletteSize(){ return Number(paletteSize?.value||16); }
    function readDither(){ return ditherCk?.checked||false; }
    function readAutoRender(){ return autoRenderCk?.checked||false; }
    function readAutoFit(){ return autoFitCk?.checked||false; }
    function readAdjustments(){
      return {
        brightness:Number(bri?.value||0),
        contrast:Number(con?.value||0),
        saturation:Number(sat?.value||100)
      };
    }
    function readGlowSettings(){
      return {
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
        }
      };
    }
    function readTuneSettings(){
      return {
        enableTune: enableTune?.checked||false,
        tune:{
          R:Number(tR?.value||0),
          Y:Number(tY?.value||0),
          G:Number(tG?.value||0),
          C:Number(tC?.value||0),
          B:Number(tB?.value||0),
          M:Number(tM?.value||0)
        }
      };
    }
    function readRemapSettings(){
      return {
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

    // Rendering helpers
    function scaleImage(targetW, targetH){
      small.width = targetW; small.height = targetH;
      const sctx = small.getContext('2d', { willReadFrequently:true });
      const m = readMethod();
      const k = Math.max(2, Math.min(64, readPaletteSize()));
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
      if(worker){
        try{ worker.postMessage({small, src}); } catch(e){}
      }
      return sctx;
    }

    let currentSettings = {};
    function collectSettings(){
      const baseSettings = {
        pixelWidth: readPixelWidth(),
        method: readMethod(),
        paletteSize: readPaletteSize(),
        dither: readDither(),
        autoRender: readAutoRender(),
        autoFit: readAutoFit()
      };

      function upsert(type, options){
        const idx = layers.findIndex(l=>l.type===type);
        if(options){
          if(idx>=0) layers[idx].options = options;
          else layers.push(new Layer(type, options));
        } else if(idx>=0){
          layers.splice(idx,1);
        }
      }

      if(bri || con || sat){
        const adj = readAdjustments();
        upsert('adjustments', adj);
      }

      if(enableTune || tR || tY || tG || tC || tB || tM){
        const tune = readTuneSettings();
        upsert('tune', tune.enableTune ? tune.tune : null);
      }

      if(enableRemap || remapStrength || mapR || mapY || mapG || mapC || mapB || mapM){
        const remap = readRemapSettings();
        if(remap.enableRemap){
          const mapping={
            R:{t:parseInt(remap.map.R,10),s:remap.mapStr.R/100},
            Y:{t:parseInt(remap.map.Y,10),s:remap.mapStr.Y/100},
            G:{t:parseInt(remap.map.G,10),s:remap.mapStr.G/100},
            C:{t:parseInt(remap.map.C,10),s:remap.mapStr.C/100},
            B:{t:parseInt(remap.map.B,10),s:remap.mapStr.B/100},
            M:{t:parseInt(remap.map.M,10),s:remap.mapStr.M/100},
          };
          const globalStrength=remap.remapStrength/100;
          upsert('remap', {mapping, globalStrength});
        } else {
          upsert('remap', null);
        }
      }

      if(enableGlow || glowThreshold || bloomAlpha || bloomBlur || bloomThreshold || gAll || gR || gY || gG || gC || gB || gM){
        const glow = readGlowSettings();
        if(glow.enableGlow){
          const glowMap={
            R:{s:glow.glow.R.strength/100, r:glow.glow.R.range},
            Y:{s:glow.glow.Y.strength/100, r:glow.glow.Y.range},
            G:{s:glow.glow.G.strength/100, r:glow.glow.G.range},
            C:{s:glow.glow.C.strength/100, r:glow.glow.C.range},
            B:{s:glow.glow.B.strength/100, r:glow.glow.B.range},
            M:{s:glow.glow.M.strength/100, r:glow.glow.M.range},
          };
          const glowThreshVal = glow.glowThreshold/100;
          const glowGlobalVal = glow.glow.global/100;
          upsert('colorGlow', {glowMap, threshold:glowThreshVal, global:glowGlobalVal});
          const bloomOpts = {
            threshold: glow.bloomThreshold,
            blur: glow.bloomBlur,
            alpha: glow.bloomAlpha/100
          };
          upsert('bloom', bloomOpts);
        } else {
          upsert('colorGlow', null);
          upsert('bloom', null);
        }
      }

      currentSettings = { baseSettings, layers: layers.map(l=>({type:l.type, options:{...l.options}})) };
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

    const onResize = ()=>{ if(readAutoFit()) fitToViewport(); };
    listen(window, 'resize', onResize);
    const onAutoFitChange = ()=>{ if(readAutoFit()) fitToViewport(); onChange(); };
    listen(autoFitCk, 'change', onAutoFitChange);
    function render(){
      if(!img) return;
      status('Rendering…');
      const targetW = Math.max(8, Math.min(1024, readPixelWidth()));
      const targetH = Math.round((img.naturalHeight/img.naturalWidth) * targetW);
        const sctx = scaleImage(targetW, targetH);
        for(const layer of layers){
          switch(layer.type){
            case 'adjustments':{
              const o = layer.options;
              applyAdjustments(sctx, targetW, targetH, o.brightness, o.contrast, o.saturation);
              break;
            }
            case 'tune':
              applyColorTuning(sctx, targetW, targetH, layer.options);
              break;
            case 'remap':{
              const o = layer.options;
              applyHueRemap(sctx, targetW, targetH, o.mapping, o.globalStrength);
              break;
            }
            case 'colorGlow':{
              const o = layer.options;
              applyColorGlow(sctx, targetW, targetH, o.glowMap, o.threshold, o.global);
              break;
            }
            case 'bloom':{
              const o = layer.options;
              applyBloom(sctx, targetW, targetH, o.threshold, o.blur, o.alpha);
              break;
            }
          }
        }
      if(readDither()){
        floydSteinbergQuantize(sctx, targetW, targetH, 16);
      }
      canvas.width = targetW; canvas.height = targetH;
      ctx.imageSmoothingEnabled = false;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.drawImage(small,0,0);
      if(pixMeta) pixMeta.textContent = `${targetW}×${targetH}`;
      if(readAutoFit()) fitToViewport();
      status('Done.');
    }

    const maybeRender = throttled(120, ()=>{ if(readAutoRender()) render(); });

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

      applyLayersToUI(layers);

    collectSettings();
    render();

      function destroy(){
        listeners.forEach(({target,event,handler,options})=>{
          target.removeEventListener(event, handler, options);
        });
      }

      function addLayer(layer){
        layers.push(layer instanceof Layer ? layer : new Layer(layer.type, layer.options));
        collectSettings();
        maybeRender();
      }

      function removeLayer(index){
        if(index>=0 && index<layers.length){
          layers.splice(index,1);
          collectSettings();
          maybeRender();
        }
      }

      function moveLayer(from,to){
        if(from>=0 && from<layers.length && to>=0 && to<layers.length){
          const [l] = layers.splice(from,1);
          layers.splice(to,0,l);
          collectSettings();
          maybeRender();
        }
      }

      function updateLayer(index, options){
        if(index>=0 && index<layers.length){
          layers[index].options = options;
          collectSettings();
          maybeRender();
        }
      }

      return {
        render,
        setImage: (image)=>{ img=image; collectSettings(); render(); },
        destroy,
        getSettings: () => { collectSettings(); return currentSettings; },
        addLayer,
        removeLayer,
        moveLayer,
        updateLayer
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


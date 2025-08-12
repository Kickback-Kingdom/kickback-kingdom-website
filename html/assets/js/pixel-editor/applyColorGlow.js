'use strict';
import { rgbToHsl, hslToRgb, nearestBucket } from './utils.js';
import { scratchCanvas, scratchCtx } from './scratch-canvas.js';

export function applyColorGlow(ctx, w, h, glows, threshold=0, globalMult=1){
  if(!glows || !Object.values(glows).some(v=>v.s>0) || !scratchCtx) return;
  scratchCanvas.width = w;
  scratchCanvas.height = h;
  for(const [bandKey, cfg] of Object.entries(glows)){
    const strength=(cfg.s||0)*globalMult;
    if(strength<=0) continue;
    const range=cfg.r||0;
    scratchCtx.drawImage(ctx.canvas,0,0);
    const img=scratchCtx.getImageData(0,0,w,h);
    const d=img.data;
    for(let i=0;i<d.length;i+=4){
      const r=d[i], g=d[i+1], bl=d[i+2];
      const hsl=rgbToHsl(r,g,bl);
      const b=nearestBucket(hsl.h);
      if(b!==bandKey || hsl.l<=threshold){ d[i]=d[i+1]=d[i+2]=0; continue; }
      const t=(hsl.l - threshold)/(1 - threshold);
      const eff=strength*t;
      const newL=Math.min(1, hsl.l+eff);
      const newS=Math.min(1, hsl.s+eff);
      const rgb=hslToRgb(hsl.h,newS,newL);
      d[i]=rgb.r; d[i+1]=rgb.g; d[i+2]=rgb.b;
    }
    scratchCtx.putImageData(img,0,0);
    ctx.save();
    if(range>0) ctx.filter=`blur(${range}px)`;
    ctx.globalCompositeOperation='lighter';
    ctx.globalAlpha=Math.min(1, strength);
    ctx.drawImage(scratchCanvas,0,0);
    ctx.restore();
  }
}

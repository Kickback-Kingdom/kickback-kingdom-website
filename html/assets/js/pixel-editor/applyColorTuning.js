'use strict';
import { rgbToHsl, hslToRgb, nearestBucket } from './utils.js';

export function applyColorTuning(ctx, w, h, gains){
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

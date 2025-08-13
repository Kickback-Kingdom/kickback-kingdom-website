'use strict';
import { clamp8 } from './utils.js';

export function applyAdjustments(ctx, w, h, bri, con, sat){
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

'use strict';
import { scratchCanvas, scratchCtx } from './scratch-canvas.js';

export function applyBloom(ctx, w, h, threshold=200, blurRadius=0, alpha=0){
  if(alpha<=0 || !scratchCtx) return;
  scratchCanvas.width = w;
  scratchCanvas.height = h;
  scratchCtx.drawImage(ctx.canvas,0,0);
  const img=scratchCtx.getImageData(0,0,w,h);
  const d=img.data;
  for(let i=0;i<d.length;i+=4){
    const r=d[i], g=d[i+1], b=d[i+2];
    const lum=0.2126*r + 0.7152*g + 0.0722*b;
    if(lum<threshold){ d[i]=d[i+1]=d[i+2]=0; }
  }
  scratchCtx.putImageData(img,0,0);
  ctx.save();
  if(blurRadius>0) ctx.filter=`blur(${blurRadius}px)`;
  ctx.globalCompositeOperation='lighter';
  const easedAlpha=Math.pow(alpha,3);
  ctx.globalAlpha=Math.min(1, easedAlpha);
  ctx.drawImage(scratchCanvas,0,0);
  ctx.restore();
}

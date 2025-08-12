'use strict';
import { rgbToHsl, hslToRgb, nearestBucket, BAND_HUE } from './utils.js';

export function applyHueRemap(ctx, w, h, mapping, globalStrength){
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

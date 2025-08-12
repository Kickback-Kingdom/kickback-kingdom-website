'use strict';

export function clamp8(v){ return v<0?0:(v>255?255:v)|0; }

export function rgbToHsl(r,g,b){
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

export function hslToRgb(h,s,l){
  const c=(1-Math.abs(2*l-1))*s, hp=h/60, x=c*(1-Math.abs((hp%2)-1));
  let r1=0,g1=0,b1=0;
  if(0<=hp&&hp<1){r1=c;g1=x;} else if(1<=hp&&hp<2){r1=x;g1=c;}
  else if(2<=hp&&hp<3){g1=c;b1=x;} else if(3<=hp&&hp<4){g1=x;b1=c;}
  else if(4<=hp&&hp<5){r1=x;b1=c;} else if(5<=hp&&hp<6){r1=c;b1=x;}
  const m=l-c/2;
  return {r:clamp8((r1+m)*255), g:clamp8((g1+m)*255), b:clamp8((b1+m)*255)};
}

export const BUCKETS = [
  {k:'R',h:0},
  {k:'Y',h:60},
  {k:'G',h:120},
  {k:'C',h:180},
  {k:'B',h:240},
  {k:'M',h:300}
];

export function nearestBucket(hh){
  let bk='R', bd=1e9;
  for(const b of BUCKETS){
    const diff=Math.min(Math.abs(hh-b.h), 360-Math.abs(hh-b.h));
    if(diff<bd){ bd=diff; bk=b.k; }
  }
  return bk;
}

export const BAND_HUE=[0,0,60,120,180,240,300];

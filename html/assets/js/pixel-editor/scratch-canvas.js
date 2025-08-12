'use strict';

export const scratchCanvas = typeof OffscreenCanvas !== 'undefined'
  ? new OffscreenCanvas(1,1)
  : (typeof document !== 'undefined' ? document.createElement('canvas') : null);

export const scratchCtx = scratchCanvas ? scratchCanvas.getContext('2d') : null;

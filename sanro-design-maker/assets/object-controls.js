(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory;
  else root.SANRODMConfigureObjectControls = factory;
}(typeof window !== 'undefined' ? window : globalThis, function (object, zoom) {
  zoom = Math.max(0.01, Number(zoom) || 1);
  const angle = (Number(object.angle) || 0) * Math.PI / 180;
  const width = Math.abs(object.width * object.scaleX * Math.cos(angle)) + Math.abs(object.height * object.scaleY * Math.sin(angle));
  const height = Math.abs(object.height * object.scaleY * Math.cos(angle)) + Math.abs(object.width * object.scaleX * Math.sin(angle));
  // Keep neighboring corner hit boxes from overlapping on small/narrow objects.
  const touchSize = Math.max(1, Math.min(48, Math.floor(Math.min(width, height) * zoom * 0.45)));
  object.set({ cornerSize: 9, touchCornerSize: touchSize, cornerStyle: 'circle',
    cornerColor: '#fff', cornerStrokeColor: '#46515d', transparentCorners: false,
    borderColor: '#68727e', borderScaleFactor: 1, padding: 2, hasControls: true, hasBorders: true,
    lockMovementX: false, lockMovementY: false, lockScalingX: false, lockScalingY: false,
    lockRotation: false, lockScalingFlip: false });
  ['ml', 'mr', 'mt', 'mb'].forEach(key => { if (object.controls[key]) object.controls[key].visible = false; });
  Object.keys(object.controls).forEach(key => {
    const control = object.controls[key];
    control.sizeX = 9; control.sizeY = 9;
    control.touchSizeX = touchSize; control.touchSizeY = touchSize;
  });
  const rotate = object.controls.mtr;
  if (rotate) {
    rotate.visible = true;
    // Fabric positions controls in viewport pixels, with the zoom removed.
    rotate.offsetY = -36;
    rotate.withConnection = true;
    if (!rotate.__sanroIconRender) {
      const baseRender = rotate.render;
      rotate.render = function (ctx, left, top, style, target) {
        baseRender.call(this, ctx, left, top, style, target);
        ctx.save(); ctx.strokeStyle = '#46515d'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.arc(left, top, 2.5, -Math.PI * 0.72, Math.PI * 0.62); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(left + 2.1, top + 1.8); ctx.lineTo(left + 3.2, top + 0.1); ctx.lineTo(left + 1.2, top + 0.2); ctx.stroke();
        ctx.restore();
      };
      rotate.__sanroIconRender = true;
    }
  }
  object.setCoords();
  return { visualSize: 9, touchSize: touchSize };
}));

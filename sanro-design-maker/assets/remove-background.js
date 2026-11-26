(function(root){
  'use strict';
  function remove(data,width,height,options){
    options=options||{};if(!data||!width||!height||width*height>8000000)return false;
    const px=data,sw=Math.max(1,Math.floor(width/100)),sh=Math.max(1,Math.floor(height/100)),samples=[];
    [[0,0],[width-sw,0],[0,height-sh],[width-sw,height-sh]].forEach(([ox,oy])=>{for(let y=oy;y<oy+sh;y++)for(let x=ox;x<ox+sw;x++){const i=(y*width+x)*4;if(px[i+3]>0)samples.push([px[i],px[i+1],px[i+2]]);}});
    if(!samples.length)return false;
    const color=[0,1,2].map(c=>samples.reduce((sum,p)=>sum+p[c],0)/samples.length),count=width*height,seen=new Uint8Array(count),queue=new Uint32Array(count),tolerance=Number(options.tolerance||48),feather=Number(options.feather||28),cutoff=tolerance+feather;
    const distanceAt=i=>{const p=i*4;return Math.hypot(px[p]-color[0],px[p+1]-color[1],px[p+2]-color[2]);};
    let head=0,tail=0;const seed=i=>{if(!seen[i]&&px[i*4+3]>0&&distanceAt(i)<cutoff){seen[i]=1;queue[tail++]=i;}};
    for(let x=0;x<width;x++){seed(x);seed((height-1)*width+x);}for(let y=1;y<height-1;y++){seed(y*width);seed(y*width+width-1);}
    while(head<tail){const i=queue[head++],d=distanceAt(i);if(d<tolerance)px[i*4+3]=0;else px[i*4+3]=Math.round(px[i*4+3]*Math.min(1,(d-tolerance)/feather));const x=i%width,y=(i/width)|0;if(x>0)seed(i-1);if(x+1<width)seed(i+1);if(y>0)seed(i-width);if(y+1<height)seed(i+width);}
    return true;
  }
  root.SanroRemoveFlatBackground=remove;
  if(typeof module==='object'&&module.exports)module.exports=remove;
}(typeof window!=='undefined'?window:globalThis));

const test=require('node:test');
const assert=require('node:assert/strict');
const remove=require('../sanro-design-maker/assets/remove-background.js');

function image(width,height,fill){const data=new Uint8ClampedArray(width*height*4);for(let i=0;i<width*height;i++){data[i*4]=fill[0];data[i*4+1]=fill[1];data[i*4+2]=fill[2];data[i*4+3]=255;}return data;}
test('clears edge-connected flat background and preserves contrasting center',()=>{
  const width=9,height=9,data=image(width,height,[250,250,250]);const center=(4*width+4)*4;data[center]=20;data[center+1]=30;data[center+2]=40;
  assert.equal(remove(data,width,height),true);assert.equal(data[3],0);assert.equal(data[center+3],255);
});
test('preserves same-color pixels disconnected from image edge',()=>{
  const width=9,height=9,data=image(width,height,[10,20,30]);[[0,0],[8,0],[0,8],[8,8]].forEach(([x,y])=>{const i=(y*width+x)*4;data[i]=250;data[i+1]=250;data[i+2]=250;});
  const island=(4*width+4)*4;data[island]=250;data[island+1]=250;data[island+2]=250;
  assert.equal(remove(data,width,height),true);assert.equal(data[3],0);assert.equal(data[island+3],255);
});
test('refuses oversized and transparent input',()=>{
  assert.equal(remove(new Uint8ClampedArray(4),5000,5000),false);assert.equal(remove(new Uint8ClampedArray(4*9*9),9,9),false);
});

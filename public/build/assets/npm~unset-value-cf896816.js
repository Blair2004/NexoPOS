import{a}from"./npm~isobject-2e03973b.js";import{h as n}from"./npm~has-value-859045a0.js";/*!
 * unset-value <https://github.com/jonschlinkert/unset-value>
 *
 * Copyright (c) 2015, 2017, Jon Schlinkert.
 * Released under the MIT License.
 */var h=a,u=n,v=function(e,r){if(!h(e))throw new TypeError("expected an object.");if(e.hasOwnProperty(r))return delete e[r],!0;if(u(e,r)){for(var t=r.split("."),i=t.pop();t.length&&t[t.length-1].slice(-1)==="\\";)i=t.pop().slice(0,-1)+"."+i;for(;t.length;)e=e[r=t.shift()];return delete e[i]}return!0};export{v as u};

import{g as p}from"./npm~@ckeditor~ckeditor5-build-classic_-ec80f51a.js";import{k as s}from"./npm~kind-of-f578382b.js";import{r as i}from"./npm~rename-keys-392f1d0e.js";/*!
 * deep-rename-keys <https://github.com/jonschlinkert/deep-rename-keys>
 *
 * Copyright (c) 2015 Jon Schlinkert, contributors.
 * Licensed under the MIT license.
 */var n=s,c=i,v=function m(r,f){var o=n(r);if(o!=="object"&&o!=="array")throw new Error("expected an object");var e=[];o==="object"&&(r=c(r,f),e={});for(var a in r)if(r.hasOwnProperty(a)){var t=r[a];n(t)==="object"||n(t)==="array"?e[a]=m(t,f):e[a]=t}return e};const u=p(v);export{u as r};

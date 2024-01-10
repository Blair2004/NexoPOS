import{k as i}from"./npm~kind-of-f578382b.js";import{r as s}from"./npm~rename-keys-8dab041c.js";/*!
 * deep-rename-keys <https://github.com/jonschlinkert/deep-rename-keys>
 *
 * Copyright (c) 2015 Jon Schlinkert, contributors.
 * Licensed under the MIT license.
 */var f=i,m=s,v=function p(r,o){var n=f(r);if(n!=="object"&&n!=="array")throw new Error("expected an object");var e=[];n==="object"&&(r=m(r,o),e={});for(var a in r)if(r.hasOwnProperty(a)){var t=r[a];f(t)==="object"||f(t)==="array"?e[a]=p(t,o):e[a]=t}return e};export{v as d};

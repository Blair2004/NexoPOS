/*!
 * get-value <https://github.com/jonschlinkert/get-value>
 *
 * Copyright (c) 2014-2015, Jon Schlinkert.
 * Licensed under the MIT License.
 */var h=function(n,t,u,s,c){if(!l(n)||!t)return n;if(t=e(t),u&&(t+="."+e(u)),s&&(t+="."+e(s)),c&&(t+="."+e(c)),t in n)return n[t];for(var r=t.split("."),g=r.length,f=-1;n&&++f<g;){for(var i=r[f];i[i.length-1]==="\\";)i=i.slice(0,-1)+"."+r[++f];n=n[i]}return n};function l(n){return n!==null&&(typeof n=="object"||typeof n=="function")}function e(n){return n?Array.isArray(n)?n.join("."):n:""}export{h as g};

import{a}from"./npm~isobject-2e03973b.js";/*!
 * is-plain-object <https://github.com/jonschlinkert/is-plain-object>
 *
 * Copyright (c) 2014-2017, Jon Schlinkert.
 * Released under the MIT License.
 */var f=a;function i(t){return f(t)===!0&&Object.prototype.toString.call(t)==="[object Object]"}var s=function(o){var e,r;return!(i(o)===!1||(e=o.constructor,typeof e!="function")||(r=e.prototype,i(r)===!1)||r.hasOwnProperty("isPrototypeOf")===!1)};export{s as i};

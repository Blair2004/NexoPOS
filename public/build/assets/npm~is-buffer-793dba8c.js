/*!
 * Determine if an object is a Buffer
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */var n=function(f){return f!=null&&(r(f)||t(f)||!!f._isBuffer)};function r(f){return!!f.constructor&&typeof f.constructor.isBuffer=="function"&&f.constructor.isBuffer(f)}function t(f){return typeof f.readFloatLE=="function"&&typeof f.slice=="function"&&r(f.slice(0,0))}export{n as i};

/*!
 * has-values <https://github.com/jonschlinkert/has-values>
 *
 * Copyright (c) 2014-2015, Jon Schlinkert.
 * Licensed under the MIT License.
 */var u=function(e,r){if(e==null)return!1;if(typeof e=="boolean")return!0;if(typeof e=="number")return!(e===0&&r===!0);if(e.length!==void 0)return e.length!==0;for(var n in e)if(e.hasOwnProperty(n))return!0;return!1};export{u as h};

function __vite__mapDeps(indexes) {
  if (!__vite__mapDeps.viteFileDeps) {
    __vite__mapDeps.viteFileDeps = ["./welcome-DhnKJMzx.js","./currency-lOMYG1Wf.js","./_plugin-vue_export-helper-DlAUqK2U.js","./runtime-core.esm-bundler-RT2b-_3S.js","./database-xDCHP3Rv.js","./bootstrap-CZq7-ikA.js","./setup-configuration-C268_U-2.js"]
  }
  return indexes.map((i) => __vite__mapDeps.viteFileDeps[i])
}
import{_ as o}from"./preload-helper-BQ24v_F8.js";import{b as e}from"./components-CZ5Z241h.js";import{c as p,a as n}from"./vue-router-D26Ko6bT.js";import{c as i}from"./bootstrap-CZq7-ikA.js";import"./ns-alert-popup-SVrn5Xft.js";import"./currency-lOMYG1Wf.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./runtime-core.esm-bundler-RT2b-_3S.js";import"./ns-avatar-image-CAD6xUGA.js";import"./index.es-Br67aBEV.js";import"./ns-prompt-popup-Ds8JknSD.js";const a=()=>o(()=>import("./welcome-DhnKJMzx.js"),__vite__mapDeps([0,1,2,3]),import.meta.url),s=()=>o(()=>import("./database-xDCHP3Rv.js"),__vite__mapDeps([4,5,1,3,2]),import.meta.url),_=()=>o(()=>import("./setup-configuration-C268_U-2.js"),__vite__mapDeps([6,5,1,3,2]),import.meta.url),c=[{path:"/",component:a},{path:"/database",component:s},{path:"/configuration",component:_}],m=p({routes:c,history:n()}),t=i({});t.use(m);for(let r in e)t.component(r,e[r]);t.mount("#nexopos-setup");window.nsRouter=m;

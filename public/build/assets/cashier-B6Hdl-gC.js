var a=Object.defineProperty;var h=(e,s,r)=>s in e?a(e,s,{enumerable:!0,configurable:!0,writable:!0,value:r}):e[s]=r;var t=(e,s,r)=>h(e,typeof s!="symbol"?s+"":s,r);import{b as i,B as p,d as n}from"./bootstrap-BXL5x0lI.js";import{_ as o}from"./currency-Dtag6qPd.js";import"./chart-C9SIUyYU.js";import"./runtime-core.esm-bundler-Bzup5G8m.js";class c{constructor(){t(this,"_mysales");t(this,"_reports",{mysales:i.get("/api/reports/cashier-report")});this._mysales=new p({});for(let s in this._reports)this.loadReport(s)}loadReport(s){return this._reports[s].subscribe(r=>{this[`_${s}`].next(r)})}refreshReport(){i.get("/api/reports/cashier-report?refresh=true").subscribe(s=>{this._mysales.next(s),n.success(o("The report has been refreshed."),o("OK")).subscribe()})}get mysales(){return this._mysales}}window.Cashier=new c;
const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./rewards-system-BKX09MEr.js","./bootstrap-BXL5x0lI.js","./currency-Dtag6qPd.js","./chart-C9SIUyYU.js","./runtime-core.esm-bundler-Bzup5G8m.js","./_plugin-vue_export-helper-DlAUqK2U.js","./create-coupons-C638mL4C.js","./ns-settings-YLRae8p6.js","./components-D_il-EOi.js","./ns-prompt-popup-76DeRno7.js","./ns-prompt-popup-CNox9BoV.css","./ns-avatar-image--GgTfTV1.js","./reset-Dufac-DU.js","./modules-uNgWdRC3.js","./ns-permissions-abmLS4wZ.js","./ns-procurement-Bc3au3Td.js","./manage-products-t5Ots47O.js","./select-api-entities-D8Jzj57e.js","./join-array-NDqpMoMN.js","./ns-notifications-D9a9g94c.js","./ns-transaction-4WwlSob2.js","./ns-dashboard-CUus5gpB.js","./ns-low-stock-report-2uDHiYRh.js","./ns-sale-report-hm2oPThb.js","./ns-sold-stock-report-BfQEd2wN.js","./ns-profit-report-DqLffqwD.js","./ns-stock-combined-report-ixCQn9f6.js","./ns-cash-flow-report-BvHRuPb4.js","./ns-yearly-report-BDMX6HtC.js","./ns-best-products-report-DsgnoqXv.js","./ns-payment-types-report-ChFHqmJt.js","./ns-customers-statement-report-Dt6xSQDx.js","./ns-stock-adjustment-BB1jb6Dz.js","./ns-procurement-quantity-uqqYftPM.js","./ns-order-invoice-Omi2X7z3.js","./ns-print-label-B6M13I80.js","./ns-transactions-rules-CAB2eddh.js","./ns-token-lNCKj-tC.js"])))=>i.map(i=>d[i]);
import{_ as e}from"./preload-helper-C1FmrZbK.js";import"./time-BXBXhs-S.js";import{b as w,n as f,a as I}from"./components-D_il-EOi.js";import{c as m,n as L}from"./bootstrap-BXL5x0lI.js";import{N as y}from"./ns-hotpress-CTuwUR6C.js";import{d as t}from"./runtime-core.esm-bundler-Bzup5G8m.js";import"./ns-prompt-popup-76DeRno7.js";import"./currency-Dtag6qPd.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./ns-avatar-image--GgTfTV1.js";import"./chart-C9SIUyYU.js";function V(o,_){_.forEach(a=>{let r=o.document.createElement("link");r.setAttribute("rel","stylesheet"),r.setAttribute("type","text/css"),r.setAttribute("href",a),o.document.getElementsByTagName("head")[0].appendChild(r)})}const O={install(o,_={}){o.config.globalProperties.$htmlToPaper=(a,r,D=()=>!0)=>{let P="_blank",R=["fullscreen=yes","titlebar=yes","scrollbars=yes"],v=!0,T=[],{name:u=P,specs:i=R,replace:A=v,styles:p=T}=_;r&&(r.name&&(u=r.name),r.specs&&(i=r.specs),r.replace&&(A=r.replace),r.styles&&(p=r.styles)),i=i.length?i.join(","):"";const l=window.document.getElementById(a);if(!l){alert(`Element to print #${a} not found!`);return}const s=window.open("",u,i);return s.document.write(`
          <html>
            <head>
              <title>${window.document.title}</title>
            </head>
            <body>
              ${l.innerHTML}
            </body>
          </html>
        `),V(s,p),setTimeout(()=>{s.document.close(),s.focus(),s.print(),s.close(),D()},1e3),!0}}},S=t(()=>e(()=>import("./rewards-system-BKX09MEr.js"),__vite__mapDeps([0,1,2,3,4,5]),import.meta.url)),g=t(()=>e(()=>import("./create-coupons-C638mL4C.js"),__vite__mapDeps([6,1,2,3,4,5]),import.meta.url)),C=t(()=>e(()=>import("./ns-settings-YLRae8p6.js"),__vite__mapDeps([7,2,1,3,4,8,9,5,10,11]),import.meta.url)),k=t(()=>e(()=>import("./reset-Dufac-DU.js"),__vite__mapDeps([12,2,1,3,4,5]),import.meta.url)),H=t(()=>e(()=>import("./modules-uNgWdRC3.js"),__vite__mapDeps([13,1,2,3,4,9,5,10]),import.meta.url)),M=t(()=>e(()=>import("./ns-permissions-abmLS4wZ.js"),__vite__mapDeps([14,1,2,3,4,5]),import.meta.url)),j=t(()=>e(()=>import("./ns-procurement-Bc3au3Td.js"),__vite__mapDeps([15,1,2,3,4,16,9,5,10,8,11,17,18]),import.meta.url)),N=t(()=>e(()=>import("./manage-products-t5Ots47O.js"),__vite__mapDeps([16,1,2,3,4,9,5,10,8,11]),import.meta.url)),q=t(()=>e(()=>import("./ns-procurement-invoice-DSSNRCNz.js"),[],import.meta.url)),x=t(()=>e(()=>import("./ns-notifications-D9a9g94c.js"),__vite__mapDeps([19,1,2,3,4,9,5,10,8,11]),import.meta.url)),$=t(()=>e(()=>import("./components-D_il-EOi.js").then(o=>o.j),__vite__mapDeps([8,9,2,5,4,1,3,10,11]),import.meta.url)),B=t(()=>e(()=>import("./ns-transaction-4WwlSob2.js"),__vite__mapDeps([20,1,2,3,4,9,5,10]),import.meta.url)),F=t(()=>e(()=>import("./ns-dashboard-CUus5gpB.js"),__vite__mapDeps([21,1,2,3,4,5]),import.meta.url)),Y=t(()=>e(()=>import("./ns-low-stock-report-2uDHiYRh.js"),__vite__mapDeps([22,1,2,3,4,8,9,5,10,11,18]),import.meta.url)),z=t(()=>e(()=>import("./ns-sale-report-hm2oPThb.js"),__vite__mapDeps([23,1,2,3,4,8,9,5,10,11,18]),import.meta.url)),G=t(()=>e(()=>import("./ns-sold-stock-report-BfQEd2wN.js"),__vite__mapDeps([24,1,2,3,4,8,9,5,10,11,17,18]),import.meta.url)),J=t(()=>e(()=>import("./ns-profit-report-DqLffqwD.js"),__vite__mapDeps([25,1,2,3,4,8,9,5,10,11,17,18]),import.meta.url)),K=t(()=>e(()=>import("./ns-stock-combined-report-ixCQn9f6.js"),__vite__mapDeps([26,1,2,3,4,17,9,5,10,18]),import.meta.url)),Q=t(()=>e(()=>import("./ns-cash-flow-report-BvHRuPb4.js"),__vite__mapDeps([27,1,2,3,4,8,9,5,10,11]),import.meta.url)),U=t(()=>e(()=>import("./ns-yearly-report-BDMX6HtC.js"),__vite__mapDeps([28,1,2,3,4,8,9,5,10,11]),import.meta.url)),W=t(()=>e(()=>import("./ns-best-products-report-DsgnoqXv.js"),__vite__mapDeps([29,1,2,3,4,8,9,5,10,11]),import.meta.url)),X=t(()=>e(()=>import("./ns-payment-types-report-ChFHqmJt.js"),__vite__mapDeps([30,1,2,3,4,8,9,5,10,11]),import.meta.url)),Z=t(()=>e(()=>import("./ns-customers-statement-report-Dt6xSQDx.js"),__vite__mapDeps([31,2,5,4]),import.meta.url)),ee=t(()=>e(()=>import("./ns-stock-adjustment-BB1jb6Dz.js"),__vite__mapDeps([32,1,2,3,4,33,5,9,10]),import.meta.url)),te=t(()=>e(()=>import("./ns-order-invoice-Omi2X7z3.js"),__vite__mapDeps([34,2,5,4]),import.meta.url)),oe=t(()=>e(()=>import("./ns-print-label-B6M13I80.js"),__vite__mapDeps([35,2,4,1,3,5]),import.meta.url)),re=t(()=>e(()=>import("./ns-transactions-rules-CAB2eddh.js"),__vite__mapDeps([36,1,2,3,4,9,5,10,8,11]),import.meta.url)),n=window.nsState,se=window.nsScreen;nsExtraComponents.nsToken=t(()=>e(()=>import("./ns-token-lNCKj-tC.js"),__vite__mapDeps([37,1,2,3,4,5,9,10]),import.meta.url));window.nsHotPress=new y;const d=Object.assign({nsModules:H,nsRewardsSystem:S,nsCreateCoupons:g,nsManageProducts:N,nsSettings:C,nsReset:k,nsPermissions:M,nsProcurement:j,nsProcurementInvoice:q,nsMedia:$,nsTransaction:B,nsDashboard:F,nsPrintLabel:oe,nsNotifications:x,nsSaleReport:z,nsSoldStockReport:G,nsProfitReport:J,nsStockCombinedReport:K,nsCashFlowReport:Q,nsYearlyReport:U,nsPaymentTypesReport:X,nsBestProductsReport:W,nsLowStockReport:Y,nsCustomersStatementReport:Z,nsTransactionsRules:re,nsStockAdjustment:ee,nsOrderInvoice:te,...w},nsExtraComponents);window.nsDashboardAside=m({data(){return{sidebar:"visible",popups:[]}},components:{nsMenu:f,nsSubmenu:I},mounted(){n.subscribe(o=>{o.sidebar&&(this.sidebar=o.sidebar)})}});window.nsDashboardOverlay=m({data(){return{sidebar:null,popups:[]}},components:d,mounted(){n.subscribe(o=>{o.sidebar&&(this.sidebar=o.sidebar)})},methods:{closeMenu(){n.setState({sidebar:this.sidebar==="hidden"?"visible":"hidden"})}}});window.nsDashboardHeader=m({data(){return{menuToggled:!1,sidebar:null}},components:d,methods:{toggleMenu(){this.menuToggled=!this.menuToggled},toggleSideMenu(){["lg","xl"].includes(se.breakpoint)?n.setState({sidebar:this.sidebar==="hidden"?"visible":"hidden"}):n.setState({sidebar:this.sidebar==="hidden"?"visible":"hidden"})}},mounted(){n.subscribe(o=>{o.sidebar&&(this.sidebar=o.sidebar)})}});window.nsDashboardContent=m({});for(let o in d)window.nsDashboardContent.component(o,d[o]);window.nsDashboardContent.use(O,{styles:Object.values(window.ns.cssFiles)});window.nsComponents=Object.assign(d,w);L.doAction("ns-before-mount");const c=document.querySelector("#dashboard-aside");window.nsDashboardAside&&c&&window.nsDashboardAside.mount(c);const b=document.querySelector("#dashboard-overlay");window.nsDashboardOverlay&&b&&window.nsDashboardOverlay.mount(b);const E=document.querySelector("#dashboard-header");window.nsDashboardHeader&&E&&window.nsDashboardHeader.mount(E);const h=document.querySelector("#dashboard-content");window.nsDashboardContent&&h&&window.nsDashboardContent.mount(h);
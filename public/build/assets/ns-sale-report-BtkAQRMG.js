import{a as x,b as h,h as w}from"./bootstrap-CZq7-ikA.js";import{c as C,e as T}from"./components-CZ5Z241h.js";import{_ as d,n as k}from"./currency-lOMYG1Wf.js";import{b as f}from"./ns-prompt-popup-Ds8JknSD.js";import{j as D}from"./join-array-DPKtuOQJ.js";import{_ as U}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{r as P,o as i,c as n,a as t,f as v,n as F,t as s,e as p,F as b,b as m}from"./runtime-core.esm-bundler-RT2b-_3S.js";import"./ns-alert-popup-SVrn5Xft.js";import"./ns-avatar-image-CAD6xUGA.js";import"./index.es-Br67aBEV.js";const L={name:"ns-sale-report",data(){return{saleReport:"",startDateField:{name:"start_date",type:"datetime",value:ns.date.moment.startOf("day").format()},endDateField:{name:"end_date",type:"datetime",value:ns.date.moment.endOf("day").format()},result:[],isLoading:!1,users:[],ns:window.ns,summary:{},selectedUser:"",selectedCategory:"",reportType:{label:d("Report Type"),name:"reportType",type:"select",value:"categories_report",options:[{label:d("Categories Detailed"),value:"categories_report"},{label:d("Categories Summary"),value:"categories_summary"},{label:d("Products"),value:"products_report"}],description:d("Allow you to choose the report type.")},filterUser:{label:d("Filter User"),name:"filterUser",type:"select",value:"",options:[],description:d("Allow you to choose the report type.")},filterCategory:{label:d("Filter By Category"),name:"filterCategory",type:"multiselect",value:"",options:[],description:d("Allow you to choose the category.")},field:{type:"datetimepicker",value:"2021-02-07",name:"date"}}},components:{nsDatepicker:C,nsDateTimePicker:T},computed:{},methods:{__:d,nsCurrency:k,joinArray:D,printSaleReport(){this.$htmlToPaper("sale-report")},async openSettings(){try{const c=await new Promise((a,l)=>{Popup.show(f,{...this.reportType,resolve:a,reject:l})});this.reportType.value=c,this.result=[],this.loadReport()}catch(c){console.log({exception:c})}},async openUserFiltering(){try{this.isLoading=!0;const c=await new Promise((l,_)=>{x.get("/api/users").subscribe({next:r=>{this.users=r,this.isLoading=!1,this.filterUser.options=[{label:d("All Users"),value:""},...this.users.map(e=>({label:e.username,value:e.id}))],Popup.show(f,{...this.filterUser,resolve:l,reject:_})},error:r=>{this.isLoading=!1,h.error(d("No user was found for proceeding the filtering.")),_(r)}})}),a=this.users.filter(l=>l.id===c);if(a.length>0){let l=a[0];this.selectedUser=`${l.username} ${l.first_name||l.last_name?l.first_name+" "+l.last_name:""}`,this.filterUser.value=c,this.result=[],this.loadReport()}}catch(c){console.log({exception:c})}},async openCategoryFiltering(){try{let c=[];this.isLoading=!0;const a=await new Promise((l,_)=>{x.get("/api/categories").subscribe({next:r=>{this.isLoading=!1,c=r,this.filterCategory.options=[...r.map(e=>({label:e.name,value:e.id}))],Popup.show(f,{...this.filterCategory,resolve:l,reject:_})},error:r=>{this.isLoading=!1,h.error(d("No category was found for proceeding the filtering.")),_(r)}})});if(a.length>0){let l=c.filter(_=>a.includes(_.id)).map(_=>_.name);this.selectedCategory=this.joinArray(l),this.filterCategory.value=a}else this.selectedCategory="",this.filterCategory.value=[];this.result=[],this.loadReport()}catch(c){console.log(c)}},getType(c){const a=this.reportType.options.filter(l=>l.value===c);return a.length>0?a[0].label:d("Unknown")},loadReport(){if(this.startDate===null||this.endDate===null)return h.error(d("Unable to proceed. Select a correct time range.")).subscribe();const c=w(this.startDate);if(w(this.endDate).isBefore(c))return h.error(d("Unable to proceed. The current time range is not valid.")).subscribe();this.isLoading=!0,x.post("/api/reports/sale-report",{startDate:this.startDateField.value,endDate:this.endDateField.value,type:this.reportType.value,user_id:this.filterUser.value,categories_id:this.filterCategory.value}).subscribe({next:l=>{this.isLoading=!1,this.result=l.result,this.summary=l.summary},error:l=>{this.isLoading=!1,h.error(l.message).subscribe()}})},computeTotal(c,a){return c.length>0?c.map(l=>parseFloat(l[a])).reduce((l,_)=>l+_):0}},props:["storeLogo","storeName"],mounted(){}},S={id:"report-section",class:"px-4"},R={class:"flex -mx-2"},B={class:"px-2"},A={class:"px-2"},N={class:"px-2"},j={class:"pl-2"},q={class:"flex -mx-2"},M={class:"px-2"},V=t("i",{class:"las la-print text-xl"},null,-1),H={class:"pl-2"},Q={class:"px-2"},O=t("i",{class:"las la-filter text-xl"},null,-1),z={class:"pl-2"},E={class:"px-2"},I=t("i",{class:"las la-filter text-xl"},null,-1),G={class:"pl-2"},J={class:"px-2"},K=t("i",{class:"las la-filter text-xl"},null,-1),W={class:"pl-2"},X={id:"sale-report",class:"anim-duration-500 fade-in-entrance"},Y={class:"flex w-full"},Z={class:"my-4 flex justify-between w-full"},$={class:"text-secondary"},tt=["innerHTML"],et={class:"pb-1 border-b border-dashed"},st={class:"pb-1 border-b border-dashed"},rt=["src","alt"],ot={class:"-mx-4 flex md:flex-row flex-col"},lt={class:"w-full md:w-1/2 px-4"},at={class:"shadow rounded my-4 ns-box"},ct={class:"border-b ns-box-body"},dt={class:"table ns-table w-full"},it={class:"text-primary"},nt={class:""},_t={width:"200",class:"font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white"},ut={class:"p-2 border text-right border-info-primary"},ht={class:""},pt={width:"200",class:"font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white"},bt={class:"p-2 border text-right border-error-primary"},mt={class:""},yt={width:"200",class:"font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white"},xt={class:"p-2 border text-right border-error-primary"},ft={key:0,class:""},gt={width:"200",class:"font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white"},wt={class:"p-2 border text-right border-error-primary"},vt={class:""},Ct={width:"200",class:"font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white"},Tt={class:"p-2 border text-right border-success-primary"},kt={class:""},Dt={width:"200",class:"font-semibold p-2 border text-left bg-success-secondary border-success-secondary text-white"},Ut={class:"p-2 border text-right border-success-primary"},Pt=t("div",{class:"w-full md:w-1/2 px-4"},null,-1),Ft={key:0,class:"bg-box-background shadow rounded my-4"},Lt={class:"border-b border-box-edge"},St={class:"table ns-table w-full"},Rt={class:"text-primary"},Bt={class:"border p-2 text-left"},At={width:"150",class:"border p-2"},Nt={width:"150",class:"border p-2"},jt={width:"150",class:"border p-2"},qt={width:"150",class:"border p-2"},Mt={width:"150",class:"border p-2"},Vt={width:"150",class:"border p-2"},Ht={class:"text-primary"},Qt={class:"p-2 border"},Ot={class:"p-2 border text-right"},zt={class:"p-2 border text-right"},Et={class:"p-2 border text-right"},It={class:"p-2 border text-right"},Gt={class:"p-2 border text-right"},Jt={class:"p-2 border text-right"},Kt={class:"text-primary font-semibold"},Wt=t("td",{class:"p-2 border text-primary"},null,-1),Xt={class:"p-2 border text-right text-primary"},Yt={class:"p-2 border text-right text-primary"},Zt={class:"p-2 border text-right text-primary"},$t={class:"p-2 border text-right text-primary"},te={class:"p-2 border text-right text-primary"},ee={class:"p-2 border text-right text-primary"},se={key:1,class:"bg-box-background shadow rounded my-4"},re={class:"border-b border-box-edge"},oe={class:"table ns-table w-full"},le={class:"text-primary"},ae={class:"border p-2 text-left"},ce={class:"border p-2 text-left"},de={width:"100",class:"border p-2"},ie={width:"150",class:"border p-2"},ne={width:"150",class:"border p-2"},_e={width:"150",class:"border p-2"},ue={width:"150",class:"border p-2"},he={width:"150",class:"border p-2"},pe={class:"text-primary"},be={class:"p-2 border"},me={class:"p-2 border"},ye={class:"p-2 border text-right"},xe={class:"p-2 border text-right"},fe={class:"p-2 border text-right"},ge={class:"p-2 border text-right"},we={class:"p-2 border text-right"},ve={class:"p-2 border text-right"},Ce={class:"bg-info-primary"},Te={colspan:"2",class:"p-2 border border-info-secondary"},ke={class:"p-2 border text-right border-info-secondary"},De={class:"p-2 border text-right border-info-secondary"},Ue={class:"p-2 border text-right border-info-secondary"},Pe={class:"p-2 border text-right border-info-secondary"},Fe={class:"p-2 border text-right border-info-secondary"},Le={class:"p-2 border text-right border-info-secondary"},Se={class:"text-primary font-semibold"},Re=t("td",{colspan:"2",class:"p-2 border text-primary"},null,-1),Be={class:"p-2 border text-right text-primary"},Ae={class:"p-2 border text-right text-primary"},Ne={class:"p-2 border text-right text-primary"},je={class:"p-2 border text-right text-primary"},qe={class:"p-2 border text-right text-primary"},Me={class:"p-2 border text-right text-primary"},Ve={key:2,class:"bg-box-background shadow rounded my-4"},He={class:"border-b border-box-edge"},Qe={class:"table ns-table w-full"},Oe={class:"text-primary"},ze={class:"border p-2 text-left"},Ee={width:"100",class:"border p-2"},Ie={width:"150",class:"border p-2"},Ge={width:"150",class:"border p-2"},Je={width:"150",class:"border p-2"},Ke={width:"150",class:"border p-2"},We={class:"text-primary"},Xe={class:"p-2 border text-left border-info-primary"},Ye={class:"p-2 border text-right border-info-primary"},Ze={class:"p-2 border text-right border-info-primary"},$e={class:"p-2 border text-right border-info-primary"},ts={class:"p-2 border text-right border-info-primary"},es={class:"p-2 border text-right border-info-primary"},ss={class:"text-primary font-semibold"},rs=t("td",{class:"p-2 border text-primary"},null,-1),os={class:"p-2 border text-right text-primary"},ls={class:"p-2 border text-right text-primary"},as={class:"p-2 border text-right text-primary"},cs={class:"p-2 border text-right text-primary"},ds={class:"p-2 border text-right text-primary"};function is(c,a,l,_,r,e){const g=P("ns-date-time-picker");return i(),n("div",S,[t("div",R,[t("div",B,[v(g,{field:r.startDateField},null,8,["field"])]),t("div",A,[v(g,{field:r.endDateField},null,8,["field"])]),t("div",N,[t("button",{onClick:a[0]||(a[0]=o=>e.loadReport()),class:"rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2"},[t("i",{class:F([r.isLoading?"animate-spin":"","las la-sync-alt text-xl"])},null,2),t("span",j,s(e.__("Load")),1)])])]),t("div",q,[t("div",M,[t("button",{onClick:a[1]||(a[1]=o=>e.printSaleReport()),class:"rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2"},[V,t("span",H,s(e.__("Print")),1)])]),t("div",Q,[t("button",{onClick:a[2]||(a[2]=o=>e.openSettings()),class:"rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2"},[O,t("span",z,s(e.__("By Type"))+" : "+s(e.getType(r.reportType.value)),1)])]),t("div",E,[t("button",{onClick:a[3]||(a[3]=o=>e.openUserFiltering()),class:"rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2"},[I,t("span",G,s(e.__("By User"))+" : "+s(r.selectedUser||e.__("All Users")),1)])]),t("div",J,[t("button",{onClick:a[4]||(a[4]=o=>e.openCategoryFiltering()),class:"rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2"},[K,t("span",W,s(e.__("By Category"))+" : "+s(r.selectedCategory||e.__("All Category")),1)])])]),t("div",X,[t("div",Y,[t("div",Z,[t("div",$,[t("ul",null,[t("li",{class:"pb-1 border-b border-dashed",innerHTML:e.__("Range : {date1} — {date2}").replace("{date1}",r.startDateField.value).replace("{date2}",r.endDateField.value)},null,8,tt),t("li",et,s(e.__("Document : Sale Report")),1),t("li",st,s(e.__("By : {user}").replace("{user}",r.ns.user.username)),1)])]),t("div",null,[t("img",{class:"w-24",src:l.storeLogo,alt:l.storeName},null,8,rt)])])]),t("div",null,[t("div",ot,[t("div",lt,[t("div",at,[t("div",ct,[t("table",dt,[t("tbody",it,[t("tr",nt,[t("td",_t,s(e.__("Sub Total")),1),t("td",ut,s(e.nsCurrency(r.summary.subtotal)),1)]),t("tr",ht,[t("td",pt,s(e.__("Sales Discounts")),1),t("td",bt,s(e.nsCurrency(r.summary.sales_discounts)),1)]),t("tr",mt,[t("td",yt,s(e.__("Sales Taxes")),1),t("td",xt,s(e.nsCurrency(r.summary.sales_taxes)),1)]),r.summary.product_taxes>0?(i(),n("tr",ft,[t("td",gt,s(e.__("Product Taxes")),1),t("td",wt,s(e.nsCurrency(r.summary.product_taxes)),1)])):p("",!0),t("tr",vt,[t("td",Ct,s(e.__("Shipping")),1),t("td",Tt,s(e.nsCurrency(r.summary.shipping)),1)]),t("tr",kt,[t("td",Dt,s(e.__("Total")),1),t("td",Ut,s(e.nsCurrency(r.summary.total)),1)])])])])])]),Pt])]),r.reportType.value==="products_report"?(i(),n("div",Ft,[t("div",Lt,[t("table",St,[t("thead",Rt,[t("tr",null,[t("th",Bt,s(e.__("Products")),1),t("th",At,s(e.__("Quantity")),1),t("th",Nt,s(e.__("Discounts")),1),t("th",jt,s(e.__("Cost")),1),t("th",qt,s(e.__("Taxes")),1),t("th",Mt,s(e.__("Total")),1),t("th",Vt,s(e.__("Profit")),1)])]),t("tbody",Ht,[(i(!0),n(b,null,m(r.result,o=>(i(),n("tr",{key:o.id},[t("td",Qt,s(o.name),1),t("td",Ot,s(o.quantity),1),t("td",zt,s(e.nsCurrency(o.discount)),1),t("td",Et,s(e.nsCurrency(o.total_purchase_price)),1),t("td",It,s(e.nsCurrency(o.tax_value)),1),t("td",Gt,s(e.nsCurrency(o.total_price)),1),t("td",Jt,s(e.nsCurrency(o.total_price-o.total_purchase_price)),1)]))),128))]),t("tfoot",Kt,[t("tr",null,[Wt,t("td",Xt,s(e.computeTotal(r.result,"quantity")),1),t("td",Yt,s(e.nsCurrency(e.computeTotal(r.result,"discount"))),1),t("td",Zt,s(e.nsCurrency(e.computeTotal(r.result,"total_purchase_price"))),1),t("td",$t,s(e.nsCurrency(e.computeTotal(r.result,"tax_value"))),1),t("td",te,s(e.nsCurrency(e.computeTotal(r.result,"total_price"))),1),t("td",ee,s(e.nsCurrency(e.computeTotal(r.result,"total_price")-e.computeTotal(r.result,"total_purchase_price"))),1)])])])])])):p("",!0),r.reportType.value==="categories_report"?(i(),n("div",se,[t("div",re,[t("table",oe,[t("thead",le,[t("tr",null,[t("th",ae,s(e.__("Category")),1),t("th",ce,s(e.__("Product")),1),t("th",de,s(e.__("Quantity")),1),t("th",ie,s(e.__("Discounts")),1),t("th",ne,s(e.__("Taxes")),1),t("th",_e,s(e.__("Total")),1),t("th",ue,s(e.__("Purchase Price")),1),t("th",he,s(e.__("Profit")),1)])]),t("tbody",pe,[(i(!0),n(b,null,m(r.result,(o,y)=>(i(),n(b,{key:y},[o.products.length>0?(i(!0),n(b,{key:0},m(o.products,u=>(i(),n("tr",{key:parseInt(o.id+""+u.id)},[t("td",be,s(o.name),1),t("td",me,s(u.name),1),t("td",ye,s(u.quantity),1),t("td",xe,s(e.nsCurrency(u.discount)),1),t("td",fe,s(e.nsCurrency(u.tax_value)),1),t("td",ge,s(e.nsCurrency(u.total_price)),1),t("td",we,s(e.nsCurrency(u.total_purchase_price)),1),t("td",ve,s(e.nsCurrency(u.total_price-(u.total_purchase_price+u.tax_value+u.discount))),1)]))),128)):p("",!0),t("tr",Ce,[t("td",Te,s(o.name),1),t("td",ke,s(e.computeTotal(o.products,"quantity")),1),t("td",De,s(e.nsCurrency(e.computeTotal(o.products,"discount"))),1),t("td",Ue,s(e.nsCurrency(e.computeTotal(o.products,"tax_value"))),1),t("td",Pe,s(e.nsCurrency(e.computeTotal(o.products,"total_price"))),1),t("td",Fe,s(e.nsCurrency(e.computeTotal(o.products,"total_purchase_price"))),1),t("td",Le,s(e.nsCurrency(e.computeTotal(o.products,"total_price")-(e.computeTotal(o.products,"total_purchase_price")+e.computeTotal(o.products,"tax_value")+e.computeTotal(o.products,"discount")))),1)])],64))),128))]),t("tfoot",Se,[t("tr",null,[Re,t("td",Be,s(e.computeTotal(r.result,"total_sold_items")),1),t("td",Ae,s(e.nsCurrency(e.computeTotal(r.result,"total_discount"))),1),t("td",Ne,s(e.nsCurrency(e.computeTotal(r.result,"total_tax_value"))),1),t("td",je,s(e.nsCurrency(e.computeTotal(r.result,"total_price"))),1),t("td",qe,s(e.nsCurrency(e.computeTotal(r.result,"total_purchase_price"))),1),t("td",Me,s(e.nsCurrency(e.computeTotal(r.result,"total_price")-(e.computeTotal(r.result,"total_purchase_price")+e.computeTotal(r.result,"total_discount")+e.computeTotal(r.result,"total_tax_value")))),1)])])])])])):p("",!0),r.reportType.value==="categories_summary"?(i(),n("div",Ve,[t("div",He,[t("table",Qe,[t("thead",Oe,[t("tr",null,[t("th",ze,s(e.__("Category")),1),t("th",Ee,s(e.__("Quantity")),1),t("th",Ie,s(e.__("Discounts")),1),t("th",Ge,s(e.__("Cost")),1),t("th",Je,s(e.__("Taxes")),1),t("th",Ke,s(e.__("Total")),1)])]),t("tbody",We,[(i(!0),n(b,null,m(r.result,(o,y)=>(i(),n("tr",{key:y,class:""},[t("td",Xe,s(o.name),1),t("td",Ye,s(e.computeTotal(o.products,"quantity")),1),t("td",Ze,s(e.nsCurrency(e.computeTotal(o.products,"discount"))),1),t("td",$e,s(e.nsCurrency(e.computeTotal(o.products,"total_purchase_price"))),1),t("td",ts,s(e.nsCurrency(e.computeTotal(o.products,"tax_value"))),1),t("td",es,s(e.nsCurrency(e.computeTotal(o.products,"total_price"))),1)]))),128))]),t("tfoot",ss,[t("tr",null,[rs,t("td",os,s(e.computeTotal(r.result,"total_sold_items")),1),t("td",ls,s(e.nsCurrency(e.computeTotal(r.result,"total_discount"))),1),t("td",as,s(e.nsCurrency(e.computeTotal(r.result,"total_purchase_price"))),1),t("td",cs,s(e.nsCurrency(e.computeTotal(r.result,"total_tax_value"))),1),t("td",ds,s(e.nsCurrency(e.computeTotal(r.result,"total_price"))),1)])])])])])):p("",!0)])])}const ws=U(L,[["render",is]]);export{ws as default};

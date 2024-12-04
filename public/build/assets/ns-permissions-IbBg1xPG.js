import{H as _,d as g,b as m,I as v,v as k}from"./bootstrap-BsWQM_6D.js";import{_ as f}from"./currency-Dtag6qPd.js";import{_ as w}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{r as P,o,c as l,a as i,B as j,t as p,e as u,F as h,b,n as C,f as y}from"./runtime-core.esm-bundler-VrNrzzXC.js";import"./chart-CAYz1qzV.js";const T={name:"ns-permissions",filters:[_],data(){return{permissions:[],toggled:!1,roles:[],searchText:""}},computed:{filteredPermissions(){return this.searchText.length!==0?this.permissions.filter(s=>{const e=new RegExp(this.searchText,"i");return e.test(s.name)||e.test(s.namespace)}):this.permissions}},mounted(){this.loadPermissionsAndRoles(),nsHotPress.create("ns-permissions").whenPressed("shift+/",s=>{this.searchText="",setTimeout(()=>{this.$refs.search.focus()},5)}).whenPressed("/",s=>{this.searchText="",setTimeout(()=>{this.$refs.search.focus()},5)})},methods:{__:f,copyPermisson(s){navigator.clipboard.writeText(s).then(function(){g.success(f("Copied to clipboard"),null,{duration:3e3}).subscribe()},function(e){console.error("Could not copy text: ",e)})},async selectAllPermissions(s){const e=new Object;e[s.namespace]=new Object;let r=!1;if(s.locked&&(r=await new Promise((a,t)=>{Popup.show(nsConfirmPopup,{title:f("Confirm Your Action"),message:f("Would you like to bulk edit a system role ?"),onAction:c=>a(!!c)})})),!s.locked||s.locked&&r){const a=this.filterObjectByKeys(s.fields,this.filteredPermissions.map(t=>t.namespace));for(let t in a)s.fields[t].value=s.field.value,e[s.namespace][t]=s.field.value;this.arrayToObject(this.filteredPermissions,"namespace",t=>e[s.namespace][t.namespace]),m.put("/api/users/roles",e).subscribe(t=>{g.success(t.message,null,{duration:3e3}).subscribe()})}else s.field.value=!s.field.value},filterObjectByKeys(s,e){return Object.fromEntries(Object.entries(s).filter(([r])=>e.includes(r)))},arrayToObject(s,e,r){return Object.assign({},...s.map(a=>({[a[e]]:r(a)})))},submitPermissions(s,e){const r=new Object;r[s.namespace]=new Object,r[s.namespace][e.name]=e.value,m.put("/api/users/roles",r).subscribe(a=>{g.success(a.message,null,{duration:3e3}).subscribe()})},loadPermissionsAndRoles(){return v([m.get("/api/users/roles"),m.get("/api/users/permissions")]).subscribe(s=>{this.permissions=s[1],this.roles=s[0].map(e=>(e.fields={},e.field={type:"checkbox",name:e.namespace,value:!1},this.permissions.forEach(r=>{e.fields[r.namespace]={type:"checkbox",value:e.permissions.filter(a=>a.namespace===r.namespace).length>0,name:r.namespace,label:null}}),e))})}}},O={id:"permission-wrapper"},B={class:"my-2"},A=["placeholder"],E={class:"rounded shadow ns-box flex"},R={id:"permissions",class:"w- bg-gray-800 flex-shrink-0"},V={class:"h-24 py-4 px-2 border-b border-gray-700 text-gray-100 flex justify-between items-center"},H={key:0},N=["onClick","title"],D={key:0},F={key:1},K={class:"flex flex-auto overflow-hidden"},S={class:"overflow-y-auto"},z={class:"text-gray-700 flex"},I={class:"mx-1"},J={class:"mx-1"};function L(s,e,r,a,t,c){const x=P("ns-checkbox");return o(),l("div",O,[i("div",B,[j(i("input",{ref:"search","onUpdate:modelValue":e[0]||(e[0]=n=>t.searchText=n),type:"text",placeholder:c.__('Press "/" to search permissions'),class:"border-2 p-2 w-full outline-none bg-input-background border-input-edge text-primary"},null,8,A),[[k,t.searchText]])]),i("div",E,[i("div",R,[i("div",V,[t.toggled?u("",!0):(o(),l("span",H,p(c.__("Permissions")),1)),i("div",null,[t.toggled?u("",!0):(o(),l("button",{key:0,onClick:e[1]||(e[1]=n=>t.toggled=!t.toggled),class:"rounded-full bg-white text-gray-700 h-6 w-6 flex items-center justify-center"},e[3]||(e[3]=[i("i",{class:"las la-expand"},null,-1)]))),t.toggled?(o(),l("button",{key:1,onClick:e[2]||(e[2]=n=>t.toggled=!t.toggled),class:"rounded-full bg-white text-gray-700 h-6 w-6 flex items-center justify-center"},e[4]||(e[4]=[i("i",{class:"las la-compress"},null,-1)]))):u("",!0)])]),(o(!0),l(h,null,b(c.filteredPermissions,n=>(o(),l("div",{key:n.id,class:C([t.toggled?"w-24":"w-54","p-3 border-b border-gray-700 text-gray-100"])},[i("a",{onClick:d=>c.copyPermisson(n.namespace),href:"javascript:void(0)",title:n.namespace},[t.toggled?u("",!0):(o(),l("span",D,p(n.name),1)),t.toggled?(o(),l("span",F,p(n.name),1)):u("",!0)],8,N)],2))),128))]),i("div",K,[i("div",S,[i("div",z,[(o(!0),l(h,null,b(t.roles,n=>(o(),l("div",{key:n.id,class:"h-24 py-4 px-2 w-56 items-center border-b justify-center flex role flex-shrink-0 border-r border-table-th-edge"},[i("p",I,[i("span",null,p(n.name),1)]),i("span",J,[y(x,{onChange:d=>c.selectAllPermissions(n),field:n.field},null,8,["onChange","field"])])]))),128))]),(o(!0),l(h,null,b(c.filteredPermissions,n=>(o(),l("div",{key:n.id,class:"permission flex"},[(o(!0),l(h,null,b(t.roles,d=>(o(),l("div",{key:d.id,class:"border-b border-table-th-edge w-56 flex-shrink-0 p-2 flex items-center justify-center border-r"},[y(x,{onChange:M=>c.submitPermissions(d,d.fields[n.namespace]),field:d.fields[n.namespace]},null,8,["onChange","field"])]))),128))]))),128))])])])])}const Q=w(T,[["render",L]]);export{Q as default};
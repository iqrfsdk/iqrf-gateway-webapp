!function(e){function n(n){for(var r,u,c=n[0],s=n[1],l=n[2],d=0,f=[];d<c.length;d++)u=c[d],Object.prototype.hasOwnProperty.call(o,u)&&o[u]&&f.push(o[u][0]),o[u]=0;for(r in s)Object.prototype.hasOwnProperty.call(s,r)&&(e[r]=s[r]);for(a&&a(n);f.length;)f.shift()();return i.push.apply(i,l||[]),t()}function t(){for(var e,n=0;n<i.length;n++){for(var t=i[n],r=!0,c=1;c<t.length;c++){var s=t[c];0!==o[s]&&(r=!1)}r&&(i.splice(n--,1),e=u(u.s=t[0]))}return e}var r={},o={1:0},i=[];function u(n){if(r[n])return r[n].exports;var t=r[n]={i:n,l:!1,exports:{}};return e[n].call(t.exports,t,t.exports,u),t.l=!0,t.exports}u.m=e,u.c=r,u.d=function(e,n,t){u.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:t})},u.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},u.t=function(e,n){if(1&n&&(e=u(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(u.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var r in e)u.d(t,r,function(n){return e[n]}.bind(null,r));return t},u.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return u.d(n,"a",n),n},u.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},u.p="/dist/";var c=window.webpackJsonp=window.webpackJsonp||[],s=c.push.bind(c);c.push=n,c=c.slice();for(var l=0;l<c.length;l++)n(c[l]);var a=s;i.push([125,0,4]),t()}({125:function(e,n,t){"use strict";t.r(n),function(e){t(41),t(127),t(128);var n=t(38),r=t.n(n),o=t(5);t(147);r.a.initOnLoad(),e((function(){e.nette.init()})),e.nette.ext("spinner",{start:function(){o.a.commit("spinner/SHOW")},complete:function(){o.a.commit("spinner/HIDE")}})}.call(this,t(41))},147:function(e,n,t){"use strict";var r=t(65),o=t.n(r);function i(){var e,n,t=document.getElementById("frm-configSchedulerForm-cron"),r=document.getElementById("frm-configSchedulerForm-timeSpec-cronTime").value,i=r.split(" ").length;if(1===i)return e=r,(n=new Map).set("@reboot",""),n.set("@yearly","0 0 0 1 1 * *"),n.set("@annually","0 0 0 1 1 * *"),n.set("@monthly","0 0 0 1 * * *"),n.set("@weekly","0 0 0 * * 0 *"),n.set("@daily","0 0 0 * * * *"),n.set("@hourly","0 0 * * * * *"),n.set("@minutely","0 * * * * * *"),void 0===(r=n.get(e))?void(null!==t&&(t.style.visibility="hidden")):u(o.a.toString(r));if(i>4&&i<8)try{u(o.a.toString(r))}catch(e){null!==t&&(t.style.visibility="hidden"),console.error(e)}else null!==t&&(t.style.visibility="hidden")}function u(e){var n=document.getElementById("frm-configSchedulerForm-cron");if(null===n){var t=document.getElementById("frm-configSchedulerForm-timeSpec-cronTime"),r=document.createElement("span");r.id="frm-configSchedulerForm-cron",r.innerText=e,r.className="label label-info",t.insertAdjacentHTML("beforebegin",r.outerHTML)}else n.innerText=e,n.style.visibility="visible"}function c(e){var n=document.getElementById("frm-configSchedulerForm-timeSpec-cronTime"),t=document.getElementById("frm-configSchedulerForm-timeSpec-exactTime"),r=document.getElementById("frm-configSchedulerForm-timeSpec-periodic"),o=document.getElementById("frm-configSchedulerForm-timeSpec-period"),i=document.getElementById("frm-configSchedulerForm-timeSpec-startTime"),u=e.currentTarget,c=u.checked;null!==n&&(n.disabled=c),null!==t&&u===r&&(t.disabled=c),null!==r&&u===t&&(r.disabled=c),null!==o&&(o.disabled=u!==r||!c),null!==i&&(i.disabled=u!==t||!c)}var s=document.getElementById("frm-configSchedulerForm-timeSpec-cronTime"),l=document.getElementById("frm-configSchedulerForm-timeSpec-exactTime"),a=document.getElementById("frm-configSchedulerForm-timeSpec-periodic");null!==l&&l.addEventListener("change",c),null!==a&&a.addEventListener("change",c),null!==s&&(i(),s.addEventListener("keyup",(function(){i()})))},5:function(e,n,t){"use strict";var r=t(9),o=t(27),i=t(63),u=t(6),c=t.n(u),s=t(8),l=new(function(){function e(){}return e.prototype.fetchAll=function(){return c.a.get("features",{headers:Object(s.a)()})},e}()),a={namespaced:!0,state:{features:{}},actions:{fetch:function(e){var n=e.commit;return l.fetchAll().then((function(e){n("SET",e.data)}))}},getters:{isEnabled:function(e){return function(n){try{return e.features[n].enabled}catch(e){return}}},configuration:function(e){return function(n){try{return e.features[n]}catch(e){return}}}},mutations:{SET:function(e,n){e.features=n}}},d={namespaced:!0,state:{show:"responsive",minimize:!1},mutations:{toggleSidebarDesktop:function(e){var n=[!0,"responsive"].includes(e.show);e.show=!n&&"responsive"},toggleSidebarMobile:function(e){var n=[!1,"responsive"].includes(e.show);e.show=!!n||"responsive"},set:function(e,n){var t=n[0],r=n[1];e[t]=r}}},f={namespaced:!0,state:{enabled:null,text:null},getters:{isEnabled:function(e){return e.enabled},text:function(e){return e.text}},mutations:{HIDE:function(e){e.enabled=!1,e.text=null},SHOW:function(e,n){void 0===n&&(n=null),e.enabled=!0,e.text=n},UPDATE_TEXT:function(e,n){e.text=n}}},m=new(function(){function e(){}return e.prototype.apiLogin=function(e,n){var t={username:e,password:n};return c.a.post("user/signIn",t)},e.prototype.netteLogin=function(e,n){var t=new URLSearchParams;return t.append("username",e),t.append("password",n),t.append("remember","on"),t.append("send","Sign+in"),t.append("_do","signInForm-submit"),c.a.post("//"+window.location.host+"/sign/in",t)},e.prototype.login=function(e,n){return Promise.all([this.apiLogin(e,n),this.netteLogin(e,n)])},e.prototype.logout=function(){return c.a.get("//"+window.location.host+"/sign/out")},e}()),p={namespaced:!0,state:{user:null},actions:{signIn:function(e,n){var t=e.commit;return m.login(n.username,n.password).then((function(e){var n=e[0];return t("SIGN_IN",n.data),e})).catch((function(e){return console.error(e),Promise.reject(e)}))},signOut:function(e){var n=e.commit;return m.logout().then((function(){n("SIGN_OUT")}))}},getters:{isLoggedIn:function(e){return null!==e.user},getId:function(e){return null===e.user?null:e.user.id},getName:function(e){return null===e.user?null:e.user.username},getRole:function(e){return null===e.user?null:e.user.role},getToken:function(e){return null===e.user?null:e.user.token}},mutations:{SIGN_IN:function(e,n){e.user=n},SIGN_OUT:function(e){e.user=null}}},g=t(271),h={state:{socket:{isConnected:!1,reconnectError:!1},requests:{},responses:{}},actions:{sendRequest:function(e,n){void 0===n.data.msgId&&(n.data.msgId=Object(g.a)()),r.default.prototype.$socket.sendObj(n),e.commit("SOCKET_ONSEND",n)}},getters:{isSocketConnected:function(e){return e.socket.isConnected}},mutations:{SOCKET_ONOPEN:function(e,n){r.default.prototype.$socket=n.currentTarget,e.socket.isConnected=!0},SOCKET_ONCLOSE:function(e){e.socket.isConnected=!1},SOCKET_ONERROR:function(e,n){console.error(e,n)},SOCKET_ONMESSAGE:function(e,n){e.responses[n.data.msgId]=n},SOCKET_ONSEND:function(e,n){e.requests[n.data.msgId]=n},SOCKET_RECONNECT:function(e,n){console.info(e,n)},SOCKET_RECONNECT_ERROR:function(e){e.socket.reconnectError=!0}}};r.default.use(o.a);var S=new o.a.Store({modules:{features:a,sidebar:d,spinner:f,user:p,webSocketClient:h},plugins:[Object(i.a)()]});n.a=S},8:function(e,n,t){"use strict";t.d(n,"a",(function(){return o}));var r=t(5);function o(){var e=r.a.getters["user/getToken"];return null===e?{}:{Authorization:"Bearer "+e}}}});
//# sourceMappingURL=app.bundle.js.map
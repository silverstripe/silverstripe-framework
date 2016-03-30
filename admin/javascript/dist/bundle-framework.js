!function e(t,r,n){function o(u,i){if(!r[u]){if(!t[u]){var c="function"==typeof require&&require;if(!i&&c)return c(u,!0);if(a)return a(u,!0);var l=new Error("Cannot find module '"+u+"'");throw l.code="MODULE_NOT_FOUND",l}var d=r[u]={exports:{}};t[u][0].call(d.exports,function(e){var r=t[u][1][e];return o(r?r:e)},d,d.exports,e,t,r,n)}return r[u].exports}for(var a="function"==typeof require&&require,u=0;u<n.length;u++)o(n[u]);return o}({1:[function(e,t,r){"use strict";function n(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&(t[r]=e[r]);return t["default"]=e,t}function o(e){return e&&e.__esModule?e:{"default":e}}function a(){E["default"].add("config",v["default"]),E["default"].add("schemas",h["default"]),E["default"].add("records",C["default"]);var e={},t=(0,c.combineReducers)(E["default"].getAll()),r=(0,c.applyMiddleware)(d["default"],(0,s["default"])())(c.createStore);window.store=r(t,e),g.setConfig(window.ss.config)(window.store.dispatch)}var u=e("jQuery"),i=o(u),c=e("redux"),l=e("redux-thunk"),d=o(l),f=e("redux-logger"),s=o(f),p=e("reducer-register"),E=o(p),y=e("state/config/actions"),g=n(y),_=e("state/config/reducer"),v=o(_),m=e("state/schema/reducer"),h=o(m),b=e("state/records/reducer"),C=o(b),R=e("sections/campaign-admin/index");o(R);(0,i["default"])("body").entwine({onadd:function(){a()}})},{jQuery:"jQuery","reducer-register":"reducer-register",redux:"redux","redux-logger":12,"redux-thunk":"redux-thunk","sections/campaign-admin/index":4,"state/config/actions":6,"state/config/reducer":7,"state/records/reducer":9,"state/schema/reducer":11}],2:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function u(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(r,"__esModule",{value:!0});var i=function(){function e(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}return function(t,r,n){return r&&e(t.prototype,r),n&&e(t,n),t}}(),c=e("react"),l=n(c),d=e("silverstripe-component"),f=n(d),s=function(e){function t(e){o(this,t);var r=a(this,Object.getPrototypeOf(t).call(this,e));return r.handleClick=r.handleClick.bind(r),r}return u(t,e),i(t,[{key:"render",value:function(){return l["default"].createElement("button",{type:this.props.type,className:this.getButtonClasses(),onClick:this.handleClick},this.getLoadingIcon(),this.props.label)}},{key:"getButtonClasses",value:function(){var e="btn";return e+=" btn-"+this.props.style,"undefined"==typeof this.props.label&&(e+=" no-text"),"undefined"!=typeof this.props.icon&&(e+=" font-icon-"+this.props.icon),this.props.loading===!0&&(e+=" btn--loading"),this.props.disabled===!0&&(e+=" disabled"),e}},{key:"getLoadingIcon",value:function(){return this.props.loading?l["default"].createElement("div",{className:"btn__loading-icon"},l["default"].createElement("svg",{viewBox:"0 0 44 12"},l["default"].createElement("circle",{cx:"6",cy:"6",r:"6"}),l["default"].createElement("circle",{cx:"22",cy:"6",r:"6"}),l["default"].createElement("circle",{cx:"38",cy:"6",r:"6"}))):null}},{key:"handleClick",value:function(e){this.props.handleClick(e)}}]),t}(f["default"]);s.propTypes={handleClick:l["default"].PropTypes.func.isRequired,label:l["default"].PropTypes.string,type:l["default"].PropTypes.string,loading:l["default"].PropTypes.bool,icon:l["default"].PropTypes.string,disabled:l["default"].PropTypes.bool,style:l["default"].PropTypes.string},s.defaultProps={type:"button",style:"secondary"},r["default"]=s},{react:"react","silverstripe-component":"silverstripe-component"}],3:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function u(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function i(e,t){return{config:e.config.sections[t.sectionConfigKey]}}Object.defineProperty(r,"__esModule",{value:!0});var c=function(){function e(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}return function(t,r,n){return r&&e(t.prototype,r),n&&e(t,n),t}}(),l=e("react"),d=n(l),f=e("react-redux"),s=e("silverstripe-component"),p=n(s),E=e("components/form-action/index"),y=n(E),g=e("i18n"),_=n(g),v=e("components/north-header/index"),m=n(v),h=e("components/form-builder/index"),b=n(h),C=function(e){function t(e){o(this,t);var r=a(this,Object.getPrototypeOf(t).call(this,e));return r.addCampaign=r.addCampaign.bind(r),r}return u(t,e),c(t,[{key:"render",value:function(){var e=this.props.config.forms.editForm.schemaUrl;return d["default"].createElement("div",null,d["default"].createElement(m["default"],null),d["default"].createElement(y["default"],{label:_["default"]._t("Campaigns.ADDCAMPAIGN"),icon:"plus-circled",handleClick:this.addCampaign}),d["default"].createElement(b["default"],{schemaUrl:e}))}},{key:"addCampaign",value:function(){}}]),t}(p["default"]);C.propTypes={config:d["default"].PropTypes.shape({forms:d["default"].PropTypes.shape({editForm:d["default"].PropTypes.shape({schemaUrl:d["default"].PropTypes.string})})}),sectionConfigKey:d["default"].PropTypes.string.isRequired},r["default"]=(0,f.connect)(i)(C)},{"components/form-action/index":2,"components/form-builder/index":"components/form-builder/index","components/north-header/index":"components/north-header/index",i18n:"i18n",react:"react","react-redux":"react-redux","silverstripe-component":"silverstripe-component"}],4:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}var o=e("reducer-register"),a=(n(o),e("jQuery")),u=n(a),i=e("react"),c=n(i),l=e("react-dom"),d=n(l),f=e("react-redux"),s=e("./controller"),p=n(s);u["default"].entwine("ss",function(e){e(".cms-content.CampaignAdmin").entwine({onadd:function(){d["default"].render(c["default"].createElement(f.Provider,{store:window.store},c["default"].createElement(p["default"],{sectionConfigKey:"CampaignAdmin"})),this[0])},onremove:function(){d["default"].unmountComponentAtNode(this[0])}})})},{"./controller":3,jQuery:"jQuery",react:"react","react-dom":"react-dom","react-redux":"react-redux","reducer-register":"reducer-register"}],5:[function(e,t,r){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),r["default"]={SET_CONFIG:"SET_CONFIG"}},{}],6:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(e){return function(t,r){return t({type:u["default"].SET_CONFIG,payload:{config:e}})}}Object.defineProperty(r,"__esModule",{value:!0}),r.setConfig=o;var a=e("./action-types"),u=n(a)},{"./action-types":5}],7:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0],t=arguments[1];switch(t.type){case c["default"].SET_CONFIG:return(0,u["default"])(Object.assign({},e,t.payload.config));default:return e}}Object.defineProperty(r,"__esModule",{value:!0});var a=e("deep-freeze"),u=n(a),i=e("./action-types"),c=n(i);r["default"]=o},{"./action-types":5,"deep-freeze":"deep-freeze"}],8:[function(e,t,r){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),r["default"]={CREATE_RECORD:"CREATE_RECORD",UPDATE_RECORD:"UPDATE_RECORD",DELETE_RECORD:"DELETE_RECORD",FETCH_RECORDS_REQUEST:"FETCH_RECORDS_REQUEST",FETCH_RECORDS_FAILURE:"FETCH_RECORDS_FAILURE",FETCH_RECORDS_SUCCESS:"FETCH_RECORDS_SUCCESS",DELETE_RECORD_REQUEST:"DELETE_RECORD_REQUEST",DELETE_RECORD_FAILURE:"DELETE_RECORD_FAILURE",DELETE_RECORD_SUCCESS:"DELETE_RECORD_SUCCESS"}},{}],9:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(){var e=arguments.length<=0||void 0===arguments[0]?d:arguments[0],t=arguments[1],r=void 0,n=void 0;switch(t.type){case l["default"].CREATE_RECORD:return(0,i["default"])(Object.assign({},e,{}));case l["default"].UPDATE_RECORD:return(0,i["default"])(Object.assign({},e,{}));case l["default"].DELETE_RECORD:return(0,i["default"])(Object.assign({},e,{}));case l["default"].FETCH_RECORDS_REQUEST:return e;case l["default"].FETCH_RECORDS_FAILURE:return e;case l["default"].FETCH_RECORDS_SUCCESS:return n=t.payload.recordType,r=t.payload.data._embedded[n+"s"],(0,i["default"])(Object.assign({},e,o({},n,r)));case l["default"].DELETE_RECORD_REQUEST:return e;case l["default"].DELETE_RECORD_FAILURE:return e;case l["default"].DELETE_RECORD_SUCCESS:return n=t.payload.recordType,r=e[n].filter(function(e){return e.ID!=t.payload.id}),(0,i["default"])(Object.assign({},e,o({},n,r)));default:return e}}Object.defineProperty(r,"__esModule",{value:!0});var u=e("deep-freeze"),i=n(u),c=e("./action-types"),l=n(c),d={};r["default"]=a},{"./action-types":8,"deep-freeze":"deep-freeze"}],10:[function(e,t,r){"use strict";Object.defineProperty(r,"__esModule",{value:!0});var n={SET_SCHEMA:"SET_SCHEMA"};r["default"]=n},{}],11:[function(e,t,r){"use strict";function n(e){return e&&e.__esModule?e:{"default":e}}function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(){var e=arguments.length<=0||void 0===arguments[0]?d:arguments[0],t=arguments.length<=1||void 0===arguments[1]?null:arguments[1];switch(t.type){case l["default"].SET_SCHEMA:var r=t.payload.schema.schema_url;return(0,i["default"])(Object.assign({},e,o({},r,t.payload)));default:return e}}Object.defineProperty(r,"__esModule",{value:!0}),r["default"]=a;var u=e("deep-freeze"),i=n(u),c=e("./action-types"),l=n(c),d=(0,i["default"])({})},{"./action-types":10,"deep-freeze":"deep-freeze"}],12:[function(e,t,r){"use strict";function n(e){if(Array.isArray(e)){for(var t=0,r=Array(e.length);t<e.length;t++)r[t]=e[t];return r}return Array.from(e)}function o(e){return e&&"undefined"!=typeof Symbol&&e.constructor===Symbol?"symbol":typeof e}function a(e,t,r,a){switch("undefined"==typeof e?"undefined":o(e)){case"object":return"function"==typeof e[a]?e[a].apply(e,n(r)):e[a];case"function":return e(t);default:return e}}function u(){function e(){S.forEach(function(e,t){var r=e.started,o=e.startedTime,i=e.action,c=e.prevState,d=e.error,s=e.took,p=e.nextState,y=S[t+1];y&&(p=y.prevState,s=y.started-r);var _=b(i),v="function"==typeof f?f(function(){return p},i):f,m=l(o),h=T.title?"color: "+T.title(_)+";":null,C="action "+(g?m:"")+" "+_.type+" "+(E?"(in "+s.toFixed(2)+" ms)":"");try{v?T.title?u.groupCollapsed("%c "+C,h):u.groupCollapsed(C):T.title?u.group("%c "+C,h):u.group(C)}catch(R){u.log(C)}var O=a(n,_,[c],"prevState"),D=a(n,_,[_],"action"),w=a(n,_,[d,c],"error"),x=a(n,_,[p],"nextState");O&&(T.prevState?u[O]("%c prev state","color: "+T.prevState(c)+"; font-weight: bold",c):u[O]("prev state",c)),D&&(T.action?u[D]("%c action","color: "+T.action(_)+"; font-weight: bold",_):u[D]("action",_)),d&&w&&(T.error?u[w]("%c error","color: "+T.error(d,c)+"; font-weight: bold",d):u[w]("error",d)),x&&(T.nextState?u[x]("%c next state","color: "+T.nextState(p)+"; font-weight: bold",p):u[x]("next state",p));try{u.groupEnd()}catch(R){u.log("—— log end ——")}}),S.length=0}var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0],r=t.level,n=void 0===r?"log":r,o=t.logger,u=void 0===o?console:o,i=t.logErrors,c=void 0===i?!0:i,f=t.collapsed,s=t.predicate,p=t.duration,E=void 0===p?!1:p,y=t.timestamp,g=void 0===y?!0:y,_=t.transformer,v=t.stateTransformer,m=void 0===v?function(e){return e}:v,h=t.actionTransformer,b=void 0===h?function(e){return e}:h,C=t.errorTransformer,R=void 0===C?function(e){return e}:C,O=t.colors,T=void 0===O?{title:function(){return"#000000"},prevState:function(){return"#9E9E9E"},action:function(){return"#03A9F4"},nextState:function(){return"#4CAF50"},error:function(){return"#F20404"}}:O;if("undefined"==typeof u)return function(){return function(e){return function(t){return e(t)}}};_&&console.error("Option 'transformer' is deprecated, use stateTransformer instead");var S=[];return function(t){var r=t.getState;return function(t){return function(n){if("function"==typeof s&&!s(r,n))return t(n);var o={};S.push(o),o.started=d.now(),o.startedTime=new Date,o.prevState=m(r()),o.action=n;var a=void 0;if(c)try{a=t(n)}catch(u){o.error=R(u)}else a=t(n);if(o.took=d.now()-o.started,o.nextState=m(r()),e(),o.error)throw o.error;return a}}}}var i=function(e,t){return new Array(t+1).join(e)},c=function(e,t){return i("0",t-e.toString().length)+e},l=function(e){return"@ "+c(e.getHours(),2)+":"+c(e.getMinutes(),2)+":"+c(e.getSeconds(),2)+"."+c(e.getMilliseconds(),3)},d="undefined"!=typeof performance&&"function"==typeof performance.now?performance:Date;t.exports=u},{}]},{},[1]);
//# sourceMappingURL=bundle-framework.js.map

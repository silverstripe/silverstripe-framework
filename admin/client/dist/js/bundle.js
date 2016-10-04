webpackJsonp([5],[function(e,t,n){"use strict"
n(2),n(5),n(18),n(21),n(23),n(25),n(35),n(39),n(49),n(50),n(51),n(52),n(53),n(54),n(56),n(60),n(62),n(65),n(68),n(70),n(72),n(74),n(75),n(84),n(85),n(86),n(88),n(89),n(90),n(91),n(92),n(93),n(94),n(96),
n(97),n(98),n(99),n(100),n(101),n(102),n(105),n(107),n(108),n(109),n(110),n(114),n(115),n(116),n(117),n(118),n(115),n(110),n(121),n(122),n(124),n(125)},,function(e,t,n){(function(t){e.exports=t.SilverStripeComponent=n(3)

}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(1),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"componentDidMount",value:function n(){if("undefined"!=typeof this.props.cmsEvents){
this.cmsEvents=this.props.cmsEvents
for(var e in this.cmsEvents)({}).hasOwnProperty.call(this.cmsEvents,e)&&(0,c["default"])(document).on(e,this.cmsEvents[e].bind(this))}}},{key:"componentWillUnmount",value:function i(){for(var e in this.cmsEvents)({}).hasOwnProperty.call(this.cmsEvents,e)&&(0,
c["default"])(document).off(e)}},{key:"emitCmsEvent",value:function l(e,t){(0,c["default"])(document).trigger(e,t)}}]),t}(l.Component)
f.propTypes={cmsEvents:u["default"].PropTypes.object},t["default"]=f},,function(e,t,n){(function(t){e.exports=t.Backend=n(6)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t,n){return t in e?Object.defineProperty(e,t,{
value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function a(e){var t=null,n=null
if(!(e.status>=200&&e.status<300))throw n=new Error(e.statusText),n.response=e,n
return t=e}function s(e){var t=null
if(e instanceof FormData||"string"==typeof e)t=e
else{if(!e||"object"!==("undefined"==typeof e?"undefined":g(e)))throw new Error("Invalid body type")
t=JSON.stringify(e)}return t}function l(e,t){switch(e){case"application/x-www-form-urlencoded":return C["default"].stringify(t)
case"application/json":case"application/x-json":case"application/x-javascript":case"text/javascript":case"text/x-javascript":case"text/x-json":return JSON.stringify(t)
default:throw new Error("Can't encode format: "+e)}}function u(e,t){switch(e){case"application/x-www-form-urlencoded":return C["default"].parse(t)
case"application/json":case"application/x-json":case"application/x-javascript":case"text/javascript":case"text/x-javascript":case"text/x-json":return JSON.parse(t)
default:throw new Error("Can't decode format: "+e)}}function d(e,t){return""===t?e:e.match(/\?/)?e+"&"+t:e+"?"+t}function c(e){return e.text().then(function(t){return u(e.headers.get("Content-Type"),t)

})}function f(e,t){return Object.keys(t).reduce(function(n,i){var r=e[i]
return!r||r.remove!==!0&&r.querystring!==!0?m(n,o({},i,t[i])):n},{})}function p(e,t,n){var i=arguments.length<=3||void 0===arguments[3]?{setFromData:!1}:arguments[3],r=t,a=Object.keys(n).reduce(function(t,r){
var a=e[r],s=i.setFromData===!0&&!(a&&a.remove===!0),l=a&&a.querystring===!0&&a.remove!==!0
return s||l?m(t,o({},r,n[r])):t},{}),s=l("application/x-www-form-urlencoded",a)
return r=d(r,s),r=Object.keys(e).reduce(function(t,i){var r=e[i].urlReplacement
return r?t.replace(r,n[i]):t},r)}Object.defineProperty(t,"__esModule",{value:!0})
var h=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),m=Object.assign||function(e){
for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},g="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol?"symbol":typeof e

},v=n(7),y=i(v),b=n(9),w=i(b),_=n(12),C=i(_),T=n(16),E=i(T)
w["default"].polyfill()
var P=function(){function e(){r(this,e),this.fetch=y["default"]}return h(e,[{key:"createEndpointFetcher",value:function t(e){var t=this,n=m({method:"get",payloadFormat:"application/x-www-form-urlencoded",
responseFormat:"application/json",payloadSchema:{},defaultData:{}},e),i={json:"application/json",urlencoded:"application/x-www-form-urlencoded"}
return["payloadFormat","responseFormat"].forEach(function(e){i[n[e]]&&(n[e]=i[n[e]])}),function(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0],i=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],r=m({},i,{
Accept:n.responseFormat,"Content-Type":n.payloadFormat}),o=E["default"].recursive({},n.defaultData,e),a=p(n.payloadSchema,n.url,o,{setFromData:"get"===n.method.toLowerCase()}),s="get"!==n.method.toLowerCase()?l(n.payloadFormat,f(n.payloadSchema,o)):"",u="get"===n.method.toLowerCase()?[a,r]:[a,s,r]


return t[n.method.toLowerCase()].apply(t,u).then(c)}}},{key:"get",value:function n(e){var t=arguments.length<=1||void 0===arguments[1]?{}:arguments[1]
return this.fetch(e,{method:"get",credentials:"same-origin",headers:t}).then(a)}},{key:"post",value:function i(e){var t=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],n=arguments.length<=2||void 0===arguments[2]?{}:arguments[2],i={
"Content-Type":"application/x-www-form-urlencoded"}
return this.fetch(e,{method:"post",credentials:"same-origin",body:s(t),headers:m({},i,n)}).then(a)}},{key:"put",value:function o(e){var t=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],n=arguments.length<=2||void 0===arguments[2]?{}:arguments[2]


return this.fetch(e,{method:"put",credentials:"same-origin",body:s(t),headers:n}).then(a)}},{key:"delete",value:function u(e){var t=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],n=arguments.length<=2||void 0===arguments[2]?{}:arguments[2]


return this.fetch(e,{method:"delete",credentials:"same-origin",body:s(t),headers:n}).then(a)}}]),e}(),O=new P
t["default"]=O},function(e,t,n){n(8),e.exports=self.fetch.bind(self)},function(e,t){!function(e){"use strict"
function t(e){if("string"!=typeof e&&(e=String(e)),/[^a-z0-9\-#$%&'*+.\^_`|~]/i.test(e))throw new TypeError("Invalid character in header field name")
return e.toLowerCase()}function n(e){return"string"!=typeof e&&(e=String(e)),e}function i(e){this.map={},e instanceof i?e.forEach(function(e,t){this.append(t,e)},this):e&&Object.getOwnPropertyNames(e).forEach(function(t){
this.append(t,e[t])},this)}function r(e){return e.bodyUsed?Promise.reject(new TypeError("Already read")):void(e.bodyUsed=!0)}function o(e){return new Promise(function(t,n){e.onload=function(){t(e.result)

},e.onerror=function(){n(e.error)}})}function a(e){var t=new FileReader
return t.readAsArrayBuffer(e),o(t)}function s(e){var t=new FileReader
return t.readAsText(e),o(t)}function l(){return this.bodyUsed=!1,this._initBody=function(e){if(this._bodyInit=e,"string"==typeof e)this._bodyText=e
else if(h.blob&&Blob.prototype.isPrototypeOf(e))this._bodyBlob=e
else if(h.formData&&FormData.prototype.isPrototypeOf(e))this._bodyFormData=e
else if(e){if(!h.arrayBuffer||!ArrayBuffer.prototype.isPrototypeOf(e))throw new Error("unsupported BodyInit type")}else this._bodyText=""
this.headers.get("content-type")||("string"==typeof e?this.headers.set("content-type","text/plain;charset=UTF-8"):this._bodyBlob&&this._bodyBlob.type&&this.headers.set("content-type",this._bodyBlob.type))

},h.blob?(this.blob=function(){var e=r(this)
if(e)return e
if(this._bodyBlob)return Promise.resolve(this._bodyBlob)
if(this._bodyFormData)throw new Error("could not read FormData body as blob")
return Promise.resolve(new Blob([this._bodyText]))},this.arrayBuffer=function(){return this.blob().then(a)},this.text=function(){var e=r(this)
if(e)return e
if(this._bodyBlob)return s(this._bodyBlob)
if(this._bodyFormData)throw new Error("could not read FormData body as text")
return Promise.resolve(this._bodyText)}):this.text=function(){var e=r(this)
return e?e:Promise.resolve(this._bodyText)},h.formData&&(this.formData=function(){return this.text().then(c)}),this.json=function(){return this.text().then(JSON.parse)},this}function u(e){var t=e.toUpperCase()


return m.indexOf(t)>-1?t:e}function d(e,t){t=t||{}
var n=t.body
if(d.prototype.isPrototypeOf(e)){if(e.bodyUsed)throw new TypeError("Already read")
this.url=e.url,this.credentials=e.credentials,t.headers||(this.headers=new i(e.headers)),this.method=e.method,this.mode=e.mode,n||(n=e._bodyInit,e.bodyUsed=!0)}else this.url=e
if(this.credentials=t.credentials||this.credentials||"omit",!t.headers&&this.headers||(this.headers=new i(t.headers)),this.method=u(t.method||this.method||"GET"),this.mode=t.mode||this.mode||null,this.referrer=null,
("GET"===this.method||"HEAD"===this.method)&&n)throw new TypeError("Body not allowed for GET or HEAD requests")
this._initBody(n)}function c(e){var t=new FormData
return e.trim().split("&").forEach(function(e){if(e){var n=e.split("="),i=n.shift().replace(/\+/g," "),r=n.join("=").replace(/\+/g," ")
t.append(decodeURIComponent(i),decodeURIComponent(r))}}),t}function f(e){var t=new i,n=e.getAllResponseHeaders().trim().split("\n")
return n.forEach(function(e){var n=e.trim().split(":"),i=n.shift().trim(),r=n.join(":").trim()
t.append(i,r)}),t}function p(e,t){t||(t={}),this.type="default",this.status=t.status,this.ok=this.status>=200&&this.status<300,this.statusText=t.statusText,this.headers=t.headers instanceof i?t.headers:new i(t.headers),
this.url=t.url||"",this._initBody(e)}if(!e.fetch){i.prototype.append=function(e,i){e=t(e),i=n(i)
var r=this.map[e]
r||(r=[],this.map[e]=r),r.push(i)},i.prototype["delete"]=function(e){delete this.map[t(e)]},i.prototype.get=function(e){var n=this.map[t(e)]
return n?n[0]:null},i.prototype.getAll=function(e){return this.map[t(e)]||[]},i.prototype.has=function(e){return this.map.hasOwnProperty(t(e))},i.prototype.set=function(e,i){this.map[t(e)]=[n(i)]},i.prototype.forEach=function(e,t){
Object.getOwnPropertyNames(this.map).forEach(function(n){this.map[n].forEach(function(i){e.call(t,i,n,this)},this)},this)}
var h={blob:"FileReader"in e&&"Blob"in e&&function(){try{return new Blob,!0}catch(e){return!1}}(),formData:"FormData"in e,arrayBuffer:"ArrayBuffer"in e},m=["DELETE","GET","HEAD","OPTIONS","POST","PUT"]


d.prototype.clone=function(){return new d(this)},l.call(d.prototype),l.call(p.prototype),p.prototype.clone=function(){return new p(this._bodyInit,{status:this.status,statusText:this.statusText,headers:new i(this.headers),
url:this.url})},p.error=function(){var e=new p(null,{status:0,statusText:""})
return e.type="error",e}
var g=[301,302,303,307,308]
p.redirect=function(e,t){if(g.indexOf(t)===-1)throw new RangeError("Invalid status code")
return new p(null,{status:t,headers:{location:e}})},e.Headers=i,e.Request=d,e.Response=p,e.fetch=function(e,t){return new Promise(function(n,i){function r(){return"responseURL"in a?a.responseURL:/^X-Request-URL:/m.test(a.getAllResponseHeaders())?a.getResponseHeader("X-Request-URL"):void 0

}var o
o=d.prototype.isPrototypeOf(e)&&!t?e:new d(e,t)
var a=new XMLHttpRequest
a.onload=function(){var e=1223===a.status?204:a.status
if(e<100||e>599)return void i(new TypeError("Network request failed"))
var t={status:e,statusText:a.statusText,headers:f(a),url:r()},o="response"in a?a.response:a.responseText
n(new p(o,t))},a.onerror=function(){i(new TypeError("Network request failed"))},a.open(o.method,o.url,!0),"include"===o.credentials&&(a.withCredentials=!0),"responseType"in a&&h.blob&&(a.responseType="blob"),
o.headers.forEach(function(e,t){a.setRequestHeader(t,e)}),a.send("undefined"==typeof o._bodyInit?null:o._bodyInit)})},e.fetch.polyfill=!0}}("undefined"!=typeof self?self:this)},function(e,t,n){var i;(function(t,r){
!function(t,n){e.exports=n()}(this,function(){"use strict"
function e(e){return"function"==typeof e||"object"==typeof e&&null!==e}function o(e){return"function"==typeof e}function a(e){K=e}function s(e){Y=e}function l(){return function(){return t.nextTick(p)}}
function u(){return function(){Q(p)}}function d(){var e=0,t=new ee(p),n=document.createTextNode("")
return t.observe(n,{characterData:!0}),function(){n.data=e=++e%2}}function c(){var e=new MessageChannel
return e.port1.onmessage=p,function(){return e.port2.postMessage(0)}}function f(){var e=setTimeout
return function(){return e(p,1)}}function p(){for(var e=0;e<X;e+=2){var t=ie[e],n=ie[e+1]
t(n),ie[e]=void 0,ie[e+1]=void 0}X=0}function h(){try{var e=i,t=n(11)
return Q=t.runOnLoop||t.runOnContext,u()}catch(r){return f()}}function m(e,t){var n=arguments,i=this,r=new this.constructor(v)
void 0===r[oe]&&M(r)
var o=i._state
return o?!function(){var e=n[o-1]
Y(function(){return F(o,r,e,i._result)})}():j(i,r,e,t),r}function g(e){var t=this
if(e&&"object"==typeof e&&e.constructor===t)return e
var n=new t(v)
return P(n,e),n}function v(){}function y(){return new TypeError("You cannot resolve a promise with itself")}function b(){return new TypeError("A promises callback cannot return that same promise.")}function w(e){
try{return e.then}catch(t){return ue.error=t,ue}}function _(e,t,n,i){try{e.call(t,n,i)}catch(r){return r}}function C(e,t,n){Y(function(e){var i=!1,r=_(n,t,function(n){i||(i=!0,t!==n?P(e,n):S(e,n))},function(t){
i||(i=!0,k(e,t))},"Settle: "+(e._label||" unknown promise"))
!i&&r&&(i=!0,k(e,r))},e)}function T(e,t){t._state===se?S(e,t._result):t._state===le?k(e,t._result):j(t,void 0,function(t){return P(e,t)},function(t){return k(e,t)})}function E(e,t,n){t.constructor===e.constructor&&n===m&&t.constructor.resolve===g?T(e,t):n===ue?k(e,ue.error):void 0===n?S(e,t):o(n)?C(e,t,n):S(e,t)

}function P(t,n){t===n?k(t,y()):e(n)?E(t,n,w(n)):S(t,n)}function O(e){e._onerror&&e._onerror(e._result),x(e)}function S(e,t){e._state===ae&&(e._result=t,e._state=se,0!==e._subscribers.length&&Y(x,e))}function k(e,t){
e._state===ae&&(e._state=le,e._result=t,Y(O,e))}function j(e,t,n,i){var r=e._subscribers,o=r.length
e._onerror=null,r[o]=t,r[o+se]=n,r[o+le]=i,0===o&&e._state&&Y(x,e)}function x(e){var t=e._subscribers,n=e._state
if(0!==t.length){for(var i=void 0,r=void 0,o=e._result,a=0;a<t.length;a+=3)i=t[a],r=t[a+n],i?F(n,i,r,o):r(o)
e._subscribers.length=0}}function R(){this.error=null}function I(e,t){try{return e(t)}catch(n){return de.error=n,de}}function F(e,t,n,i){var r=o(n),a=void 0,s=void 0,l=void 0,u=void 0
if(r){if(a=I(n,i),a===de?(u=!0,s=a.error,a=null):l=!0,t===a)return void k(t,b())}else a=i,l=!0
t._state!==ae||(r&&l?P(t,a):u?k(t,s):e===se?S(t,a):e===le&&k(t,a))}function A(e,t){try{t(function i(t){P(e,t)},function r(t){k(e,t)})}catch(n){k(e,n)}}function D(){return ce++}function M(e){e[oe]=ce++,
e._state=void 0,e._result=void 0,e._subscribers=[]}function N(e,t){this._instanceConstructor=e,this.promise=new e(v),this.promise[oe]||M(this.promise),z(t)?(this._input=t,this.length=t.length,this._remaining=t.length,
this._result=new Array(this.length),0===this.length?S(this.promise,this._result):(this.length=this.length||0,this._enumerate(),0===this._remaining&&S(this.promise,this._result))):k(this.promise,U())}function U(){
return new Error("Array Methods must be provided an Array")}function L(e){return new N(this,e).promise}function B(e){var t=this
return new t(z(e)?function(n,i){for(var r=e.length,o=0;o<r;o++)t.resolve(e[o]).then(n,i)}:function(e,t){return t(new TypeError("You must pass an array to race."))})}function H(e){var t=this,n=new t(v)
return k(n,e),n}function $(){throw new TypeError("You must pass a resolver function as the first argument to the promise constructor")}function V(){throw new TypeError("Failed to construct 'Promise': Please use the 'new' operator, this object constructor cannot be called as a function.")

}function q(e){this[oe]=D(),this._result=this._state=void 0,this._subscribers=[],v!==e&&("function"!=typeof e&&$(),this instanceof q?A(this,e):V())}function G(){var e=void 0
if("undefined"!=typeof r)e=r
else if("undefined"!=typeof self)e=self
else try{e=Function("return this")()}catch(t){throw new Error("polyfill failed because global object is unavailable in this environment")}var n=e.Promise
if(n){var i=null
try{i=Object.prototype.toString.call(n.resolve())}catch(t){}if("[object Promise]"===i&&!n.cast)return}e.Promise=q}var W=void 0
W=Array.isArray?Array.isArray:function(e){return"[object Array]"===Object.prototype.toString.call(e)}
var z=W,X=0,Q=void 0,K=void 0,Y=function fe(e,t){ie[X]=e,ie[X+1]=t,X+=2,2===X&&(K?K(p):re())},J="undefined"!=typeof window?window:void 0,Z=J||{},ee=Z.MutationObserver||Z.WebKitMutationObserver,te="undefined"==typeof self&&"undefined"!=typeof t&&"[object process]"==={}.toString.call(t),ne="undefined"!=typeof Uint8ClampedArray&&"undefined"!=typeof importScripts&&"undefined"!=typeof MessageChannel,ie=new Array(1e3),re=void 0


re=te?l():ee?d():ne?c():void 0===J?h():f()
var oe=Math.random().toString(36).substring(16),ae=void 0,se=1,le=2,ue=new R,de=new R,ce=0
return N.prototype._enumerate=function(){for(var e=this.length,t=this._input,n=0;this._state===ae&&n<e;n++)this._eachEntry(t[n],n)},N.prototype._eachEntry=function(e,t){var n=this._instanceConstructor,i=n.resolve


if(i===g){var r=w(e)
if(r===m&&e._state!==ae)this._settledAt(e._state,t,e._result)
else if("function"!=typeof r)this._remaining--,this._result[t]=e
else if(n===q){var o=new n(v)
E(o,e,r),this._willSettleAt(o,t)}else this._willSettleAt(new n(function(t){return t(e)}),t)}else this._willSettleAt(i(e),t)},N.prototype._settledAt=function(e,t,n){var i=this.promise
i._state===ae&&(this._remaining--,e===le?k(i,n):this._result[t]=n),0===this._remaining&&S(i,this._result)},N.prototype._willSettleAt=function(e,t){var n=this
j(e,void 0,function(e){return n._settledAt(se,t,e)},function(e){return n._settledAt(le,t,e)})},q.all=L,q.race=B,q.resolve=g,q.reject=H,q._setScheduler=a,q._setAsap=s,q._asap=Y,q.prototype={constructor:q,
then:m,"catch":function pe(e){return this.then(null,e)}},G(),q.polyfill=G,q.Promise=q,q})}).call(t,n(10),function(){return this}())},,function(e,t){},function(e,t,n){"use strict"
var i=n(13),r=n(15)
e.exports={stringify:i,parse:r}},function(e,t,n){"use strict"
var i=n(14),r={brackets:function s(e){return e+"[]"},indices:function l(e,t){return e+"["+t+"]"},repeat:function u(e){return e}},o={delimiter:"&",strictNullHandling:!1,skipNulls:!1,encode:!0,encoder:i.encode
},a=function d(e,t,n,r,o,a,s,l,u){var c=e
if("function"==typeof s)c=s(t,c)
else if(c instanceof Date)c=c.toISOString()
else if(null===c){if(r)return a?a(t):t
c=""}if("string"==typeof c||"number"==typeof c||"boolean"==typeof c||i.isBuffer(c))return a?[a(t)+"="+a(c)]:[t+"="+String(c)]
var f=[]
if("undefined"==typeof c)return f
var p
if(Array.isArray(s))p=s
else{var h=Object.keys(c)
p=l?h.sort(l):h}for(var m=0;m<p.length;++m){var g=p[m]
o&&null===c[g]||(f=Array.isArray(c)?f.concat(d(c[g],n(t,g),n,r,o,a,s,l,u)):f.concat(d(c[g],t+(u?"."+g:"["+g+"]"),n,r,o,a,s,l,u)))}return f}
e.exports=function(e,t){var n=e,i=t||{},s="undefined"==typeof i.delimiter?o.delimiter:i.delimiter,l="boolean"==typeof i.strictNullHandling?i.strictNullHandling:o.strictNullHandling,u="boolean"==typeof i.skipNulls?i.skipNulls:o.skipNulls,d="boolean"==typeof i.encode?i.encode:o.encode,c=d?"function"==typeof i.encoder?i.encoder:o.encoder:null,f="function"==typeof i.sort?i.sort:null,p="undefined"!=typeof i.allowDots&&i.allowDots,h,m


if(null!==i.encoder&&void 0!==i.encoder&&"function"!=typeof i.encoder)throw new TypeError("Encoder has to be a function.")
"function"==typeof i.filter?(m=i.filter,n=m("",n)):Array.isArray(i.filter)&&(h=m=i.filter)
var g=[]
if("object"!=typeof n||null===n)return""
var v
v=i.arrayFormat in r?i.arrayFormat:"indices"in i?i.indices?"indices":"repeat":"indices"
var y=r[v]
h||(h=Object.keys(n)),f&&h.sort(f)
for(var b=0;b<h.length;++b){var w=h[b]
u&&null===n[w]||(g=g.concat(a(n[w],w,y,l,u,c,m,f,p)))}return g.join(s)}},function(e,t){"use strict"
var n=function(){for(var e=new Array(256),t=0;t<256;++t)e[t]="%"+((t<16?"0":"")+t.toString(16)).toUpperCase()
return e}()
t.arrayToObject=function(e,t){for(var n=t.plainObjects?Object.create(null):{},i=0;i<e.length;++i)"undefined"!=typeof e[i]&&(n[i]=e[i])
return n},t.merge=function(e,n,i){if(!n)return e
if("object"!=typeof n){if(Array.isArray(e))e.push(n)
else{if("object"!=typeof e)return[e,n]
e[n]=!0}return e}if("object"!=typeof e)return[e].concat(n)
var r=e
return Array.isArray(e)&&!Array.isArray(n)&&(r=t.arrayToObject(e,i)),Object.keys(n).reduce(function(e,r){var o=n[r]
return Object.prototype.hasOwnProperty.call(e,r)?e[r]=t.merge(e[r],o,i):e[r]=o,e},r)},t.decode=function(e){try{return decodeURIComponent(e.replace(/\+/g," "))}catch(t){return e}},t.encode=function(e){if(0===e.length)return e


for(var t="string"==typeof e?e:String(e),i="",r=0;r<t.length;++r){var o=t.charCodeAt(r)
45===o||46===o||95===o||126===o||o>=48&&o<=57||o>=65&&o<=90||o>=97&&o<=122?i+=t.charAt(r):o<128?i+=n[o]:o<2048?i+=n[192|o>>6]+n[128|63&o]:o<55296||o>=57344?i+=n[224|o>>12]+n[128|o>>6&63]+n[128|63&o]:(r+=1,
o=65536+((1023&o)<<10|1023&t.charCodeAt(r)),i+=n[240|o>>18]+n[128|o>>12&63]+n[128|o>>6&63]+n[128|63&o])}return i},t.compact=function(e,n){if("object"!=typeof e||null===e)return e
var i=n||[],r=i.indexOf(e)
if(r!==-1)return i[r]
if(i.push(e),Array.isArray(e)){for(var o=[],a=0;a<e.length;++a)e[a]&&"object"==typeof e[a]?o.push(t.compact(e[a],i)):"undefined"!=typeof e[a]&&o.push(e[a])
return o}for(var s=Object.keys(e),l=0;l<s.length;++l){var u=s[l]
e[u]=t.compact(e[u],i)}return e},t.isRegExp=function(e){return"[object RegExp]"===Object.prototype.toString.call(e)},t.isBuffer=function(e){return null!==e&&"undefined"!=typeof e&&!!(e.constructor&&e.constructor.isBuffer&&e.constructor.isBuffer(e))

}},function(e,t,n){"use strict"
var i=n(14),r=Object.prototype.hasOwnProperty,o={delimiter:"&",depth:5,arrayLimit:20,parameterLimit:1e3,strictNullHandling:!1,plainObjects:!1,allowPrototypes:!1,allowDots:!1,decoder:i.decode},a=function u(e,t){
for(var n={},i=e.split(t.delimiter,t.parameterLimit===1/0?void 0:t.parameterLimit),o=0;o<i.length;++o){var a=i[o],s=a.indexOf("]=")===-1?a.indexOf("="):a.indexOf("]=")+1,l,u
s===-1?(l=t.decoder(a),u=t.strictNullHandling?null:""):(l=t.decoder(a.slice(0,s)),u=t.decoder(a.slice(s+1))),r.call(n,l)?n[l]=[].concat(n[l]).concat(u):n[l]=u}return n},s=function d(e,t,n){if(!e.length)return t


var i=e.shift(),r
if("[]"===i)r=[],r=r.concat(d(e,t,n))
else{r=n.plainObjects?Object.create(null):{}
var o="["===i[0]&&"]"===i[i.length-1]?i.slice(1,i.length-1):i,a=parseInt(o,10)
!isNaN(a)&&i!==o&&String(a)===o&&a>=0&&n.parseArrays&&a<=n.arrayLimit?(r=[],r[a]=d(e,t,n)):r[o]=d(e,t,n)}return r},l=function c(e,t,n){if(e){var i=n.allowDots?e.replace(/\.([^\.\[]+)/g,"[$1]"):e,o=/^([^\[\]]*)/,a=/(\[[^\[\]]*\])/g,l=o.exec(i),u=[]


if(l[1]){if(!n.plainObjects&&r.call(Object.prototype,l[1])&&!n.allowPrototypes)return
u.push(l[1])}for(var d=0;null!==(l=a.exec(i))&&d<n.depth;)d+=1,(n.plainObjects||!r.call(Object.prototype,l[1].replace(/\[|\]/g,""))||n.allowPrototypes)&&u.push(l[1])
return l&&u.push("["+i.slice(l.index)+"]"),s(u,t,n)}}
e.exports=function(e,t){var n=t||{}
if(null!==n.decoder&&void 0!==n.decoder&&"function"!=typeof n.decoder)throw new TypeError("Decoder has to be a function.")
if(n.delimiter="string"==typeof n.delimiter||i.isRegExp(n.delimiter)?n.delimiter:o.delimiter,n.depth="number"==typeof n.depth?n.depth:o.depth,n.arrayLimit="number"==typeof n.arrayLimit?n.arrayLimit:o.arrayLimit,
n.parseArrays=n.parseArrays!==!1,n.decoder="function"==typeof n.decoder?n.decoder:o.decoder,n.allowDots="boolean"==typeof n.allowDots?n.allowDots:o.allowDots,n.plainObjects="boolean"==typeof n.plainObjects?n.plainObjects:o.plainObjects,
n.allowPrototypes="boolean"==typeof n.allowPrototypes?n.allowPrototypes:o.allowPrototypes,n.parameterLimit="number"==typeof n.parameterLimit?n.parameterLimit:o.parameterLimit,n.strictNullHandling="boolean"==typeof n.strictNullHandling?n.strictNullHandling:o.strictNullHandling,
""===e||null===e||"undefined"==typeof e)return n.plainObjects?Object.create(null):{}
for(var r="string"==typeof e?a(e,n):e,s=n.plainObjects?Object.create(null):{},u=Object.keys(r),d=0;d<u.length;++d){var c=u[d],f=l(c,r[c],n)
s=i.merge(s,f,n)}return i.compact(s)}},function(e,t,n){(function(e){!function(t){function n(e,t){if("object"!==r(e))return t
for(var i in t)"object"===r(e[i])&&"object"===r(t[i])?e[i]=n(e[i],t[i]):e[i]=t[i]
return e}function i(e,t,i){var a=i[0],s=i.length;(e||"object"!==r(a))&&(a={})
for(var l=0;l<s;++l){var u=i[l],d=r(u)
if("object"===d)for(var c in u){var f=e?o.clone(u[c]):u[c]
t?a[c]=n(a[c],f):a[c]=f}}return a}function r(e){return{}.toString.call(e).slice(8,-1).toLowerCase()}var o=function(e){return i(e===!0,!1,arguments)},a="merge"
o.recursive=function(e){return i(e===!0,!0,arguments)},o.clone=function(e){var t=e,n=r(e),i,a
if("array"===n)for(t=[],a=e.length,i=0;i<a;++i)t[i]=o.clone(e[i])
else if("object"===n){t={}
for(i in e)t[i]=o.clone(e[i])}return t},t?e.exports=o:window[a]=o}("object"==typeof e&&e&&"object"==typeof e.exports&&e.exports)}).call(t,n(17)(e))},function(e,t){e.exports=function(e){return e.webpackPolyfill||(e.deprecate=function(){},
e.paths=[],e.children=[],e.webpackPolyfill=1),e}},function(e,t,n){(function(t){e.exports=t.Form=n(19)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleSubmit=n.handleSubmit.bind(n),n}return a(t,e),l(t,[{key:"componentWillUnmount",value:function n(){"undefined"!=typeof this.props.componentWillUnmount&&this.props.componentWillUnmount(this.props.formId)

}},{key:"render",value:function i(){var e={className:"form",onSubmit:this.handleSubmit},t=s({},e,this.props.attributes),n=this.props.mapFieldsToComponents(this.props.fields),i=this.props.mapActionsToComponents(this.props.actions)


return d["default"].createElement("form",t,n&&d["default"].createElement("fieldset",null,n),i&&d["default"].createElement("div",{className:"btn-toolbar",role:"group"},i))}},{key:"handleSubmit",value:function u(e){
"undefined"!=typeof this.props.handleSubmit&&this.props.handleSubmit(e)}}]),t}(f["default"])
p.propTypes={actions:d["default"].PropTypes.array,attributes:d["default"].PropTypes.shape({action:d["default"].PropTypes.string.isRequired,className:d["default"].PropTypes.string,encType:d["default"].PropTypes.string,
id:d["default"].PropTypes.string,method:d["default"].PropTypes.string.isRequired}),componentWillUnmount:d["default"].PropTypes.func,data:d["default"].PropTypes.array,fields:d["default"].PropTypes.array.isRequired,
formId:d["default"].PropTypes.string.isRequired,handleSubmit:d["default"].PropTypes.func,mapActionsToComponents:d["default"].PropTypes.func.isRequired,mapFieldsToComponents:d["default"].PropTypes.func.isRequired
},t["default"]=p},function(e,t){e.exports=SilverStripeComponent},function(e,t,n){(function(t){e.exports=t.FormConstants=n(22)}).call(t,function(){return this}())},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0}),t["default"]={CSRF_HEADER:"X-SecurityID"}},function(e,t,n){(function(t){e.exports=t.FormAction=n(24)}).call(t,function(){return this}())},function(e,t,n){
"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleClick=n.handleClick.bind(n),n}return a(t,e),l(t,[{key:"render",value:function n(){return d["default"].createElement("button",this.getButtonProps(),this.getLoadingIcon(),d["default"].createElement("span",null,this.props.title))

}},{key:"getButtonProps",value:function i(){return s({},"undefined"==typeof this.props.attributes?{}:this.props.attributes,{id:this.props.id,className:this.getButtonClasses(),disabled:this.props.disabled,
onClick:this.handleClick})}},{key:"getButtonClasses",value:function u(){var e=["btn"],t=this.getButtonStyle()
t&&e.push("btn-"+t),"string"!=typeof this.props.title&&e.push("btn--no-text")
var n=this.getIcon()
return n&&e.push("font-icon-"+n),this.props.loading&&e.push("btn--loading"),this.props.disabled&&e.push("disabled"),"string"==typeof this.props.extraClass&&e.push(this.props.extraClass),e.join(" ")}},{
key:"getButtonStyle",value:function c(){if("undefined"!=typeof this.props.data.buttonStyle)return this.props.data.buttonStyle
var e=this.props.extraClass.split(" ")
return e.find(function(e){return e.indexOf("btn-")>-1})?null:"action_save"===this.props.name||e.find(function(e){return"ss-ui-action-constructive"===e})?"primary":"secondary"}},{key:"getIcon",value:function f(){
return this.props.icon||this.props.data.icon||null}},{key:"getLoadingIcon",value:function p(){return this.props.loading?d["default"].createElement("div",{className:"btn__loading-icon"},d["default"].createElement("span",{
className:"btn__circle btn__circle--1"}),d["default"].createElement("span",{className:"btn__circle btn__circle--2"}),d["default"].createElement("span",{className:"btn__circle btn__circle--3"})):null}},{
key:"handleClick",value:function h(e){"function"==typeof this.props.handleClick&&this.props.handleClick(e,this.props.name||this.props.id)}}]),t}(f["default"])
p.propTypes={id:d["default"].PropTypes.string,name:d["default"].PropTypes.string,handleClick:d["default"].PropTypes.func,title:d["default"].PropTypes.string,type:d["default"].PropTypes.string,loading:d["default"].PropTypes.bool,
icon:d["default"].PropTypes.string,disabled:d["default"].PropTypes.bool,data:d["default"].PropTypes.oneOfType([d["default"].PropTypes.array,d["default"].PropTypes.shape({buttonStyle:d["default"].PropTypes.string
})]),extraClass:d["default"].PropTypes.string,attributes:d["default"].PropTypes.object},p.defaultProps={title:"",icon:"",extraClass:"",attributes:{},data:{},disabled:!1},t["default"]=p},function(e,t,n){
(function(t){e.exports=t.FormBuilder=n(26)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){if(e&&e.__esModule)return e
var t={}
if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])
return t["default"]=e,t}function r(e){return e&&e.__esModule?e:{"default":e}}function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function a(e,t){
if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function s(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")
return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function u(e){return{config:e.config,
form:e.form,schemas:e.schemas}}function d(e){return{formActions:(0,g.bindActionCreators)(y,e),schemaActions:(0,g.bindActionCreators)(w,e)}}Object.defineProperty(t,"__esModule",{value:!0}),t.FormBuilderComponent=void 0


var c=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},f=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),p=n(4),h=r(p),m=n(27),g=n(28),v=n(29),y=i(v),b=n(31),w=i(b),_=n(20),C=r(_),T=n(19),E=r(T),P=n(7),O=r(P),S=n(33),k=r(S),j=n(34),x=r(j),R=n(16),I=r(R),F=n(9),A=r(F)


A["default"].polyfill()
var D=t.FormBuilderComponent=function(e){function t(e){a(this,t)
var n=s(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.formSchemaPromise=null,n.state={isFetching:!1},n.mapActionsToComponents=n.mapActionsToComponents.bind(n),n.mapFieldsToComponents=n.mapFieldsToComponents.bind(n),n.handleFieldUpdate=n.handleFieldUpdate.bind(n),
n.handleSubmit=n.handleSubmit.bind(n),n.handleAction=n.handleAction.bind(n),n.removeForm=n.removeForm.bind(n),n.getFormId=n.getFormId.bind(n),n.getFormSchema=n.getFormSchema.bind(n),n.findField=n.findField.bind(n),
n}return l(t,e),f(t,[{key:"getFormSchema",value:function n(){return this.props.schemas[this.props.schemaUrl]}},{key:"getFormId",value:function i(){var e=this.getFormSchema()
return e?e.id:null}},{key:"componentDidMount",value:function r(){this.fetch()}},{key:"componentDidUpdate",value:function u(e){this.props.schemaUrl!==e.schemaUrl&&this.fetch()}},{key:"fetch",value:function d(){
var e=this,t=arguments.length<=0||void 0===arguments[0]||arguments[0],n=arguments.length<=1||void 0===arguments[1]||arguments[1],i=[]
return this.state.isFetching===!0?this.formSchemaPromise:(t===!0&&i.push("schema"),n===!0&&i.push("state"),this.formSchemaPromise=(0,O["default"])(this.props.schemaUrl,{headers:{"X-FormSchema-Request":i.join()
},credentials:"same-origin"}).then(function(e){return e.json()}).then(function(t){var n=c({},{id:t.id,schema:t.schema}),i=c({},t.state)
"undefined"!=typeof n.id&&!function(){var t={SecurityID:e.props.config.SecurityID}
e.submitApi=function(){var i=k["default"].createEndpointFetcher({url:n.schema.attributes.action,method:n.schema.attributes.method,defaultData:t})
return i.apply(void 0,arguments).then(function(t){if(t.schema){var n=c({},{id:t.id,schema:t.schema})
e.props.schemaActions.setSchema(n)}return t})},e.props.schemaActions.setSchema(n)}(),"undefined"!=typeof i.id&&e.props.formActions.addForm(i)}),this.formSchemaPromise)}},{key:"handleFieldUpdate",value:function p(e,t,n){
"function"==typeof n?n(this.getFormId(),this.props.formActions.updateField):this.props.formActions.updateField(this.getFormId(),t)}},{key:"handleAction",value:function m(e,t){this.props.formActions.setSubmitAction(this.getFormId(),t),
"function"==typeof this.props.handleAction&&this.props.handleAction(e,t,this.getFieldValues())}},{key:"handleSubmit",value:function g(e){var t=this,n=this.getFieldValues(),i=function r(){return t.props.formActions.submitForm(t.submitApi,t.getFormId(),n)

}
return"undefined"!=typeof this.props.handleSubmit?this.props.handleSubmit(e,n,i):(e.preventDefault(),i())}},{key:"getFieldValues",value:function v(){var e=this,t=this.props.schemas[this.props.schemaUrl],n=t.state?t.state.fields:t.schema.fields,i=this.getSubmitAction(),r={}


return i&&(r[i]=1),this.props.form[this.getFormId()].fields.reduce(function(t,i){var r=e.findField(n,i.id)
return r?c({},t,o({},r.name,i.value)):t},r)}},{key:"getSubmitAction",value:function y(){return this.props.form[this.getFormId()].submitAction}},{key:"findField",value:function b(e,t){var n=null
if(!e)return n
n=e.find(function(e){return e.id===t})
var i=!0,r=!1,o=void 0
try{for(var a=e[Symbol.iterator](),s;!(i=(s=a.next()).done);i=!0){var l=s.value
if(n)break
n=this.findField(l.children,t)}}catch(u){r=!0,o=u}finally{try{!i&&a["return"]&&a["return"]()}finally{if(r)throw o}}return n}},{key:"buildComponent",value:function w(e){var t=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],n=null!==e.component?x["default"].getComponentByName(e.component):x["default"].getComponentByDataType(e.type)


if(null===n)return null
if(null!==e.component&&void 0===n)throw Error("Component not found in injector: "+e.component)
var i=c({},e,t)
null===i.value&&delete i.value
var r=this.props.createFn
return"function"==typeof r?r(n,i):h["default"].createElement(n,c({key:i.id},i))}},{key:"mapFieldsToComponents",value:function _(e){var t=this
return e.map(function(e){var n={onChange:t.handleFieldUpdate}
return e.children&&(n.children=t.mapFieldsToComponents(e.children)),t.buildComponent(e,n)})}},{key:"mapActionsToComponents",value:function C(e){var t=this,n=this.props.form[this.getFormId()]
return e.map(function(e){var i=n&&n.submitting&&n.submitAction===e.name,r={handleClick:t.handleAction,loading:i,disabled:i||e.disabled}
return e.children&&(r.children=t.mapActionsToComponents(e.children)),t.buildComponent(e,r)})}},{key:"mergeFieldData",value:function T(e,t){return"undefined"==typeof t?e:I["default"].recursive(!0,e,{data:t.data,
source:t.source,messages:t.messages,valid:t.valid,value:t.value})}},{key:"removeForm",value:function P(e){this.props.formActions.removeForm(e)}},{key:"getFieldData",value:function S(e,t){var n=this
return e&&t&&t.fields?e.map(function(e){var i=t.fields.find(function(t){return t.id===e.id}),r=n.mergeFieldData(e,i)
return e.children?c({},r,{children:n.getFieldData(e.children,t)}):r}):e}},{key:"render",value:function j(){var e=this.getFormId()
if(!e)return null
var t=this.getFormSchema(),n=this.props.form[e]
if(!t||!t.schema)return null
var i=c({},t.schema.attributes,{className:t.schema.attributes["class"],encType:t.schema.attributes.enctype})
delete i["class"],delete i.enctype
var r=this.getFieldData(t.schema.fields,n),o=this.getFieldData(t.schema.actions,n),a={actions:o,attributes:i,componentWillUnmount:this.removeForm,data:t.schema.data,fields:r,formId:e,handleSubmit:this.handleSubmit,
mapActionsToComponents:this.mapActionsToComponents,mapFieldsToComponents:this.mapFieldsToComponents}
return h["default"].createElement(E["default"],a)}}]),t}(C["default"])
D.propTypes={config:h["default"].PropTypes.object,createFn:h["default"].PropTypes.func,form:h["default"].PropTypes.object.isRequired,formActions:h["default"].PropTypes.object.isRequired,handleSubmit:h["default"].PropTypes.func,
handleAction:h["default"].PropTypes.func,schemas:h["default"].PropTypes.object.isRequired,schemaActions:h["default"].PropTypes.object.isRequired,schemaUrl:h["default"].PropTypes.string.isRequired},t["default"]=(0,
m.connect)(u,d)(D)},function(e,t){e.exports=ReactRedux},,function(e,t,n){"use strict"
function i(e){return function(t){t({type:u.ACTION_TYPES.REMOVE_FORM,payload:{formId:e}})}}function r(e,t){return function(n){n({type:u.ACTION_TYPES.UPDATE_FIELD,payload:{formId:e,updates:t}})}}function o(e){
return function(t){t({type:u.ACTION_TYPES.ADD_FORM,payload:{formState:e}})}}function a(e,t,n){return function(i){var r={"X-Formschema-Request":"schema,state","X-Requested-With":"XMLHttpRequest"}
return i({type:u.ACTION_TYPES.SUBMIT_FORM_REQUEST,payload:{formId:t}}),e(l({ID:t},n),r).then(function(e){return i({type:u.ACTION_TYPES.SUBMIT_FORM_SUCCESS,payload:{response:e}}),e})["catch"](function(e){
throw e.response.text().then(function(e){return i({type:u.ACTION_TYPES.SUBMIT_FORM_FAILURE,payload:{formId:t,error:e}}),e})})}}function s(e,t){return function(n){n({type:u.ACTION_TYPES.SET_SUBMIT_ACTION,
payload:{formId:e,submitAction:t}})}}Object.defineProperty(t,"__esModule",{value:!0})
var l=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e}
t.removeForm=i,t.updateField=r,t.addForm=o,t.submitForm=a,t.setSubmitAction=s
var u=n(30)},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0})
var n=t.ACTION_TYPES={ADD_FORM:"ADD_FORM",REMOVE_FORM:"REMOVE_FORM",SUBMIT_FORM_FAILURE:"SUBMIT_FORM_FAILURE",SUBMIT_FORM_REQUEST:"SUBMIT_FORM_REQUEST",SUBMIT_FORM_SUCCESS:"SUBMIT_FORM_SUCCESS",UPDATE_FIELD:"UPDATE_FIELD",
SET_SUBMIT_ACTION:"SET_SUBMIT_ACTION"}},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e){return{type:a["default"].SET_SCHEMA,payload:e}}Object.defineProperty(t,"__esModule",{value:!0}),t.setSchema=r
var o=n(32),a=i(o)},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0})
var n={SET_SCHEMA:"SET_SCHEMA"}
t["default"]=n},function(e,t){e.exports=Backend},function(e,t){"use strict"
function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),r=function(){function e(){
n(this,e),this.components={}}return i(e,[{key:"getComponentByName",value:function t(e){return this.components[e]}},{key:"getComponentByDataType",value:function r(e){switch(e){case"Text":case"DateTime":
return this.components.TextField
case"Hidden":return this.components.HiddenField
case"SingleSelect":return this.components.SingleSelectField
case"Custom":return this.components.GridField
case"Structural":return this.components.CompositeField
case"Boolean":return this.components.CheckboxField
case"MultiSelect":return this.components.CheckboxSetField
default:return null}}},{key:"register",value:function o(e,t){this.components[e]=t}}]),e}()
window.ss=window.ss||{},window.ss.injector=window.ss.injector||new r,t["default"]=window.ss.injector},function(e,t,n){(function(t){e.exports=t.FormBuilderModal=n(36)}).call(t,function(){return this}())

},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(37),c=n(20),f=i(c),p=n(38),h=i(p),m=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleSubmit=n.handleSubmit.bind(n),n.handleHide=n.handleHide.bind(n),n.clearResponse=n.clearResponse.bind(n),n}return a(t,e),s(t,[{key:"getForm",value:function n(){return u["default"].createElement(h["default"],{
schemaUrl:this.props.schemaUrl,handleSubmit:this.handleSubmit,handleAction:this.props.handleAction})}},{key:"getResponse",value:function i(){if(!this.state||!this.state.response)return null
var e=""
return e=this.state.error?this.props.responseClassBad||"response error":this.props.responseClassGood||"response good",u["default"].createElement("div",{className:e},u["default"].createElement("span",null,this.state.response))

}},{key:"clearResponse",value:function l(){this.setState({response:null})}},{key:"handleHide",value:function c(){this.clearResponse(),"function"==typeof this.props.handleHide&&this.props.handleHide()}},{
key:"handleSubmit",value:function f(e,t,n){var i=this,r=null
return"function"==typeof this.props.handleSubmit?r=this.props.handleSubmit(e,t,n):(e.preventDefault(),r=n()),r&&r.then(function(e){return i.setState({response:e.message,error:!1}),e})["catch"](function(e){
e.then(function(e){i.setState({response:e,error:!0})})}),r}},{key:"render",value:function p(){var e=this.getForm(),t=this.getResponse()
return u["default"].createElement(d.Modal,{show:this.props.show,onHide:this.handleHide,className:this.props.className},this.props.title!==!1&&u["default"].createElement(d.Modal.Header,{closeButton:!0},u["default"].createElement(d.Modal.Title,null,this.props.title)),u["default"].createElement(d.Modal.Body,{
className:this.props.bodyClassName},t,e,this.props.children))}}]),t}(f["default"])
m.propTypes={show:u["default"].PropTypes.bool,title:u["default"].PropTypes.string,className:u["default"].PropTypes.string,bodyClassName:u["default"].PropTypes.string,handleHide:u["default"].PropTypes.func,
schemaUrl:u["default"].PropTypes.string,handleSubmit:u["default"].PropTypes.func,handleAction:u["default"].PropTypes.func,responseClassGood:u["default"].PropTypes.string,responseClassBad:u["default"].PropTypes.string
},m.defaultProps={show:!1,title:null},t["default"]=m},function(e,t){e.exports=ReactBootstrap},function(e,t){e.exports=FormBuilder},function(e,t,n){(function(t){e.exports=t.GridField=n(40)}).call(t,function(){
return this}())},function(e,t,n){"use strict"
function i(e){if(e&&e.__esModule)return e
var t={}
if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])
return t["default"]=e,t}function r(e){return e&&e.__esModule?e:{"default":e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function s(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function l(e,t){var n=t.data?t.data.recordType:null


return{config:e.config,records:n&&e.records[n]?e.records[n]:A}}function u(e){return{actions:(0,h.bindActionCreators)(F,e)}}Object.defineProperty(t,"__esModule",{value:!0})
var d=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),c=function M(e,t,n){null===e&&(e=Function.prototype)


var i=Object.getOwnPropertyDescriptor(e,t)
if(void 0===i){var r=Object.getPrototypeOf(e)
return null===r?void 0:M(r,t,n)}if("value"in i)return i.value
var o=i.get
if(void 0!==o)return o.call(n)},f=n(4),p=r(f),h=n(28),m=n(27),g=n(20),v=r(g),y=n(41),b=r(y),w=n(42),_=r(w),C=n(44),T=r(C),E=n(43),P=r(E),O=n(45),S=r(O),k=n(46),j=r(k),x=n(22),R=r(x),I=n(47),F=i(I),A={},D=function(e){
function t(e){o(this,t)
var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.deleteRecord=n.deleteRecord.bind(n),n.editRecord=n.editRecord.bind(n),n}return s(t,e),d(t,[{key:"componentDidMount",value:function n(){c(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"componentDidMount",this).call(this)


var e=this.props.data
this.props.actions.fetchRecords(e.recordType,e.collectionReadEndpoint.method,e.collectionReadEndpoint.url)}},{key:"render",value:function i(){var e=this
if(this.props.records===A)return p["default"].createElement("div",null,"Loading...")
if(!Object.getOwnPropertyNames(this.props.records).length)return p["default"].createElement("div",null,"No campaigns created yet.")
var t=p["default"].createElement("th",{key:"holder",className:"grid-field__action-placeholder"}),n=this.props.data.columns.map(function(e){return p["default"].createElement(T["default"],{key:""+e.name},e.name)

}),i=p["default"].createElement(_["default"],null,n.concat(t)),r=Object.keys(this.props.records).map(function(t){return e.createRow(e.props.records[t])})
return p["default"].createElement(b["default"],{header:i,rows:r})}},{key:"createRowActions",value:function r(e){return p["default"].createElement(S["default"],{className:"grid-field__cell--actions",key:"Actions"
},p["default"].createElement(j["default"],{icon:"cog",handleClick:this.editRecord,record:e}),p["default"].createElement(j["default"],{icon:"cancel",handleClick:this.deleteRecord,record:e}))}},{key:"createCell",
value:function l(e,t){var n=this.props.data.handleDrillDown,i={className:n?"grid-field__cell--drillable":"",handleDrillDown:n?function(t){return n(t,e)}:null,key:""+t.name,width:t.width},r=t.field.split(".").reduce(function(e,t){
return e[t]},e)
return p["default"].createElement(S["default"],i,r)}},{key:"createRow",value:function u(e){var t=this,n={className:this.props.data.handleDrillDown?"grid-field__row--drillable":"",key:""+e.ID},i=this.props.data.columns.map(function(n){
return t.createCell(e,n)}),r=this.createRowActions(e)
return p["default"].createElement(P["default"],n,i,r)}},{key:"deleteRecord",value:function f(e,t){e.preventDefault()
var n={}
n[R["default"].CSRF_HEADER]=this.props.config.SecurityID,this.props.actions.deleteRecord(this.props.data.recordType,t,this.props.data.itemDeleteEndpoint.method,this.props.data.itemDeleteEndpoint.url,n)

}},{key:"editRecord",value:function h(e,t){e.preventDefault(),"undefined"!=typeof this.props.data&&"undefined"!=typeof this.props.data.handleEditRecord&&this.props.data.handleEditRecord(e,t)}}]),t}(v["default"])


D.propTypes={data:p["default"].PropTypes.shape({recordType:p["default"].PropTypes.string.isRequired,headerColumns:p["default"].PropTypes.array,collectionReadEndpoint:p["default"].PropTypes.object,handleDrillDown:p["default"].PropTypes.func,
handleEditRecord:p["default"].PropTypes.func})},t["default"]=(0,m.connect)(l,u)(D)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("div",{className:"grid-field"
},u["default"].createElement("table",{className:"table table-hover grid-field__table"},u["default"].createElement("thead",null,this.generateHeader()),u["default"].createElement("tbody",null,this.generateRows())))

}},{key:"generateHeader",value:function i(){return"undefined"!=typeof this.props.header?this.props.header:("undefined"!=typeof this.props.data,null)}},{key:"generateRows",value:function l(){return"undefined"!=typeof this.props.rows?this.props.rows:("undefined"!=typeof this.props.data,
null)}}]),t}(c["default"])
f.propTypes={data:u["default"].PropTypes.object,header:u["default"].PropTypes.object,rows:u["default"].PropTypes.array},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(43),p=i(f),h=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement(p["default"],null,this.props.children)

}}]),t}(c["default"])
t["default"]=h},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){var e="grid-field__row "+this.props.className
return u["default"].createElement("tr",{tabIndex:"0",className:e},this.props.children)}}]),t}(c["default"])
t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("th",null,this.props.children)

}}]),t}(c["default"])
f.PropTypes={width:u["default"].PropTypes.number},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleDrillDown=n.handleDrillDown.bind(n),n}return a(t,e),s(t,[{key:"render",value:function n(){var e=["grid-field__cell"]
"undefined"!=typeof this.props.className&&e.push(this.props.className)
var t={className:e.join(" "),onClick:this.handleDrillDown}
return u["default"].createElement("td",t,this.props.children)}},{key:"handleDrillDown",value:function i(e){"undefined"!=typeof this.props.handleDrillDown&&this.props.handleDrillDown(e)}}]),t}(c["default"])


f.PropTypes={className:u["default"].PropTypes.string,width:u["default"].PropTypes.number,handleDrillDown:u["default"].PropTypes.func},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleClick=n.handleClick.bind(n),n}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("button",{className:"grid-field__icon-action font-icon-"+this.props.icon+" btn--icon-large",
onClick:this.handleClick})}},{key:"handleClick",value:function i(e){this.props.handleClick(e,this.props.record.ID)}}]),t}(c["default"])
f.PropTypes={handleClick:u["default"].PropTypes.func.isRequired},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){var n=["id"]
return n.reduce(function(e,n){return e.replace(":"+n,t[n])},e)}function o(e,t,n){var i={recordType:e},o={Accept:"text/json"},a=t.toLowerCase()
return function(t){t({type:u["default"].FETCH_RECORDS_REQUEST,payload:i})
var s="get"===a?[r(n,i),o]:[r(n,i),{},o]
return c["default"][a].apply(c["default"],s).then(function(e){return e.json()}).then(function(n){t({type:u["default"].FETCH_RECORDS_SUCCESS,payload:{recordType:e,data:n}})})["catch"](function(n){throw t({
type:u["default"].FETCH_RECORDS_FAILURE,payload:{error:n,recordType:e}}),n})}}function a(e,t,n){var i={recordType:e},o={Accept:"text/json"},a=t.toLowerCase()
return function(t){t({type:u["default"].FETCH_RECORD_REQUEST,payload:i})
var s="get"===a?[r(n,i),o]:[r(n,i),{},o]
return c["default"][a].apply(c["default"],s).then(function(e){return e.json()}).then(function(n){t({type:u["default"].FETCH_RECORD_SUCCESS,payload:{recordType:e,data:n}})})["catch"](function(n){throw t({
type:u["default"].FETCH_RECORD_FAILURE,payload:{error:n,recordType:e}}),n})}}function s(e,t,n,i){var o=arguments.length<=4||void 0===arguments[4]?{}:arguments[4],a={recordType:e,id:t},s=n.toLowerCase(),l="get"===s?[r(i,a),o]:[r(i,a),{},o]


return function(n){return n({type:u["default"].DELETE_RECORD_REQUEST,payload:a}),c["default"][s].apply(c["default"],l).then(function(){n({type:u["default"].DELETE_RECORD_SUCCESS,payload:{recordType:e,id:t
}})})["catch"](function(i){throw n({type:u["default"].DELETE_RECORD_FAILURE,payload:{error:i,recordType:e,id:t}}),i})}}Object.defineProperty(t,"__esModule",{value:!0}),t.fetchRecords=o,t.fetchRecord=a,
t.deleteRecord=s
var l=n(48),u=i(l),d=n(6),c=i(d)},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0}),t["default"]={CREATE_RECORD:"CREATE_RECORD",UPDATE_RECORD:"UPDATE_RECORD",DELETE_RECORD:"DELETE_RECORD",FETCH_RECORDS_REQUEST:"FETCH_RECORDS_REQUEST",FETCH_RECORDS_FAILURE:"FETCH_RECORDS_FAILURE",
FETCH_RECORDS_SUCCESS:"FETCH_RECORDS_SUCCESS",FETCH_RECORD_REQUEST:"FETCH_RECORD_REQUEST",FETCH_RECORD_FAILURE:"FETCH_RECORD_FAILURE",FETCH_RECORD_SUCCESS:"FETCH_RECORD_SUCCESS",DELETE_RECORD_REQUEST:"DELETE_RECORD_REQUEST",
DELETE_RECORD_FAILURE:"DELETE_RECORD_FAILURE",DELETE_RECORD_SUCCESS:"DELETE_RECORD_SUCCESS"}},function(e,t,n){(function(t){e.exports=t.GridFieldCell=n(45)}).call(t,function(){return this}())},function(e,t,n){
(function(t){e.exports=t.GridFieldHeader=n(42)}).call(t,function(){return this}())},function(e,t,n){(function(t){e.exports=t.GridFieldHeaderCell=n(44)}).call(t,function(){return this}())},function(e,t,n){
(function(t){e.exports=t.GridFieldRow=n(43)}).call(t,function(){return this}())},function(e,t,n){(function(t){e.exports=t.GridFieldTable=n(41)}).call(t,function(){return this}())},function(e,t,n){(function(t){
e.exports=t.HiddenField=n(55)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(37),p=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"getInputProps",value:function n(){return{bsClass:this.props.bsClass,componentClass:"input",
className:this.props.className+" "+this.props.extraClass,id:this.props.id,name:this.props.name,type:"hidden",value:this.props.value}}},{key:"render",value:function i(){return u["default"].createElement(f.FormControl,this.getInputProps())

}}]),t}(c["default"])
p.propTypes={id:u["default"].PropTypes.string,extraClass:u["default"].PropTypes.string,name:u["default"].PropTypes.string.isRequired,value:u["default"].PropTypes.any},p.defaultProps={className:"",extraClass:"",
value:""},t["default"]=p},function(e,t,n){(function(t){e.exports=t.TextField=n(57)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.TextField=void 0
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=n(58),h=i(p),m=n(37),g=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleChange=n.handleChange.bind(n),n}return a(t,e),l(t,[{key:"render",value:function n(){var e=null
return e=this.props.readOnly?d["default"].createElement(m.FormControl.Static,this.getInputProps(),this.props.value):d["default"].createElement(m.FormControl,this.getInputProps())}},{key:"getInputProps",
value:function i(){var e={bsClass:this.props.bsClass,className:this.props.className+" "+this.props.extraClass,id:this.props.id,name:this.props.name,disabled:this.props.disabled,readOnly:this.props.readOnly
}
return this.props.readOnly||(s(e,{placeholder:this.props.placeholder,onChange:this.handleChange,value:this.props.value}),this.isMultiline()?s(e,{componentClass:"textarea",rows:this.props.data.rows,cols:this.props.data.columns
}):s(e,{componentClass:"input",type:this.props.type})),e}},{key:"isMultiline",value:function u(){return this.props.data&&this.props.data.rows>1}},{key:"handleChange",value:function c(e){"function"==typeof this.props.onChange&&this.props.onChange(e,{
id:this.props.id,value:e.target.value})}}]),t}(f["default"])
g.propTypes={extraClass:d["default"].PropTypes.string,id:d["default"].PropTypes.string,name:d["default"].PropTypes.string.isRequired,onChange:d["default"].PropTypes.func,value:d["default"].PropTypes.oneOfType([d["default"].PropTypes.string,d["default"].PropTypes.number]),
readOnly:d["default"].PropTypes.bool,disabled:d["default"].PropTypes.bool,placeholder:d["default"].PropTypes.string,type:d["default"].PropTypes.string},g.defaultProps={value:"",extraClass:"",className:"",
type:"text"},t.TextField=g,t["default"]=(0,h["default"])(g)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function s(e){var t=function(t){
function n(){return r(this,n),o(this,(n.__proto__||Object.getPrototypeOf(n)).apply(this,arguments))}return a(n,t),l(n,[{key:"getDescription",value:function i(){return null===this.props.description?null:(0,
m["default"])("div",this.props.description,{className:"form__field-description"})}},{key:"getLeftTitle",value:function s(){var e=null!==this.props.leftTitle?this.props.leftTitle:this.props.title
return!e||this.props.hideLabels?null:(0,m["default"])(p.ControlLabel,e,{className:"form__field-label"})}},{key:"getRightTitle",value:function u(){return!this.props.rightTitle||this.props.hideLabels?null:(0,
m["default"])(p.ControlLabel,this.props.rightTitle,{className:"form__field-label"})}},{key:"getHolderProps",value:function c(){var e=["field form__field-holder",this.props.extraClass]
return this.props.readOnly&&e.push("readonly"),{bsClass:this.props.bsClass,bsSize:this.props.bsSize,validationState:this.props.validationState,className:e.join(" "),controlId:this.props.id,id:this.props.holderId
}}},{key:"render",value:function f(){return d["default"].createElement(p.FormGroup,this.getHolderProps(),this.getLeftTitle(),d["default"].createElement(e,this.props),this.getRightTitle(),this.getDescription())

}}]),n}(f["default"])
return t.propTypes={leftTitle:d["default"].PropTypes.any,rightTitle:d["default"].PropTypes.any,title:d["default"].PropTypes.any,extraClass:d["default"].PropTypes.string,holderId:d["default"].PropTypes.string,
id:d["default"].PropTypes.string,description:d["default"].PropTypes.any,hideLabels:d["default"].PropTypes.bool},t.defaultProps={className:"",extraClass:"",leftTitle:null,rightTitle:null},t.defaultProps={
className:"",extraClass:""},t}Object.defineProperty(t,"__esModule",{value:!0})
var l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=n(37),h=n(59),m=i(h)


t["default"]=s},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){var n=arguments.length<=2||void 0===arguments[2]?{}:arguments[2]
if(t&&"undefined"!=typeof t.html){var i={__html:t.html}
return l["default"].createElement(e,a({},n,{dangerouslySetInnerHTML:i}))}var r=null
if(r=t&&"undefined"!=typeof t.text?t.text:t,r&&"object"===("undefined"==typeof r?"undefined":o(r)))throw new Error("Unsupported string value "+JSON.stringify(r))
return l["default"].createElement(e,n,r)}Object.defineProperty(t,"__esModule",{value:!0})
var o="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol?"symbol":typeof e},a=Object.assign||function(e){
for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e}
t["default"]=r
var s=n(4),l=i(s)},function(e,t,n){(function(t){e.exports=t.Toolbar=n(61)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleBackButtonClick=n.handleBackButtonClick.bind(n),n}return a(t,e),s(t,[{key:"render",value:function n(){var e=["btn","btn-secondary","action","font-icon-left-open-big","toolbar__back-button","btn--no-text"],t={
className:e.join(" "),onClick:this.handleBackButtonClick,href:"#",type:"button"}
return u["default"].createElement("div",{className:"toolbar--north container-fluid"},u["default"].createElement("div",{className:"toolbar__navigation"},this.props.showBackButton&&u["default"].createElement("button",t),this.props.children))

}},{key:"handleBackButtonClick",value:function i(e){return"undefined"!=typeof this.props.handleBackButtonClick?void this.props.handleBackButtonClick(e):void e.preventDefault()}}]),t}(c["default"])
f.propTypes={handleBackButtonClick:u["default"].PropTypes.func,showBackButton:u["default"].PropTypes.bool,breadcrumbs:u["default"].PropTypes.array},f.defaultProps={showBackButton:!1},t["default"]=f},function(e,t,n){
(function(t){e.exports=t.Breadcrumb=n(63)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(64),p=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("ol",{className:"breadcrumb"
},this.getBreadcrumbs())}},{key:"getBreadcrumbs",value:function i(){return"undefined"==typeof this.props.crumbs?null:[].concat(this.props.crumbs.slice(0,-1).map(function(e,t){return[u["default"].createElement("li",{
className:"breadcrumb__item"},u["default"].createElement(f.Link,{key:t,className:"breadcrumb__item-title",to:e.href},e.text))]}),this.props.crumbs.slice(-1).map(function(e,t){var n=["breadcrumb__icon",e.icon?e.icon.className:""].join(" ")


return[u["default"].createElement("li",{className:"breadcrumb__item breadcrumb__item--last"},u["default"].createElement("h2",{className:"breadcrumb__item-title breadcrumb__item-title--last",key:t},e.text,e.icon&&u["default"].createElement("span",{
className:n,onClick:e.icon.action})))]}))}}]),t}(c["default"])
p.propTypes={crumbs:u["default"].PropTypes.array},t["default"]=p},function(e,t){e.exports=ReactRouter},function(e,t,n){(function(t){e.exports=t.BreadcrumbsActions=n(66)}).call(t,function(){return this}())

},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e){return{type:a["default"].SET_BREADCRUMBS,payload:{breadcrumbs:e}}}Object.defineProperty(t,"__esModule",{value:!0}),t.setBreadcrumbs=r
var o=n(67),a=i(o)},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0}),t["default"]={SET_BREADCRUMBS:"SET_BREADCRUMBS"}},function(e,t,n){(function(t){e.exports=t.Config=n(69)}).call(t,function(){return this}())},function(e,t){
"use strict"
function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),r=function(){function e(){
n(this,e)}return i(e,null,[{key:"get",value:function t(e){return window.ss.config[e]}},{key:"getAll",value:function r(){return window.ss.config}},{key:"getSection",value:function o(e){return window.ss.config.sections[e]

}}]),e}()
t["default"]=r},function(e,t,n){(function(t){e.exports=t.ReducerRegister=n(71)}).call(t,function(){return this}())},function(e,t){"use strict"
function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),r={},o=function(){function e(){
n(this,e)}return i(e,[{key:"add",value:function t(e,n){if("undefined"!=typeof r[e])throw new Error("Reducer already exists at '"+e+"'")
r[e]=n}},{key:"getAll",value:function o(){return r}},{key:"getByKey",value:function a(e){return r[e]}},{key:"remove",value:function s(e){delete r[e]}}]),e}()
window.ss=window.ss||{},window.ss.reducerRegister=window.ss.reducerRegister||new o,t["default"]=window.ss.reducerRegister},function(e,t,n){(function(t){e.exports=t.ReactRouteRegister=n(73)}).call(t,function(){
return this}())},function(e,t){"use strict"
function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var i=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},r=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),o=function(){function e(){
n(this,e),this.reset()}return r(e,[{key:"reset",value:function t(){var e=this
this.childRoutes=[],this.rootRoute={path:"/",getChildRoutes:function t(n,i){i(null,e.childRoutes)}}}},{key:"updateRootRoute",value:function o(e){this.rootRoute=i({},this.rootRoute,e)}},{key:"add",value:function a(e){
var t=arguments.length<=1||void 0===arguments[1]?[]:arguments[1],n=this.findChildRoute(t),r=i({},{childRoutes:[]},e),o=r.childRoutes[r.childRoutes.length-1]
o&&"**"===o.path||(o={path:"**"},r.childRoutes.push(o))
var a=n.findIndex(function(t){return t.path===e.path})
a>=0?n[a]=r:n.unshift(r)}},{key:"findChildRoute",value:function s(e){var t=this.childRoutes
return e&&e.forEach(function(e){var n=t.find(function(t){return t.path===e})
if(!n)throw new Error("Parent path "+e+" could not be found.")
t=n.childRoutes}),t}},{key:"getRootRoute",value:function l(){return this.rootRoute}},{key:"getChildRoutes",value:function u(){return this.childRoutes}},{key:"remove",value:function d(e){var t=arguments.length<=1||void 0===arguments[1]?[]:arguments[1],n=this.findChildRoute(t),i=n.findIndex(function(t){
return t.path===e})
return i<0?null:n.splice(i,1)[0]}}]),e}()
window.ss=window.ss||{},window.ss.routeRegister=window.ss.routeRegister||new o,t["default"]=window.ss.routeRegister},function(e,t,n){(function(t){e.exports=t.Injector=n(34)}).call(t,function(){return this

}())},function(e,t,n){(function(t){e.exports=t.Router=n(76)}).call(t,function(){return this}())},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e){var t=d["default"].getAbsoluteBase(),n=f["default"].resolve(t,e)
return 0!==n.indexOf(t)?n:n.substring(t.length-1)}function o(e){return function(t,n,i,r){return e(d["default"].resolveURLToBase(t),n,i,r)}}function a(e){var t=new d["default"].Route(e)
return t.match(d["default"].current,{})}function s(){return d["default"].absoluteBaseURL}function l(e){d["default"].absoluteBaseURL=e
var t=document.createElement("a")
t.href=e
var n=t.pathname
n=n.replace(/\/$/,""),n.match(/^[^\/]/)&&(n="/"+n),d["default"].base(n)}Object.defineProperty(t,"__esModule",{value:!0})
var u=n(77),d=i(u),c=n(78),f=i(c)
d["default"].oldshow||(d["default"].oldshow=d["default"].show),d["default"].setAbsoluteBase=l.bind(d["default"]),d["default"].getAbsoluteBase=s.bind(d["default"]),d["default"].resolveURLToBase=r.bind(d["default"]),
d["default"].show=o(d["default"].oldshow),d["default"].routeAppliesToCurrentLocation=a,window.ss=window.ss||{},window.ss.router=window.ss.router||d["default"],t["default"]=window.ss.router},function(e,t){
e.exports=Page},function(e,t,n){"use strict"
function i(){this.protocol=null,this.slashes=null,this.auth=null,this.host=null,this.port=null,this.hostname=null,this.hash=null,this.search=null,this.query=null,this.pathname=null,this.path=null,this.href=null

}function r(e,t,n){if(e&&u.isObject(e)&&e instanceof i)return e
var r=new i
return r.parse(e,t,n),r}function o(e){return u.isString(e)&&(e=r(e)),e instanceof i?e.format():i.prototype.format.call(e)}function a(e,t){return r(e,!1,!0).resolve(t)}function s(e,t){return e?r(e,!1,!0).resolveObject(t):t

}var l=n(79),u=n(80)
t.parse=r,t.resolve=a,t.resolveObject=s,t.format=o,t.Url=i
var d=/^([a-z0-9.+-]+:)/i,c=/:[0-9]*$/,f=/^(\/\/?(?!\/)[^\?\s]*)(\?[^\s]*)?$/,p=["<",">",'"',"`"," ","\r","\n","\t"],h=["{","}","|","\\","^","`"].concat(p),m=["'"].concat(h),g=["%","/","?",";","#"].concat(m),v=["/","?","#"],y=255,b=/^[+a-z0-9A-Z_-]{0,63}$/,w=/^([+a-z0-9A-Z_-]{0,63})(.*)$/,_={
javascript:!0,"javascript:":!0},C={javascript:!0,"javascript:":!0},T={http:!0,https:!0,ftp:!0,gopher:!0,file:!0,"http:":!0,"https:":!0,"ftp:":!0,"gopher:":!0,"file:":!0},E=n(81)
i.prototype.parse=function(e,t,n){if(!u.isString(e))throw new TypeError("Parameter 'url' must be a string, not "+typeof e)
var i=e.indexOf("?"),r=i!==-1&&i<e.indexOf("#")?"?":"#",o=e.split(r),a=/\\/g
o[0]=o[0].replace(a,"/"),e=o.join(r)
var s=e
if(s=s.trim(),!n&&1===e.split("#").length){var c=f.exec(s)
if(c)return this.path=s,this.href=s,this.pathname=c[1],c[2]?(this.search=c[2],t?this.query=E.parse(this.search.substr(1)):this.query=this.search.substr(1)):t&&(this.search="",this.query={}),this}var p=d.exec(s)


if(p){p=p[0]
var h=p.toLowerCase()
this.protocol=h,s=s.substr(p.length)}if(n||p||s.match(/^\/\/[^@\/]+@[^@\/]+/)){var P="//"===s.substr(0,2)
!P||p&&C[p]||(s=s.substr(2),this.slashes=!0)}if(!C[p]&&(P||p&&!T[p])){for(var O=-1,S=0;S<v.length;S++){var k=s.indexOf(v[S])
k!==-1&&(O===-1||k<O)&&(O=k)}var j,x
x=O===-1?s.lastIndexOf("@"):s.lastIndexOf("@",O),x!==-1&&(j=s.slice(0,x),s=s.slice(x+1),this.auth=decodeURIComponent(j)),O=-1
for(var S=0;S<g.length;S++){var k=s.indexOf(g[S])
k!==-1&&(O===-1||k<O)&&(O=k)}O===-1&&(O=s.length),this.host=s.slice(0,O),s=s.slice(O),this.parseHost(),this.hostname=this.hostname||""
var R="["===this.hostname[0]&&"]"===this.hostname[this.hostname.length-1]
if(!R)for(var I=this.hostname.split(/\./),S=0,F=I.length;S<F;S++){var A=I[S]
if(A&&!A.match(b)){for(var D="",M=0,N=A.length;M<N;M++)D+=A.charCodeAt(M)>127?"x":A[M]
if(!D.match(b)){var U=I.slice(0,S),L=I.slice(S+1),B=A.match(w)
B&&(U.push(B[1]),L.unshift(B[2])),L.length&&(s="/"+L.join(".")+s),this.hostname=U.join(".")
break}}}this.hostname.length>y?this.hostname="":this.hostname=this.hostname.toLowerCase(),R||(this.hostname=l.toASCII(this.hostname))
var H=this.port?":"+this.port:"",$=this.hostname||""
this.host=$+H,this.href+=this.host,R&&(this.hostname=this.hostname.substr(1,this.hostname.length-2),"/"!==s[0]&&(s="/"+s))}if(!_[h])for(var S=0,F=m.length;S<F;S++){var V=m[S]
if(s.indexOf(V)!==-1){var q=encodeURIComponent(V)
q===V&&(q=escape(V)),s=s.split(V).join(q)}}var G=s.indexOf("#")
G!==-1&&(this.hash=s.substr(G),s=s.slice(0,G))
var W=s.indexOf("?")
if(W!==-1?(this.search=s.substr(W),this.query=s.substr(W+1),t&&(this.query=E.parse(this.query)),s=s.slice(0,W)):t&&(this.search="",this.query={}),s&&(this.pathname=s),T[h]&&this.hostname&&!this.pathname&&(this.pathname="/"),
this.pathname||this.search){var H=this.pathname||"",z=this.search||""
this.path=H+z}return this.href=this.format(),this},i.prototype.format=function(){var e=this.auth||""
e&&(e=encodeURIComponent(e),e=e.replace(/%3A/i,":"),e+="@")
var t=this.protocol||"",n=this.pathname||"",i=this.hash||"",r=!1,o=""
this.host?r=e+this.host:this.hostname&&(r=e+(this.hostname.indexOf(":")===-1?this.hostname:"["+this.hostname+"]"),this.port&&(r+=":"+this.port)),this.query&&u.isObject(this.query)&&Object.keys(this.query).length&&(o=E.stringify(this.query))


var a=this.search||o&&"?"+o||""
return t&&":"!==t.substr(-1)&&(t+=":"),this.slashes||(!t||T[t])&&r!==!1?(r="//"+(r||""),n&&"/"!==n.charAt(0)&&(n="/"+n)):r||(r=""),i&&"#"!==i.charAt(0)&&(i="#"+i),a&&"?"!==a.charAt(0)&&(a="?"+a),n=n.replace(/[?#]/g,function(e){
return encodeURIComponent(e)}),a=a.replace("#","%23"),t+r+n+a+i},i.prototype.resolve=function(e){return this.resolveObject(r(e,!1,!0)).format()},i.prototype.resolveObject=function(e){if(u.isString(e)){
var t=new i
t.parse(e,!1,!0),e=t}for(var n=new i,r=Object.keys(this),o=0;o<r.length;o++){var a=r[o]
n[a]=this[a]}if(n.hash=e.hash,""===e.href)return n.href=n.format(),n
if(e.slashes&&!e.protocol){for(var s=Object.keys(e),l=0;l<s.length;l++){var d=s[l]
"protocol"!==d&&(n[d]=e[d])}return T[n.protocol]&&n.hostname&&!n.pathname&&(n.path=n.pathname="/"),n.href=n.format(),n}if(e.protocol&&e.protocol!==n.protocol){if(!T[e.protocol]){for(var c=Object.keys(e),f=0;f<c.length;f++){
var p=c[f]
n[p]=e[p]}return n.href=n.format(),n}if(n.protocol=e.protocol,e.host||C[e.protocol])n.pathname=e.pathname
else{for(var h=(e.pathname||"").split("/");h.length&&!(e.host=h.shift()););e.host||(e.host=""),e.hostname||(e.hostname=""),""!==h[0]&&h.unshift(""),h.length<2&&h.unshift(""),n.pathname=h.join("/")}if(n.search=e.search,
n.query=e.query,n.host=e.host||"",n.auth=e.auth,n.hostname=e.hostname||e.host,n.port=e.port,n.pathname||n.search){var m=n.pathname||"",g=n.search||""
n.path=m+g}return n.slashes=n.slashes||e.slashes,n.href=n.format(),n}var v=n.pathname&&"/"===n.pathname.charAt(0),y=e.host||e.pathname&&"/"===e.pathname.charAt(0),b=y||v||n.host&&e.pathname,w=b,_=n.pathname&&n.pathname.split("/")||[],h=e.pathname&&e.pathname.split("/")||[],E=n.protocol&&!T[n.protocol]


if(E&&(n.hostname="",n.port=null,n.host&&(""===_[0]?_[0]=n.host:_.unshift(n.host)),n.host="",e.protocol&&(e.hostname=null,e.port=null,e.host&&(""===h[0]?h[0]=e.host:h.unshift(e.host)),e.host=null),b=b&&(""===h[0]||""===_[0])),
y)n.host=e.host||""===e.host?e.host:n.host,n.hostname=e.hostname||""===e.hostname?e.hostname:n.hostname,n.search=e.search,n.query=e.query,_=h
else if(h.length)_||(_=[]),_.pop(),_=_.concat(h),n.search=e.search,n.query=e.query
else if(!u.isNullOrUndefined(e.search)){if(E){n.hostname=n.host=_.shift()
var P=!!(n.host&&n.host.indexOf("@")>0)&&n.host.split("@")
P&&(n.auth=P.shift(),n.host=n.hostname=P.shift())}return n.search=e.search,n.query=e.query,u.isNull(n.pathname)&&u.isNull(n.search)||(n.path=(n.pathname?n.pathname:"")+(n.search?n.search:"")),n.href=n.format(),
n}if(!_.length)return n.pathname=null,n.search?n.path="/"+n.search:n.path=null,n.href=n.format(),n
for(var O=_.slice(-1)[0],S=(n.host||e.host||_.length>1)&&("."===O||".."===O)||""===O,k=0,j=_.length;j>=0;j--)O=_[j],"."===O?_.splice(j,1):".."===O?(_.splice(j,1),k++):k&&(_.splice(j,1),k--)
if(!b&&!w)for(;k--;k)_.unshift("..")
!b||""===_[0]||_[0]&&"/"===_[0].charAt(0)||_.unshift(""),S&&"/"!==_.join("/").substr(-1)&&_.push("")
var x=""===_[0]||_[0]&&"/"===_[0].charAt(0)
if(E){n.hostname=n.host=x?"":_.length?_.shift():""
var P=!!(n.host&&n.host.indexOf("@")>0)&&n.host.split("@")
P&&(n.auth=P.shift(),n.host=n.hostname=P.shift())}return b=b||n.host&&_.length,b&&!x&&_.unshift(""),_.length?n.pathname=_.join("/"):(n.pathname=null,n.path=null),u.isNull(n.pathname)&&u.isNull(n.search)||(n.path=(n.pathname?n.pathname:"")+(n.search?n.search:"")),
n.auth=e.auth||n.auth,n.slashes=n.slashes||e.slashes,n.href=n.format(),n},i.prototype.parseHost=function(){var e=this.host,t=c.exec(e)
t&&(t=t[0],":"!==t&&(this.port=t.substr(1)),e=e.substr(0,e.length-t.length)),e&&(this.hostname=e)}},function(e,t,n){var i;(function(e,r){!function(o){function a(e){throw RangeError(A[e])}function s(e,t){
for(var n=e.length,i=[];n--;)i[n]=t(e[n])
return i}function l(e,t){var n=e.split("@"),i=""
n.length>1&&(i=n[0]+"@",e=n[1]),e=e.replace(F,".")
var r=e.split("."),o=s(r,t).join(".")
return i+o}function u(e){for(var t=[],n=0,i=e.length,r,o;n<i;)r=e.charCodeAt(n++),r>=55296&&r<=56319&&n<i?(o=e.charCodeAt(n++),56320==(64512&o)?t.push(((1023&r)<<10)+(1023&o)+65536):(t.push(r),n--)):t.push(r)


return t}function d(e){return s(e,function(e){var t=""
return e>65535&&(e-=65536,t+=N(e>>>10&1023|55296),e=56320|1023&e),t+=N(e)}).join("")}function c(e){return e-48<10?e-22:e-65<26?e-65:e-97<26?e-97:T}function f(e,t){return e+22+75*(e<26)-((0!=t)<<5)}function p(e,t,n){
var i=0
for(e=n?M(e/S):e>>1,e+=M(e/t);e>D*P>>1;i+=T)e=M(e/D)
return M(i+(D+1)*e/(e+O))}function h(e){var t=[],n=e.length,i,r=0,o=j,s=k,l,u,f,h,m,g,v,y,b
for(l=e.lastIndexOf(x),l<0&&(l=0),u=0;u<l;++u)e.charCodeAt(u)>=128&&a("not-basic"),t.push(e.charCodeAt(u))
for(f=l>0?l+1:0;f<n;){for(h=r,m=1,g=T;f>=n&&a("invalid-input"),v=c(e.charCodeAt(f++)),(v>=T||v>M((C-r)/m))&&a("overflow"),r+=v*m,y=g<=s?E:g>=s+P?P:g-s,!(v<y);g+=T)b=T-y,m>M(C/b)&&a("overflow"),m*=b
i=t.length+1,s=p(r-h,i,0==h),M(r/i)>C-o&&a("overflow"),o+=M(r/i),r%=i,t.splice(r++,0,o)}return d(t)}function m(e){var t,n,i,r,o,s,l,d,c,h,m,g=[],v,y,b,w
for(e=u(e),v=e.length,t=j,n=0,o=k,s=0;s<v;++s)m=e[s],m<128&&g.push(N(m))
for(i=r=g.length,r&&g.push(x);i<v;){for(l=C,s=0;s<v;++s)m=e[s],m>=t&&m<l&&(l=m)
for(y=i+1,l-t>M((C-n)/y)&&a("overflow"),n+=(l-t)*y,t=l,s=0;s<v;++s)if(m=e[s],m<t&&++n>C&&a("overflow"),m==t){for(d=n,c=T;h=c<=o?E:c>=o+P?P:c-o,!(d<h);c+=T)w=d-h,b=T-h,g.push(N(f(h+w%b,0))),d=M(w/b)
g.push(N(f(d,0))),o=p(n,y,i==r),n=0,++i}++n,++t}return g.join("")}function g(e){return l(e,function(e){return R.test(e)?h(e.slice(4).toLowerCase()):e})}function v(e){return l(e,function(e){return I.test(e)?"xn--"+m(e):e

})}var y="object"==typeof t&&t&&!t.nodeType&&t,b="object"==typeof e&&e&&!e.nodeType&&e,w="object"==typeof r&&r
w.global!==w&&w.window!==w&&w.self!==w||(o=w)
var _,C=2147483647,T=36,E=1,P=26,O=38,S=700,k=72,j=128,x="-",R=/^xn--/,I=/[^\x20-\x7E]/,F=/[\x2E\u3002\uFF0E\uFF61]/g,A={overflow:"Overflow: input needs wider integers to process","not-basic":"Illegal input >= 0x80 (not a basic code point)",
"invalid-input":"Invalid input"},D=T-E,M=Math.floor,N=String.fromCharCode,U
_={version:"1.3.2",ucs2:{decode:u,encode:d},decode:h,encode:m,toASCII:v,toUnicode:g},i=function(){return _}.call(t,n,t,e),!(void 0!==i&&(e.exports=i))}(this)}).call(t,n(17)(e),function(){return this}())

},function(e,t){"use strict"
e.exports={isString:function(e){return"string"==typeof e},isObject:function(e){return"object"==typeof e&&null!==e},isNull:function(e){return null===e},isNullOrUndefined:function(e){return null==e}}},function(e,t,n){
"use strict"
t.decode=t.parse=n(82),t.encode=t.stringify=n(83)},function(e,t){"use strict"
function n(e,t){return Object.prototype.hasOwnProperty.call(e,t)}e.exports=function(e,t,i,r){t=t||"&",i=i||"="
var o={}
if("string"!=typeof e||0===e.length)return o
var a=/\+/g
e=e.split(t)
var s=1e3
r&&"number"==typeof r.maxKeys&&(s=r.maxKeys)
var l=e.length
s>0&&l>s&&(l=s)
for(var u=0;u<l;++u){var d=e[u].replace(a,"%20"),c=d.indexOf(i),f,p,h,m
c>=0?(f=d.substr(0,c),p=d.substr(c+1)):(f=d,p=""),h=decodeURIComponent(f),m=decodeURIComponent(p),n(o,h)?Array.isArray(o[h])?o[h].push(m):o[h]=[o[h],m]:o[h]=m}return o}},function(e,t){"use strict"
var n=function(e){switch(typeof e){case"string":return e
case"boolean":return e?"true":"false"
case"number":return isFinite(e)?e:""
default:return""}}
e.exports=function(e,t,i,r){return t=t||"&",i=i||"=",null===e&&(e=void 0),"object"==typeof e?Object.keys(e).map(function(r){var o=encodeURIComponent(n(r))+i
return Array.isArray(e[r])?e[r].map(function(e){return o+encodeURIComponent(n(e))}).join(t):o+encodeURIComponent(n(e[r]))}).join(t):r?encodeURIComponent(n(r))+i+encodeURIComponent(n(e)):""}},function(e,t){
"use strict"
function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var i=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),r=function(){function e(){
n(this,e),this.defaultLocale="en_US",this.currentLocale=this.detectLocale(),this.lang={}}return i(e,[{key:"setLocale",value:function t(e){this.currentLocale=e}},{key:"getLocale",value:function r(){return null!==this.currentLocale?this.currentLocale:this.defaultLocale

}},{key:"_t",value:function o(e,t,n,i){var r=this.getLocale().replace(/_[\w]+/i,""),o=this.defaultLocale.replace(/_[\w]+/i,"")
return this.lang&&this.lang[this.getLocale()]&&this.lang[this.getLocale()][e]?this.lang[this.getLocale()][e]:this.lang&&this.lang[r]&&this.lang[r][e]?this.lang[r][e]:this.lang&&this.lang[this.defaultLocale]&&this.lang[this.defaultLocale][e]?this.lang[this.defaultLocale][e]:this.lang&&this.lang[o]&&this.lang[o][e]?this.lang[o][e]:t?t:""

}},{key:"addDictionary",value:function a(e,t){"undefined"==typeof this.lang[e]&&(this.lang[e]={})
for(var n in t)this.lang[e][n]=t[n]}},{key:"getDictionary",value:function s(e){return this.lang[e]}},{key:"stripStr",value:function l(e){return e.replace(/^\s*/,"").replace(/\s*$/,"")}},{key:"stripStrML",
value:function u(e){for(var t=e.split("\n"),n=0;n<t.length;n+=1)t[n]=stripStr(t[n])
return stripStr(t.join(" "))}},{key:"sprintf",value:function d(e){for(var t=arguments.length,n=Array(t>1?t-1:0),i=1;i<t;i++)n[i-1]=arguments[i]
if(0===n.length)return e
var r=new RegExp("(.?)(%s)","g"),o=0
return e.replace(r,function(e,t,i,r,a){return"%"===t?e:t+n[o++]})}},{key:"inject",value:function c(e,t){var n=new RegExp("{([A-Za-z0-9_]*)}","g")
return e.replace(n,function(e,n,i,r){return t[n]?t[n]:e})}},{key:"detectLocale",value:function f(){var t,n
if(t=document.body.getAttribute("lang"),!t)for(var i=document.getElementsByTagName("meta"),r=0;r<i.length;r++)i[r].attributes["http-equiv"]&&"content-language"==i[r].attributes["http-equiv"].nodeValue.toLowerCase()&&(t=i[r].attributes.content.nodeValue)


t||(t=this.defaultLocale)
var o=t.match(/([^-|_]*)[-|_](.*)/)
if(2==t.length){for(var a in e.lang)if(a.substr(0,2).toLowerCase()==t.toLowerCase()){n=a
break}}else o&&(n=o[1].toLowerCase()+"_"+o[2].toUpperCase())
return n}},{key:"addEvent",value:function p(e,t,n,i){return e.addEventListener?(e.addEventListener(t,n,i),!0):e.attachEvent?e.attachEvent("on"+t,n):void console.log("Handler could not be attached")}}]),
e}(),o=new r
window.ss="undefined"!=typeof window.ss?window.ss:{},window.ss.i18n=window.i18n=o,t["default"]=o},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r),a=(0,o["default"])(window),s=(0,o["default"])("html"),l=(0,o["default"])("head"),u={urlParseRE:/^(((([^:\/#\?]+:)?(?:(\/\/)((?:(([^:@\/#\?]+)(?:\:([^:@\/#\?]+))?)@)?(([^:\/#\?\]\[]+|\[[^\/\]@#?]+\])(?:\:([0-9]+))?))?)?)?((\/?(?:[^\/\?#]+\/+)*)([^\?#]*)))?(\?[^#]+)?)(#.*)?/,
parseUrl:function d(e){if("object"===o["default"].type(e))return e
var t=u.urlParseRE.exec(e||"")||[]
return{href:t[0]||"",hrefNoHash:t[1]||"",hrefNoSearch:t[2]||"",domain:t[3]||"",protocol:t[4]||"",doubleSlash:t[5]||"",authority:t[6]||"",username:t[8]||"",password:t[9]||"",host:t[10]||"",hostname:t[11]||"",
port:t[12]||"",pathname:t[13]||"",directory:t[14]||"",filename:t[15]||"",search:t[16]||"",hash:t[17]||""}},makePathAbsolute:function c(e,t){if(e&&"/"===e.charAt(0))return e
e=e||"",t=t?t.replace(/^\/|(\/[^\/]*|[^\/]+)$/g,""):""
for(var n=t?t.split("/"):[],i=e.split("/"),r=0;r<i.length;r++){var o=i[r]
switch(o){case".":break
case"..":n.length&&n.pop()
break
default:n.push(o)}}return"/"+n.join("/")},isSameDomain:function f(e,t){return u.parseUrl(e).domain===u.parseUrl(t).domain},isRelativeUrl:function p(e){return""===u.parseUrl(e).protocol},isAbsoluteUrl:function h(e){
return""!==u.parseUrl(e).protocol},makeUrlAbsolute:function m(e,t){if(!u.isRelativeUrl(e))return e
var n=u.parseUrl(e),i=u.parseUrl(t),r=n.protocol||i.protocol,o=n.protocol?n.doubleSlash:n.doubleSlash||i.doubleSlash,a=n.authority||i.authority,s=""!==n.pathname,l=u.makePathAbsolute(n.pathname||i.filename,i.pathname),d=n.search||!s&&i.search||"",c=n.hash


return r+o+a+l+d+c},addSearchParams:function g(e,t){var n=u.parseUrl(e),t="string"==typeof t?u.convertSearchToArray(t):t,i=o["default"].extend(u.convertSearchToArray(n.search),t)
return n.hrefNoSearch+"?"+o["default"].param(i)+(n.hash||"")},getSearchParams:function v(e){var t=u.parseUrl(e)
return u.convertSearchToArray(t.search)},convertSearchToArray:function y(e){var t,n,i,r={}
for(e=e.replace(/^\?/,""),t=e?e.split("&"):[],n=0;n<t.length;n++)i=t[n].split("="),r[decodeURIComponent(i[0])]=decodeURIComponent(i[1])
return r},convertUrlToDataUrl:function b(e){var t=u.parseUrl(e)
return u.isEmbeddedPage(t)?t.hash.split(dialogHashKey)[0].replace(/^#/,""):u.isSameDomain(t,document)?t.hrefNoHash.replace(document.domain,""):e},get:function w(e){return void 0===e&&(e=location.hash),
u.stripHash(e).replace(/[^\/]*\.[^\/*]+$/,"")},getFilePath:function _(e){var t="&"+o["default"].mobile.subPageUrlKey
return e&&e.split(t)[0].split(dialogHashKey)[0]},set:function C(e){location.hash=e},isPath:function T(e){return/\//.test(e)},clean:function E(e){return e.replace(document.domain,"")},stripHash:function P(e){
return e.replace(/^#/,"")},cleanHash:function O(e){return u.stripHash(e.replace(/\?.*$/,"").replace(dialogHashKey,""))},isExternal:function S(e){var t=u.parseUrl(e)
return!(!t.protocol||t.domain===document.domain)},hasProtocol:function k(e){return/^(:?\w+:)/.test(e)}}
o["default"].path=u},function(e,t,n){(function(e){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var i=n(1),r=t(i)
n(87),r["default"].widget("ssui.button",r["default"].ui.button,{options:{alternate:{icon:null,text:null},showingAlternate:!1},toggleAlternate:function o(){this._trigger("ontogglealternate")!==!1&&(this.options.alternate.icon||this.options.alternate.text)&&(this.options.showingAlternate=!this.options.showingAlternate,
this.refresh())},_refreshAlternate:function a(){this._trigger("beforerefreshalternate"),(this.options.alternate.icon||this.options.alternate.text)&&(this.options.showingAlternate?(this.element.find(".ui-button-icon-primary").hide(),
this.element.find(".ui-button-text").hide(),this.element.find(".ui-button-icon-alternate").show(),this.element.find(".ui-button-text-alternate").show()):(this.element.find(".ui-button-icon-primary").show(),
this.element.find(".ui-button-text").show(),this.element.find(".ui-button-icon-alternate").hide(),this.element.find(".ui-button-text-alternate").hide()),this._trigger("afterrefreshalternate"))},_resetButton:function s(){
var e=this.element.data("icon-primary"),t=this.element.data("icon-secondary")
e||(e=this.element.data("icon")),e&&(this.options.icons.primary="btn-icon-"+e),t&&(this.options.icons.secondary="btn-icon-"+t),r["default"].ui.button.prototype._resetButton.call(this),this.options.alternate.text||(this.options.alternate.text=this.element.data("text-alternate")),
this.options.alternate.icon||(this.options.alternate.icon=this.element.data("icon-alternate")),this.options.showingAlternate||(this.options.showingAlternate=this.element.hasClass("ss-ui-alternate")),this.options.alternate.icon&&this.buttonElement.append("<span class='ui-button-icon-alternate ui-button-icon-primary ui-icon btn-icon-"+this.options.alternate.icon+"'></span>"),
this.options.alternate.text&&this.buttonElement.append("<span class='ui-button-text-alternate ui-button-text'>"+this.options.alternate.text+"</span>"),this._refreshAlternate()},refresh:function l(){r["default"].ui.button.prototype.refresh.call(this),
this._refreshAlternate()},destroy:function u(){this.element.find(".ui-button-text-alternate").remove(),this.element.find(".ui-button-icon-alternate").remove(),r["default"].ui.button.prototype.destroy.call(this)

}}),r["default"].widget("ssui.ssdialog",r["default"].ui.dialog,{options:{iframeUrl:"",reloadOnOpen:!0,dialogExtraClass:"",modal:!0,bgiframe:!0,autoOpen:!1,autoPosition:!0,minWidth:500,maxWidth:800,minHeight:300,
maxHeight:700,widthRatio:.8,heightRatio:.8,resizable:!1},_create:function d(){r["default"].ui.dialog.prototype._create.call(this)
var e=this,t=(0,r["default"])('<iframe marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto"></iframe>')
t.bind("load",function(n){"about:blank"!=(0,r["default"])(this).attr("src")&&(t.addClass("loaded").show(),e._resizeIframe(),e.uiDialog.removeClass("loading"))}).hide(),this.options.dialogExtraClass&&this.uiDialog.addClass(this.options.dialogExtraClass),
this.element.append(t),this.options.iframeUrl&&this.element.css("overflow","hidden")},open:function c(){r["default"].ui.dialog.prototype.open.call(this)
var e=this,t=this.element.children("iframe")
!this.options.iframeUrl||t.hasClass("loaded")&&!this.options.reloadOnOpen||(t.hide(),t.attr("src",this.options.iframeUrl),this.uiDialog.addClass("loading")),(0,r["default"])(window).bind("resize.ssdialog",function(){
e._resizeIframe()})},close:function f(){r["default"].ui.dialog.prototype.close.call(this),this.uiDialog.unbind("resize.ssdialog"),(0,r["default"])(window).unbind("resize.ssdialog")},_resizeIframe:function p(){
var t={},n,i,o=this.element.children("iframe")
this.options.widthRatio&&(n=(0,r["default"])(window).width()*this.options.widthRatio,this.options.minWidth&&n<this.options.minWidth?t.width=this.options.minWidth:this.options.maxWidth&&n>this.options.maxWidth?t.width=this.options.maxWidth:t.width=n),
this.options.heightRatio&&(i=(0,r["default"])(window).height()*this.options.heightRatio,this.options.minHeight&&i<this.options.minHeight?t.height=this.options.minHeight:this.options.maxHeight&&i>this.options.maxHeight?t.height=this.options.maxHeight:t.height=i),
e.isEmptyObject(t)||(this._setOptions(t),o.attr("width",t.width-parseFloat(this.element.css("paddingLeft"))-parseFloat(this.element.css("paddingRight"))),o.attr("height",t.height-parseFloat(this.element.css("paddingTop"))-parseFloat(this.element.css("paddingBottom"))),
this.options.autoPosition&&this._setOption("position",this.options.position))}}),r["default"].widget("ssui.titlebar",{_create:function h(){this.originalTitle=this.element.attr("title")
var e=this,t=this.options,n=t.title||this.originalTitle||"&nbsp;",i=r["default"].ui.dialog.getTitleId(this.element)
this.element.parent().addClass("ui-dialog")
var o=this.element.addClass("ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix")
if(t.closeButton)var a=(0,r["default"])('<a href="#"/>').addClass("ui-dialog-titlebar-close ui-corner-all").attr("role","button").hover(function(){a.addClass("ui-state-hover")},function(){a.removeClass("ui-state-hover")

}).focus(function(){a.addClass("ui-state-focus")}).blur(function(){a.removeClass("ui-state-focus")}).mousedown(function(e){e.stopPropagation()}).appendTo(o),s=(this.uiDialogTitlebarCloseText=(0,r["default"])("<span/>")).addClass("ui-icon ui-icon-closethick").text(t.closeText).appendTo(a)


var l=(0,r["default"])("<span/>").addClass("ui-dialog-title").attr("id",i).html(n).prependTo(o)
o.find("*").add(o).disableSelection()},destroy:function m(){this.element.unbind(".dialog").removeData("dialog").removeClass("ui-dialog-content ui-widget-content").hide().appendTo("body"),this.originalTitle&&this.element.attr("title",this.originalTitle)

}}),r["default"].extend(r["default"].ssui.titlebar,{version:"0.0.1",options:{title:"",closeButton:!1,closeText:"close"},uuid:0,getTitleId:function g(e){return"ui-dialog-title-"+(e.attr("id")||++this.uuid)

}})}).call(t,n(1))},,function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].fn.layout.defaults.resize=!1,jLayout="undefined"==typeof jLayout?{}:jLayout,jLayout.threeColumnCompressor=function(e,t){function n(e){var t=e+"Size"
return function(e){var n=r[t](),i=a[t](),o=s[t](),l=e.insets()
return width=n.width+i.width+o.width,height=Math.max(n.height,i.height,o.height),{width:l.left+l.right+width,height:l.top+l.bottom+height}}}if("undefined"==typeof e.menu||"undefined"==typeof e.content||"undefined"==typeof e.preview)throw'Spec is invalid. Please provide "menu", "content" and "preview" elements.'


if("undefined"==typeof t.minContentWidth||"undefined"==typeof t.minPreviewWidth||"undefined"==typeof t.mode)throw'Spec is invalid. Please provide "minContentWidth", "minPreviewWidth", "mode"'
if("split"!==t.mode&&"content"!==t.mode&&"preview"!==t.mode)throw'Spec is invalid. "mode" should be either "split", "content" or "preview"'
var i={options:t},r=o["default"].jLayoutWrap(e.menu),a=o["default"].jLayoutWrap(e.content),s=o["default"].jLayoutWrap(e.preview)
return i.layout=function(n){var i=n.bounds(),o=n.insets(),l=o.top,u=i.height-o.bottom,d=o.left,c=i.width-o.right,f=e.menu.width(),p=0,h=0
"preview"===this.options.mode?(p=0,h=c-d-f):"content"===this.options.mode?(p=c-d-f,h=0):(p=(c-d-f)/2,h=c-d-(f+p),p<this.options.minContentWidth?(p=this.options.minContentWidth,h=c-d-(f+p)):h<this.options.minPreviewWidth&&(h=this.options.minPreviewWidth,
p=c-d-(f+h)),(p<this.options.minContentWidth||h<this.options.minPreviewWidth)&&(p=c-d-f,h=0))
var m={content:e.content.hasClass("column-hidden"),preview:e.preview.hasClass("column-hidden")},g={content:0===p,preview:0===h}
return e.content.toggleClass("column-hidden",g.content),e.preview.toggleClass("column-hidden",g.preview),r.bounds({x:d,y:l,height:u-l,width:f}),r.doLayout(),d+=f,a.bounds({x:d,y:l,height:u-l,width:p}),
g.content||a.doLayout(),d+=p,s.bounds({x:d,y:l,height:u-l,width:h}),g.preview||s.doLayout(),g.content!==m.content&&e.content.trigger("columnvisibilitychanged"),g.preview!==m.preview&&e.preview.trigger("columnvisibilitychanged"),
p+h<t.minContentWidth+t.minPreviewWidth?e.preview.trigger("disable"):e.preview.trigger("enable"),n},i.preferred=n("preferred"),i.minimum=n("minimum"),i.maximum=n("maximum"),i}},function(module,exports,__webpack_require__){
(function(jQuery){"use strict"
function _interopRequireDefault(e){return e&&e.__esModule?e:{"default":e}}var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol?"symbol":typeof e

},_jQuery=__webpack_require__(1),_jQuery2=_interopRequireDefault(_jQuery)
__webpack_require__(86)
var windowWidth,windowHeight
_jQuery2["default"].noConflict(),window.ss=window.ss||{},window.ss.debounce=function(e,t,n){var i,r,o,a=function s(){i=null,n||e.apply(r,o)}
return function(){var s=n&&!i
r=this,o=arguments,clearTimeout(i),i=setTimeout(a,t),s&&e.apply(r,o)}},(0,_jQuery2["default"])(window).bind("resize.leftandmain",function(e){(0,_jQuery2["default"])(".cms-container").trigger("windowresize")

}),_jQuery2["default"].entwine.warningLevel=_jQuery2["default"].entwine.WARN_LEVEL_BESTPRACTISE,_jQuery2["default"].entwine("ss",function($){$(window).on("message",function(e){var t,n=e.originalEvent,i="object"===_typeof(n.data)?n.data:JSON.parse(n.data)


if($.path.parseUrl(window.location.href).domain===$.path.parseUrl(n.origin).domain)switch(t=$("undefined"==typeof i.target?window:i.target),i.type){case"event":t.trigger(i.event,i.data)
break
case"callback":t[i.callback].call(t,i.data)}})
var positionLoadingSpinner=function e(){var e=120,t=$(".ss-loading-screen .loading-animation"),n=($(window).height()-t.height())/2
t.css("top",n+e),t.show()},applyChosen=function t(e){e.is(":visible")?e.addClass("has-chosen").chosen({allow_single_deselect:!0,disable_search_threshold:20,display_disabled_options:!0}):setTimeout(function(){
e.show(),t(e)},500)},isSameUrl=function n(e,t){var n=$("base").attr("href")
e=$.path.isAbsoluteUrl(e)?e:$.path.makeUrlAbsolute(e,n),t=$.path.isAbsoluteUrl(t)?t:$.path.makeUrlAbsolute(t,n)
var i=$.path.parseUrl(e),r=$.path.parseUrl(t)
return i.pathname.replace(/\/*$/,"")==r.pathname.replace(/\/*$/,"")&&i.search==r.search},ajaxCompleteEvent=window.ss.debounce(function(){$(window).trigger("ajaxComplete")},1e3,!0)
$(window).bind("resize",positionLoadingSpinner).trigger("resize"),$(document).ajaxComplete(function(e,t,n){var i=document.URL,r=t.getResponseHeader("X-ControllerURL"),o=n.url,a=null!==t.getResponseHeader("X-Status")?t.getResponseHeader("X-Status"):t.statusText,s=t.status<200||t.status>399?"bad":"good",l=["OK","success","HTTP/2.0 200"]


return null===r||isSameUrl(i,r)&&isSameUrl(o,r)||window.ss.router.show(r,{id:(new Date).getTime()+String(Math.random()).replace(/\D/g,""),pjax:t.getResponseHeader("X-Pjax")?t.getResponseHeader("X-Pjax"):n.headers["X-Pjax"]
}),t.getResponseHeader("X-Reauthenticate")?void $(".cms-container").showLoginDialog():(0!==t.status&&a&&$.inArray(a,l)===-1&&statusMessage(decodeURIComponent(a),s),void ajaxCompleteEvent(this))}),$(".cms-container").entwine({
StateChangeXHR:null,FragmentXHR:{},StateChangeCount:0,LayoutOptions:{minContentWidth:940,minPreviewWidth:400,mode:"content"},onadd:function i(){return $.browser.msie&&parseInt($.browser.version,10)<8?($(".ss-loading-screen").append('<p class="ss-loading-incompat-warning"><span class="notice">Your browser is not compatible with the CMS interface. Please use Internet Explorer 8+, Google Chrome or Mozilla Firefox.</span></p>').css("z-index",$(".ss-loading-screen").css("z-index")+1),
$(".loading-animation").remove(),void this._super()):(this.redraw(),$(".ss-loading-screen").hide(),$("body").removeClass("loading"),$(window).unbind("resize",positionLoadingSpinner),this.restoreTabState(),
void this._super())},onwindowresize:function r(){this.redraw()},"from .cms-panel":{ontoggle:function o(){this.redraw()}},"from .cms-container":{onaftersubmitform:function a(){this.redraw()}},updateLayoutOptions:function s(e){
var t=this.getLayoutOptions(),n=!1
for(var i in e)t[i]!==e[i]&&(t[i]=e[i],n=!0)
n&&this.redraw()},splitViewMode:function l(){this.updateLayoutOptions({mode:"split"})},contentViewMode:function u(){this.updateLayoutOptions({mode:"content"})},previewMode:function d(){this.updateLayoutOptions({
mode:"preview"})},RedrawSuppression:!1,redraw:function c(){this.getRedrawSuppression()||(window.debug&&console.log("redraw",this.attr("class"),this.get(0)),this.data("jlayout",jLayout.threeColumnCompressor({
menu:this.children(".cms-menu"),content:this.children(".cms-content"),preview:this.children(".cms-preview")},this.getLayoutOptions())),this.layout(),this.find(".cms-panel-layout").redraw(),this.find(".cms-content-fields[data-layout-type]").redraw(),
this.find(".cms-edit-form[data-layout-type]").redraw(),this.find(".cms-preview").redraw(),this.find(".cms-content").redraw())},checkCanNavigate:function f(e){var t=this._findFragments(e||["Content"]),n=t.find(":data(changetracker)").add(t.filter(":data(changetracker)")),i=!0


return!n.length||(n.each(function(){$(this).confirmUnsavedChanges()||(i=!1)}),i)},loadPanel:function p(e){var t=arguments.length<=1||void 0===arguments[1]?"":arguments[1],n=arguments.length<=2||void 0===arguments[2]?{}:arguments[2],i=arguments[3],r=arguments.length<=4||void 0===arguments[4]?document.URL:arguments[4]


this.checkCanNavigate(n.pjax?n.pjax.split(","):["Content"])&&(this.saveTabState(),n.__forceReferer=r,i&&(n.__forceReload=1+Math.random()),window.ss.router.show(e,n))},reloadCurrentPanel:function h(){this.loadPanel(document.URL,null,null,!0)

},submitForm:function m(e,t,n,i){var r=this
t||(t=this.find(".btn-toolbar :submit[name=action_save]")),t||(t=this.find(".btn-toolbar :submit:first")),e.trigger("beforesubmitform"),this.trigger("submitform",{form:e,button:t}),$(t).addClass("loading")


var o=e.validate()
if("undefined"!=typeof o&&!o)return statusMessage("Validation failed.","bad"),$(t).removeClass("loading"),!1
var a=e.serializeArray()
return a.push({name:$(t).attr("name"),value:"1"}),a.push({name:"BackURL",value:document.URL.replace(/\/$/,"")}),this.saveTabState(),jQuery.ajax(jQuery.extend({headers:{"X-Pjax":"CurrentForm,Breadcrumbs"
},url:e.attr("action"),data:a,type:"POST",complete:function s(){$(t).removeClass("loading")},success:function l(t,i,o){e.removeClass("changed"),n&&n(t,i,o)
var s=r.handleAjaxResponse(t,i,o)
s&&s.filter("form").trigger("aftersubmitform",{status:i,xhr:o,formData:a})}},i)),!1},LastState:null,PauseState:!1,handleStateChange:function g(e){var t=arguments.length<=1||void 0===arguments[1]?window.history.state:arguments[1]


if(!this.getPauseState()){this.getStateChangeXHR()&&this.getStateChangeXHR().abort()
var n=this,i=t.pjax||"Content",r={},o=i.split(","),a=this._findFragments(o)
if(this.setStateChangeCount(this.getStateChangeCount()+1),!this.checkCanNavigate()){var s=this.getLastState()
return this.setPauseState(!0),s&&s.path?window.ss.router.show(s.path):window.ss.router.back(),void this.setPauseState(!1)}if(this.setLastState(t),a.length<o.length&&(i="Content",o=["Content"],a=this._findFragments(o)),
this.trigger("beforestatechange",{state:t,element:a}),r["X-Pjax"]=i,"undefined"!=typeof t.__forceReferer){var l=t.__forceReferer
try{l=decodeURI(l)}catch(u){}finally{r["X-Backurl"]=encodeURI(l)}}a.addClass("loading")
var d=$.ajax({headers:r,url:t.path||document.URL}).done(function(e,i,r){var o=n.handleAjaxResponse(e,i,r,t)
n.trigger("afterstatechange",{data:e,status:i,xhr:r,element:o,state:t})}).always(function(){n.setStateChangeXHR(null),a.removeClass("loading")})
return this.setStateChangeXHR(d),d}},loadFragment:function v(e,t){var n=this,i,r={},o=$("base").attr("href"),a=this.getFragmentXHR()
return"undefined"!=typeof a[t]&&null!==a[t]&&(a[t].abort(),a[t]=null),e=$.path.isAbsoluteUrl(e)?e:$.path.makeUrlAbsolute(e,o),r["X-Pjax"]=t,i=$.ajax({headers:r,url:e,success:function s(e,t,i){var r=n.handleAjaxResponse(e,t,i,null)


n.trigger("afterloadfragment",{data:e,status:t,xhr:i,elements:r})},error:function l(e,t,i){n.trigger("loadfragmenterror",{xhr:e,status:t,error:i})},complete:function u(){var e=n.getFragmentXHR()
"undefined"!=typeof e[t]&&null!==e[t]&&(e[t]=null)}}),a[t]=i,i},handleAjaxResponse:function y(e,t,n,i){var r=this,o,a,s,l,u
if(n.getResponseHeader("X-Reload")&&n.getResponseHeader("X-ControllerURL")){var d=$("base").attr("href"),c=n.getResponseHeader("X-ControllerURL"),o=$.path.isAbsoluteUrl(c)?c:$.path.makeUrlAbsolute(c,d)


return void(document.location.href=o)}if(e){var f=n.getResponseHeader("X-Title")
f&&(document.title=decodeURIComponent(f.replace(/\+/g," ")))
var p={},h
n.getResponseHeader("Content-Type").match(/^((text)|(application))\/json[ \t]*;?/i)?p=e:(l=document.createDocumentFragment(),jQuery.clean([e],document,l,[]),u=$(jQuery.merge([],l.childNodes)),s="Content",
u.is("form")&&!u.is("[data-pjax-fragment~=Content]")&&(s="CurrentForm"),p[s]=u),this.setRedrawSuppression(!0)
try{$.each(p,function(e,t){var n=$("[data-pjax-fragment]").filter(function(){return $.inArray(e,$(this).data("pjaxFragment").split(" "))!=-1}),i=$(t)
if(h?h.add(i):h=i,i.find(".cms-container").length)throw'Content loaded via ajax is not allowed to contain tags matching the ".cms-container" selector to avoid infinite loops'
var r=n.attr("style"),o=n.parent(),a="undefined"!=typeof o.data("jlayout"),s=["east","west","center","north","south","column-hidden"],l=n.attr("class"),u=[]
l&&(u=$.grep(l.split(" "),function(e){return $.inArray(e,s)>=0})),i.removeClass(s.join(" ")).addClass(u.join(" ")),r&&i.attr("style",r)
var d=i.find("style").detach()
d.length&&$(document).find("head").append(d),n.replaceWith(i),!o.is(".cms-container")&&a&&o.layout()})
var m=h.filter("form")
m.hasClass("cms-tabset")&&m.removeClass("cms-tabset").addClass("cms-tabset")}finally{this.setRedrawSuppression(!1)}return this.redraw(),this.restoreTabState(i&&"undefined"!=typeof i.tabState?i.tabState:null),
h}},_findFragments:function b(e){return $("[data-pjax-fragment]").filter(function(){var t,n=$(this).data("pjaxFragment").split(" ")
for(t in e)if($.inArray(e[t],n)!=-1)return!0
return!1})},refresh:function w(){$(window).trigger("statechange"),$(this).redraw()},saveTabState:function _(){if("undefined"!=typeof window.sessionStorage&&null!==window.sessionStorage){var e=[],t=this._tabStateUrl()


if(this.find(".cms-tabset,.ss-tabset").each(function(t,n){var i=$(n).attr("id")
i&&$(n).data("tabs")&&($(n).data("ignoreTabState")||$(n).getIgnoreTabState()||e.push({id:i,selected:$(n).tabs("option","selected")}))}),e){var n="tabs-"+t
try{window.sessionStorage.setItem(n,JSON.stringify(e))}catch(i){if(i.code===DOMException.QUOTA_EXCEEDED_ERR&&0===window.sessionStorage.length)return
throw i}}}},restoreTabState:function C(e){var t=this,n=this._tabStateUrl(),i="undefined"!=typeof window.sessionStorage&&window.sessionStorage,r=i?window.sessionStorage.getItem("tabs-"+n):null,o=!!r&&JSON.parse(r)


this.find(".cms-tabset, .ss-tabset").each(function(){var n,i,r=$(this),a=r.attr("id"),s=r.children("ul").children("li.ss-tabs-force-active")
r.data("tabs")&&(r.tabs("refresh"),s.length?n=s.first().index():e&&e[a]?(i=r.find(e[a].tabSelector),i.length&&(n=i.index())):o&&$.each(o,function(e,t){a==t.id&&(n=t.selected)}),null!==n&&(r.tabs("option","active",n),
t.trigger("tabstaterestored")))})},clearTabState:function T(e){if("undefined"!=typeof window.sessionStorage){var t=window.sessionStorage
if(e)t.removeItem("tabs-"+e)
else for(var n=0;n<t.length;n++)t.key(n).match(/^tabs-/)&&t.removeItem(t.key(n))}},clearCurrentTabState:function E(){this.clearTabState(this._tabStateUrl())},_tabStateUrl:function P(){return window.location.href.replace(/\?.*/,"").replace(/#.*/,"").replace($("base").attr("href"),"")

},showLoginDialog:function O(){var e=$("body").data("member-tempid"),t=$(".leftandmain-logindialog"),n="CMSSecurity/login"
t.length&&t.remove(),n=$.path.addSearchParams(n,{tempid:e,BackURL:window.location.href}),t=$('<div class="leftandmain-logindialog"></div>'),t.attr("id",(new Date).getTime()),t.data("url",n),$("body").append(t)

}}),$(".leftandmain-logindialog").entwine({onmatch:function S(){this._super(),this.ssdialog({iframeUrl:this.data("url"),dialogClass:"leftandmain-logindialog-dialog",autoOpen:!0,minWidth:500,maxWidth:500,
minHeight:370,maxHeight:400,closeOnEscape:!1,open:function e(){$(".ui-widget-overlay").addClass("leftandmain-logindialog-overlay")},close:function t(){$(".ui-widget-overlay").removeClass("leftandmain-logindialog-overlay")

}})},onunmatch:function k(){this._super()},open:function j(){this.ssdialog("open")},close:function x(){this.ssdialog("close")},toggle:function R(e){this.is(":visible")?this.close():this.open()},reauthenticate:function I(e){
"undefined"!=typeof e.SecurityID&&$(":input[name=SecurityID]").val(e.SecurityID),"undefined"!=typeof e.TempID&&$("body").data("member-tempid",e.TempID),this.close()}}),$("form.loading,.cms-content.loading,.cms-content-fields.loading,.cms-content-view.loading").entwine({
onmatch:function F(){this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>'),this._super()},onunmatch:function A(){this.find(".cms-content-loading-overlay,.cms-content-loading-spinner").remove(),
this._super()}}),$('.cms input[type="submit"], .cms button, .cms input[type="reset"], .cms .ss-ui-button').entwine({onadd:function D(){this.addClass("ss-ui-button"),this.data("button")||this.button(),this._super()

},onremove:function M(){this.data("button")&&this.button("destroy"),this._super()}}),$(".cms .cms-panel-link").entwine({onclick:function N(e){if($(this).hasClass("external-link"))return void e.stopPropagation()


var t=this.attr("href"),n=t&&!t.match(/^#/)?t:this.data("href"),i={pjax:this.data("pjaxTarget")}
$(".cms-container").loadPanel(n,null,i),e.preventDefault()}}),$(".cms .ss-ui-button-ajax").entwine({onclick:function onclick(e){$(this).removeClass("ui-button-text-only"),$(this).addClass("ss-ui-button-loading ui-button-text-icons")


var loading=$(this).find(".ss-ui-loading-icon")
loading.length<1&&(loading=$("<span></span>").addClass("ss-ui-loading-icon ui-button-icon-primary ui-icon"),$(this).prepend(loading)),loading.show()
var href=this.attr("href"),url=href?href:this.data("href")
jQuery.ajax({url:url,complete:function complete(xmlhttp,status){var msg=xmlhttp.getResponseHeader("X-Status")?xmlhttp.getResponseHeader("X-Status"):xmlhttp.responseText
try{"undefined"!=typeof msg&&null!==msg&&eval(msg)}catch(e){}loading.hide(),$(".cms-container").refresh(),$(this).removeClass("ss-ui-button-loading ui-button-text-icons"),$(this).addClass("ui-button-text-only")

},dataType:"html"}),e.preventDefault()}}),$(".cms .ss-ui-dialog-link").entwine({UUID:null,onmatch:function U(){this._super(),this.setUUID((new Date).getTime())},onunmatch:function L(){this._super()},onclick:function B(){
this._super()
var e=this,t="ss-ui-dialog-"+this.getUUID(),n=$("#"+t)
n.length||(n=$('<div class="ss-ui-dialog" id="'+t+'" />'),$("body").append(n))
var i=this.data("popupclass")?this.data("popupclass"):""
return n.ssdialog({iframeUrl:this.attr("href"),autoOpen:!0,dialogExtraClass:i}),!1}}),$(".cms-content .btn-toolbar").entwine({onmatch:function H(){this.find(".ss-ui-button").click(function(){var e=this.form


e&&(e.clickedButton=this,setTimeout(function(){e.clickedButton=null},10))}),this.redraw(),this._super()},onunmatch:function V(){this._super()},redraw:function q(){window.debug&&console.log("redraw",this.attr("class"),this.get(0)),
this.contents().filter(function(){return 3==this.nodeType&&!/\S/.test(this.nodeValue)}).remove(),this.find(".ss-ui-button").each(function(){$(this).data("button")||$(this).button()}),this.find(".ss-ui-buttonset").buttonset()

}}),$(".cms .field.date input.text").entwine({onmatch:function G(){var e=$(this).parents(".field.date:first"),t=e.data()
return t.showcalendar?(t.showOn="button",t.locale&&$.datepicker.regional[t.locale]&&(t=$.extend(t,$.datepicker.regional[t.locale],{})),this.prop("disabled")||this.prop("readonly")||$(this).datepicker(t),
void this._super()):void this._super()},onunmatch:function W(){this._super()}}),$(".cms .field.dropdown select, .cms .field select[multiple], .form__fieldgroup-item select.dropdown").entwine({onmatch:function z(){
return this.is(".no-chosen")?void this._super():(this.data("placeholder")||this.data("placeholder"," "),this.removeClass("has-chosen").chosen("destroy"),this.siblings(".chosen-container").remove(),applyChosen(this),
void this._super())},onunmatch:function X(){this._super()}}),$(".cms-panel-layout").entwine({redraw:function Q(){window.debug&&console.log("redraw",this.attr("class"),this.get(0))}}),$(".cms .grid-field").entwine({
showDetailView:function K(e){var t=window.location.search.replace(/^\?/,"")
t&&(e=$.path.addSearchParams(e,t)),$(".cms-container").loadPanel(e)}}),$(".cms-search-form").entwine({onsubmit:function Y(e){var t,n
t=this.find(":input:not(:submit)").filter(function(){var e=$.grep($(this).fieldValue(),function(e){return e})
return e.length}),n=this.attr("action"),t.length&&(n=$.path.addSearchParams(n,t.serialize().replace("+","%20")))
var i=this.closest(".cms-container")
return i.find(".cms-edit-form").tabs("select",0),i.loadPanel(n,"",{},!0),!1}}),$(".cms-search-form button[type=reset], .cms-search-form input[type=reset]").entwine({onclick:function J(e){e.preventDefault()


var t=$(this).parents("form")
t.clearForm(),t.find(".dropdown select").prop("selectedIndex",0).trigger("chosen:updated"),t.submit()}}),window._panelDeferredCache={},$(".cms-panel-deferred").entwine({onadd:function Z(){this._super(),
this.redraw()},onremove:function ee(){window.debug&&console.log("saving",this.data("url"),this),this.data("deferredNoCache")||(window._panelDeferredCache[this.data("url")]=this.html()),this._super()},redraw:function te(){
window.debug&&console.log("redraw",this.attr("class"),this.get(0))
var e=this,t=this.data("url")
if(!t)throw'Elements of class .cms-panel-deferred need a "data-url" attribute'
this._super(),this.children().length||(this.data("deferredNoCache")||"undefined"==typeof window._panelDeferredCache[t]?(this.addClass("loading"),$.ajax({url:t,complete:function n(){e.removeClass("loading")

},success:function i(t,n,r){e.html(t)}})):this.html(window._panelDeferredCache[t]))}}),$(".cms-tabset").entwine({onadd:function ne(){this.redrawTabs(),this._super()},onremove:function ie(){this.data("tabs")&&this.tabs("destroy"),
this._super()},redrawTabs:function re(){this.rewriteHashlinks()
var e=this.attr("id"),t=this.find("ul:first .ui-tabs-active")
this.data("tabs")||this.tabs({active:t.index()!=-1?t.index():0,beforeLoad:function n(e,t){return!1},beforeActivate:function i(e,t){var n=t.oldTab.find(".cms-panel-link")
if(n&&1===n.length)return!1},activate:function r(e,t){var n=$(this).closest("form").find(".btn-toolbar")
$(t.newTab).closest("li").hasClass("readonly")?n.fadeOut():n.show()}}),this.trigger("afterredrawtabs")},rewriteHashlinks:function oe(){$(this).find("ul a").each(function(){if($(this).attr("href")){var e=$(this).attr("href").match(/#.*/)


e&&$(this).attr("href",document.location.href.replace(/#.*/,"")+e[0])}})}}),$("#filters-button").entwine({onmatch:function ae(){this._super(),this.data("collapsed",!0),this.data("animating",!1)},onunmatch:function se(){
this._super()},showHide:function le(){var e=this,t=$(".cms-content-filters").first(),n=this.data("collapsed")
this.data("animating")||(this.toggleClass("active"),this.data("animating",!0),t[n?"slideDown":"slideUp"]({complete:function i(){e.data("collapsed",!n),e.data("animating",!1)}}))},onclick:function ue(){
this.showHide()}})})
var statusMessage=function e(t,n){t=jQuery("<div/>").text(t).html(),jQuery.noticeAdd({text:t,type:n,stayTime:5e3,inEffect:{left:"0",opacity:"show"}})}}).call(exports,__webpack_require__(1))},function(e,t,n){
"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".ss-tabset.ss-ui-action-tabset").entwine({IgnoreTabState:!0,onadd:function t(){this._super(),this.tabs({collapsible:!0,active:!1})},onremove:function n(){var t=e(".cms-container").find("iframe")


t.each(function(t,n){try{e(n).contents().off("click.ss-ui-action-tabset")}catch(i){console.warn("Unable to access iframe, possible https mis-match")}}),e(document).off("click.ss-ui-action-tabset"),this._super()

},ontabsbeforeactivate:function i(e,t){this.riseUp(e,t)},onclick:function r(e,t){this.attachCloseHandler(e,t)},attachCloseHandler:function o(t,n){var i=this,r=e(".cms-container").find("iframe"),o
o=function a(t){var n,r
n=e(t.target).closest(".ss-ui-action-tabset .ui-tabs-panel"),e(t.target).closest(i).length||n.length||(i.tabs("option","active",!1),r=e(".cms-container").find("iframe"),r.each(function(t,n){e(n).contents().off("click.ss-ui-action-tabset",o)

}),e(document).off("click.ss-ui-action-tabset",o))},e(document).on("click.ss-ui-action-tabset",o),r.length>0&&r.each(function(t,n){e(n).contents().on("click.ss-ui-action-tabset",o)})},riseUp:function a(t,n){
var i,r,o,a,s,l,u,d,c
return i=e(this).find(".ui-tabs-panel").outerHeight(),r=e(this).find(".ui-tabs-nav").outerHeight(),o=e(window).height()+e(document).scrollTop()-r,a=e(this).find(".ui-tabs-nav").offset().top,s=n.newPanel,
l=n.newTab,a+i>=o&&a-i>0?(this.addClass("rise-up"),null!==l.position()&&(u=-s.outerHeight(),d=s.parents(".toolbar--south"),d&&(c=l.offset().top-d.offset().top,u-=c),e(s).css("top",u+"px"))):(this.removeClass("rise-up"),
null!==l.position()&&e(s).css("bottom","100%")),!1}}),e(".cms-content-actions .ss-tabset.ss-ui-action-tabset").entwine({ontabsbeforeactivate:function s(t,n){this._super(t,n),e(n.newPanel).length>0&&e(n.newPanel).css("left",n.newTab.position().left+"px")

}}),e(".cms-actions-row.ss-tabset.ss-ui-action-tabset").entwine({ontabsbeforeactivate:function l(t,n){this._super(t,n),e(this).closest(".ss-ui-action-tabset").removeClass("tabset-open tabset-open-last")

}}),e(".cms-content-fields .ss-tabset.ss-ui-action-tabset").entwine({ontabsbeforeactivate:function u(t,n){this._super(t,n),e(n.newPanel).length>0&&(e(n.newTab).hasClass("last")?(e(n.newPanel).css({left:"auto",
right:"0px"}),e(n.newPanel).parent().addClass("tabset-open-last")):(e(n.newPanel).css("left",n.newTab.position().left+"px"),e(n.newTab).hasClass("first")&&(e(n.newPanel).css("left","0px"),e(n.newPanel).parent().addClass("tabset-open"))))

}}),e(".cms-tree-view-sidebar .cms-actions-row.ss-tabset.ss-ui-action-tabset").entwine({"from .ui-tabs-nav li":{onhover:function d(t){e(t.target).parent().find("li .active").removeClass("active"),e(t.target).find("a").addClass("active")

}},ontabsbeforeactivate:function c(t,n){this._super(t,n),e(n.newPanel).css({left:"auto",right:"auto"}),e(n.newPanel).length>0&&e(n.newPanel).parent().addClass("tabset-open")}})})},function(e,t,n){"use strict"


function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e.entwine.warningLevel=e.entwine.WARN_LEVEL_BESTPRACTISE,e(".cms-panel").entwine({WidthExpanded:null,WidthCollapsed:null,canSetCookie:function t(){return void 0!==e.cookie&&void 0!==this.attr("id")

},getPersistedCollapsedState:function n(){var t,n
return this.canSetCookie()&&(n=e.cookie("cms-panel-collapsed-"+this.attr("id")),void 0!==n&&null!==n&&(t="true"===n)),t},setPersistedCollapsedState:function i(t){this.canSetCookie()&&e.cookie("cms-panel-collapsed-"+this.attr("id"),t,{
path:"/",expires:31})},clearPersistedCollapsedState:function r(){this.canSetCookie()&&e.cookie("cms-panel-collapsed-"+this.attr("id"),"",{path:"/",expires:-1})},getInitialCollapsedState:function o(){var e=this.getPersistedCollapsedState()


return void 0===e&&(e=this.hasClass("collapsed")),e},onadd:function a(){var t,n
if(!this.find(".cms-panel-content").length)throw new Exception('Content panel for ".cms-panel" not found')
this.find(".cms-panel-toggle").length||(n=e("<div class='toolbar toolbar--south cms-panel-toggle'></div>").append('<a class="toggle-expand" href="#"><span>&raquo;</span></a>').append('<a class="toggle-collapse" href="#"><span>&laquo;</span></a>'),
this.append(n)),this.setWidthExpanded(this.find(".cms-panel-content").innerWidth()),t=this.find(".cms-panel-content-collapsed"),this.setWidthCollapsed(t.length?t.innerWidth():this.find(".toggle-expand").innerWidth()),
this.togglePanel(!this.getInitialCollapsedState(),!0,!1),this._super()},togglePanel:function s(e,t,n){var i,r
t||(this.trigger("beforetoggle.sspanel",e),this.trigger(e?"beforeexpand":"beforecollapse")),this.toggleClass("collapsed",!e),i=e?this.getWidthExpanded():this.getWidthCollapsed(),this.width(i),r=this.find(".cms-panel-content-collapsed"),
r.length&&(this.find(".cms-panel-content")[e?"show":"hide"](),this.find(".cms-panel-content-collapsed")[e?"hide":"show"]()),n!==!1&&this.setPersistedCollapsedState(!e),this.trigger("toggle",e),this.trigger(e?"expand":"collapse")

},expandPanel:function l(e){(e||this.hasClass("collapsed"))&&this.togglePanel(!0)},collapsePanel:function u(e){!e&&this.hasClass("collapsed")||this.togglePanel(!1)}}),e(".cms-panel.collapsed .cms-panel-toggle").entwine({
onclick:function d(e){this.expandPanel(),e.preventDefault()}}),e(".cms-panel *").entwine({getPanel:function c(){return this.parents(".cms-panel:first")}}),e(".cms-panel .toggle-expand").entwine({onclick:function f(e){
e.preventDefault(),e.stopPropagation(),this.getPanel().expandPanel(),this._super(e)}}),e(".cms-panel .toggle-collapse").entwine({onclick:function p(e){e.preventDefault(),e.stopPropagation(),this.getPanel().collapsePanel(),
this._super(e)}}),e(".cms-content-tools.collapsed").entwine({onclick:function h(e){this.expandPanel(),this._super(e)}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss.tree",function(e){e(".cms-tree").entwine({Hints:null,IsUpdatingTree:!1,IsLoaded:!1,onadd:function t(){if(this._super(),!e.isNumeric(this.data("jstree_instance_id"))){var t=this.attr("data-hints")


t&&this.setHints(e.parseJSON(t))
var n=this
this.jstree(this.getTreeConfig()).bind("loaded.jstree",function(t,i){n.setIsLoaded(!0),i.inst._set_settings({html_data:{ajax:{url:n.data("urlTree"),data:function r(t){var i=n.data("searchparams")||[]
return i=e.grep(i,function(e,t){return"ID"!=e.name&&"value"!=e.name}),i.push({name:"ID",value:e(t).data("id")?e(t).data("id"):0}),i.push({name:"ajax",value:1}),i}}}}),n.updateFromEditForm(),n.css("visibility","visible"),
i.inst.hide_checkboxes()}).bind("before.jstree",function(t,i){if("start_drag"==i.func&&(!n.hasClass("draggable")||n.hasClass("multiselect")))return t.stopImmediatePropagation(),!1
if(e.inArray(i.func,["check_node","uncheck_node"])){var r=e(i.args[0]).parents("li:first"),o=r.find("li:not(.disabled)")
if(r.hasClass("disabled")&&0==o)return t.stopImmediatePropagation(),!1}}).bind("move_node.jstree",function(t,i){if(!n.getIsUpdatingTree()){var r=i.rslt.o,o=i.rslt.np,a=i.inst._get_parent(r),s=e(o).data("id")||0,l=e(r).data("id"),u=e.map(e(r).siblings().andSelf(),function(t){
return e(t).data("id")})
e.ajax({url:e.path.addSearchParams(n.data("urlSavetreenode"),n.data("extraParams")),type:"POST",data:{ID:l,ParentID:s,SiblingIDs:u},success:function d(){e(".cms-edit-form :input[name=ID]").val()==l&&e(".cms-edit-form :input[name=ParentID]").val(s),
n.updateNodesFromServer([l])},statusCode:{403:function c(){e.jstree.rollback(i.rlbk)}}})}}).bind("select_node.jstree check_node.jstree uncheck_node.jstree",function(t,n){e(document).triggerHandler(t,n)

})}},onremove:function n(){this.jstree("destroy"),this._super()},"from .cms-container":{onafterstatechange:function i(e){this.updateFromEditForm()}},"from .cms-container form":{onaftersubmitform:function r(t){
var n=e(".cms-edit-form :input[name=ID]").val()
this.updateNodesFromServer([n])}},getTreeConfig:function o(){var t=this
return{core:{initially_open:["record-0"],animation:0,html_titles:!0},html_data:{},ui:{select_limit:1,initially_select:[this.find(".current").attr("id")]},crrm:{move:{check_move:function n(i){var r=e(i.o),o=e(i.np),a=i.ot.get_container()[0]==i.np[0],s=r.getClassname(),l=o.getClassname(),u=t.getHints(),d=[],c=l?l:"Root",f=u&&"undefined"!=typeof u[c]?u[c]:null


f&&r.attr("class").match(/VirtualPage-([^\s]*)/)&&(s=RegExp.$1),f&&(d="undefined"!=typeof f.disallowedChildren?f.disallowedChildren:[])
var p=!(0===r.data("id")||r.hasClass("status-archived")||a&&"inside"!=i.p||o.hasClass("nochildren")||d.length&&e.inArray(s,d)!=-1)
return p}}},dnd:{drop_target:!1,drag_target:!1},checkbox:{two_state:!0},themes:{theme:"apple",url:e("body").data("frameworkpath")+"/thirdparty/jstree/themes/apple/style.css"},plugins:["html_data","ui","dnd","crrm","themes","checkbox"]
}},search:function a(e,t){e?this.data("searchparams",e):this.removeData("searchparams"),this.jstree("refresh",-1,t)},getNodeByID:function s(e){return this.find("*[data-id="+e+"]")},createNode:function l(t,n,i){
var r=this,o=void 0!==n.ParentID&&r.getNodeByID(n.ParentID),a=e(t),s={data:""}
a.hasClass("jstree-open")?s.state="open":a.hasClass("jstree-closed")&&(s.state="closed"),this.jstree("create_node",o.length?o:-1,"last",s,function(e){for(var t=e.attr("class"),n=0;n<a[0].attributes.length;n++){
var r=a[0].attributes[n]
e.attr(r.name,r.value)}e.addClass(t).html(a.html()),i(e)})},updateNode:function u(t,n,i){var r=this,o=e(n),a=!!i.NextID&&this.getNodeByID(i.NextID),s=!!i.PrevID&&this.getNodeByID(i.PrevID),l=!!i.ParentID&&this.getNodeByID(i.ParentID)


e.each(["id","style","class","data-pagetype"],function(e,n){t.attr(n,o.attr(n))})
var u=t.children("ul").detach()
t.html(o.html()).append(u),a&&a.length?this.jstree("move_node",t,a,"before"):s&&s.length?this.jstree("move_node",t,s,"after"):this.jstree("move_node",t,l.length?l:-1)},updateFromEditForm:function d(){var t,n=e(".cms-edit-form :input[name=ID]").val()


n?(t=this.getNodeByID(n),t.length?(this.jstree("deselect_all"),this.jstree("select_node",t)):this.updateNodesFromServer([n])):this.jstree("deselect_all")},updateNodesFromServer:function c(t){if(!this.getIsUpdatingTree()&&this.getIsLoaded()){
var n=this,i,r=!1
this.setIsUpdatingTree(!0),n.jstree("save_selected")
var o=function a(e){n.getNodeByID(e.data("id")).not(e).remove(),n.jstree("deselect_all"),n.jstree("select_node",e)}
n.jstree("open_node",this.getNodeByID(0)),n.jstree("save_opened"),n.jstree("save_selected"),e.ajax({url:e.path.addSearchParams(this.data("urlUpdatetreenodes"),"ids="+t.join(",")),dataType:"json",success:function s(t,i){
e.each(t,function(e,t){var i=n.getNodeByID(e)
return t?void(i.length?(n.updateNode(i,t.html,t),setTimeout(function(){o(i)},500)):(r=!0,t.ParentID&&!n.find("li[data-id="+t.ParentID+"]").length?n.jstree("load_node",-1,function(){newNode=n.find("li[data-id="+e+"]"),
o(newNode)}):n.createNode(t.html,t,function(e){o(e)}))):void n.jstree("delete_node",i)}),r||(n.jstree("deselect_all"),n.jstree("reselect"),n.jstree("reopen"))},complete:function l(){n.setIsUpdatingTree(!1)

}})}}}),e(".cms-tree.multiple").entwine({onmatch:function f(){this._super(),this.jstree("show_checkboxes")},onunmatch:function p(){this._super(),this.jstree("uncheck_all"),this.jstree("hide_checkboxes")

},getSelectedIDs:function h(){return e(this).jstree("get_checked").not(".disabled").map(function(){return e(this).data("id")}).get()}}),e(".cms-tree li").entwine({setEnabled:function m(e){this.toggleClass("disabled",!e)

},getClassname:function g(){var e=this.attr("class").match(/class-([^\s]*)/i)
return e?e[1]:""},getID:function v(){return this.data("id")}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".cms-content").entwine({onadd:function t(){var e=this
this.find(".cms-tabset").redrawTabs(),this._super()},redraw:function n(){window.debug&&console.log("redraw",this.attr("class"),this.get(0)),this.add(this.find(".cms-tabset")).redrawTabs(),this.find(".cms-content-header").redraw(),
this.find(".cms-content-actions").redraw()}}),e(".cms-content .cms-tree").entwine({onadd:function i(){var t=this
this._super(),this.bind("select_node.jstree",function(n,i){var r=i.rslt.obj,o=t.find(":input[name=ID]").val(),a=i.args[2],s=e(".cms-container")
if(!a)return!1
if(e(r).hasClass("disabled"))return!1
if(e(r).data("id")!=o){var l=e(r).find("a:first").attr("href")
l&&"#"!=l?(l=l.split("?")[0],t.jstree("deselect_all"),t.jstree("uncheck_all"),e.path.isExternal(e(r).find("a:first"))&&(l=l=e.path.makeUrlAbsolute(l,e("base").attr("href"))),document.location.search&&(l=e.path.addSearchParams(l,document.location.search.replace(/^\?/,""))),
s.loadPanel(l)):t.removeForm()}})}}),e(".cms-content .cms-content-fields").entwine({redraw:function r(){window.debug&&console.log("redraw",this.attr("class"),this.get(0))}}),e(".cms-content .cms-content-header, .cms-content .cms-content-actions").entwine({
redraw:function o(){window.debug&&console.log("redraw",this.attr("class"),this.get(0)),this.height("auto"),this.height(this.innerHeight()-this.css("padding-top")-this.css("padding-bottom"))}})})},function(e,t,n){
(function(e){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var i=n(1),r=t(i),o=n(95),a=t(o)
window.onbeforeunload=function(e){var t=(0,r["default"])(".cms-edit-form")
if(t.trigger("beforesubmitform"),t.is(".changed")&&!t.is(".discardchanges"))return a["default"]._t("LeftAndMain.CONFIRMUNSAVEDSHORT")},r["default"].entwine("ss",function(e){e(".cms-edit-form").entwine({
PlaceholderHtml:"",ChangeTrackerOptions:{ignoreFieldSelector:".no-change-track, .ss-upload :input, .cms-navigator :input"},ValidationErrorShown:!1,onadd:function t(){var e=this
this.attr("autocomplete","off"),this._setupChangeTracker()
for(var t in{action:!0,method:!0,enctype:!0,name:!0}){var n=this.find(":input[name=_form_"+t+"]")
n&&(this.attr(t,n.val()),n.remove())}this.setValidationErrorShown(!1),this._super()},"from .cms-tabset":{onafterredrawtabs:function n(){if(this.hasClass("validationerror")){var t=this.find(".message.validation, .message.required").first().closest(".tab")


e(".cms-container").clearCurrentTabState()
var n=t.closest(".ss-tabset")
n.length||(n=t.closest(".cms-tabset")),n.length?n.tabs("option","active",t.index(".tab")):this.getValidationErrorShown()||(this.setValidationErrorShown(!0),s(ss.i18n._t("ModelAdmin.VALIDATIONERROR","Validation Error")))

}}},onremove:function i(){this.changetracker("destroy"),this._super()},onmatch:function r(){this._super()},onunmatch:function o(){this._super()},redraw:function l(){window.debug&&console.log("redraw",this.attr("class"),this.get(0)),
this.add(this.find(".cms-tabset")).redrawTabs(),this.find(".cms-content-header").redraw()},_setupChangeTracker:function u(){this.changetracker(this.getChangeTrackerOptions())},confirmUnsavedChanges:function d(){
if(this.trigger("beforesubmitform"),!this.is(".changed")||this.is(".discardchanges"))return!0
var e=confirm(a["default"]._t("LeftAndMain.CONFIRMUNSAVED"))
return e&&this.addClass("discardchanges"),e},onsubmit:function c(e,t){if("_blank"!=this.prop("target"))return t&&this.closest(".cms-container").submitForm(this,t),!1},validate:function f(){var e=!0
return this.trigger("validate",{isValid:e}),e},"from .htmleditor":{oneditorinit:function p(t){var n=this,i=e(t.target).closest(".field.htmleditor"),r=i.find("textarea.htmleditor").getEditor().getInstance()


r.onClick.add(function(e){n.saveFieldFocus(i.attr("id"))})}},"from .cms-edit-form :input:not(:submit)":{onclick:function h(t){this.saveFieldFocus(e(t.target).attr("id"))},onfocus:function m(t){this.saveFieldFocus(e(t.target).attr("id"))

}},"from .cms-edit-form .treedropdown *":{onfocusin:function g(t){var n=e(t.target).closest(".field.treedropdown")
this.saveFieldFocus(n.attr("id"))}},"from .cms-edit-form .dropdown .chosen-container a":{onfocusin:function v(t){var n=e(t.target).closest(".field.dropdown")
this.saveFieldFocus(n.attr("id"))}},"from .cms-container":{ontabstaterestored:function y(e){this.restoreFieldFocus()}},saveFieldFocus:function b(t){if("undefined"!=typeof window.sessionStorage&&null!==window.sessionStorage){
var n=e(this).attr("id"),i=[]
if(i.push({id:n,selected:t}),i)try{window.sessionStorage.setItem(n,JSON.stringify(i))}catch(r){if(r.code===DOMException.QUOTA_EXCEEDED_ERR&&0===window.sessionStorage.length)return
throw r}}},restoreFieldFocus:function w(){if("undefined"!=typeof window.sessionStorage&&null!==window.sessionStorage){var t=this,n="undefined"!=typeof window.sessionStorage&&window.sessionStorage,i=n?window.sessionStorage.getItem(this.attr("id")):null,r=!!i&&JSON.parse(i),o,a=0!==this.find(".ss-tabset").length,s,l,u,d


if(n&&r.length>0){if(e.each(r,function(n,i){t.is("#"+i.id)&&(o=e("#"+i.selected))}),e(o).length<1)return void this.focusFirstInput()
if(s=e(o).closest(".ss-tabset").find(".ui-tabs-nav .ui-tabs-active .ui-tabs-anchor").attr("id"),l="tab-"+e(o).closest(".ss-tabset .ui-tabs-panel").attr("id"),a&&l!==s)return
u=e(o).closest(".togglecomposite"),u.length>0&&u.accordion("activate",u.find(".ui-accordion-header")),d=e(o).position().top,e(o).is(":visible")||(o="#"+e(o).closest(".field").attr("id"),d=e(o).position().top),
e(o).focus(),d>e(window).height()/2&&t.find(".cms-content-fields").scrollTop(d)}else this.focusFirstInput()}},focusFirstInput:function _(){this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(":visible:first").focus()

}}),e(".cms-edit-form .btn-toolbar input.action[type=submit], .cms-edit-form .btn-toolbar button.action").entwine({onclick:function C(e){return this.hasClass("gridfield-button-delete")&&!confirm(a["default"]._t("TABLEFIELD.DELETECONFIRMMESSAGE"))?(e.preventDefault(),
!1):(this.is(":disabled")||this.parents("form").trigger("submit",[this]),e.preventDefault(),!1)}}),e(".cms-edit-form .btn-toolbar input.action[type=submit].ss-ui-action-cancel, .cms-edit-form .btn-toolbar button.action.ss-ui-action-cancel").entwine({
onclick:function T(e){window.history.length>1?window.history.back():this.parents("form").trigger("submit",[this]),e.preventDefault()}}),e(".cms-edit-form .ss-tabset").entwine({onmatch:function E(){if(!this.hasClass("ss-ui-action-tabset")){
var e=this.find("> ul:first")
1==e.children("li").length&&e.hide().parent().addClass("ss-tabset-tabshidden")}this._super()},onunmatch:function P(){this._super()}})})
var s=function l(t){e.noticeAdd({text:t,type:"error",stayTime:5e3,inEffect:{left:"0",opacity:"show"}})}}).call(t,n(1))},function(e,t){e.exports=i18n},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".cms-panel.cms-menu").entwine({togglePanel:function t(n,i,r){e(".cms-menu-list").children("li").each(function(){n?e(this).children("ul").each(function(){e(this).removeClass("collapsed-flyout"),
e(this).data("collapse")&&(e(this).removeData("collapse"),e(this).addClass("collapse"))}):e(this).children("ul").each(function(){e(this).addClass("collapsed-flyout"),e(this).hasClass("collapse"),e(this).removeClass("collapse"),
e(this).data("collapse",!0)})}),this.toggleFlyoutState(n),this._super(n,i,r)},toggleFlyoutState:function n(t){if(t)e(".collapsed").find("li").show(),e(".cms-menu-list").find(".child-flyout-indicator").hide()
else{e(".collapsed-flyout").find("li").each(function(){e(this).hide()})
var n=e(".cms-menu-list ul.collapsed-flyout").parent()
0===n.children(".child-flyout-indicator").length&&n.append('<span class="child-flyout-indicator"></span>').fadeIn(),n.children(".child-flyout-indicator").fadeIn()}},siteTreePresent:function i(){return e("#cms-content-tools-CMSMain").length>0

},getPersistedStickyState:function r(){var t,n
return void 0!==e.cookie&&(n=e.cookie("cms-menu-sticky"),void 0!==n&&null!==n&&(t="true"===n)),t},setPersistedStickyState:function o(t){void 0!==e.cookie&&e.cookie("cms-menu-sticky",t,{path:"/",expires:31
})},getEvaluatedCollapsedState:function a(){var t,n=this.getPersistedCollapsedState(),i=e(".cms-menu").getPersistedStickyState(),r=this.siteTreePresent()
return t=void 0===n?r:n!==r&&i?n:r},onadd:function s(){var t=this
setTimeout(function(){t.togglePanel(!t.getEvaluatedCollapsedState(),!1,!1)},0),e(window).on("ajaxComplete",function(e){setTimeout(function(){t.togglePanel(!t.getEvaluatedCollapsedState(),!1,!1)},0)}),this._super()

}}),e(".cms-menu-list").entwine({onmatch:function l(){var e=this
this.find("li.current").select(),this.updateItems(),this._super()},onunmatch:function u(){this._super()},updateMenuFromResponse:function d(e){var t=e.getResponseHeader("X-Controller")
if(t){var n=this.find("li#Menu-"+t.replace(/\\/g,"-").replace(/[^a-zA-Z0-9\-_:.]+/,""))
n.hasClass("current")||n.select()}this.updateItems()},"from .cms-container":{onafterstatechange:function c(e,t){this.updateMenuFromResponse(t.xhr)},onaftersubmitform:function f(e,t){this.updateMenuFromResponse(t.xhr)

}},"from .cms-edit-form":{onrelodeditform:function p(e,t){this.updateMenuFromResponse(t.xmlhttp)}},getContainingPanel:function h(){return this.closest(".cms-panel")},fromContainingPanel:{ontoggle:function m(t){
this.toggleClass("collapsed",e(t.target).hasClass("collapsed")),e(".cms-container").trigger("windowresize"),this.hasClass("collapsed")&&this.find("li.children.opened").removeClass("opened"),this.hasClass("collapsed")||e(".toggle-children.opened").closest("li").addClass("opened")

}},updateItems:function g(){var t=this.find("#Menu-CMSMain")
t[t.is(".current")?"show":"hide"]()
var n=e(".cms-content input[name=ID]").val()
n&&this.find("li").each(function(){e.isFunction(e(this).setRecordID)&&e(this).setRecordID(n)})}}),e(".cms-menu-list li").entwine({toggleFlyout:function v(t){var n=e(this)
if(n.children("ul").first().hasClass("collapsed-flyout"))if(t){if(!n.children("ul").first().children("li").first().hasClass("clone")){var i=n.clone()
i.addClass("clone").css({}),i.children("ul").first().remove(),i.find("span").not(".text").remove(),i.find("a").first().unbind("click"),n.children("ul").prepend(i)}e(".collapsed-flyout").show(),n.addClass("opened"),
n.children("ul").find("li").fadeIn("fast")}else i&&i.remove(),e(".collapsed-flyout").hide(),n.removeClass("opened"),n.find("toggle-children").removeClass("opened"),n.children("ul").find("li").hide()}}),
e(".cms-menu-list li").hoverIntent(function(){e(this).toggleFlyout(!0)},function(){e(this).toggleFlyout(!1)}),e(".cms-menu-list .toggle").entwine({onclick:function y(t){t.preventDefault(),e(this).toogleFlyout(!0)

}}),e(".cms-menu-list li").entwine({onmatch:function b(){this.find("ul").length&&this.find("a:first").append('<span class="toggle-children"><span class="toggle-children-icon"></span></span>'),this._super()

},onunmatch:function w(){this._super()},toggle:function _(){this[this.hasClass("opened")?"close":"open"]()},open:function C(){var e=this.getMenuItem()
e&&e.open(),this.find("li.clone")&&this.find("li.clone").remove(),this.addClass("opened").find("ul").show(),this.find(".toggle-children").addClass("opened")},close:function T(){this.removeClass("opened").find("ul").hide(),
this.find(".toggle-children").removeClass("opened")},select:function E(){var e=this.getMenuItem()
if(this.addClass("current").open(),this.siblings().removeClass("current").close(),this.siblings().find("li").removeClass("current"),e){var t=e.siblings()
e.addClass("current"),t.removeClass("current").close(),t.find("li").removeClass("current").close()}this.getMenu().updateItems(),this.trigger("select")}}),e(".cms-menu-list *").entwine({getMenu:function P(){
return this.parents(".cms-menu-list:first")}}),e(".cms-menu-list li *").entwine({getMenuItem:function O(){return this.parents("li:first")}}),e(".cms-menu-list li a").entwine({onclick:function S(t){var n=e.path.isExternal(this.attr("href"))


if(!(t.which>1||n)&&"_blank"!=this.attr("target")){t.preventDefault()
var i=this.getMenuItem(),r=this.attr("href")
n||(r=e("base").attr("href")+r)
var o=i.find("li")
o.length?o.first().find("a").click():document.location.href=r,i.select()}}}),e(".cms-menu-list li .toggle-children").entwine({onclick:function k(e){var t=this.closest("li")
return t.toggle(),!1}}),e(".cms .profile-link").entwine({onclick:function j(){return e(".cms-container").loadPanel(this.attr("href")),e(".cms-menu-list li").removeClass("current").close(),!1}}),e(".cms-menu .sticky-toggle").entwine({
onadd:function x(){var t=!!e(".cms-menu").getPersistedStickyState()
this.toggleCSS(t),this.toggleIndicator(t),this._super()},toggleCSS:function R(e){this[e?"addClass":"removeClass"]("active")},toggleIndicator:function I(e){this.next(".sticky-status-indicator").text(e?"fixed":"auto")

},onclick:function F(){var e=this.closest(".cms-menu"),t=e.getPersistedCollapsedState(),n=e.getPersistedStickyState(),i=void 0===n?!this.hasClass("active"):!n
void 0===t?e.setPersistedCollapsedState(e.hasClass("collapsed")):void 0!==t&&i===!1&&e.clearPersistedCollapsedState(),e.setPersistedStickyState(i),this.toggleCSS(i),this.toggleIndicator(i),this._super()

}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r),a=n(95),s=i(a)
o["default"].entwine("ss.preview",function(e){e(".cms-preview").entwine({AllowedStates:["StageLink","LiveLink","ArchiveLink"],CurrentStateName:null,CurrentSizeName:"auto",IsPreviewEnabled:!1,DefaultMode:"split",
Sizes:{auto:{width:"100%",height:"100%"},mobile:{width:"335px",height:"568px"},mobileLandscape:{width:"583px",height:"320px"},tablet:{width:"783px",height:"1024px"},tabletLandscape:{width:"1039px",height:"768px"
},desktop:{width:"1024px",height:"800px"}},changeState:function t(n,i){var r=this,o=this._getNavigatorStates()
return i!==!1&&e.each(o,function(e,t){r.saveState("state",n)}),this.setCurrentStateName(n),this._loadCurrentState(),this.redraw(),this},changeMode:function n(t,i){var r=e(".cms-container")
if("split"==t)r.entwine(".ss").splitViewMode(),this.setIsPreviewEnabled(!0),this._loadCurrentState()
else if("content"==t)r.entwine(".ss").contentViewMode(),this.setIsPreviewEnabled(!1)
else{if("preview"!=t)throw"Invalid mode: "+t
r.entwine(".ss").previewMode(),this.setIsPreviewEnabled(!0),this._loadCurrentState()}return i!==!1&&this.saveState("mode",t),this.redraw(),this},changeSize:function i(e){var t=this.getSizes()
return this.setCurrentSizeName(e),this.removeClass("auto desktop tablet mobile").addClass(e),this.find(".preview-device-outer").width(t[e].width).height(t[e].height),this.find(".preview-device-inner").width(t[e].width),
this.saveState("size",e),this.redraw(),this},redraw:function r(){window.debug&&console.log("redraw",this.attr("class"),this.get(0))
var t=this.getCurrentStateName()
t&&this.find(".cms-preview-states").changeVisibleState(t)
var n=e(".cms-container").entwine(".ss").getLayoutOptions()
n&&e(".preview-mode-selector").changeVisibleMode(n.mode)
var i=this.getCurrentSizeName()
return i&&this.find(".preview-size-selector").changeVisibleSize(this.getCurrentSizeName()),this},saveState:function o(e,t){this._supportsLocalStorage()&&window.localStorage.setItem("cms-preview-state-"+e,t)

},loadState:function a(e){if(this._supportsLocalStorage())return window.localStorage.getItem("cms-preview-state-"+e)},disablePreview:function l(){return this.setPendingURL(null),this._loadUrl("about:blank"),
this._block(),this.changeMode("content",!1),this.setIsPreviewEnabled(!1),this},enablePreview:function u(){return this.getIsPreviewEnabled()||(this.setIsPreviewEnabled(!0),e.browser.msie&&e.browser.version.slice(0,3)<=7?this.changeMode("content"):this.changeMode(this.getDefaultMode(),!1)),
this},getOrAppendFontFixStyleElement:function d(){var t=e("#FontFixStyleElement")
return t.length||(t=e('<style type="text/css" id="FontFixStyleElement" disabled="disabled">:before,:after{content:none !important}</style>').appendTo("head")),t},onadd:function c(){var t=this,n=this.parent(),i=this.find("iframe")


i.addClass("center"),i.bind("load",function(){t._adjustIframeForPreview(),t._loadCurrentPage(),e(this).removeClass("loading")}),e.browser.msie&&8===parseInt(e.browser.version,10)&&i.bind("readystatechange",function(e){
"interactive"==i[0].readyState&&(t.getOrAppendFontFixStyleElement().removeAttr("disabled"),setTimeout(function(){t.getOrAppendFontFixStyleElement().attr("disabled","disabled")},0))}),this.append('<div class="cms-preview-overlay ui-widget-overlay-light"></div>'),
this.find(".cms-preview-overlay").hide(),this.disablePreview(),this._super()},_supportsLocalStorage:function f(){var e=new Date,t,n
try{return(t=window.localStorage).setItem(e,e),n=t.getItem(e)==e,t.removeItem(e),n&&t}catch(i){console.warn("localStorge is not available due to current browser / system settings.")}},onenable:function p(){
var t=e(".preview-mode-selector")
t.removeClass("split-disabled"),t.find(".disabled-tooltip").hide()},ondisable:function h(){var t=e(".preview-mode-selector")
t.addClass("split-disabled"),t.find(".disabled-tooltip").show()},_block:function m(){return this.addClass("blocked"),this.find(".cms-preview-overlay").show(),this},_unblock:function g(){return this.removeClass("blocked"),
this.find(".cms-preview-overlay").hide(),this},_initialiseFromContent:function v(){var t,n
return e(".cms-previewable").length?(t=this.loadState("mode"),n=this.loadState("size"),this._moveNavigator(),t&&"content"==t||(this.enablePreview(),this._loadCurrentState()),this.redraw(),t&&this.changeMode(t),
n&&this.changeSize(n)):this.disablePreview(),this},"from .cms-container":{onafterstatechange:function y(e,t){t.xhr.getResponseHeader("X-ControllerURL")||this._initialiseFromContent()}},PendingURL:null,
oncolumnvisibilitychanged:function b(){var e=this.getPendingURL()
e&&!this.is(".column-hidden")&&(this.setPendingURL(null),this._loadUrl(e),this._unblock())},"from .cms-container .cms-edit-form":{onaftersubmitform:function w(){this._initialiseFromContent()}},_loadUrl:function _(e){
return this.find("iframe").addClass("loading").attr("src",e),this},_getNavigatorStates:function C(){var t=e.map(this.getAllowedStates(),function(t){var n=e(".cms-preview-states .state-name[data-name="+t+"]")


return n.length?{name:t,url:n.attr("href"),active:n.hasClass("active")}:null})
return t},_loadCurrentState:function T(){if(!this.getIsPreviewEnabled())return this
var t=this._getNavigatorStates(),n=this.getCurrentStateName(),i=null
t&&(i=e.grep(t,function(e,t){return n===e.name||!n&&e.active}))
var r=null
return i[0]?r=i[0].url:t.length?(this.setCurrentStateName(t[0].name),r=t[0].url):this.setCurrentStateName(null),r&&(r+=(r.indexOf("?")===-1?"?":"&")+"CMSPreview=1"),this.is(".column-hidden")?(this.setPendingURL(r),
this._loadUrl("about:blank"),this._block()):(this.setPendingURL(null),r?(this._loadUrl(r),this._unblock()):this._block()),this},_moveNavigator:function E(){var t=e(".cms-preview .cms-preview-controls"),n=e(".cms-edit-form .cms-navigator")


n.length&&t.length?t.html(e(".cms-edit-form .cms-navigator").detach()):this._block()},_loadCurrentPage:function P(){if(this.getIsPreviewEnabled()){var t,n=e(".cms-container")
try{t=this.find("iframe")[0].contentDocument}catch(i){console.warn("Unable to access iframe, possible https mis-match")}if(t){var r=e(t).find("meta[name=x-page-id]").attr("content"),o=e(t).find("meta[name=x-cms-edit-link]").attr("content"),a=e(".cms-content")


r&&a.find(":input[name=ID]").val()!=r&&e(".cms-container").entwine(".ss").loadPanel(o)}}},_adjustIframeForPreview:function O(){var e=this.find("iframe")[0],t
if(e){try{t=e.contentDocument}catch(n){console.warn("Unable to access iframe, possible https mis-match")}if(t){for(var i=t.getElementsByTagName("A"),r=0;r<i.length;r++){var o=i[r].getAttribute("href")
o&&o.match(/^http:\/\//)&&i[r].setAttribute("target","_blank")}var a=t.getElementById("SilverStripeNavigator")
a&&(a.style.display="none")
var s=t.getElementById("SilverStripeNavigatorMessage")
s&&(s.style.display="none"),this.trigger("afterIframeAdjustedForPreview",[t])}}}}),e(".cms-edit-form").entwine({onadd:function S(){this._super(),e(".cms-preview")._initialiseFromContent()}}),e(".cms-preview-states").entwine({
changeVisibleState:function k(e){this.find('[data-name="'+e+'"]').addClass("active").siblings().removeClass("active")}}),e(".cms-preview-states .state-name").entwine({onclick:function j(t){if(1==t.which){
var n=e(this).attr("data-name")
this.addClass("active").siblings().removeClass("active"),e(".cms-preview").changeState(n),t.preventDefault()}}}),e(".preview-mode-selector").entwine({changeVisibleMode:function x(e){this.find("select").val(e).trigger("chosen:updated")._addIcon()

}}),e(".preview-mode-selector select").entwine({onchange:function R(t){this._super(t),t.preventDefault()
var n=e(this).val()
e(".cms-preview").changeMode(n)}}),e(".cms-preview.column-hidden").entwine({onmatch:function I(){e("#preview-mode-dropdown-in-content").show(),e(".cms-preview .result-selected").hasClass("font-icon-columns")&&statusMessage(s["default"]._t("LeftAndMain.DISABLESPLITVIEW","Screen too small to show site preview in split mode"),"error"),
this._super()},onunmatch:function F(){e("#preview-mode-dropdown-in-content").hide(),this._super()}}),e("#preview-mode-dropdown-in-content").entwine({onmatch:function A(){e(".cms-preview").is(".column-hidden")?this.show():this.hide(),
this._super()},onunmatch:function D(){this._super()}}),e(".preview-size-selector").entwine({changeVisibleSize:function M(e){this.find("select").val(e).trigger("chosen:updated")._addIcon()}}),e(".preview-size-selector select").entwine({
onchange:function N(t){t.preventDefault()
var n=e(this).val()
e(".cms-preview").changeSize(n)}}),e(".preview-selector select.preview-dropdown").entwine({"onchosen:ready":function U(){this._super(),this._addIcon()},_addIcon:function L(){var e=this.find(":selected"),t=e.attr("data-icon"),n=this.parent().find(".chosen-container a.chosen-single"),i=n.attr("data-icon")


return"undefined"!=typeof i&&n.removeClass(i),n.addClass(t),n.attr("data-icon",t),this}}),e(".preview-mode-selector .chosen-drop li:last-child").entwine({onmatch:function B(){e(".preview-mode-selector").hasClass("split-disabled")?this.parent().append('<div class="disabled-tooltip"></div>'):this.parent().append('<div class="disabled-tooltip" style="display: none;"></div>')

}}),e(".preview-device-outer").entwine({onclick:function H(){this.toggleClass("rotate")}})})},function(e,t,n){(function(e){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var i=n(1),r=t(i),o=n(95),a=t(o)
r["default"].entwine("ss.tree",function(t){t("#Form_BatchActionsForm").entwine({Actions:[],getTree:function n(){return t(".cms-tree")},fromTree:{oncheck_node:function i(e,t){this.serializeFromTree()},onuncheck_node:function r(e,t){
this.serializeFromTree()}},onmatch:function o(){var e=this
e.getTree().bind("load_node.jstree",function(t,n){e.refreshSelected()})},onunmatch:function s(){var e=this
e.getTree().unbind("load_node.jstree")},registerDefault:function l(){this.register(ss.config.adminUrl+"pages/batchactions/publish",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_PUBLISH_PROMPT","You have {num} page(s) selected.\n\nDo you really want to publish?"),{
num:e.length}))
return!!t&&e}),this.register(ss.config.adminUrl+"pages/batchactions/unpublish",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_UNPUBLISH_PROMPT","You have {num} page(s) selected.\n\nDo you really want to unpublish"),{
num:e.length}))
return!!t&&e}),this.register(ss.config.adminUrl+"pages/batchactions/delete",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_DELETE_PROMPT","You have {num} page(s) selected.\n\nDo you really want to delete?"),{
num:e.length}))
return!!t&&e}),this.register(ss.config.adminUrl+"pages/batchactions/archive",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_ARCHIVE_PROMPT","You have {num} page(s) selected.\n\nAre you sure you want to archive these pages?\n\nThese pages and all of their children pages will be unpublished and sent to the archive."),{
num:e.length}))
return!!t&&e}),this.register(ss.config.adminUrl+"pages/batchactions/restore",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_RESTORE_PROMPT","You have {num} page(s) selected.\n\nDo you really want to restore to stage?\n\nChildren of archived pages will be restored to the root level, unless those pages are also being restored."),{
num:e.length}))
return!!t&&e}),this.register(ss.config.adminUrl+"pages/batchactions/deletefromlive",function(e){var t=confirm(a["default"].inject(a["default"]._t("CMSMAIN.BATCH_DELETELIVE_PROMPT","You have {num} page(s) selected.\n\nDo you really want to delete these pages from live?"),{
num:e.length}))
return!!t&&e})},onadd:function u(){this.registerDefault(),this._super()},register:function d(e,t){this.trigger("register",{type:e,callback:t})
var n=this.getActions()
n[e]=t,this.setActions(n)},unregister:function c(e){this.trigger("unregister",{type:e})
var t=this.getActions()
t[e]&&delete t[e],this.setActions(t)},refreshSelected:function f(n){var i=this,r=this.getTree(),o=this.getIDs(),a=[],s=t(".cms-content-batchactions-button"),l=this.find(":input[name=Action]").val()
null==n&&(n=r)
for(var u in o)t(t(r).getNodeByID(u)).addClass("selected").attr("selected","selected")
if(!l||l==-1||!s.hasClass("active"))return void t(n).find("li").each(function(){t(this).setEnabled(!0)})
t(n).find("li").each(function(){a.push(t(this).data("id")),t(this).addClass("treeloading").setEnabled(!1)})
var d=t.path.parseUrl(l),c=d.hrefNoSearch+"/applicablepages/"
c=t.path.addSearchParams(c,d.search),c=t.path.addSearchParams(c,{csvIDs:a.join(",")}),e.getJSON(c,function(r){e(n).find("li").each(function(){t(this).removeClass("treeloading")
var e=t(this).data("id")
0==e||t.inArray(e,r)>=0?t(this).setEnabled(!0):(t(this).removeClass("selected").setEnabled(!1),t(this).prop("selected",!1))}),i.serializeFromTree()})},serializeFromTree:function p(){var e=this.getTree(),t=e.getSelectedIDs()


return this.setIDs(t),!0},setIDs:function h(e){this.find(":input[name=csvIDs]").val(e?e.join(","):null)},getIDs:function m(){var e=this.find(":input[name=csvIDs]").val()
return e?e.split(","):[]},onsubmit:function g(n){var i=this,r=this.getIDs(),o=this.getTree(),s=this.getActions()
if(!r||!r.length)return alert(a["default"]._t("CMSMAIN.SELECTONEPAGE","Please select at least one page")),n.preventDefault(),!1
var l=this.find(":input[name=Action]").val()
if(s[l]&&(r=this.getActions()[l].apply(this,[r])),!r||!r.length)return n.preventDefault(),!1
this.setIDs(r),o.find("li").removeClass("failed")
var u=this.find(":submit:first")
return u.addClass("loading"),e.ajax({url:l,type:"POST",data:this.serializeArray(),complete:function d(e,t){u.removeClass("loading"),o.jstree("refresh",-1),i.setIDs([]),i.find(":input[name=Action]").val("").change()


var n=e.getResponseHeader("X-Status")
n&&statusMessage(decodeURIComponent(n),"success"==t?"good":"bad")},success:function c(e,n){var i,r
if(e.modified){var a=[]
for(i in e.modified)r=o.getNodeByID(i),o.jstree("set_text",r,e.modified[i].TreeTitle),a.push(r)
t(a).effect("highlight")}if(e.deleted)for(i in e.deleted)r=o.getNodeByID(i),r.length&&o.jstree("delete_node",r)
if(e.error)for(i in e.error)r=o.getNodeByID(i),t(r).addClass("failed")},dataType:"json"}),n.preventDefault(),!1}}),t(".cms-content-batchactions-button").entwine({onmatch:function v(){this._super(),this.updateTree()

},onunmatch:function y(){this._super()},onclick:function b(e){this.updateTree()},updateTree:function w(){var e=t(".cms-tree"),n=t("#Form_BatchActionsForm")
this._super(),this.data("active")?(e.addClass("multiple"),e.removeClass("draggable"),n.serializeFromTree()):(e.removeClass("multiple"),e.addClass("draggable")),t("#Form_BatchActionsForm").refreshSelected()

}}),t("#Form_BatchActionsForm select[name=Action]").entwine({onchange:function _(e){var n=t(e.target.form),i=n.find(":submit"),r=t(e.target).val()
r&&r!=-1?i.removeAttr("disabled").button("refresh"):i.attr("disabled","disabled").button("refresh"),t("#Form_BatchActionsForm").refreshSelected(),this.trigger("chosen:updated"),this._super(e)}})})}).call(t,n(1))

},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".cms .field.cms-description-tooltip").entwine({onmatch:function t(){this._super()
var e=this.find(".description"),t,n
e.length&&(this.attr("title",e.text()).tooltip({content:e.html()}),e.remove())}}),e(".cms .field.cms-description-tooltip :input").entwine({onfocusin:function n(e){this.closest(".field").tooltip("open")

},onfocusout:function i(e){this.closest(".field").tooltip("close")}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".cms-description-toggle").entwine({onadd:function t(){var e=!1,t=this.prop("id").substr(0,this.prop("id").indexOf("_Holder")),n=this.find(".cms-description-trigger"),i=this.find(".description")


this.hasClass("description-toggle-enabled")||(0===n.length&&(n=this.find(".middleColumn").first().after('<label class="right" for="'+t+'"><a class="cms-description-trigger" href="javascript:void(0)"><span class="btn-icon-information"></span></a></label>').next()),
this.addClass("description-toggle-enabled"),n.on("click",function(){i[e?"hide":"show"](),e=!e}),i.hide())}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".TreeDropdownField").entwine({"from .cms-container form":{onaftersubmitform:function t(e){this.find(".tree-holder").empty(),this._super()}}})})},function(e,t,n){
"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r),a=n(95),s=i(a),l=n(4),u=i(l),d=n(103),c=i(d),f=n(27),p=n(104),h=i(p)
o["default"].entwine("ss",function(e){e(".cms-content-actions .add-to-campaign-action,#add-to-campaign__action").entwine({onclick:function t(){var t=e("#add-to-campaign__dialog-wrapper")
return t.length||(t=e('<div id="add-to-campaign__dialog-wrapper" />'),e("body").append(t)),t.open(),!1}}),e("#add-to-campaign__dialog-wrapper").entwine({open:function n(){this._renderModal()},close:function i(){
this._clearModal()},_renderModal:function r(){var t=this,n=function l(){return t._clearModal()},i=function d(){return t._handleSubmitModal.apply(t,arguments)},r=e("form.cms-edit-form :input[name=ID]").val(),o=window.ss.store,a=o.getState().config.sections["SilverStripe\\CMS\\Controllers\\CMSPageEditController"],s=a.form.AddToCampaignForm.schemaUrl+"/"+r


c["default"].render(u["default"].createElement(f.Provider,{store:o},u["default"].createElement(h["default"],{show:!0,handleSubmit:i,handleHide:n,schemaUrl:s,bodyClassName:"modal__dialog",responseClassBad:"modal__response modal__response--error",
responseClassGood:"modal__response modal__response--good"})),this[0])},_clearModal:function o(){c["default"].unmountComponentAtNode(this[0])},_handleSubmitModal:function a(e,t,n){return e.preventDefault(),
t.Campaign?n():(alert(s["default"]._t("AddToCampaigns.ErrorCampaignNotSelected","There was no campaign selected to be added to")),null)}})})},,function(e,t){e.exports=FormBuilderModal},function(e,t,n){
"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
n(89),n(106)
var a=function s(e){var t=(0,o["default"])((0,o["default"])(this).contents()).find(".message")
if(t&&t.html()){var n=(0,o["default"])(window.parent.document).find("#Form_EditForm_Members").get(0)
n&&n.refresh()
var i=(0,o["default"])(window.parent.document).find(".cms-tree").get(0)
i&&i.reload()}};(0,o["default"])("#MemberImportFormIframe, #GroupImportFormIframe").entwine({onadd:function l(){this._super(),(0,o["default"])(this).bind("load",a)}}),o["default"].entwine("ss",function(e){
e(".permissioncheckboxset .checkbox[value=ADMIN]").entwine({onmatch:function t(){this.toggleCheckboxes(),this._super()},onunmatch:function n(){this._super()},onclick:function i(e){this.toggleCheckboxes()

},toggleCheckboxes:function r(){var t=this,n=this.parents(".field:eq(0)").find(".checkbox").not(this)
this.is(":checked")?n.each(function(){e(this).data("SecurityAdmin.oldChecked",e(this).is(":checked")),e(this).data("SecurityAdmin.oldDisabled",e(this).is(":disabled")),e(this).prop("disabled",!0),e(this).prop("checked",!0)

}):n.each(function(){e(this).prop("checked",e(this).data("SecurityAdmin.oldChecked")),e(this).prop("disabled",e(this).data("SecurityAdmin.oldDisabled"))})}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
o["default"].entwine("ss",function(e){e(".permissioncheckboxset .valADMIN input").entwine({onmatch:function t(){this._super()},onunmatch:function n(){this._super()},onclick:function i(e){this.toggleCheckboxes()

},toggleCheckboxes:function r(){var t=e(this).parents(".field:eq(0)").find(".checkbox").not(this)
e(this).is(":checked")?t.each(function(){e(this).data("SecurityAdmin.oldChecked",e(this).attr("checked")),e(this).data("SecurityAdmin.oldDisabled",e(this).attr("disabled")),e(this).attr("disabled","disabled"),
e(this).attr("checked","checked")}):t.each(function(){var t=e(this).data("SecurityAdmin.oldChecked"),n=e(this).data("SecurityAdmin.oldDisabled")
null!==t&&e(this).attr("checked",t),null!==n&&e(this).attr("disabled",n)})}}),e(".permissioncheckboxset .valCMS_ACCESS_LeftAndMain input").entwine({getCheckboxesExceptThisOne:function o(){return e(this).parents(".field:eq(0)").find("li").filter(function(t){
var n=e(this).attr("class")
return!!n&&n.match(/CMS_ACCESS_/)}).find(".checkbox").not(this)},onmatch:function a(){this.toggleCheckboxes(),this._super()},onunmatch:function s(){this._super()},onclick:function l(e){this.toggleCheckboxes()

},toggleCheckboxes:function u(){var t=this.getCheckboxesExceptThisOne()
e(this).is(":checked")?t.each(function(){e(this).data("PermissionCheckboxSetField.oldChecked",e(this).is(":checked")),e(this).data("PermissionCheckboxSetField.oldDisabled",e(this).is(":disabled")),e(this).prop("disabled","disabled"),
e(this).prop("checked","checked")}):t.each(function(){e(this).prop("checked",e(this).data("PermissionCheckboxSetField.oldChecked")),e(this).prop("disabled",e(this).data("PermissionCheckboxSetField.oldDisabled"))

})}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
n(89),o["default"].entwine("ss",function(e){e(".cms-content-tools #Form_SearchForm").entwine({onsubmit:function t(e){this.trigger("beforeSubmit")}}),e(".importSpec").entwine({onmatch:function n(){this.find("div.details").hide(),
this.find("a.detailsLink").click(function(){return e("#"+e(this).attr("href").replace(/.*#/,"")).slideToggle(),!1}),this._super()},onunmatch:function i(){this._super()}})})},function(e,t,n){"use strict"


function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r);(0,o["default"])(".ss-assetuploadfield").entwine({onmatch:function a(){this._super(),this.find(".ss-uploadfield-editandorganize").hide()

},onunmatch:function s(){this._super()},onfileuploadadd:function l(e){this.find(".ss-uploadfield-editandorganize").show()},onfileuploadstart:function u(e){this.find(".ss-uploadfield-editandorganize").show()

}}),(0,o["default"])(".ss-uploadfield-view-allowed-extensions .toggle").entwine({onclick:function d(e){var t=this.closest(".ss-uploadfield-view-allowed-extensions"),n=this.closest(".ui-tabs-panel").height()+20


t.toggleClass("active"),t.find(".toggle-content").css("minHeight",n)}})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r);(0,o["default"])(document).on("click",".confirmedpassword .showOnClick a",function(){var e=(0,o["default"])(".showOnClickContainer",(0,
o["default"])(this).parent())
return e.toggle("fast",function(){e.find('input[type="hidden"]').val(e.is(":visible")?1:0)}),!1})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r),a=n(95),s=i(a)
window.tmpl=n(111),n(112),n(113),o["default"].widget("blueimpUIX.fileupload",o["default"].blueimpUI.fileupload,{_initTemplates:function l(){this.options.templateContainer=document.createElement(this._files.prop("nodeName")),
this.options.uploadTemplate=window.tmpl(this.options.uploadTemplateName),this.options.downloadTemplate=window.tmpl(this.options.downloadTemplateName)},_enableFileInputButton:function u(){o["default"].blueimpUI.fileupload.prototype._enableFileInputButton.call(this),
this.element.find(".ss-uploadfield-addfile").show()},_disableFileInputButton:function d(){o["default"].blueimpUI.fileupload.prototype._disableFileInputButton.call(this),this.element.find(".ss-uploadfield-addfile").hide()

},_onAdd:function c(e,t){var n=o["default"].blueimpUI.fileupload.prototype._onAdd.call(this,e,t),i=this._files.find(".ss-uploadfield-item").slice(t.files.length*-1).first(),r="+="+(i.position().top-parseInt(i.css("marginTop"),10)||0-parseInt(i.css("borderTopWidth"),10)||0)


i.offsetParent().animate({scrollTop:r},1e3)
for(var a=0,l=0;l<t.files.length;l++)"number"==typeof t.files[l].size&&(a+=t.files[l].size)
return(0,o["default"])(".fileOverview .uploadStatus .details .total").text(t.files.length),"number"==typeof a&&a>0&&(a=this._formatFileSize(a),(0,o["default"])(".fileOverview .uploadStatus .details .fileSize").text(a)),
1==t.files.length&&null!==t.files[0].error?((0,o["default"])(".fileOverview .uploadStatus .state").text(s["default"]._t("AssetUploadField.UploadField.UPLOADFAIL","Sorry your upload failed")),(0,o["default"])(".fileOverview .uploadStatus").addClass("bad").removeClass("good").removeClass("notice")):((0,
o["default"])(".fileOverview .uploadStatus .state").text(s["default"]._t("AssetUploadField.UPLOADINPROGRESS","Please wait… upload in progress")),(0,o["default"])(".ss-uploadfield-item-edit-all").hide(),
(0,o["default"])(".fileOverview .uploadStatus").addClass("notice").removeClass("good").removeClass("bad")),n},_onDone:function f(e,t,n,i){this.options.changeDetection&&this.element.closest("form").trigger("dirty"),
o["default"].blueimpUI.fileupload.prototype._onDone.call(this,e,t,n,i)},_onSend:function p(e,t){var n=this,i=this.options
return i.overwriteWarning&&i.replaceFile?void o["default"].get(i.urlFileExists,{filename:t.files[0].name},function(r,a,s){return r.exists?(t.context.find(".ss-uploadfield-item-status").text(i.errorMessages.overwriteWarning).addClass("ui-state-warning-text"),
t.context.find(".ss-uploadfield-item-progress").hide(),t.context.find(".ss-uploadfield-item-overwrite").show(),t.context.find(".ss-uploadfield-item-overwrite-warning").on("click",function(e){return t.context.find(".ss-uploadfield-item-progress").show(),
t.context.find(".ss-uploadfield-item-overwrite").hide(),t.context.find(".ss-uploadfield-item-status").removeClass("ui-state-warning-text"),o["default"].blueimpUI.fileupload.prototype._onSend.call(n,e,t),
e.preventDefault(),!1}),void 0):o["default"].blueimpUI.fileupload.prototype._onSend.call(n,e,t)}):o["default"].blueimpUI.fileupload.prototype._onSend.call(n,e,t)},_onAlways:function h(e,t,n,i){o["default"].blueimpUI.fileupload.prototype._onAlways.call(this,e,t,n,i),
"string"==typeof n?((0,o["default"])(".fileOverview .uploadStatus .state").text(s["default"]._t("AssetUploadField.UploadField.UPLOADFAIL","Sorry your upload failed")),(0,o["default"])(".fileOverview .uploadStatus").addClass("bad").removeClass("good").removeClass("notice")):200===n.status&&((0,
o["default"])(".fileOverview .uploadStatus .state").text(s["default"]._t("AssetUploadField.FILEUPLOADCOMPLETED","File upload completed!")),(0,o["default"])(".ss-uploadfield-item-edit-all").show(),(0,o["default"])(".fileOverview .uploadStatus").addClass("good").removeClass("notice").removeClass("bad"))

},_create:function m(){o["default"].blueimpUI.fileupload.prototype._create.call(this),this._adjustMaxNumberOfFiles(0)},attach:function g(e){this.options.changeDetection&&this.element.closest("form").trigger("dirty")


var t=this,n=e.files,i=e.replaceFileID,r=!0,a=null
i&&(a=(0,o["default"])(".ss-uploadfield-item[data-fileid='"+i+"']"),0===a.length?a=null:t._adjustMaxNumberOfFiles(1)),o["default"].each(n,function(e,n){t._adjustMaxNumberOfFiles(-1),r=t._validate([n])&&r

}),e.isAdjusted=!0,e.files.valid=e.isValidated=r,e.context=this._renderDownload(n),a?a.replaceWith(e.context):e.context.appendTo(this._files),e.context.data("data",e),this._reflow=this._transition&&e.context[0].offsetWidth,
e.context.addClass("in")}}),o["default"].entwine("ss",function(e){e("div.ss-upload").entwine({Config:null,onmatch:function t(){if(!this.is(".readonly,.disabled")){var t=this.find(".ss-uploadfield-fromcomputer-fileinput"),n=e(".ss-uploadfield-dropzone"),i=t.data("config")


n.on("dragover",function(e){e.preventDefault()}),n.on("dragenter",function(e){n.addClass("hover active")}),n.on("dragleave",function(e){e.target===n[0]&&n.removeClass("hover active")}),n.on("drop",function(e){
if(n.removeClass("hover active"),e.target!==n[0])return!1}),this.setConfig(i),this.fileupload(e.extend(!0,{formData:function r(t){var n=e(t).find(":input[name=ID]").val(),i=[{name:"SecurityID",value:e(t).find(":input[name=SecurityID]").val()
}]
return n&&i.push({name:"ID",value:n}),i},errorMessages:{1:s["default"]._t("UploadField.PHP_MAXFILESIZE"),2:s["default"]._t("UploadField.HTML_MAXFILESIZE"),3:s["default"]._t("UploadField.ONLYPARTIALUPLOADED"),
4:s["default"]._t("UploadField.NOFILEUPLOADED"),5:s["default"]._t("UploadField.NOTMPFOLDER"),6:s["default"]._t("UploadField.WRITEFAILED"),7:s["default"]._t("UploadField.STOPEDBYEXTENSION"),maxFileSize:s["default"]._t("UploadField.TOOLARGESHORT"),
minFileSize:s["default"]._t("UploadField.TOOSMALL"),acceptFileTypes:s["default"]._t("UploadField.INVALIDEXTENSIONSHORT"),maxNumberOfFiles:s["default"]._t("UploadField.MAXNUMBEROFFILESSHORT"),uploadedBytes:s["default"]._t("UploadField.UPLOADEDBYTES"),
emptyResult:s["default"]._t("UploadField.EMPTYRESULT")},send:function o(t,n){n.context&&n.dataType&&"iframe"===n.dataType.substr(0,6)&&(n.total=1,n.loaded=1,e(this).data("fileupload").options.progress(t,n))

},progress:function a(e,t){if(t.context){var n=parseInt(t.loaded/t.total*100,10)+"%"
t.context.find(".ss-uploadfield-item-status").html(1==t.total?s["default"]._t("UploadField.LOADING"):n),t.context.find(".ss-uploadfield-item-progressbarvalue").css("width",n)}}},i,{fileInput:t,dropZone:n,
form:t.closest("form"),previewAsCanvas:!1,acceptFileTypes:new RegExp(i.acceptFileTypes,"i")})),this.data("fileupload")._isXHRUpload({multipart:!0})&&e(".ss-uploadfield-item-uploador").hide().show(),this._super()

}},onunmatch:function n(){e(".ss-uploadfield-dropzone").off("dragover dragenter dragleave drop"),this._super()},openSelectDialog:function i(t){var n=this,i=this.getConfig(),r="ss-uploadfield-dialog-"+this.attr("id"),o=e("#"+r)


o.length||(o=e('<div class="ss-uploadfield-dialog" id="'+r+'" />'))
var a=i.urlSelectDialog,s=null
t&&t.attr("data-fileid")>0&&(s=t.attr("data-fileid")),o.ssdialog({iframeUrl:a,height:550}),o.find("iframe").bind("load",function(t){var i=e(this).contents(),r=i.find(".grid-field")
i.find("table.grid-field").css("margin-top",0),i.find("input[name=action_doAttach]").unbind("click.openSelectDialog").bind("click.openSelectDialog",function(){var t=e.map(r.find(".ss-gridfield-item.ui-selected"),function(t){
return e(t).data("id")})
return t&&t.length&&n.attachFiles(t,s),o.ssdialog("close"),!1})}),o.ssdialog("open")},attachFiles:function r(t,n){var i=this,r=this.getConfig(),o=e('<div class="loader" />'),a=n?this.find(".ss-uploadfield-item[data-fileid='"+n+"']"):this.find(".ss-uploadfield-addfile")


a.children().hide(),a.append(o),e.ajax({type:"POST",url:r.urlAttach,data:{ids:t},complete:function s(e,t){a.children().show(),o.remove()},success:function l(t,r,o){t&&!e.isEmptyObject(t)&&i.fileupload("attach",{
files:t,options:i.fileupload("option"),replaceFileID:n})}})}}),e("div.ss-upload *").entwine({getUploadField:function o(){return this.parents("div.ss-upload:first")}}),e("div.ss-upload .ss-uploadfield-files .ss-uploadfield-item").entwine({
onadd:function a(){this._super(),this.closest(".ss-upload").find(".ss-uploadfield-addfile").addClass("borderTop")},onremove:function l(){e(".ss-uploadfield-files:not(:has(.ss-uploadfield-item))").closest(".ss-upload").find(".ss-uploadfield-addfile").removeClass("borderTop"),
this._super()}}),e("div.ss-upload .ss-uploadfield-startall").entwine({onclick:function u(e){return this.closest(".ss-upload").find(".ss-uploadfield-item-start button").click(),e.preventDefault(),!1}}),
e("div.ss-upload .ss-uploadfield-item-cancelfailed").entwine({onclick:function d(e){return this.closest(".ss-uploadfield-item").remove(),e.preventDefault(),!1}}),e("div.ss-upload .ss-uploadfield-item-remove:not(.ui-state-disabled), .ss-uploadfield-item-delete:not(.ui-state-disabled)").entwine({
onclick:function c(e){var t=this.closest("div.ss-upload"),n=t.getConfig("changeDetection"),i=t.data("fileupload"),r=this.closest(".ss-uploadfield-item"),o=""
return this.is(".ss-uploadfield-item-delete")?confirm(s["default"]._t("UploadField.ConfirmDelete"))&&(n.changeDetection&&this.closest("form").trigger("dirty"),i&&i._trigger("destroy",e,{context:r,url:this.data("href"),
type:"get",dataType:i.options.dataType})):(n.changeDetection&&this.closest("form").trigger("dirty"),i&&i._trigger("destroy",e,{context:r})),e.preventDefault(),!1}}),e("div.ss-upload .ss-uploadfield-item-edit-all").entwine({
onclick:function f(t){return e(this).hasClass("opened")?(e(".ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon.opened").each(function(t){e(this).closest(".ss-uploadfield-item-edit").click()

}),e(this).removeClass("opened").find(".toggle-details-icon").removeClass("opened")):(e(".ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon").each(function(t){e(this).hasClass("opened")||e(this).closest(".ss-uploadfield-item-edit").click()

}),e(this).addClass("opened").find(".toggle-details-icon").addClass("opened")),t.preventDefault(),!1}}),e("div.ss-upload:not(.disabled):not(.readonly) .ss-uploadfield-item-edit").entwine({onclick:function p(e){
var t=this,n=t.closest(".ss-uploadfield-item").find(".ss-uploadfield-item-editform"),i=n.prev(".ss-uploadfield-item-info"),r=n.find("iframe")
if(r.parent().hasClass("loading"))return e.preventDefault(),!1
if("about:blank"==r.attr("src")){var o=this.siblings()
r.attr("src",r.data("src")),r.parent().addClass("loading"),o.addClass("ui-state-disabled"),o.attr("disabled","disabled"),r.on("load",function(){r.parent().removeClass("loading"),r.data("src")&&(t._prepareIframe(r,n,i),
r.data("src",""))})}else t._prepareIframe(r,n,i)
return e.preventDefault(),!1},_prepareIframe:function h(e,t,n){var i
e.contents().ready(function(){var n=e.get(0).contentWindow.jQuery
n(n.find(":input")).bind("change",function(e){t.removeClass("edited"),t.addClass("edited")})}),t.hasClass("loading")||(i=this.hasClass("ss-uploadfield-item-edit")?this.siblings():this.find("ss-uploadfield-item-edit").siblings(),
t.parent(".ss-uploadfield-item").removeClass("ui-state-warning"),t.toggleEditForm(),n.find(".toggle-details-icon").hasClass("opened")?(i.addClass("ui-state-disabled"),i.attr("disabled","disabled")):(i.removeClass("ui-state-disabled"),
i.removeAttr("disabled")))}}),e("div.ss-upload .ss-uploadfield-item-editform").entwine({fitHeight:function m(){var t=this.find("iframe"),n=t.contents().find("body"),i=n.find("form").outerHeight(!0),r=i+(t.outerHeight(!0)-t.height()),o=r+(this.outerHeight(!0)-this.height())


e.browser.msie||"8.0"==e.browser.version.slice(0,3)||n.find("body").css({height:i}),t.height(r),this.animate({height:o},500)},toggleEditForm:function g(){var t=this.prev(".ss-uploadfield-item-info"),n=t.find(".ss-uploadfield-item-status"),i=this.find("iframe").contents(),r=i.find("#Form_EditForm_error"),o=""


0===this.height()?(o=s["default"]._t("UploadField.Editing","Editing ..."),this.fitHeight(),this.addClass("opened"),t.find(".toggle-details-icon").addClass("opened"),n.removeClass("ui-state-success-text").removeClass("ui-state-warning-text"),
i.find("#Form_EditForm_action_doEdit").click(function(){t.find("label .name").text(i.find("#Name input").val())}),e("div.ss-upload  .ss-uploadfield-files .ss-uploadfield-item-actions .toggle-details-icon:not(.opened)").index()<0&&e("div.ss-upload .ss-uploadfield-item-edit-all").addClass("opened").find(".toggle-details-icon").addClass("opened")):(this.animate({
height:0},500),this.removeClass("opened"),t.find(".toggle-details-icon").removeClass("opened"),e("div.ss-upload .ss-uploadfield-item-edit-all").removeClass("opened").find(".toggle-details-icon").removeClass("opened"),
this.hasClass("edited")?r.hasClass("good")?(o=s["default"]._t("UploadField.CHANGESSAVED","Changes Saved"),this.removeClass("edited").parent(".ss-uploadfield-item").removeClass("ui-state-warning"),n.addClass("ui-state-success-text")):(o=s["default"]._t("UploadField.UNSAVEDCHANGES","Unsaved Changes"),
this.parent(".ss-uploadfield-item").addClass("ui-state-warning"),n.addClass("ui-state-warning-text")):(o=s["default"]._t("UploadField.NOCHANGES","No Changes"),n.addClass("ui-state-success-text")),r.removeClass("good").hide()),
n.attr("title",o).text(o)}}),e("div.ss-upload .ss-uploadfield-fromfiles").entwine({onclick:function v(e){return this.getUploadField().openSelectDialog(this.closest(".ss-uploadfield-item")),e.preventDefault(),
!1}})})},function(e,t,n){var i
!function(r){"use strict"
var o=function(e,t){var n=/[^\-\w]/.test(e)?new Function(o.arg,("var _s=''"+o.helper+";_s+='"+e.replace(o.regexp,o.func)+"';return _s;").split("_s+='';").join("")):o.cache[e]=o.cache[e]||o(o.load(e))
return n.tmpl=n.tmpl||o,t?n(t):n}
o.cache={},o.load=function(e){return document.getElementById(e).innerHTML},o.regexp=/(\s+)|('|\\)(?![^%]*%\})|(?:\{%(=|#)(.+?)%\})|(\{%)|(%\})/g,o.func=function(e,t,n,i,r,o,a,s,l){return t?s&&s+e.length!==l.length?" ":"":n?"\\"+e:i?"="===i?"'+_e("+r+")+'":"'+("+r+"||'')+'":o?"';":a?"_s+='":void 0

},o.encReg=/[<>&"\x00]/g,o.encMap={"<":"&lt;",">":"&gt;","&":"&amp;",'"':"&quot;","\0":""},o.encode=function(e){return String(e||"").replace(o.encReg,function(e){return o.encMap[e]})},o.arg="o",o.helper=",_t=arguments.callee.tmpl,_e=_t.encode,print=function(s,e){_s+=e&&(s||'')||_e(s);},include=function(s,d){_s+=_t(s,d);}",
i=function(){return o}.call(t,n,t,e),!(void 0!==i&&(e.exports=i))}(this)},function(e,t){"use strict"
window.tmpl.cache["ss-uploadfield-uploadtemplate"]=window.tmpl('{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}<li class="ss-uploadfield-item template-upload{% if (file.error) { %} ui-state-error{% } %}"><div class="ss-uploadfield-item-preview preview"><span></span></div><div class="ss-uploadfield-item-info"><label class="ss-uploadfield-item-name"><span class="name" title="{% if (file.name) { %}{%=file.name%}{% } else { %}'+ss.i18n._t("UploadField.NOFILENAME","Untitled")+'{% } %}">{% if (file.name) { %}{%=file.name%}{% } else { %}'+ss.i18n._t("UploadField.NOFILENAME","Untitled")+'{% } %}</span> {% if (!file.error) { %}<div class="ss-uploadfield-item-status">0%</div>{% } else {  %}<div class="ss-uploadfield-item-status ui-state-error-text" title="{%=o.options.errorMessages[file.error] || file.error%}">{%=o.options.errorMessages[file.error] || file.error%}</div>{% } %}<div class="clear"><!-- --></div></label><div class="ss-uploadfield-item-actions">{% if (!file.error) { %}<div class="ss-uploadfield-item-progress"><div class="ss-uploadfield-item-progressbar"><div class="ss-uploadfield-item-progressbarvalue"></div></div></div>{% if (!o.options.autoUpload) { %}<div class="ss-uploadfield-item-start start"><button type="button" class="icon icon-16" data-icon="navigation">'+ss.i18n._t("UploadField.START","Start")+'</button></div>{% } %}{% } %}<div class="ss-uploadfield-item-cancel cancel"><button type="button" class="icon icon-16" data-icon="minus-circle" title="'+ss.i18n._t("UploadField.CANCELREMOVE","Cancel/Remove")+'">'+ss.i18n._t("UploadField.CANCELREMOVE","Cancel/Remove")+'</button></div><div class="ss-uploadfield-item-overwrite hide "><button type="button" data-icon="drive-upload" class="ss-uploadfield-item-overwrite-warning" title="'+ss.i18n._t("UploadField.OVERWRITE","Overwrite")+'">'+ss.i18n._t("UploadField.OVERWRITE","Overwrite")+"</button></div></div></div></li>{% } %}")

},function(e,t){"use strict"
tmpl.cache["ss-uploadfield-downloadtemplate"]=tmpl('{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}<li class="ss-uploadfield-item template-download{% if (file.error) { %} ui-state-error{% } %}" data-fileid="{%=file.id%}">{% if (file.thumbnail_url) { %}<div class="ss-uploadfield-item-preview preview"><span><img src="{%=file.thumbnail_url%}" alt="" /></span></div>{% } %}<div class="ss-uploadfield-item-info">{% if (!file.error && file.id) { %}<input type="hidden" name="{%=file.fieldname%}[Files][]" value="{%=file.id%}" />{% } %}{% if (!file.error && file.filename) { %}<input type="hidden" value="{%=file.filename%}" name="{%=file.fieldname%}[Filename]" /><input type="hidden" value="{%=file.hash%}" name="{%=file.fieldname%}[Hash]" /><input type="hidden" value="{%=file.variant%}" name="{%=file.fieldname%}[Variant]" />{% } %}<label class="ss-uploadfield-item-name"><span class="name" title="{%=file.name%}">{%=file.name%}</span> <span class="size">{%=o.formatFileSize(file.size)%}</span>{% if (!file.error) { %}<div class="ss-uploadfield-item-status ui-state-success-text" title="'+ss.i18n._t("UploadField.Uploaded","Uploaded")+'">'+ss.i18n._t("UploadField.Uploaded","Uploaded")+'</div>{% } else {  %}<div class="ss-uploadfield-item-status ui-state-error-text" title="{%=o.options.errorMessages[file.error] || file.error%}">{%=o.options.errorMessages[file.error] || file.error%}</div>{% } %}<div class="clear"><!-- --></div></label>{% if (file.error) { %}<div class="ss-uploadfield-item-actions"><div class="ss-uploadfield-item-cancel ss-uploadfield-item-cancelfailed delete"><button type="button" class="icon icon-16" data-icon="delete" title="'+ss.i18n._t("UploadField.CANCELREMOVE","Cancel/Remove")+'">'+ss.i18n._t("UploadField.CANCELREMOVE","Cancel/Remove")+'</button></div></div>{% } else { %}<div class="ss-uploadfield-item-actions">{% print(file.buttons, true); %}</div>{% } %}</div>{% if (!file.error) { %}<div class="ss-uploadfield-item-editform"><iframe frameborder="0" data-src="{%=file.edit_url%}" src="about:blank"></iframe></div>{% } %}</li>{% } %}')

},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r);(0,o["default"])(document).ready(function(){(0,o["default"])("ul.SelectionGroup input.selector, ul.selection-group input.selector").live("click",function(){
var e=(0,o["default"])(this).closest("li")
e.addClass("selected")
var t=e.prevAll("li.selected")
t.length&&t.removeClass("selected")
var n=e.nextAll("li.selected")
n.length&&n.removeClass("selected"),(0,o["default"])(this).focus()})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
n(87),o["default"].fn.extend({ssDatepicker:function a(e){return(0,o["default"])(this).each(function(){if(!((0,o["default"])(this).prop("disabled")||(0,o["default"])(this).prop("readonly")||(0,o["default"])(this).data("datepicker"))){
(0,o["default"])(this).siblings("button").addClass("ui-icon ui-icon-calendar")
var t=(0,o["default"])(this).closest(".field.date"),n=o["default"].extend(e||{},(0,o["default"])(this).data(),(0,o["default"])(this).data("jqueryuiconfig"),{})
n.showcalendar&&(n.locale&&o["default"].datepicker.regional[n.locale]&&(n=o["default"].extend(n,o["default"].datepicker.regional[n.locale],{})),n.min&&(n.minDate=o["default"].datepicker.parseDate("yy-mm-dd",n.min)),
n.max&&(n.maxDate=o["default"].datepicker.parseDate("yy-mm-dd",n.max)),n.dateFormat=n.jquerydateformat,(0,o["default"])(this).datepicker(n))}})}}),(0,o["default"])(document).on("click",".field.date input.text,input.text.date",function(){
(0,o["default"])(this).ssDatepicker(),(0,o["default"])(this).data("datepicker")&&(0,o["default"])(this).datepicker("show")})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
n(87),o["default"].entwine("ss",function(e){e(".ss-toggle").entwine({onadd:function t(){this._super(),this.accordion({heightStyle:"content",collapsible:!0,active:!this.hasClass("ss-toggle-start-closed")&&0
})},onremove:function n(){this.data("accordion")&&this.accordion("destroy"),this._super()},getTabSet:function i(){return this.closest(".ss-tabset")},fromTabSet:{ontabsshow:function r(){this.accordion("resize")

}}})})},function(e,t,n){(function(e){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var i=n(1),r=t(i)
r["default"].entwine("ss",function(t){t(".memberdatetimeoptionset").entwine({onmatch:function n(){this.find(".toggle-content").hide(),this._super()}}),t(".memberdatetimeoptionset .toggle").entwine({onclick:function i(t){
return e(this).closest(".form__field-description").parent().find(".toggle-content").toggle(),!1}})})}).call(t,n(1))},function(e,t,n){(function(e){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var i=n(1),r=t(i),o=n(95),a=t(o)
n(119),n(120),r["default"].entwine("ss",function(t){var n,i
t(window).bind("resize.treedropdownfield",function(){var e=function a(){t(".TreeDropdownField").closePanel()}
if(t.browser.msie&&parseInt(t.browser.version,10)<9){var r=t(window).width(),o=t(window).height()
r==n&&o==i||(n=r,i=o,e())}else e()})
var r={openlink:a["default"]._t("TreeDropdownField.OpenLink"),fieldTitle:"("+a["default"]._t("TreeDropdownField.FieldTitle")+")",searchFieldTitle:"("+a["default"]._t("TreeDropdownField.SearchFieldTitle")+")"
},o=function s(e){t(e.target).parents(".TreeDropdownField").length||t(".TreeDropdownField").closePanel()}
t(".TreeDropdownField").entwine({CurrentXhr:null,onadd:function l(){this.append('<span class="treedropdownfield-title"></span><div class="treedropdownfield-toggle-panel-link"><a href="#" class="ui-icon ui-icon-triangle-1-s"></a></div><div class="treedropdownfield-panel"><div class="tree-holder"></div></div>')


var e=r.openLink
e&&this.find("treedropdownfield-toggle-panel-link a").attr("title",e),this.data("title")&&this.setTitle(this.data("title")),this.getPanel().hide(),this._super()},getPanel:function u(){return this.find(".treedropdownfield-panel")

},openPanel:function d(){t(".TreeDropdownField").closePanel(),t("body").bind("click",o)
var e=this.getPanel(),n=this.find(".tree-holder")
e.css("width",this.width()),e.show()
var i=this.find(".treedropdownfield-toggle-panel-link")
i.addClass("treedropdownfield-open-tree"),this.addClass("treedropdownfield-open-tree"),i.find("a").removeClass("ui-icon-triangle-1-s").addClass("ui-icon-triangle-1-n"),n.is(":empty")&&!e.hasClass("loading")?this.loadTree(null,this._riseUp):this._riseUp(),
this.trigger("panelshow")},_riseUp:function c(){var e=this,n=this.getPanel(),i=this.find(".treedropdownfield-toggle-panel-link"),r=i.innerHeight(),o,a,s
i.length>0&&(s=t(window).height()+t(document).scrollTop()-i.innerHeight(),a=i.offset().top,o=n.innerHeight(),a+o>s&&a-o>0?(e.addClass("treedropdownfield-with-rise"),r=-n.outerHeight()):e.removeClass("treedropdownfield-with-rise")),
n.css({top:r+"px"})},closePanel:function f(){e("body").unbind("click",o)
var t=this.find(".treedropdownfield-toggle-panel-link")
t.removeClass("treedropdownfield-open-tree"),this.removeClass("treedropdownfield-open-tree treedropdownfield-with-rise"),t.find("a").removeClass("ui-icon-triangle-1-n").addClass("ui-icon-triangle-1-s"),
this.getPanel().hide(),this.trigger("panelhide")},togglePanel:function p(){this[this.getPanel().is(":visible")?"closePanel":"openPanel"]()},setTitle:function h(e){e=e||this.data("title")||r.fieldTitle,
this.find(".treedropdownfield-title").html(e),this.data("title",e)},getTitle:function m(){return this.find(".treedropdownfield-title").text()},updateTitle:function g(){var e=this,t=e.find(".tree-holder"),n=this.getValue(),i=function r(){
var n=e.getValue()
if(n){var i=t.find('*[data-id="'+n+'"]'),r=i.children("a").find("span.jstree_pageicon")?i.children("a").find("span.item").html():null
r||(r=i.length>0?t.jstree("get_text",i[0]):null),r&&(e.setTitle(r),e.data("title",r)),i&&t.jstree("select_node",i)}else e.setTitle(e.data("empty-title")),e.removeData("title")}
t.is(":empty")&&n?this.loadTree({forceValue:n},i):i()},setValue:function v(e){this.data("metadata",t.extend(this.data("metadata"),{id:e})),this.find(":input:hidden").val(e).trigger("valueupdated").trigger("change")

},getValue:function y(){return this.find(":input:hidden").val()},loadTree:function b(e,n){var i=this,r=this.getPanel(),o=t(r).find(".tree-holder"),e=e?t.extend({},this.getRequestParams(),e):this.getRequestParams(),a


this.getCurrentXhr()&&this.getCurrentXhr().abort(),r.addClass("loading"),a=t.ajax({url:this.data("urlTree"),data:e,complete:function s(e,t){r.removeClass("loading")},success:function l(e,r,a){o.html(e)


var s=!0
o.jstree("destroy").bind("loaded.jstree",function(e,t){var r=i.getValue(),a=o.find('*[data-id="'+r+'"]'),l=t.inst.get_selected()
r&&a!=l&&t.inst.select_node(a),s=!1,n&&n.apply(i)}).jstree(i.getTreeConfig()).bind("select_node.jstree",function(e,n){var r=n.rslt.obj,o=t(r).data("id")
s||i.getValue()!=o?(i.data("metadata",t.extend({id:o},t(r).getMetaData())),i.setTitle(n.inst.get_text(r)),i.setValue(o)):(i.data("metadata",null),i.setTitle(null),i.setValue(null),n.inst.deselect_node(r)),
s||i.closePanel(),s=!1}),i.setCurrentXhr(null)}}),this.setCurrentXhr(a)},getTreeConfig:function w(){var e=this
return{core:{html_titles:!0,animation:0},html_data:{data:this.getPanel().find(".tree-holder").html(),ajax:{url:function n(i){var n=t.path.parseUrl(e.data("urlTree")).hrefNoSearch
return n+"/"+(t(i).data("id")?t(i).data("id"):0)},data:function i(n){var i=t.query.load(e.data("urlTree")).keys,r=e.getRequestParams()
return r=t.extend({},i,r,{ajax:1})}}},ui:{select_limit:1,initially_select:[this.getPanel().find(".current").attr("id")]},themes:{theme:"apple"},types:{types:{"default":{check_node:function r(e){return!e.hasClass("disabled")

},uncheck_node:function o(e){return!e.hasClass("disabled")},select_node:function a(e){return!e.hasClass("disabled")},deselect_node:function s(e){return!e.hasClass("disabled")}}}},plugins:["html_data","ui","themes","types"]
}},getRequestParams:function _(){return{}}}),t(".TreeDropdownField .tree-holder li").entwine({getMetaData:function C(){var e=this.attr("class").match(/class-([^\s]*)/i),t=e?e[1]:""
return{ClassName:t}}}),t(".TreeDropdownField *").entwine({getField:function T(){return this.parents(".TreeDropdownField:first")}}),t(".TreeDropdownField").entwine({onclick:function E(e){return this.togglePanel(),
!1}}),t(".TreeDropdownField .treedropdownfield-panel").entwine({onclick:function P(e){return!1}}),t(".TreeDropdownField.searchable").entwine({onadd:function O(){this._super()
var e=a["default"]._t("TreeDropdownField.ENTERTOSEARCH")
this.find(".treedropdownfield-panel").prepend(t('<input type="text" class="search treedropdownfield-search" data-skip-autofocus="true" placeholder="'+e+'" value="" />'))},search:function S(e,t){this.openPanel(),
this.loadTree({search:e},t)},cancelSearch:function k(){this.closePanel(),this.loadTree()}}),t(".TreeDropdownField.searchable input.search").entwine({onkeydown:function j(e){var t=this.getField()
return 13==e.keyCode?(t.search(this.val()),!1):void(27==e.keyCode&&t.cancelSearch())}}),t(".TreeDropdownField.multiple").entwine({getTreeConfig:function x(){var e=this._super()
return e.checkbox={override_ui:!0,two_state:!0},e.plugins.push("checkbox"),e.ui.select_limit=-1,e},loadTree:function R(e,n){var i=this,r=this.getPanel(),o=t(r).find(".tree-holder"),e=e?t.extend({},this.getRequestParams(),e):this.getRequestParams(),a


this.getCurrentXhr()&&this.getCurrentXhr().abort(),r.addClass("loading"),a=t.ajax({url:this.data("urlTree"),data:e,complete:function s(e,t){r.removeClass("loading")},success:function l(e,r,a){o.html(e)


var s=!0
i.setCurrentXhr(null),o.jstree("destroy").bind("loaded.jstree",function(e,r){t.each(i.getValue(),function(e,t){r.inst.check_node(o.find("*[data-id="+t+"]"))}),s=!1,n&&n.apply(i)}).jstree(i.getTreeConfig()).bind("uncheck_node.jstree check_node.jstree",function(e,n){
var r=n.inst.get_checked(null,!0)
i.setValue(t.map(r,function(e,n){return t(e).data("id")})),i.setTitle(t.map(r,function(e,t){return n.inst.get_text(e)})),i.data("metadata",t.map(r,function(e,n){return{id:t(e).data("id"),metadata:t(e).getMetaData()
}}))})}}),this.setCurrentXhr(a)},getValue:function I(){var e=this._super()
return e.split(/ *, */)},setValue:function F(e){this._super(t.isArray(e)?e.join(","):e)},setTitle:function A(e){this._super(t.isArray(e)?e.join(", "):e)},updateTitle:function D(){}}),t(".TreeDropdownField input[type=hidden]").entwine({
onadd:function M(){this._super(),this.bind("change.TreeDropdownField",function(){t(this).getField().updateTitle()})},onremove:function N(){this._super(),this.unbind(".TreeDropdownField")}})})}).call(t,n(1))

},,,function(module,exports,__webpack_require__){"use strict"
function _interopRequireDefault(e){return e&&e.__esModule?e:{"default":e}}var _jQuery=__webpack_require__(1),_jQuery2=_interopRequireDefault(_jQuery),_i18n=__webpack_require__(95),_i18n2=_interopRequireDefault(_i18n),ss="undefined"!=typeof window.ss?window.ss:{}


ss.editorWrappers={},ss.editorWrappers.tinyMCE=function(){var editorID
return{init:function e(t){editorID=t,this.create()},destroy:function t(){tinymce.EditorManager.execCommand("mceRemoveEditor",!1,editorID)},getInstance:function n(){return tinymce.EditorManager.get(editorID)

},onopen:function i(){},onclose:function r(){},getConfig:function o(){var e="#"+editorID,t=(0,_jQuery2["default"])(e).data("config"),n=this
return t.selector=e,t.setup=function(e){e.on("change",function(){n.save()})},t},save:function a(){var e=this.getInstance()
e.save(),(0,_jQuery2["default"])(e.getElement()).trigger("change")},create:function s(){var e=this.getConfig()
"undefined"!=typeof e.baseURL&&(tinymce.EditorManager.baseURL=e.baseURL),tinymce.init(e)},repaint:function l(){},isDirty:function u(){return this.getInstance().isDirty()},getContent:function d(){return this.getInstance().getContent()

},getDOM:function c(){return this.getInstance().getElement()},getContainer:function f(){return this.getInstance().getContainer()},getSelectedNode:function p(){return this.getInstance().selection.getNode()

},selectNode:function h(e){this.getInstance().selection.select(e)},setContent:function m(e,t){this.getInstance().setContent(e,t)},insertContent:function g(e,t){this.getInstance().insertContent(e,t)},replaceContent:function v(e,t){
this.getInstance().execCommand("mceReplaceContent",!1,e,t)},insertLink:function y(e,t){this.getInstance().execCommand("mceInsertLink",!1,e,t)},removeLink:function b(){this.getInstance().execCommand("unlink",!1)

},cleanLink:function cleanLink(href,node){var settings=this.getConfig,cb=settings.urlconverter_callback
return cb&&(href=eval(cb+"(href, node, true);")),href.match(new RegExp("^"+tinyMCE.settings.document_base_url+"(.*)$"))&&(href=RegExp.$1),href.match(/^javascript:\s*mctmp/)&&(href=""),href},createBookmark:function w(){
return this.getInstance().selection.getBookmark()},moveToBookmark:function _(e){this.getInstance().selection.moveToBookmark(e),this.getInstance().focus()},blur:function C(){this.getInstance().selection.collapse()

},addUndo:function T(){this.getInstance().undoManager.add()}}},ss.editorWrappers["default"]=ss.editorWrappers.tinyMCE,_jQuery2["default"].entwine("ss",function(e){e("textarea.htmleditor").entwine({Editor:null,
onadd:function t(){var e=this.data("editor")||"default",t=ss.editorWrappers[e]()
this.setEditor(t),t.init(this.attr("id")),this._super()},onremove:function n(){this.getEditor().destroy(),this._super()},"from .cms-edit-form":{onbeforesubmitform:function i(){this.getEditor().save(),this._super()

}},openLinkDialog:function r(){this.openDialog("link")},openMediaDialog:function o(){this.openDialog("media")},openDialog:function a(t){var n=function a(e){return e.charAt(0).toUpperCase()+e.slice(1).toLowerCase()

},i=this,r=e("#cms-editor-dialogs").data("url"+n(t)+"form"),o=e(".htmleditorfield-"+t+"dialog")
o.length?(o.getForm().setElement(this),o.html(""),o.addClass("loading"),o.open()):(o=e('<div class="htmleditorfield-dialog htmleditorfield-'+t+'dialog loading">'),e("body").append(o)),e.ajax({url:r,complete:function s(){
o.removeClass("loading")},success:function l(e){o.html(e),o.getForm().setElement(i),o.trigger("ssdialogopen")}})}}),e(".htmleditorfield-dialog").entwine({onadd:function s(){this.is(".ui-dialog-content")||this.ssdialog({
autoOpen:!0,buttons:{insert:{text:_i18n2["default"]._t("HtmlEditorField.INSERT","Insert"),"data-icon":"accept","class":"ss-ui-action-constructive media-insert",click:function t(){e(this).find("form").submit()

}}}}),this._super()},getForm:function l(){return this.find("form")},open:function u(){this.ssdialog("open")},close:function d(){this.ssdialog("close")},toggle:function c(e){this.is(":visible")?this.close():this.open()

},onscroll:function f(){this.animate({scrollTop:this.find("form").height()},500)}}),e("form.htmleditorfield-form").entwine({Selection:null,Bookmark:null,Element:null,setSelection:function p(t){return this._super(e(t))

},onadd:function h(){var e=this.find(":header:first")
this.getDialog().attr("title",e.text()),this._super()},onremove:function m(){this.setSelection(null),this.setBookmark(null),this.setElement(null),this._super()},getDialog:function g(){return this.closest(".htmleditorfield-dialog")

},fromDialog:{onssdialogopen:function v(){var e=this.getEditor()
this.setSelection(e.getSelectedNode()),this.setBookmark(e.createBookmark()),e.blur(),this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(":visible:enabled").eq(0).focus(),this.redraw(),
this.updateFromEditor()},onssdialogclose:function y(){var e=this.getEditor()
e.moveToBookmark(this.getBookmark()),this.setSelection(null),this.setBookmark(null),this.resetFields()}},getEditor:function b(){return this.getElement().getEditor()},modifySelection:function w(e){var t=this.getEditor()


t.moveToBookmark(this.getBookmark()),e.call(this,t),this.setSelection(t.getSelectedNode()),this.setBookmark(t.createBookmark()),t.blur()},updateFromEditor:function _(){},redraw:function C(){},resetFields:function T(){
this.find(".tree-holder").empty()}}),e("form.htmleditorfield-linkform").entwine({onsubmit:function E(e){return this.insertLink(),this.getDialog().close(),!1},resetFields:function P(){this._super(),this[0].reset()

},redraw:function O(){this._super()
var e=this.find(":input[name=LinkType]:checked").val()
this.addAnchorSelector(),this.resetFileField(),this.find(".step2").nextAll(".field").not('.field[id$="'+e+'_Holder"]').hide(),this.find('.field[id$="LinkType_Holder"]').attr("style","display: -webkit-flex; display: flex"),
this.find('.field[id$="'+e+'_Holder"]').attr("style","display: -webkit-flex; display: flex"),"internal"!=e&&"anchor"!=e||this.find('.field[id$="Anchor_Holder"]').attr("style","display: -webkit-flex; display: flex"),
"email"==e?this.find('.field[id$="Subject_Holder"]').attr("style","display: -webkit-flex; display: flex"):this.find('.field[id$="TargetBlank_Holder"]').attr("style","display: -webkit-flex; display: flex"),
"anchor"==e&&this.find('.field[id$="AnchorSelector_Holder"]').attr("style","display: -webkit-flex; display: flex"),this.find('.field[id$="Description_Holder"]').attr("style","display: -webkit-flex; display: flex")

},getLinkAttributes:function S(){var e,t=null,n=this.find(":input[name=Subject]").val(),i=this.find(":input[name=Anchor]").val()
switch(this.find(":input[name=TargetBlank]").is(":checked")&&(t="_blank"),this.find(":input[name=LinkType]:checked").val()){case"internal":e="[sitetree_link,id="+this.find(":input[name=internal]").val()+"]",
i&&(e+="#"+i)
break
case"anchor":e="#"+i
break
case"file":var r=this.find(".ss-uploadfield .ss-uploadfield-item").attr("data-fileid")
e=r?"[file_link,id="+r+"]":""
break
case"email":e="mailto:"+this.find(":input[name=email]").val(),n&&(e+="?subject="+encodeURIComponent(n)),t=null
break
default:e=this.find(":input[name=external]").val(),e.indexOf("://")==-1&&(e="http://"+e)}return{href:e,target:t,title:this.find(":input[name=Description]").val()}},insertLink:function k(){this.modifySelection(function(e){
e.insertLink(this.getLinkAttributes())})},removeLink:function j(){this.modifySelection(function(e){e.removeLink()}),this.resetFileField(),this.close()},resetFileField:function x(){var e=this.find('.ss-uploadfield[id$="file_Holder"]'),t=e.data("fileupload"),n=e.find(".ss-uploadfield-item[data-fileid]")


n.length&&(t._trigger("destroy",null,{context:n}),e.find(".ss-uploadfield-addfile").removeClass("borderTop"))},addAnchorSelector:function R(){if(!this.find(":input[name=AnchorSelector]").length){var t=this,n=e('<select id="Form_EditorToolbarLinkForm_AnchorSelector" name="AnchorSelector"></select>')


this.find(":input[name=Anchor]").parent().append(n),this.updateAnchorSelector(),n.change(function(n){t.find(':input[name="Anchor"]').val(e(this).val())})}},getAnchors:function I(){var t=this.find(":input[name=LinkType]:checked").val(),n=e.Deferred()


switch(t){case"anchor":var i=[],r=this.getEditor()
if(r){var o=r.getContent().match(/\s+(name|id)\s*=\s*(["'])([^\2\s>]*?)\2|\s+(name|id)\s*=\s*([^"']+)[\s +>]/gim)
if(o&&o.length)for(var a=0;a<o.length;a++){var s=o[a].indexOf("id=")==-1?7:5
i.push(o[a].substr(s).replace(/"$/,""))}}n.resolve(i)
break
case"internal":var l=this.find(":input[name=internal]").val()
l?e.ajax({url:e.path.addSearchParams(this.attr("action").replace("LinkForm","getanchors"),{PageID:parseInt(l)}),success:function u(t,i,r){n.resolve(e.parseJSON(t))},error:function d(e,t){n.reject(e.responseText)

}}):n.resolve([])
break
default:n.reject(_i18n2["default"]._t("HtmlEditorField.ANCHORSNOTSUPPORTED","Anchors are not supported for this link type."))}return n.promise()},updateAnchorSelector:function F(){var t=this,n=this.find(":input[name=AnchorSelector]"),i=this.getAnchors()


n.empty(),n.append(e('<option value="" selected="1">'+_i18n2["default"]._t("HtmlEditorField.LOOKINGFORANCHORS","Looking for anchors...")+"</option>")),i.done(function(t){if(n.empty(),n.append(e('<option value="" selected="1">'+_i18n2["default"]._t("HtmlEditorField.SelectAnchor")+"</option>")),
t)for(var i=0;i<t.length;i++)n.append(e('<option value="'+t[i]+'">'+t[i]+"</option>"))}).fail(function(t){n.empty(),n.append(e('<option value="" selected="1">'+t+"</option>"))}),e.browser.msie&&n.hide().show()

},updateFromEditor:function A(){var e=/<\S[^><]*>/g,t,n=this.getCurrentLink()
if(n)for(t in n){var i=this.find(":input[name="+t+"]"),r=n[t]
"string"==typeof r&&(r=r.replace(e,"")),i.is(":checkbox")?i.prop("checked",r).change():i.is(":radio")?i.val([r]).change():"file"==t?(i=this.find(':input[name="'+t+'[Uploads][]"]'),i=i.parents(".ss-uploadfield"),
function o(e,t){e.getConfig()?e.attachFiles([t]):setTimeout(function(){o(e,t)},50)}(i,r)):i.val(r).change()}},getCurrentLink:function D(){var e=this.getSelection(),t="",n="",i="",r="insert",o="",a=null


return e.length&&(a=e.is("a")?e:e=e.parents("a:first")),a&&a.length&&this.modifySelection(function(e){e.selectNode(a[0])}),a.attr("href")||(a=null),a&&(t=a.attr("href"),n=a.attr("target"),i=a.attr("title"),
o=a.attr("class"),t=this.getEditor().cleanLink(t,a),r="update"),t.match(/^mailto:(.*)$/)?{LinkType:"email",email:RegExp.$1,Description:i}:t.match(/^(assets\/.*)$/)||t.match(/^\[file_link\s*(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/)?{
LinkType:"file",file:RegExp.$1,Description:i,TargetBlank:!!n}:t.match(/^#(.*)$/)?{LinkType:"anchor",Anchor:RegExp.$1,Description:i,TargetBlank:!!n}:t.match(/^\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/i)?{
LinkType:"internal",internal:RegExp.$1,Anchor:RegExp.$2?RegExp.$2.substr(1):"",Description:i,TargetBlank:!!n}:t?{LinkType:"external",external:t,Description:i,TargetBlank:!!n}:null}}),e("form.htmleditorfield-linkform input[name=LinkType]").entwine({
onclick:function M(e){this.parents("form:first").redraw(),this._super()},onchange:function N(){this.parents("form:first").redraw()
var e=this.parent().find(":checked").val()
"anchor"!==e&&"internal"!==e||this.parents("form.htmleditorfield-linkform").updateAnchorSelector(),this._super()}}),e("form.htmleditorfield-linkform input[name=internal]").entwine({onvalueupdated:function U(){
this.parents("form.htmleditorfield-linkform").updateAnchorSelector(),this._super()}}),e("form.htmleditorfield-linkform :submit[name=action_remove]").entwine({onclick:function L(e){return this.parents("form:first").removeLink(),
this._super(),!1}}),e("form.htmleditorfield-mediaform").entwine({toggleCloseButton:function B(){var e=Boolean(this.find(".ss-htmleditorfield-file").length)
this.find(".overview .action-delete")[e?"hide":"show"]()},onsubmit:function H(){return this.modifySelection(function(t){this.find(".ss-htmleditorfield-file").each(function(){e(this).insertHTML(t)})}),this.getDialog().close(),
!1},updateFromEditor:function $(){var e=this,t=this.getSelection()
if(t.is("img")){var n=t.data("id")||t.data("url")||t.attr("src")
this.showFileView(n).done(function(n){n.updateFromNode(t),e.toggleCloseButton(),e.redraw()})}this.redraw()},redraw:function V(t){this._super()
var n=this.getSelection(),i=Boolean(this.find(".ss-htmleditorfield-file").length),r=n.is("img"),o=this.hasClass("insertingURL"),a=this.find(".header-edit")
a[i?"show":"hide"](),this.closest("ui-dialog").find("ui-dialog-buttonpane .media-insert").button(i?"enable":"disable").toggleClass("ui-state-disabled",!i),this.find(".htmleditorfield-default-panel")[r||o?"hide":"show"](),
this.find(".htmleditorfield-web-panel")[r||!o?"hide":"show"]()
var s=this.find(".htmleditorfield-mediaform-heading.insert")
r?s.hide():o?(s.show().text(_i18n2["default"]._t("HtmlEditorField.INSERTURL")).prepend('<button class="back-button font-icon-left-open no-text" title="'+_i18n2["default"]._t("HtmlEditorField.BACK")+'"></button>'),
this.find(".htmleditorfield-web-panel input.remoteurl").focus()):s.show().text(_i18n2["default"]._t("HtmlEditorField.INSERTFROM")).find(".back-button").remove(),this.find(".htmleditorfield-mediaform-heading.update")[r?"show":"hide"](),
this.find(".ss-uploadfield-item-actions")[r?"hide":"show"](),this.find(".ss-uploadfield-item-name")[r?"hide":"show"](),this.find(".ss-uploadfield-item-preview")[r?"hide":"show"](),this.find(".btn-toolbar .media-update")[r?"show":"hide"](),
this.find(".ss-uploadfield-item-editform").toggleEditForm(r),this.find(".htmleditorfield-from-cms .field.treedropdown").css("left",e(".htmleditorfield-mediaform-heading:visible").outerWidth()),this.closest(".ui-dialog").addClass("ss-uploadfield-dropzone"),
this.closest(".ui-dialog").find(".ui-dialog-buttonpane .media-insert .ui-button-text").text([r?_i18n2["default"]._t("HtmlEditorField.UPDATE","Update"):_i18n2["default"]._t("HtmlEditorField.INSERT","Insert")])

},resetFields:function q(){this.find(".ss-htmleditorfield-file").remove(),this.find(".ss-gridfield-items .ui-selected").removeClass("ui-selected"),this.find("li.ss-uploadfield-item").remove(),this.redraw(),
this._super()},getFileView:function G(e){return this.find(".ss-htmleditorfield-file[data-id="+e+"]")},showFileView:function W(t){var n=this,i=Number(t)==t?{ID:t}:{FileURL:t},r=e('<div class="ss-htmleditorfield-file loading" />')


this.find(".content-edit").prepend(r)
var o=e.Deferred()
return e.ajax({url:e.path.addSearchParams(this.attr("action").replace(/MediaForm/,"viewfile"),i),success:function a(t,i,s){var l=e(t).filter(".ss-htmleditorfield-file")
r.replaceWith(l),n.redraw(),o.resolve(l)},error:function s(){r.remove(),o.reject()}}),o.promise()}}),e("form.htmleditorfield-mediaform div.ss-upload .upload-url").entwine({onclick:function z(){var e=this.closest("form")


e.addClass("insertingURL"),e.redraw()}}),e("form.htmleditorfield-mediaform .htmleditorfield-mediaform-heading .back-button").entwine({onclick:function X(){var e=this.closest("form")
e.removeClass("insertingURL"),e.redraw()}}),e("form.htmleditorfield-mediaform .ss-gridfield-items").entwine({onselectableselected:function Q(t,n){var i=this.closest("form"),r=e(n.selected)
r.is(".ss-gridfield-item")&&(i.closest("form").showFileView(r.data("id")),i.redraw(),i.parent().trigger("scroll"))},onselectableunselected:function K(t,n){var i=this.closest("form"),r=e(n.unselected)
r.is(".ss-gridfield-item")&&(i.getFileView(r.data("id")).remove(),i.redraw())}}),e("form.htmleditorfield-form.htmleditorfield-mediaform div.ss-assetuploadfield").entwine({onfileuploadstop:function Y(t){
var n=this.closest("form"),i=[]
n.find("div.content-edit").find("div.ss-htmleditorfield-file").each(function(){i.push(e(this).data("id"))})
var r=e(".ss-uploadfield-files",this).children(".ss-uploadfield-item")
r.each(function(){var t=e(this).data("fileid")
t&&e.inArray(t,i)==-1&&(e(this).remove(),n.showFileView(t))}),n.parent().trigger("scroll"),n.redraw()}}),e("form.htmleditorfield-form.htmleditorfield-mediaform input.remoteurl").entwine({onadd:function J(){
this._super(),this.validate()},onkeyup:function Z(){this.validate()},onchange:function ee(){this.validate()},getAddButton:function te(){return this.closest(".CompositeField").find("button.add-url")},validate:function ne(){
var t=this.val(),n=t
return t=e.trim(t),t=t.replace(/^https?:\/\//i,""),n!==t&&this.val(t),this.getAddButton().button(t?"enable":"disable"),!!t}}),e("form.htmleditorfield-form.htmleditorfield-mediaform .add-url").entwine({
getURLField:function ie(){return this.closest(".CompositeField").find("input.remoteurl")},onclick:function re(e){var t=this.getURLField(),n=this.closest(".CompositeField"),i=this.closest("form")
return t.validate()&&(n.addClass("loading"),i.showFileView("http://"+t.val()).done(function(){n.removeClass("loading"),i.parent().trigger("scroll")}),i.redraw()),!1}}),e("form.htmleditorfield-mediaform .ss-htmleditorfield-file").entwine({
getAttributes:function oe(){},getExtraData:function ae(){},getHTML:function se(){return e("<div>").append(e("<a/>").attr({href:this.data("url")}).text(this.find(".name").text())).html()},insertHTML:function le(e){
e.replaceContent(this.getHTML())},updateFromNode:function ue(e){},updateDimensions:function de(e,t,n){var i=this.find(":input[name=Width]"),r=this.find(":input[name=Height]"),o=i.val(),a=r.val(),s
o&&a&&(e?(s=r.getOrigVal()/i.getOrigVal(),"Width"==e?(t&&o>t&&(o=t),a=Math.floor(o*s)):"Height"==e&&(n&&a>n&&(a=n),o=Math.ceil(a/s))):(t&&o>t&&(o=t),n&&a>n&&(a=n)),i.val(o),r.val(a))}}),e("form.htmleditorfield-mediaform .ss-htmleditorfield-file.image").entwine({
getAttributes:function ce(){var e=this.find(":input[name=Width]").val(),t=this.find(":input[name=Height]").val()
return{src:this.find(":input[name=URL]").val(),alt:this.find(":input[name=AltText]").val(),width:e?parseInt(e,10):null,height:t?parseInt(t,10):null,title:this.find(":input[name=Title]").val(),"class":this.find(":input[name=CSSClass]").val(),
"data-id":this.find(":input[name=FileID]").val()}},getExtraData:function fe(){return{CaptionText:this.find(":input[name=CaptionText]").val()}},getHTML:function pe(){},insertHTML:function he(t){var n=this.closest("form"),i=n.getSelection()


t||(t=n.getEditor())
var r=this.getAttributes(),o=this.getExtraData(),a=i&&i.is("img")?i:null
a&&a.parent().is(".captionImage")&&(a=a.parent())
var s=i&&i.is("img")?i:e("<img />")
s.attr(r)
var l=s.parent(".captionImage"),u=l.find(".caption")
o.CaptionText?(l.length||(l=e("<div></div>")),l.attr("class","captionImage "+r["class"]).css("width",r.width),u.length||(u=e('<p class="caption"></p>').appendTo(l)),u.attr("class","caption "+r["class"]).text(o.CaptionText)):l=u=null


var d=l?l:s
a&&a.not(d).length&&a.replaceWith(d),l&&l.prepend(s),a||(t.repaint(),t.insertContent(e("<div />").append(d).html(),{skip_undo:1})),t.addUndo(),t.repaint()},updateFromNode:function me(e){this.find(":input[name=AltText]").val(e.attr("alt")),
this.find(":input[name=Title]").val(e.attr("title")),this.find(":input[name=CSSClass]").val(e.attr("class")),this.find(":input[name=Width]").val(e.width()),this.find(":input[name=Height]").val(e.height()),
this.find(":input[name=CaptionText]").val(e.siblings(".caption:first").text()),this.find(":input[name=FileID]").val(e.data("id"))}}),e("form.htmleditorfield-mediaform .ss-htmleditorfield-file.flash").entwine({
getAttributes:function ge(){var e=this.find(":input[name=Width]").val(),t=this.find(":input[name=Height]").val()
return{src:this.find(":input[name=URL]").val(),width:e?parseInt(e,10):null,height:t?parseInt(t,10):null,"data-fileid":this.find(":input[name=FileID]").val()}},getHTML:function ve(){var t=this.getAttributes(),n=tinyMCE.activeEditor.plugins.media.dataToImg({
type:"flash",width:t.width,height:t.height,params:{src:t.src},video:{sources:[]}})
return e("<div />").append(n).html()},updateFromNode:function ye(e){}}),e("form.htmleditorfield-mediaform .ss-htmleditorfield-file.embed").entwine({getAttributes:function be(){var e=this.find(":input[name=Width]").val(),t=this.find(":input[name=Height]").val()


return{src:this.find(".thumbnail-preview").attr("src"),width:e?parseInt(e,10):null,height:t?parseInt(t,10):null,"class":this.find(":input[name=CSSClass]").val(),alt:this.find(":input[name=AltText]").val(),
title:this.find(":input[name=Title]").val(),"data-fileid":this.find(":input[name=FileID]").val()}},getExtraData:function we(){var e=this.find(":input[name=Width]").val(),t=this.find(":input[name=Height]").val()


return{CaptionText:this.find(":input[name=CaptionText]").val(),Url:this.find(":input[name=URL]").val(),thumbnail:this.find(".thumbnail-preview").attr("src"),width:e?parseInt(e,10):null,height:t?parseInt(t,10):null,
cssclass:this.find(":input[name=CSSClass]").val()}},getHTML:function _e(){var t,n=this.getAttributes(),i=this.getExtraData(),r=e("<img />").attr(n).addClass("ss-htmleditorfield-file embed")
return e.each(i,function(e,t){r.attr("data-"+e,t)}),t=i.CaptionText?e('<div style="width: '+n.width+'px;" class="captionImage '+n["class"]+'"><p class="caption">'+i.CaptionText+"</p></div>").prepend(r):r,
e("<div />").append(t).html()},updateFromNode:function Ce(e){this.find(":input[name=AltText]").val(e.attr("alt")),this.find(":input[name=Title]").val(e.attr("title")),this.find(":input[name=Width]").val(e.width()),
this.find(":input[name=Height]").val(e.height()),this.find(":input[name=Title]").val(e.attr("title")),this.find(":input[name=CSSClass]").val(e.data("cssclass")),this.find(":input[name=FileID]").val(e.data("fileid"))

}}),e("form.htmleditorfield-mediaform .ss-htmleditorfield-file .dimensions :input").entwine({OrigVal:null,onmatch:function Te(){this._super(),this.setOrigVal(parseInt(this.val(),10))},onunmatch:function Ee(){
this._super()},onfocusout:function Pe(e){this.closest(".ss-htmleditorfield-file").updateDimensions(this.attr("name"))}}),e("form.htmleditorfield-mediaform .ss-uploadfield-item .ss-uploadfield-item-cancel").entwine({
onclick:function Oe(e){var t=this.closest("form"),n=this.closest("ss-uploadfield-item")
t.find(".ss-gridfield-item[data-id="+n.data("id")+"]").removeClass("ui-selected"),this.closest(".ss-uploadfield-item").remove(),t.redraw(),e.preventDefault()}}),e("div.ss-assetuploadfield .ss-uploadfield-item-edit, div.ss-assetuploadfield .ss-uploadfield-item-name").entwine({
getEditForm:function Se(){return this.closest(".ss-uploadfield-item").find(".ss-uploadfield-item-editform")},fromEditForm:{onchange:function ke(t){var n=e(t.target)
n.removeClass("edited"),n.addClass("edited")}},onclick:function je(e){var t=this.getEditForm()
return this.closest(".ss-uploadfield-item").hasClass("ss-htmleditorfield-file")?(t.parent("ss-uploadfield-item").removeClass("ui-state-warning"),t.toggleEditForm(),e.preventDefault(),!1):void this._super(e)

}}),e("div.ss-assetuploadfield .ss-uploadfield-item-editform").entwine({toggleEditForm:function xe(e){var t=this.prev(".ss-uploadfield-item-info"),n=t.find(".ss-uploadfield-item-status"),i=""
e===!0||e!==!1&&0===this.height()?(i=_i18n2["default"]._t("UploadField.Editing","Editing ..."),this.height("auto"),t.find(".toggle-details-icon").addClass("opened"),n.removeClass("ui-state-success-text").removeClass("ui-state-warning-text")):(this.height(0),
t.find(".toggle-details-icon").removeClass("opened"),this.hasClass("edited")?(i=_i18n2["default"]._t("UploadField.CHANGESSAVED","Changes Made"),this.removeClass("edited"),n.addClass("ui-state-success-text")):(i=_i18n2["default"]._t("UploadField.NOCHANGES","No Changes"),
n.addClass("ui-state-success-text"))),n.attr("title",i).text(i)}}),e('form.htmleditorfield-mediaform .field[id$="ParentID_Holder"] .TreeDropdownField').entwine({onadd:function Re(){this._super()
var e=this
this.bind("change",function(){var t=e.closest("form").find(".grid-field")
t.setState("ParentID",e.getValue()),t.reload()})}})})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r)
n(87),n(123),n(119),o["default"].entwine("ss",function(e){e(".ss-tabset").entwine({IgnoreTabState:!1,onadd:function t(){var e=window.location.hash
this.redrawTabs(),""!==e&&this.openTabFromURL(e),this._super()},onremove:function n(){this.data("tabs")&&this.tabs("destroy"),this._super()},redrawTabs:function i(){this.rewriteHashlinks(),this.tabs()},
openTabFromURL:function r(t){var n
e.each(this.find(".ui-tabs-anchor"),function(){if(this.href.indexOf(t)!==-1&&1===e(t).length)return n=e(this),!1}),void 0!==n&&e(document).ready("ajaxComplete",function(){n.click()})},rewriteHashlinks:function o(){
e(this).find("ul a").each(function(){if(e(this).attr("href")){var t=e(this).attr("href").match(/#.*/)
t&&e(this).attr("href",document.location.href.replace(/#.*/,"")+t[0])}})}}),e(".ui-tabs-active .ui-tabs-anchor").entwine({onmatch:function a(){this.addClass("nav-link active")},onunmatch:function s(){this.removeClass("active")

}})})},,function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(1),o=i(r),a=n(95),s=i(a)
n(87),n(119),o["default"].entwine("ss",function(e){e(".grid-field").entwine({reload:function t(n,i){var r=this,o=this.closest("form"),a=this.find(":input:focus").attr("name"),l=o.find(":input").serializeArray()


n||(n={}),n.data||(n.data=[]),n.data=n.data.concat(l),window.location.search&&(n.data=window.location.search.replace(/^\?/,"")+"&"+e.param(n.data)),o.addClass("loading"),e.ajax(e.extend({},{headers:{"X-Pjax":"CurrentField"
},type:"POST",url:this.data("url"),dataType:"html",success:function u(t){if(r.empty().append(e(t).children()),a&&r.find(':input[name="'+a+'"]').focus(),r.find(".filter-header").length){var s
"show"==n.data[0].filter?(s='<span class="non-sortable"></span>',r.addClass("show-filter").find(".filter-header").show()):(s='<button type="button" name="showFilter" class="btn font-icon-search btn--no-text btn--icon-large grid-field__filter-open ss-gridfield-button-filter trigger"></button>',
r.removeClass("show-filter").find(".filter-header").hide()),r.find(".sortable-header th:last").html(s)}o.removeClass("loading"),i&&i.apply(this,arguments),r.trigger("reload",r)},error:function d(e){alert(s["default"]._t("GRIDFIELD.ERRORINTRANSACTION")),
o.removeClass("loading")}},n))},showDetailView:function n(e){window.location.href=e},getItems:function i(){return this.find(".ss-gridfield-item")},setState:function r(e,t){var n=this.getState()
n[e]=t,this.find(':input[name="'+this.data("name")+'[GridState]"]').val(JSON.stringify(n))},getState:function o(){return JSON.parse(this.find(':input[name="'+this.data("name")+'[GridState]"]').val())}}),
e(".grid-field *").entwine({getGridField:function a(){return this.closest(".grid-field")}}),e(".grid-field :button[name=showFilter]").entwine({onclick:function l(t){e(".filter-header").show("slow").find(":input:first").focus(),
this.closest(".grid-field").addClass("show-filter"),this.parent().html('<span class="non-sortable"></span>'),t.preventDefault()}}),e(".grid-field .ss-gridfield-item").entwine({onclick:function u(t){if(e(t.target).closest(".action").length)return this._super(t),
!1
var n=this.find(".edit-link")
n.length&&this.getGridField().showDetailView(n.prop("href"))},onmouseover:function d(){this.find(".edit-link").length&&this.css("cursor","pointer")},onmouseout:function c(){this.css("cursor","default")

}}),e(".grid-field .action:button").entwine({onclick:function f(e){var t="show"
return this.button("option","disabled")?void e.preventDefault():(!this.hasClass("ss-gridfield-button-close")&&this.closest(".grid-field").hasClass("show-filter")||(t="hidden"),this.getGridField().reload({
data:[{name:this.attr("name"),value:this.val(),filter:t}]}),void e.preventDefault())},actionurl:function p(){var t=this.closest(":button"),n=this.getGridField(),i=this.closest("form"),r=i.find(":input.gridstate").serialize(),o=i.find('input[name="SecurityID"]').val()


r+="&"+encodeURIComponent(t.attr("name"))+"="+encodeURIComponent(t.val()),o&&(r+="&SecurityID="+encodeURIComponent(o)),window.location.search&&(r=window.location.search.replace(/^\?/,"")+"&"+r)
var a=n.data("url").indexOf("?")==-1?"?":"&"
return e.path.makeUrlAbsolute(n.data("url")+a+r,e("base").attr("href"))}}),e(".grid-field .add-existing-autocompleter").entwine({onbuttoncreate:function h(){var e=this
this.toggleDisabled(),this.find('input[type="text"]').on("keyup",function(){e.toggleDisabled()})},onunmatch:function m(){this.find('input[type="text"]').off("keyup")},toggleDisabled:function g(){var e=this.find(".ss-ui-button"),t=this.find('input[type="text"]'),n=""!==t.val(),i=e.is(":disabled")

;(n&&i||!n&&!i)&&e.button("option","disabled",!i)}}),e(".grid-field .grid-field__col-compact .action.gridfield-button-delete, .cms-edit-form .btn-toolbar button.action.action-delete").entwine({onclick:function v(e){
return confirm(s["default"]._t("TABLEFIELD.DELETECONFIRMMESSAGE"))?void this._super(e):(e.preventDefault(),!1)}}),e(".grid-field .action.gridfield-button-print").entwine({UUID:null,onmatch:function y(){
this._super(),this.setUUID((new Date).getTime())},onunmatch:function b(){this._super()},onclick:function w(e){var t=this.actionurl()
return window.open(t),e.preventDefault(),!1}}),e(".ss-gridfield-print-iframe").entwine({onmatch:function _(){this._super(),this.hide().bind("load",function(){this.focus()
var e=this.contentWindow||this
e.print()})},onunmatch:function C(){this._super()}}),e(".grid-field .action.no-ajax").entwine({onclick:function T(e){return window.location.href=this.actionurl(),e.preventDefault(),!1}}),e(".grid-field .action-detail").entwine({
onclick:function E(){return this.getGridField().showDetailView(e(this).prop("href")),!1}}),e(".grid-field[data-selectable]").entwine({getSelectedItems:function P(){return this.find(".ss-gridfield-item.ui-selected")

},getSelectedIDs:function O(){return e.map(this.getSelectedItems(),function(t){return e(t).data("id")})}}),e(".grid-field[data-selectable] .ss-gridfield-items").entwine({onadd:function S(){this._super(),
this.selectable()},onremove:function k(){this._super(),this.data("selectable")&&this.selectable("destroy")}}),e(".grid-field .filter-header :input").entwine({onmatch:function j(){var e=this.closest(".extra").find(".ss-gridfield-button-filter"),t=this.closest(".extra").find(".ss-gridfield-button-reset")


this.val()&&(e.addClass("filtered"),t.addClass("filtered")),this._super()},onunmatch:function x(){this._super()},onkeydown:function R(e){if(!this.closest(".ss-gridfield-button-reset").length){var t=this.closest(".extra").find(".ss-gridfield-button-filter"),n=this.closest(".extra").find(".ss-gridfield-button-reset")


if("13"==e.keyCode){var i=this.closest(".filter-header").find(".ss-gridfield-button-filter"),r="show"
return!this.hasClass("ss-gridfield-button-close")&&this.closest(".grid-field").hasClass("show-filter")||(r="hidden"),this.getGridField().reload({data:[{name:i.attr("name"),value:i.val(),filter:r}]}),!1

}t.addClass("hover-alike"),n.addClass("hover-alike")}}}),e(".grid-field .relation-search").entwine({onfocusin:function I(t){this.autocomplete({source:function n(t,i){var r=e(this.element),o=e(this.element).closest("form")


e.ajax({headers:{"X-Pjax":"Partial"},dataType:"json",type:"GET",url:e(r).data("searchUrl"),data:encodeURIComponent(r.attr("name"))+"="+encodeURIComponent(r.val()),success:i,error:function a(e){alert(s["default"]._t("GRIDFIELD.ERRORINTRANSACTION","An error occured while fetching data from the server\n Please try again later."))

}})},select:function i(t,n){var i=e('<input type="hidden" name="relationID" class="action_gridfield_relationfind" />')
i.val(n.item.id),e(this).closest(".grid-field").find(".action_gridfield_relationfind").replaceWith(i)
var r=e(this).closest(".grid-field").find(".action_gridfield_relationadd")
r.data("button")?r.button("enable"):r.removeAttr("disabled")}})}}),e(".grid-field .pagination-page-number input").entwine({onkeydown:function F(t){if(13==t.keyCode){var n=parseInt(e(this).val(),10),i=e(this).getGridField()


return i.setState("GridFieldPaginator",{currentPage:n}),i.reload(),!1}}})})},function(e,t,n){"use strict"
function i(e){if(e&&e.__esModule)return e
var t={}
if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])
return t["default"]=e,t}function r(e){return e&&e.__esModule?e:{"default":e}}function o(){h["default"].add("config",y["default"]),h["default"].add("form",w["default"]),h["default"].add("schemas",C["default"]),
h["default"].add("records",E["default"]),h["default"].add("campaign",O["default"]),h["default"].add("breadcrumbs",k["default"]),h["default"].add("routing",j.routerReducer),R["default"].start()
var e={},t=(0,l.combineReducers)(h["default"].getAll()),n=[d["default"]],i=f["default"].get("environment"),r=f["default"].get("debugging"),o=l.applyMiddleware.apply(void 0,n),a=window.devToolsExtension


"dev"===i&&r&&"function"==typeof a&&(o=(0,l.compose)(l.applyMiddleware.apply(void 0,n),a()))
var u=o(l.createStore),c=u(t,e)
c.dispatch(g.setConfig(f["default"].getAll())),window.ss=window.ss||{},window.ss.store=c
var p=new s["default"](c)
p.start(window.location.pathname)}var a=n(126),s=r(a),l=n(28),u=n(148),d=r(u),c=n(69),f=r(c),p=n(149),h=r(p),m=n(150),g=i(m),v=n(152),y=r(v),b=n(154),w=r(b),_=n(155),C=r(_),T=n(156),E=r(T),P=n(157),O=r(P),S=n(159),k=r(S),j=n(147),x=n(160),R=r(x),I=n(176),F=r(I)


window.onload=o},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var o=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),a=n(1),s=i(a),l=n(4),u=i(l),d=n(103),c=i(d),f=n(27),p=n(64),h=n(127),m=i(h),g=n(69),v=i(g),y=n(144),b=i(y),w=n(145),_=i(w),C=n(146),T=i(C),E=n(147),P=function(){
function e(t){r(this,e),this.store=t
var n=v["default"].get("absoluteBaseUrl")
b["default"].setAbsoluteBase(n)}return o(e,[{key:"start",value:function t(e){this.matchesLegacyRoute(e)?this.initLegacyRouter():this.initReactRouter()}},{key:"matchesLegacyRoute",value:function n(e){var t=v["default"].get("sections"),n=b["default"].resolveURLToBase(e).replace(/\/$/,"")


return!!Object.keys(t).find(function(e){var i=t[e],r=b["default"].resolveURLToBase(i.url).replace(/\/$/,"")
return!i.reactRouter&&n.match(r)})}},{key:"initReactRouter",value:function i(){_["default"].updateRootRoute({component:T["default"]})
var e=(0,E.syncHistoryWithStore)((0,p.useRouterHistory)(m["default"])({basename:v["default"].get("baseUrl")}),this.store)
c["default"].render(u["default"].createElement(f.Provider,{store:this.store},u["default"].createElement(p.Router,{history:e,routes:_["default"].getRootRoute()})),document.getElementsByClassName("cms-content")[0])

}},{key:"initLegacyRouter",value:function a(){var e=v["default"].get("sections"),t=this.store;(0,b["default"])("*",function(e,n){e.store=t,n()})
var n=null
Object.keys(e).forEach(function(t){var i=b["default"].resolveURLToBase(e[t].url)
i=i.replace(/\/$/,""),i+="(/*?)?",(0,b["default"])(i,function(e,t){if("complete"!==document.readyState||e.init)return void t()
n||(n=window.location.pathname)
var i=e.data&&e.data.__forceReload;(e.path!==n||i)&&(n=e.path.replace(/#.*$/,""),(0,s["default"])(".cms-container").entwine("ss").handleStateChange(null,e.state))})}),b["default"].start()}}]),e}()
t["default"]=P},,,,,,,,,,,,,,,,,,function(e,t){e.exports=Router},function(e,t){e.exports=ReactRouteRegister},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("div",{className:"app"
},this.props.children)}}]),t}(c["default"])
t["default"]=f},function(e,t){e.exports=ReactRouterRedux},function(e,t){e.exports=ReduxThunk},function(e,t){e.exports=ReducerRegister},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e){return{type:a["default"].SET_CONFIG,payload:{config:e}}}Object.defineProperty(t,"__esModule",{value:!0}),t.setConfig=r
var o=n(151),a=i(o)},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0}),t["default"]={SET_CONFIG:"SET_CONFIG"}},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0],t=arguments[1]
switch(t.type){case u["default"].SET_CONFIG:return(0,s["default"])(o({},e,t.payload.config))
default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var o=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},a=n(153),s=i(a),l=n(151),u=i(l)
t["default"]=r},function(e,t){e.exports=DeepFreezeStrict},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function o(){var e=arguments.length<=0||void 0===arguments[0]?d:arguments[0],t=arguments[1],n=function i(t,n){
return a({},e,r({},t,a({},e[t],n)))}
switch(t.type){case u.ACTION_TYPES.SUBMIT_FORM_REQUEST:return(0,l["default"])(n(t.payload.formId,{error:!1,submitting:!0}))
case u.ACTION_TYPES.REMOVE_FORM:return(0,l["default"])(Object.keys(e).reduce(function(n,i){return i===t.payload.formId?n:a({},n,r({},i,e[i]))},{}))
case u.ACTION_TYPES.ADD_FORM:return(0,l["default"])(a({},e,r({},t.payload.formState.id,{fields:t.payload.formState.fields,error:!1,submitting:!1})))
case u.ACTION_TYPES.UPDATE_FIELD:return(0,l["default"])(n(t.payload.formId,{fields:e[t.payload.formId].fields.map(function(e){return e.id===t.payload.updates.id?a({},e,t.payload.updates):e})}))
case u.ACTION_TYPES.SUBMIT_FORM_SUCCESS:return(0,l["default"])(n(t.payload.response.id,{fields:t.payload.response.state.fields,error:!1,messages:t.payload.response.state.messages,submitting:!1}))
case u.ACTION_TYPES.SUBMIT_FORM_FAILURE:return(0,l["default"])(n(t.payload.formId,{error:!0,messages:t.payload.error,submitting:!1}))
case u.ACTION_TYPES.SET_SUBMIT_ACTION:return(0,l["default"])(n(t.payload.formId,{submitAction:t.payload.submitAction}))
default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var a=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},s=n(153),l=i(s),u=n(30),d=(0,l["default"])({})
t["default"]=o},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function o(){var e=arguments.length<=0||void 0===arguments[0]?c:arguments[0],t=arguments.length<=1||void 0===arguments[1]?null:arguments[1]


switch(t.type){case d["default"].SET_SCHEMA:var n=t.payload.schema.schema_url
return(0,l["default"])(a({},e,r({},n,t.payload)))
default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var a=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e}
t["default"]=o
var s=n(153),l=i(s),u=n(32),d=i(u),c=(0,l["default"])({})},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function o(){var e=arguments.length<=0||void 0===arguments[0]?c:arguments[0],t=arguments[1],n=null,i=null,o=null


switch(t.type){case d["default"].CREATE_RECORD:return(0,l["default"])(a({},e,{}))
case d["default"].UPDATE_RECORD:return(0,l["default"])(a({},e,{}))
case d["default"].DELETE_RECORD:return(0,l["default"])(a({},e,{}))
case d["default"].FETCH_RECORDS_REQUEST:return e
case d["default"].FETCH_RECORDS_FAILURE:return e
case d["default"].FETCH_RECORDS_SUCCESS:if(i=t.payload.recordType,!i)throw new Error("Undefined record type")
return n=t.payload.data._embedded[i]||{},n=n.reduce(function(e,t){return a({},e,r({},t.ID,t))},{}),(0,l["default"])(a({},e,r({},i,n)))
case d["default"].FETCH_RECORD_REQUEST:return e
case d["default"].FETCH_RECORD_FAILURE:return e
case d["default"].FETCH_RECORD_SUCCESS:if(i=t.payload.recordType,o=t.payload.data,!i)throw new Error("Undefined record type")
return(0,l["default"])(a({},e,r({},i,a({},e[i],r({},o.ID,o)))))
case d["default"].DELETE_RECORD_REQUEST:return e
case d["default"].DELETE_RECORD_FAILURE:return e
case d["default"].DELETE_RECORD_SUCCESS:return i=t.payload.recordType,n=e[i],n=Object.keys(n).reduce(function(e,i){return parseInt(i,10)!==parseInt(t.payload.id,10)?a({},e,r({},i,n[i])):e},{}),(0,l["default"])(a({},e,r({},i,n)))


default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var a=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},s=n(153),l=i(s),u=n(48),d=i(u),c={}
t["default"]=o},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(){var e=arguments.length<=0||void 0===arguments[0]?d:arguments[0],t=arguments[1]
switch(t.type){case u["default"].SET_CAMPAIGN_SELECTED_CHANGESETITEM:return(0,s["default"])(o({},e,{changeSetItemId:t.payload.changeSetItemId}))
case u["default"].SET_CAMPAIGN_ACTIVE_CHANGESET:return(0,s["default"])(o({},e,{campaignId:t.payload.campaignId,view:t.payload.view,changeSetItemId:null}))
case u["default"].PUBLISH_CAMPAIGN_REQUEST:return(0,s["default"])(o({},e,{isPublishing:!0}))
case u["default"].PUBLISH_CAMPAIGN_SUCCESS:case u["default"].PUBLISH_CAMPAIGN_FAILURE:return(0,s["default"])(o({},e,{isPublishing:!1}))
default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var o=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},a=n(153),s=i(a),l=n(158),u=i(l),d=(0,s["default"])({campaignId:null,changeSetItemId:null,isPublishing:!1,view:null})
t["default"]=r},function(e,t){"use strict"
Object.defineProperty(t,"__esModule",{value:!0}),t["default"]={SET_CAMPAIGN_ACTIVE_CHANGESET:"SET_CAMPAIGN_ACTIVE_CHANGESET",SET_CAMPAIGN_SELECTED_CHANGESETITEM:"SET_CAMPAIGN_SELECTED_CHANGESETITEM",PUBLISH_CAMPAIGN_REQUEST:"PUBLISH_CAMPAIGN_REQUEST",
PUBLISH_CAMPAIGN_SUCCESS:"PUBLISH_CAMPAIGN_SUCCESS",PUBLISH_CAMPAIGN_FAILURE:"PUBLISH_CAMPAIGN_FAILURE"}},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(){var e=arguments.length<=0||void 0===arguments[0]?d:arguments[0],t=arguments[1]
switch(t.type){case u["default"].SET_BREADCRUMBS:return(0,s["default"])(o([],t.payload.breadcrumbs))
default:return e}}Object.defineProperty(t,"__esModule",{value:!0})
var o=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},a=n(153),s=i(a),l=n(67),u=i(l),d=(0,s["default"])([])
t["default"]=r},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0})
var o=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),a=n(34),s=i(a),l=n(57),u=i(l),d=n(55),c=i(d),f=n(161),p=i(f),h=n(163),m=i(h),g=n(164),v=i(g),y=n(165),b=i(y),w=n(166),_=i(w),C=n(167),T=i(C),E=n(168),P=i(E),O=n(169),S=i(O),k=n(170),j=i(k),x=n(171),R=i(x),I=n(172),F=i(I),A=n(173),D=i(A),M=n(174),N=i(M),U=n(175),L=i(U),B=function(){
function e(){r(this,e)}return o(e,[{key:"start",value:function t(){s["default"].register("TextField",u["default"]),s["default"].register("HiddenField",c["default"]),s["default"].register("CheckboxField",p["default"]),
s["default"].register("CheckboxSetField",m["default"]),s["default"].register("OptionsetField",v["default"]),s["default"].register("GridField",b["default"]),s["default"].register("SingleSelectField",_["default"]),
s["default"].register("PopoverField",T["default"]),s["default"].register("HeaderField",P["default"]),s["default"].register("LiteralField",S["default"]),s["default"].register("HtmlReadonlyField",j["default"]),
s["default"].register("LookupField",R["default"]),s["default"].register("CompositeField",F["default"]),s["default"].register("Tabs",D["default"]),s["default"].register("TabItem",N["default"]),s["default"].register("FormAction",L["default"])

}}]),e}()
t["default"]=new B},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(162),f=i(c),p=n(58),h=i(p),m=n(20),g=i(m),v=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),l(t,[{key:"render",value:function n(){var e=(0,h["default"])(f["default"])
return d["default"].createElement(e,s({},this.props,{type:"checkbox",hideLabels:!0}))}}]),t}(g["default"])
t["default"]=v},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(59),p=i(f),h=n(37),m=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleChange=n.handleChange.bind(n),n}return a(t,e),s(t,[{key:"handleChange",value:function n(e){"function"==typeof this.props.onChange?this.props.onChange(e,{id:this.props.id,value:e.target.checked?1:0
}):"function"==typeof this.props.onClick&&this.props.onClick(e,{id:this.props.id,value:e.target.checked?1:0})}},{key:"getInputProps",value:function i(){return{id:this.props.id,name:this.props.name,disabled:this.props.disabled,
readOnly:this.props.readOnly,className:this.props.className+" "+this.props.extraClass,onChange:this.handleChange,checked:!!this.props.value,value:1}}},{key:"render",value:function l(){var e=null!==this.props.leftTitle?this.props.leftTitle:this.props.title,t=void 0


switch(this.props.type){case"checkbox":t=h.Checkbox
break
case"radio":t=h.Radio
break
default:throw new Error("Invalid OptionField type: "+this.props.type)}return(0,p["default"])(t,e,this.getInputProps())}}]),t}(c["default"])
m.propTypes={type:u["default"].PropTypes.oneOf(["checkbox","radio"]),leftTitle:u["default"].PropTypes.any,title:u["default"].PropTypes.any,extraClass:u["default"].PropTypes.string,id:u["default"].PropTypes.string,
name:u["default"].PropTypes.string.isRequired,onChange:u["default"].PropTypes.func,value:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number,u["default"].PropTypes.bool]),
readOnly:u["default"].PropTypes.bool,disabled:u["default"].PropTypes.bool},m.defaultProps={extraClass:"",className:"",type:"radio",leftTitle:null},t["default"]=m},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.CheckboxSetField=void 0
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(162),p=i(f),h=n(58),m=i(h),g=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.getItemKey=n.getItemKey.bind(n),n.getOptionProps=n.getOptionProps.bind(n),n.handleChange=n.handleChange.bind(n),n.getValues=n.getValues.bind(n),n}return a(t,e),s(t,[{key:"getItemKey",value:function n(e,t){
return this.props.id+"-"+(e.value||"empty"+t)}},{key:"getValues",value:function i(){var e=this.props.value
return Array.isArray(e)||!e&&"string"!=typeof e&&"number"!=typeof e||(e=[e]),e?e.map(function(e){return""+e}):[]}},{key:"handleChange",value:function l(e,t){var n=this
"function"==typeof this.props.onChange&&!function(){var i=n.getValues(),r=n.props.source.filter(function(e,r){return n.getItemKey(e,r)===t.id?1===t.value:i.indexOf(""+e.value)>-1}).map(function(e){return""+e.value

})
n.props.onChange(e,{id:n.props.id,value:r})}()}},{key:"getOptionProps",value:function d(e,t){var n=this.getValues(),i=this.getItemKey(e,t)
return{key:i,id:i,name:this.props.name,className:this.props.itemClass,disabled:e.disabled||this.props.disabled,readOnly:this.props.readOnly,onChange:this.handleChange,value:n.indexOf(""+e.value)>-1,title:e.title,
type:"checkbox"}}},{key:"render",value:function c(){var e=this
return this.props.source?u["default"].createElement("div",null,this.props.source.map(function(t,n){return u["default"].createElement(p["default"],e.getOptionProps(t,n))})):null}}]),t}(c["default"])
g.propTypes={className:u["default"].PropTypes.string,extraClass:u["default"].PropTypes.string,itemClass:u["default"].PropTypes.string,id:u["default"].PropTypes.string,name:u["default"].PropTypes.string.isRequired,
source:u["default"].PropTypes.arrayOf(u["default"].PropTypes.shape({value:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number]),title:u["default"].PropTypes.any,
disabled:u["default"].PropTypes.bool})),onChange:u["default"].PropTypes.func,value:u["default"].PropTypes.any,readOnly:u["default"].PropTypes.bool,disabled:u["default"].PropTypes.bool},g.defaultProps={
extraClass:"",className:"",value:[]},t.CheckboxSetField=g,t["default"]=(0,m["default"])(g)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.OptionsetField=void 0
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(162),p=i(f),h=n(58),m=i(h),g=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.getItemKey=n.getItemKey.bind(n),n.getOptionProps=n.getOptionProps.bind(n),n.handleChange=n.handleChange.bind(n),n}return a(t,e),s(t,[{key:"getItemKey",value:function n(e,t){return this.props.id+"-"+(e.value||"empty"+t)

}},{key:"handleChange",value:function i(e,t){var n=this
if("function"==typeof this.props.onChange&&1===t.value){var i=this.props.source.find(function(e,i){return n.getItemKey(e,i)===t.id})
this.props.onChange(e,{id:this.props.id,value:""+i.value})}}},{key:"getOptionProps",value:function l(e,t){var n=this.getItemKey(e,t)
return{key:n,id:n,name:this.props.name,className:this.props.itemClass,disabled:e.disabled||this.props.disabled,readOnly:this.props.readOnly,onChange:this.handleChange,value:""+this.props.value==""+e.value,
title:e.title,type:"radio"}}},{key:"render",value:function d(){var e=this
return this.props.source?u["default"].createElement("div",null,this.props.source.map(function(t,n){return u["default"].createElement(p["default"],e.getOptionProps(t,n))})):null}}]),t}(c["default"])
g.propTypes={extraClass:u["default"].PropTypes.string,itemClass:u["default"].PropTypes.string,id:u["default"].PropTypes.string,name:u["default"].PropTypes.string.isRequired,source:u["default"].PropTypes.arrayOf(u["default"].PropTypes.shape({
value:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number]),title:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number]),
disabled:u["default"].PropTypes.bool})),onChange:u["default"].PropTypes.func,value:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number]),readOnly:u["default"].PropTypes.bool,
disabled:u["default"].PropTypes.bool},g.defaultProps={extraClass:"",className:""},t.OptionsetField=g,t["default"]=(0,m["default"])(g)},function(e,t){e.exports=GridField},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.SingleSelectField=void 0
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=n(58),h=i(p),m=n(95),g=i(m),v=n(37),y=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleChange=n.handleChange.bind(n),n}return a(t,e),l(t,[{key:"render",value:function n(){var e=null
return e=this.props.readOnly?this.getReadonlyField():this.getSelectField()}},{key:"getReadonlyField",value:function i(){var e=this,t=this.props.source&&this.props.source.find(function(t){return t.value===e.props.value

})
return t="string"==typeof t?t:this.props.value,d["default"].createElement(v.FormControl.Static,this.getInputProps(),t)}},{key:"getSelectField",value:function u(){var e=this,t=this.props.source?this.props.source.slice():[]


return this.props.data.hasEmptyDefault&&!t.find(function(e){return!e.value})&&t.unshift({value:"",title:this.props.data.emptyString,disabled:!1}),d["default"].createElement(v.FormControl,this.getInputProps(),t.map(function(t,n){
var i=e.props.name+"-"+(t.value||"empty"+n)
return d["default"].createElement("option",{key:i,value:t.value,disabled:t.disabled},t.title)}))}},{key:"getInputProps",value:function c(){var e={bsClass:this.props.bsClass,className:this.props.className+" "+this.props.extraClass,
id:this.props.id,name:this.props.name,disabled:this.props.disabled}
return this.props.readOnly||s(e,{onChange:this.handleChange,value:this.props.value,componentClass:"select"}),e}},{key:"handleChange",value:function f(e){"function"==typeof this.props.onChange&&this.props.onChange(e,{
id:this.props.id,value:e.target.value})}}]),t}(f["default"])
y.propTypes={id:d["default"].PropTypes.string,name:d["default"].PropTypes.string.isRequired,onChange:d["default"].PropTypes.func,value:d["default"].PropTypes.oneOfType([d["default"].PropTypes.string,d["default"].PropTypes.number]),
readOnly:d["default"].PropTypes.bool,disabled:d["default"].PropTypes.bool,source:d["default"].PropTypes.arrayOf(d["default"].PropTypes.shape({value:d["default"].PropTypes.oneOfType([d["default"].PropTypes.string,d["default"].PropTypes.number]),
title:d["default"].PropTypes.oneOfType([d["default"].PropTypes.string,d["default"].PropTypes.number]),disabled:d["default"].PropTypes.bool})),data:d["default"].PropTypes.oneOfType([d["default"].PropTypes.array,d["default"].PropTypes.shape({
hasEmptyDefault:d["default"].PropTypes.bool,emptyString:d["default"].PropTypes.oneOfType([d["default"].PropTypes.string,d["default"].PropTypes.number])})])},y.defaultProps={source:[],extraClass:"",className:"",
data:{emptyString:g["default"]._t("Boolean.ANY","Any")}},t.SingleSelectField=y,t["default"]=(0,h["default"])(y)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(37),c=n(20),f=i(c),p=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleShow=n.handleShow.bind(n),n.handleHide=n.handleHide.bind(n),n.state={showing:!1},n}return a(t,e),s(t,[{key:"handleShow",value:function n(){this.setState({showing:!0})}},{key:"handleHide",
value:function i(){this.setState({showing:!1})}},{key:"render",value:function l(){var e=this.getPlacement(),t=u["default"].createElement(d.Popover,{id:this.props.id+"_Popover",className:"fade in popover-"+e,
title:this.props.data.popoverTitle},this.props.children),n=["btn","btn-secondary"]
return this.state.showing&&n.push("btn--no-focus"),this.props.title||n.push("font-icon-dot-3 btn--no-text btn--icon-xl"),u["default"].createElement(d.OverlayTrigger,{rootClose:!0,trigger:"click",container:this,
placement:e,overlay:t,onEnter:this.handleShow,onExited:this.handleHide},u["default"].createElement("button",{id:this.props.id,type:"button",className:n.join(" ")},this.props.title))}},{key:"getPlacement",
value:function c(){var e=this.props.data.placement
return e||"bottom"}}]),t}(f["default"])
p.propTypes={id:u["default"].PropTypes.string,title:u["default"].PropTypes.any,data:u["default"].PropTypes.oneOfType([u["default"].PropTypes.array,u["default"].PropTypes.shape({popoverTitle:u["default"].PropTypes.string,
placement:u["default"].PropTypes.oneOf(["top","right","bottom","left"])})])},t["default"]=p},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){var e="h"+(this.props.data.headingLevel||3)
return u["default"].createElement("div",{className:"field"},u["default"].createElement(e,this.getInputProps(),this.props.data.title))}},{key:"getInputProps",value:function i(){return{className:this.props.className+" "+this.props.extraClass,
id:this.props.id}}}]),t}(c["default"])
f.propTypes={extraClass:u["default"].PropTypes.string,id:u["default"].PropTypes.string,data:u["default"].PropTypes.oneOfType([u["default"].PropTypes.array,u["default"].PropTypes.shape({headingLevel:u["default"].PropTypes.number,
title:u["default"].PropTypes.string.isRequired})]).isRequired},f.defaultProps={className:"",extraClass:""},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),l(t,[{key:"getContent",value:function n(){return{__html:this.props.data.content}}},{key:"getInputProps",
value:function i(){return{className:this.props.className+" "+this.props.extraClass,id:this.props.id,name:this.props.name}}},{key:"render",value:function u(){return d["default"].createElement("div",s({},this.getInputProps(),{
dangerouslySetInnerHTML:this.getContent()}))}}]),t}(f["default"])
p.propTypes={id:d["default"].PropTypes.string,name:d["default"].PropTypes.string.isRequired,extraClass:d["default"].PropTypes.string,data:d["default"].PropTypes.oneOfType([d["default"].PropTypes.array,d["default"].PropTypes.shape({
content:d["default"].PropTypes.string.isRequired})]).isRequired},p.defaultProps={extraClass:"",className:""},t["default"]=p},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.HtmlReadonlyField=void 0
var s=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},l=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),u=n(4),d=i(u),c=n(20),f=i(c),p=n(58),h=i(p),m=n(37),g=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.getContent=n.getContent.bind(n),n}return a(t,e),l(t,[{key:"getContent",value:function n(){return{__html:this.props.value}}},{key:"getInputProps",value:function i(){return{bsClass:this.props.bsClass,
componentClass:this.props.componentClass,className:this.props.className+" "+this.props.extraClass,id:this.props.id,name:this.props.name}}},{key:"render",value:function u(){return d["default"].createElement(m.FormControl.Static,s({},this.getInputProps(),{
dangerouslySetInnerHTML:this.getContent()}))}}]),t}(f["default"])
g.propTypes={id:d["default"].PropTypes.string,name:d["default"].PropTypes.string.isRequired,extraClass:d["default"].PropTypes.string,value:d["default"].PropTypes.string},g.defaultProps={extraClass:"",className:""
},t.HtmlReadonlyField=g,t["default"]=(0,h["default"])(g)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0}),t.LookupField=void 0
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(37),p=n(58),h=i(p),m=n(95),g=i(m),v=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.getValueCSV=n.getValueCSV.bind(n),n}return a(t,e),s(t,[{key:"getValueCSV",value:function n(){var e=this,t=this.props.value
if(!Array.isArray(t)&&(t||"string"==typeof t||"number"==typeof t)){var n=this.props.source.find(function(e){return e.value===t})
return n?n.title:""}return t&&t.length?t.map(function(t){var n=e.props.source.find(function(e){return e.value===t})
return n&&n.title}).filter(function(e){return(""+e).length}).join(", "):""}},{key:"getFieldProps",value:function i(){return{id:this.props.id,name:this.props.name,className:this.props.className+" "+this.props.extraClass
}}},{key:"render",value:function l(){if(!this.props.source)return null
var e="('"+g["default"]._t("FormField.NONE","None")+"')"
return u["default"].createElement(f.FormControl.Static,this.getFieldProps(),this.getValueCSV()||e)}}]),t}(c["default"])
v.propTypes={extraClass:u["default"].PropTypes.string,id:u["default"].PropTypes.string,name:u["default"].PropTypes.string.isRequired,source:u["default"].PropTypes.arrayOf(u["default"].PropTypes.shape({
value:u["default"].PropTypes.oneOfType([u["default"].PropTypes.string,u["default"].PropTypes.number]),title:u["default"].PropTypes.any,disabled:u["default"].PropTypes.bool})),value:u["default"].PropTypes.any
},v.defaultProps={extraClass:"",className:"",value:[]},t.LookupField=v,t["default"]=(0,h["default"])(v)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(59),p=i(f),h=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"getLegend",value:function n(){return"fieldset"===this.props.data.tag&&this.props.data.legend?(0,
p["default"])("legend",this.props.data.legend):null}},{key:"render",value:function i(){var e=this.getLegend(),t=this.props.data.tag||"div",n=this.props.className+" "+this.props.extraClass
return u["default"].createElement(t,{className:n},e,this.props.children)}}]),t}(c["default"])
h.propTypes={data:u["default"].PropTypes.oneOfType([u["default"].PropTypes.array,u["default"].PropTypes.shape({tag:u["default"].PropTypes.string,legend:u["default"].PropTypes.string})]),extraClass:u["default"].PropTypes.string
},h.defaultProps={className:"",extraClass:""},t["default"]=h},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(37),p=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"getContainerProps",value:function n(){var e=this.props,t=e.activeKey,n=e.onSelect,i=e.className,r=e.extraClass,o=e.id,a=i+" "+r


return{activeKey:t,className:a,defaultActiveKey:this.getDefaultActiveKey(),onSelect:n,id:o}}},{key:"getDefaultActiveKey",value:function i(){var e=this,t=null
if("string"==typeof this.props.defaultActiveKey){var n=u["default"].Children.toArray(this.props.children).find(function(t){return t.props.name===e.props.defaultActiveKey})
n&&(t=n.props.name)}return"string"!=typeof t&&u["default"].Children.forEach(this.props.children,function(e){"string"!=typeof t&&(t=e.props.name)}),t}},{key:"renderTab",value:function l(e){return null===e.props.title?null:u["default"].createElement(f.NavItem,{
eventKey:e.props.name,disabled:e.props.disabled,className:e.props.tabClassName},e.props.title)}},{key:"renderNav",value:function d(){var e=u["default"].Children.map(this.props.children,this.renderTab)
return e.length<=1?null:u["default"].createElement(f.Nav,{bsStyle:this.props.bsStyle,role:"tablist"},e)}},{key:"render",value:function c(){var e=this.getContainerProps(),t=this.renderNav()
return u["default"].createElement(f.Tab.Container,e,u["default"].createElement("div",{className:"wrapper"},t,u["default"].createElement(f.Tab.Content,{animation:this.props.animation},this.props.children)))

}}]),t}(c["default"])
p.propTypes={id:u["default"].PropTypes.string.isRequired,defaultActiveKey:u["default"].PropTypes.string,extraClass:u["default"].PropTypes.string},p.defaultProps={bsStyle:"tabs",className:"",extraClass:""
},t["default"]=p},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(37),p=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"getTabProps",value:function n(){var e=this.props,t=e.name,n=e.className,i=e.extraClass,r=e.disabled,o=e.bsClass,a=e.onEnter,s=e.onEntering,l=e.onEntered,u=e.onExit,d=e.onExiting,c=e.onExited,f=e.animation,p=e.unmountOnExit


return{eventKey:t,className:n+" "+i,disabled:r,bsClass:o,onEnter:a,onEntering:s,onEntered:l,onExit:u,onExiting:d,onExited:c,animation:f,unmountOnExit:p}}},{key:"render",value:function i(){var e=this.getTabProps()


return u["default"].createElement(f.Tab.Pane,e,this.props.children)}}]),t}(c["default"])
p.propTypes={name:u["default"].PropTypes.string.isRequired,extraClass:u["default"].PropTypes.string,tabClassName:u["default"].PropTypes.string},p.defaultProps={className:"",extraClass:""},t["default"]=p

},function(e,t){e.exports=FormAction},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}var r=n(64),o=n(69),a=i(o),s=n(145),l=i(s),u=n(177),d=i(u)
document.addEventListener("DOMContentLoaded",function(){var e=a["default"].getSection("SilverStripe\\Admin\\CampaignAdmin")
l["default"].add({path:e.url,component:(0,r.withRouter)(d["default"]),childRoutes:[{path:":type/:id/:view",component:d["default"]},{path:"set/:id/:view",component:d["default"]}]})})},function(e,t,n){"use strict"


function i(e){if(e&&e.__esModule)return e
var t={}
if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])
return t["default"]=e,t}function r(e){return e&&e.__esModule?e:{"default":e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function s(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function l(e){return{config:e.config,
campaignId:e.campaign.campaignId,view:e.campaign.view,breadcrumbs:e.breadcrumbs,sectionConfig:e.config.sections["SilverStripe\\Admin\\CampaignAdmin"],securityId:e.config.SecurityID}}function u(e){return{
breadcrumbsActions:(0,m.bindActionCreators)(w,e)}}Object.defineProperty(t,"__esModule",{value:!0})
var d=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},c=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),f=n(4),p=r(f),h=n(27),m=n(28),g=n(64),v=n(33),y=r(v),b=n(178),w=i(b),_=n(179),C=r(_),T=n(20),E=r(T),P=n(175),O=r(P),S=n(95),k=r(S),j=n(180),x=r(j),R=n(38),I=r(R),F=n(181),A=r(F),D=function(e){
function t(e){o(this,t)
var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.publishApi=y["default"].createEndpointFetcher({url:n.props.sectionConfig.publishEndpoint.url,method:n.props.sectionConfig.publishEndpoint.method,defaultData:{SecurityID:n.props.securityId},payloadSchema:{
id:{urlReplacement:":id",remove:!0}}}),n.handleBackButtonClick=n.handleBackButtonClick.bind(n),n}return s(t,e),c(t,[{key:"componentWillMount",value:function n(){0===this.props.breadcrumbs.length&&this.setBreadcrumbs(this.props.params.view,this.props.params.id)

}},{key:"componentWillReceiveProps",value:function i(e){var t=this.props.params.id!==e.params.id||this.props.params.view!==e.params.view
t&&this.setBreadcrumbs(e.params.view,e.params.id)}},{key:"setBreadcrumbs",value:function r(e,t){var n=[{text:k["default"]._t("Campaigns.CAMPAIGN","Campaigns"),href:this.props.sectionConfig.url}]
switch(e){case"show":break
case"edit":n.push({text:k["default"]._t("Campaigns.EDIT_CAMPAIGN","Editing Campaign"),href:this.getActionRoute(t,e)})
break
case"create":n.push({text:k["default"]._t("Campaigns.ADD_CAMPAIGN","Add Campaign"),href:this.getActionRoute(t,e)})}this.props.breadcrumbsActions.setBreadcrumbs(n)}},{key:"handleBackButtonClick",value:function l(e){
if(this.props.breadcrumbs.length>1){var t=this.props.breadcrumbs[this.props.breadcrumbs.length-2]
if(t&&t.href)return e.preventDefault(),void this.props.router.push(t.href)}}},{key:"render",value:function u(){var e=null
switch(this.props.params.view){case"show":e=this.renderItemListView()
break
case"edit":e=this.renderDetailEditView()
break
case"create":e=this.renderCreateView()
break
default:e=this.renderIndexView()}return e}},{key:"renderIndexView",value:function f(){var e=this.props.sectionConfig.form.EditForm.schemaUrl,t={title:k["default"]._t("Campaigns.ADDCAMPAIGN"),icon:"plus",
handleClick:this.addCampaign.bind(this)},n={createFn:this.campaignListCreateFn.bind(this),schemaUrl:e}
return p["default"].createElement("div",{className:"cms-content__inner no-preview"},p["default"].createElement("div",{className:"cms-content__left cms-campaigns collapse in","aria-expanded":"true"},p["default"].createElement(x["default"],null,p["default"].createElement(C["default"],{
multiline:!0,crumbs:this.props.breadcrumbs})),p["default"].createElement("div",{className:"panel--padded panel--scrollable panel--single-toolbar"},p["default"].createElement("div",{className:"toolbar--content"
},p["default"].createElement("div",{className:"btn-toolbar"},p["default"].createElement(O["default"],t))),p["default"].createElement("div",{className:"campaign-admin"},p["default"].createElement(I["default"],n)))))

}},{key:"renderItemListView",value:function h(){var e={sectionConfig:this.props.sectionConfig,campaignId:this.props.params.id,itemListViewEndpoint:this.props.sectionConfig.itemListViewEndpoint,publishApi:this.publishApi,
handleBackButtonClick:this.handleBackButtonClick.bind(this)}
return p["default"].createElement(A["default"],e)}},{key:"renderDetailEditView",value:function m(){var e=this.props.sectionConfig.form.DetailEditForm.schemaUrl,t=e
this.props.params.id>0&&(t=e+"/"+this.props.params.id)
var n={createFn:this.campaignEditCreateFn.bind(this),schemaUrl:t}
return p["default"].createElement("div",{className:"cms-content__inner"},p["default"].createElement(x["default"],{showBackButton:!0,handleBackButtonClick:this.handleBackButtonClick},p["default"].createElement(C["default"],{
multiline:!0,crumbs:this.props.breadcrumbs})),p["default"].createElement("div",{className:"panel panel--padded panel--scrollable panel--single-toolbar"},p["default"].createElement("div",{className:"form--inline"
},p["default"].createElement(I["default"],n))))}},{key:"renderCreateView",value:function g(){var e=this.props.sectionConfig.form.DetailEditForm.schemaUrl,t=e
this.props.params.id>0&&(t=e+"/"+this.props.params.id)
var n={createFn:this.campaignAddCreateFn.bind(this),schemaUrl:t}
return p["default"].createElement("div",{className:"cms-content__inner"},p["default"].createElement(x["default"],{showBackButton:!0,handleBackButtonClick:this.handleBackButtonClick},p["default"].createElement(C["default"],{
multiline:!0,crumbs:this.props.breadcrumbs})),p["default"].createElement("div",{className:"panel panel--padded panel--scrollable panel--single-toolbar"},p["default"].createElement("div",{className:"form--inline"
},p["default"].createElement(I["default"],n))))}},{key:"campaignEditCreateFn",value:function v(e,t){var n=this,i=this.props.sectionConfig.url
if("action_cancel"===t.name){var r=d({},t,{handleClick:function o(e){e.preventDefault(),n.props.router.push(i)}})
return p["default"].createElement(e,d({key:t.id},r))}return p["default"].createElement(e,d({key:t.id},t))}},{key:"campaignAddCreateFn",value:function b(e,t){var n=this,i=this.props.sectionConfig.url
if("action_cancel"===t.name){var r=d({},t,{handleClick:function o(e){e.preventDefault(),n.props.router.push(i)}})
return p["default"].createElement(e,d({key:t.name},r))}return p["default"].createElement(e,d({key:t.name},t))}},{key:"campaignListCreateFn",value:function w(e,t){var n=this,i=this.props.sectionConfig.url,r="set"


if("GridField"===t.component){var o=d({},t,{data:d({},t.data,{handleDrillDown:function a(e,t){n.props.router.push(i+"/"+r+"/"+t.ID+"/show")},handleEditRecord:function s(e,t){n.props.router.push(i+"/"+r+"/"+t+"/edit")

}})})
return p["default"].createElement(e,d({key:o.name},o))}return p["default"].createElement(e,d({key:t.name},t))}},{key:"addCampaign",value:function _(){var e=this.getActionRoute(0,"create")
this.props.router.push(e)}},{key:"getActionRoute",value:function T(e,t){return this.props.sectionConfig.url+"/set/"+e+"/"+t}}]),t}(E["default"])
D.propTypes={breadcrumbsActions:p["default"].PropTypes.object.isRequired,campaignId:p["default"].PropTypes.string,sectionConfig:p["default"].PropTypes.object.isRequired,securityId:p["default"].PropTypes.string.isRequired,
view:p["default"].PropTypes.string},t["default"]=(0,g.withRouter)((0,h.connect)(l,u)(D))},function(e,t){e.exports=BreadcrumbsActions},function(e,t){e.exports=Breadcrumb},function(e,t){e.exports=Toolbar

},function(e,t,n){"use strict"
function i(e){if(e&&e.__esModule)return e
var t={}
if(null!=e)for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n])
return t["default"]=e,t}function r(e){return e&&e.__esModule?e:{"default":e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function s(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function l(e,t){var n=null,i=t.sectionConfig.treeClass


return e.records&&e.records[i]&&t.campaignId&&(n=e.records[i][parseInt(t.campaignId,10)]),{config:e.config,record:n||{},campaign:e.campaign,treeClass:i,breadcrumbs:e.breadcrumbs}}function u(e){return{breadcrumbsActions:(0,
m.bindActionCreators)(y,e),recordActions:(0,m.bindActionCreators)(w,e),campaignActions:(0,m.bindActionCreators)(C,e)}}Object.defineProperty(t,"__esModule",{value:!0})
var d=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]
for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(e[i]=n[i])}return e},c=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),f=function q(e,t,n){null===e&&(e=Function.prototype)


var i=Object.getOwnPropertyDescriptor(e,t)
if(void 0===i){var r=Object.getPrototypeOf(e)
return null===r?void 0:q(r,t,n)}if("value"in i)return i.value
var o=i.get
if(void 0!==o)return o.call(n)},p=n(4),h=r(p),m=n(28),g=n(27),v=n(178),y=i(v),b=n(47),w=i(b),_=n(182),C=i(_),T=n(20),E=r(T),P=n(183),O=r(P),S=n(184),k=r(S),j=n(186),x=r(j),R=n(180),I=r(R),F=n(175),A=r(F),D=n(187),M=r(D),N=n(179),U=r(N),L=n(188),B=r(L),H=n(95),$=r(H),V=function(e){
function t(e){o(this,t)
var n=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handlePublish=n.handlePublish.bind(n),n.handleItemSelected=n.handleItemSelected.bind(n),n.setBreadcrumbs=n.setBreadcrumbs.bind(n),n}return s(t,e),c(t,[{key:"componentDidMount",value:function n(){
var e=this.props.itemListViewEndpoint.url.replace(/:id/,this.props.campaignId)
f(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"componentDidMount",this).call(this),this.setBreadcrumbs(),Object.keys(this.props.record).length||this.props.recordActions.fetchRecord(this.props.treeClass,"get",e).then(this.setBreadcrumbs)

}},{key:"setBreadcrumbs",value:function i(){if(this.props.record){var e=[{text:$["default"]._t("Campaigns.CAMPAIGN","Campaigns"),href:this.props.sectionConfig.url}]
e.push({text:this.props.record.Name,href:this.props.sectionConfig.url+"/set/"+this.props.campaignId+"/show"}),this.props.breadcrumbsActions.setBreadcrumbs(e)}}},{key:"render",value:function r(){var e=this,t=this.props.campaign.changeSetItemId,n=null,i=this.props.campaignId,r=this.props.record,o=this.groupItemsForSet(),a=[]


Object.keys(o).forEach(function(s){var l=o[s],u=l.items.length,d=[],c=u+" "+(1===u?l.singular:l.plural),f="Set_"+i+"_Group_"+s
l.items.forEach(function(i){t||(t=i.ID)
var o=t===i.ID
o&&i._links&&(n=i._links)
var a=[]
"none"!==i.ChangeType&&"published"!==r.State||a.push("list-group-item--inactive"),o&&a.push("active"),d.push(h["default"].createElement(x["default"],{key:i.ID,className:a.join(" "),handleClick:e.handleItemSelected,
handleClickArg:i.ID},h["default"].createElement(M["default"],{item:i,campaign:e.props.record})))}),a.push(h["default"].createElement(k["default"],{key:f,groupid:f,title:c},d))})
var s=[this.props.config.baseUrl,this.props.config.sections["SilverStripe\\CMS\\Controllers\\CMSPagesController"].url].join("/"),l=a.length?h["default"].createElement(O["default"],null,a):h["default"].createElement("div",{
className:"alert alert-warning",role:"alert"},h["default"].createElement("strong",null,"This campaign is empty.")," You can add pages by selecting"," ",h["default"].createElement("em",null,"Add to campaign")," from within the ",h["default"].createElement("em",null,"More Options")," popup on"," ","the ",h["default"].createElement("a",{
href:s},"edit page screen"),"."),u=["panel","panel--padded","panel--scrollable","panel--double-toolbar","campaign-items"]
return h["default"].createElement("div",{className:"cms-content__split cms-content__split--left-sm"},h["default"].createElement("div",{className:"cms-content__left cms-campaigns collapse in","aria-expanded":"true"
},h["default"].createElement(I["default"],{showBackButton:!0,handleBackButtonClick:this.props.handleBackButtonClick},h["default"].createElement(U["default"],{multiline:!0,crumbs:this.props.breadcrumbs})),h["default"].createElement("div",{
className:u.join(" ")},l),h["default"].createElement("div",{className:"toolbar--south"},this.renderButtonToolbar())),h["default"].createElement(B["default"],{itemLinks:n,itemId:t}))}},{key:"handleItemSelected",
value:function l(e,t){this.props.campaignActions.selectChangeSetItem(t)}},{key:"renderButtonToolbar",value:function u(){var e=this.getItems()
if(!e||!e.length)return h["default"].createElement("div",{className:"btn-toolbar"})
var t={}
return"open"===this.props.record.State?t=d(t,{title:$["default"]._t("Campaigns.PUBLISHCAMPAIGN"),bootstrapButtonStyle:"primary",loading:this.props.campaign.isPublishing,handleClick:this.handlePublish,icon:"rocket"
}):"published"===this.props.record.State&&(t=d(t,{title:$["default"]._t("Campaigns.REVERTCAMPAIGN"),bootstrapButtonStyle:"default",icon:"back-in-time",disabled:!0})),h["default"].createElement("div",{className:"btn-toolbar"
},h["default"].createElement(A["default"],t))}},{key:"getItems",value:function p(){return this.props.record&&this.props.record._embedded?this.props.record._embedded.items:null}},{key:"groupItemsForSet",
value:function m(){var e={},t=this.getItems()
return t?(t.forEach(function(t){var n=t.BaseClass
e[n]||(e[n]={singular:t.Singular,plural:t.Plural,items:[]}),e[n].items.push(t)}),e):e}},{key:"handlePublish",value:function g(e){e.preventDefault(),this.props.campaignActions.publishCampaign(this.props.publishApi,this.props.treeClass,this.props.campaignId)

}}]),t}(E["default"])
V.propTypes={campaign:h["default"].PropTypes.shape({isPublishing:h["default"].PropTypes.bool.isRequired,changeSetItemId:h["default"].PropTypes.number}),breadcrumbsActions:h["default"].PropTypes.object.isRequired,
campaignActions:h["default"].PropTypes.object.isRequired,publishApi:h["default"].PropTypes.func.isRequired,record:h["default"].PropTypes.object.isRequired,recordActions:h["default"].PropTypes.object.isRequired,
sectionConfig:h["default"].PropTypes.object.isRequired,handleBackButtonClick:h["default"].PropTypes.func},t["default"]=(0,g.connect)(l,u)(V)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e){return{type:l["default"].SET_CAMPAIGN_SELECTED_CHANGESETITEM,payload:{changeSetItemId:e}}}function o(e,t){return function(n){n({type:l["default"].SET_CAMPAIGN_ACTIVE_CHANGESET,
payload:{campaignId:e,view:t}})}}function a(e,t,n){return function(i){i({type:l["default"].PUBLISH_CAMPAIGN_REQUEST,payload:{campaignId:n}}),e({id:n}).then(function(e){i({type:l["default"].PUBLISH_CAMPAIGN_SUCCESS,
payload:{campaignId:n}}),i({type:d["default"].FETCH_RECORD_SUCCESS,payload:{recordType:t,data:e}})})["catch"](function(e){i({type:l["default"].PUBLISH_CAMPAIGN_FAILURE,payload:{error:e}})})}}Object.defineProperty(t,"__esModule",{
value:!0}),t.selectChangeSetItem=r,t.showCampaignView=o,t.publishCampaign=a
var s=n(158),l=i(s),u=n(48),d=i(u)},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){return u["default"].createElement("div",{className:"accordion",
role:"tablist","aria-multiselectable":"true"},this.props.children)}}]),t}(c["default"])
t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d)


n(185)
var f=function(e){function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){var e=this.props.groupid+"_Header",t=this.props.groupid+"_Items",n="#"+t,i={
id:t,"aria-expanded":!0,className:"list-group list-group-flush collapse in",role:"tabpanel","aria-labelledby":e}
return u["default"].createElement("div",{className:"accordion__block"},u["default"].createElement("a",{className:"accordion__title","data-toggle":"collapse",href:n,"aria-expanded":"true","aria-controls":t,
id:e,role:"tab"},this.props.title),u["default"].createElement("div",i,this.props.children))}}]),t}(c["default"])
t["default"]=f},function(e,t){e.exports=BootstrapCollapse},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=function(e){
function t(e){r(this,t)
var n=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e))
return n.handleClick=n.handleClick.bind(n),n}return a(t,e),s(t,[{key:"render",value:function n(){var e="list-group-item "+this.props.className
return u["default"].createElement("a",{tabIndex:"0",className:e,onClick:this.handleClick},this.props.children)}},{key:"handleClick",value:function i(e){this.props.handleClick&&this.props.handleClick(e,this.props.handleClickArg)

}}]),t}(c["default"])
f.propTypes={handleClickArg:u["default"].PropTypes.any,handleClick:u["default"].PropTypes.func},t["default"]=f},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(20),c=i(d),f=n(95),p=i(f),h=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){var e=null,t={},n=this.props.item,i=this.props.campaign


if("open"===i.State)switch(n.ChangeType){case"created":t.className="label label-warning list-group-item__status",t.Title=p["default"]._t("CampaignItem.DRAFT","Draft")
break
case"modified":t.className="label label-warning list-group-item__status",t.Title=p["default"]._t("CampaignItem.MODIFIED","Modified")
break
case"deleted":t.className="label label-error list-group-item__status",t.Title=p["default"]._t("CampaignItem.REMOVED","Removed")
break
case"none":default:t.className="label label-success list-group-item__status",t.Title=p["default"]._t("CampaignItem.NO_CHANGES","No changes")}var r=u["default"].createElement("span",{className:"list-group-item__info campaign-admin__item-links--has-links font-icon-link"
},"3 linked items")
return n.Thumbnail&&(e=u["default"].createElement("span",{className:"list-group-item__thumbnail"},u["default"].createElement("img",{alt:n.Title,src:n.Thumbnail}))),u["default"].createElement("div",null,e,u["default"].createElement("h4",{
className:"list-group-item-heading"},n.Title),u["default"].createElement("span",{className:"list-group-item__info campaign-admin__item-links--is-linked font-icon-link"}),r,t.className&&t.Title&&u["default"].createElement("span",{
className:t.className},t.Title))}}]),t}(c["default"])
h.propTypes={campaign:u["default"].PropTypes.object.isRequired,item:u["default"].PropTypes.object.isRequired},t["default"]=h},function(e,t,n){"use strict"
function i(e){return e&&e.__esModule?e:{"default":e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called")


return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t)
e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{
value:!0})
var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var i=t[n]
i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}return function(t,n,i){return n&&e(t.prototype,n),i&&e(t,i),t}}(),l=n(4),u=i(l),d=n(95),c=i(d),f=n(20),p=i(f),h=function(e){
function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return a(t,e),s(t,[{key:"render",value:function n(){var e=null,t=null,n=""
this.props.itemLinks&&this.props.itemLinks.preview&&(this.props.itemLinks.preview.Stage?(t=this.props.itemLinks.preview.Stage.href,n=this.props.itemLinks.preview.Stage.type):this.props.itemLinks.preview.Live&&(t=this.props.itemLinks.preview.Live.href,
n=this.props.itemLinks.preview.Live.type))
var i=null,r="edit",o=[]
return this.props.itemLinks&&this.props.itemLinks.edit&&(i=this.props.itemLinks.edit.href,o.push(u["default"].createElement("a",{key:r,href:i,className:"btn btn-secondary-outline font-icon-edit"},u["default"].createElement("span",{
className:"btn__title"},c["default"]._t("Preview.EDIT","Edit"))))),e=this.props.itemId?t?n&&0===n.indexOf("image/")?u["default"].createElement("div",{className:"preview__file-container panel--scrollable"
},u["default"].createElement("img",{alt:t,className:"preview__file--fits-space",src:t})):u["default"].createElement("iframe",{className:"preview__iframe",src:t}):u["default"].createElement("div",{className:"preview__overlay"
},u["default"].createElement("h3",{className:"preview__overlay-text"},"There is no preview available for this item.")):u["default"].createElement("div",{className:"preview__overlay"},u["default"].createElement("h3",{
className:"preview__overlay-text"},"No preview available.")),u["default"].createElement("div",{className:"cms-content__right preview"},e,u["default"].createElement("a",{href:"",className:"cms-content__back-btn font-icon-left-open-big"
}),u["default"].createElement("div",{className:"toolbar--south"},u["default"].createElement("div",{className:"btn-toolbar"},o)))}}]),t}(p["default"])
h.propTypes={itemLinks:u["default"].PropTypes.object,itemId:u["default"].PropTypes.number},t["default"]=h}])

//# sourceMappingURL=bundle.js.map
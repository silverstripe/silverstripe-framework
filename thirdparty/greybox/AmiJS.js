//AJS JavaScript library (minify'ed version)
//Copyright (c) 2006 Amir Salihefendic. All rights reserved.
//Copyright (c) 2005 Bob Ippolito. All rights reserved.
//License: http://www.opensource.org/licenses/mit-license.php
//Visit http://orangoo.com/AmiNation/AJS for full version.
AJS = {
BASE_URL: "",
drag_obj: null,
drag_elm: null,
_drop_zones: [],
_cur_pos: null,

_unloadListeners: function() {
if(AJS.listeners)
AJS.map(AJS.listeners, function(elm, type, fn) {AJS.removeEventListener(elm, type, fn)});
AJS.listeners = [];
},
getElement: function(id) {
if(AJS.isString(id) || AJS.isNumber(id))
return document.getElementById(id);
else
return id;
},
getScrollTop: function() {
//From: http://www.quirksmode.org/js/doctypes.html
var t;
if (document.documentElement && document.documentElement.scrollTop)
t = document.documentElement.scrollTop;
else if (document.body)
t = document.body.scrollTop;
return t;
},
isArray: function(obj) {
return obj instanceof Array;
},
removeElement: function(/*elm1, elm2...*/) {
var args = AJS.flattenList(arguments);
AJS.map(args, function(elm) { AJS.swapDOM(elm, null); });
},
isDict: function(o) {
var str_repr = String(o);
return str_repr.indexOf(" Object") != -1;
},
isString: function(obj) {
return (typeof obj == 'string');
},
getIndex: function(elm, list/*optional*/, eval_fn) {
for(var i=0; i < list.length; i++)
if(eval_fn && eval_fn(list[i]) || elm == list[i])
return i;
return -1;
},
createDOM: function(name, attrs) {
var i=0, attr;
elm = document.createElement(name);
if(AJS.isDict(attrs[i])) {
for(k in attrs[0]) {
attr = attrs[0][k];
if(k == "style")
elm.style.cssText = attr;
else if(k == "class" || k == 'className')
elm.className = attr;
else {
elm.setAttribute(k, attr);
}
}
i++;
}
if(attrs[0] == null)
i = 1;
AJS.map(attrs, function(n) {
if(n) {
if(AJS.isString(n) || AJS.isNumber(n))
n = AJS.TN(n);
elm.appendChild(n);
}
}, i);
return elm;
},
isIe: function() {
return (navigator.userAgent.toLowerCase().indexOf("msie") != -1 && navigator.userAgent.toLowerCase().indexOf("opera") == -1);
},
addEventListener: function(elm, type, fn, /*optional*/listen_once, cancle_bubble) {
if(!cancle_bubble)
cancle_bubble = false;
var elms = AJS.$A(elm);
AJS.map(elms, function(elmz) {
if(listen_once)
fn = AJS._listenOnce(elmz, type, fn);
if(AJS.isIn(type, ['submit', 'load', 'scroll', 'resize'])) {
var old = elm['on' + type];
elm['on' + type] = function() {
if(old) {
fn(arguments);
return old(arguments);
}
else
return fn(arguments);
};
return;
}
if (elmz.attachEvent) {
//FIXME: We ignore cancle_bubble for IE... hmmz
elmz.attachEvent("on" + type, fn);
}
else if(elmz.addEventListener)
elmz.addEventListener(type, fn, cancle_bubble);
AJS.listeners = AJS.$A(AJS.listeners);
AJS.listeners.push([elmz, type, fn]);
});
},
swapDOM: function(dest, src) {
dest = AJS.getElement(dest);
var parent = dest.parentNode;
if (src) {
src = AJS.getElement(src);
parent.replaceChild(src, dest);
} else {
parent.removeChild(dest);
}
return src;
},
getLast: function(list) {
if(list.length > 0)
return list[list.length-1];
else
return null;
},
map: function(list, fn,/*optional*/ start_index, end_index) {
var i = 0, l = list.length;
if(start_index)
i = start_index;
if(end_index)
l = end_index;
for(i; i < l; i++)
fn.apply(null, [list[i]]);
},
getElementsByTagAndClassName: function(tag_name, class_name, /*optional*/ parent) {
var class_elements = [];
if(!AJS.isDefined(parent))
parent = document;
if(!AJS.isDefined(tag_name))
tag_name = '*';
var els = parent.getElementsByTagName(tag_name);
var els_len = els.length;
var pattern = new RegExp("(^|\\s)" + class_name + "(\\s|$)");
for (i = 0, j = 0; i < els_len; i++) {
if ( pattern.test(els[i].className) || class_name == null ) {
class_elements[j] = els[i];
j++;
}
}
return class_elements;
},
isOpera: function() {
return (navigator.userAgent.toLowerCase().indexOf("opera") != -1);
},
setLeft: function(/*elm1, elm2..., left*/) {
var args = AJS.flattenList(arguments);
var l = AJS.getLast(args);
AJS.map(args, function(elm) { elm.style.left = AJS.getCssDim(l)}, 0, args.length-1);
},
getBody: function() {
return AJS.$bytc('body')[0]
},
getWindowSize: function() {
var win_w, win_h;
if (self.innerHeight) {
win_w = self.innerWidth;
win_h = self.innerHeight;
} else if (document.documentElement && document.documentElement.clientHeight) {
win_w = document.documentElement.clientWidth;
win_h = document.documentElement.clientHeight;
} else if (document.body) {
win_w = document.body.clientWidth;
win_h = document.body.clientHeight;
}
return {'w': win_w, 'h': win_h};
},
showElement: function(/*elms...*/) {
var args = AJS.flattenList(arguments);
AJS.map(args, function(elm) { elm.style.display = ''});
},
removeEventListener: function(elm, type, fn, /*optional*/cancle_bubble) {
if(!cancle_bubble)
cancle_bubble = false;
if(elm.removeEventListener) {
elm.removeEventListener(type, fn, cancle_bubble);
if(AJS.isOpera())
elm.removeEventListener(type, fn, !cancle_bubble);
}
else if(elm.detachEvent)
elm.detachEvent("on" + type, fn);
},
_getRealScope: function(fn, /*optional*/ extra_args, dont_send_event, rev_extra_args) {
var scope = window;
extra_args = AJS.$A(extra_args);
if(fn._cscope)
scope = fn._cscope;
return function() {
//Append all the orginal arguments + extra_args
var args = [];
var i = 0;
if(dont_send_event)
i = 1;
AJS.map(arguments, function(arg) { args.push(arg) }, i);
args = args.concat(extra_args);
if(rev_extra_args)
args = args.reverse();
return fn.apply(scope, args);
};
},
_createDomShortcuts: function() {
var elms = [
"ul", "li", "td", "tr", "th",
"tbody", "table", "input", "span", "b",
"a", "div", "img", "button", "h1",
"h2", "h3", "br", "textarea", "form",
"p", "select", "option", "iframe", "script",
"center", "dl", "dt", "dd", "small",
"pre"
];
var createDOM = AJS.createDOM;
var extends_ajs = function(elm) {
var c_dom = "return createDOM.apply(null, ['" + elm + "', arguments]);";
var c_fun_dom = 'function() { ' + c_dom + '  }';
eval("AJS." + elm.toUpperCase() + "=" + c_fun_dom);
}
AJS.map(elms, extends_ajs);
AJS.TN = function(text) { return document.createTextNode(text) };
},
isNumber: function(obj) {
return (typeof obj == 'number');
},
bind: function(fn, scope, /*optional*/ extra_args, dont_send_event, rev_extra_args) {
fn._cscope = scope;
return AJS._getRealScope(fn, extra_args, dont_send_event, rev_extra_args);
},
setTop: function(/*elm1, elm2..., top*/) {
var args = AJS.flattenList(arguments);
var t = AJS.getLast(args);
AJS.map(args, function(elm) { elm.style.top = AJS.getCssDim(t)}, 0, args.length-1);
},
appendChildNodes: function(elm/*, elms...*/) {
if(arguments.length >= 2) {
AJS.map(arguments, function(n) {
if(AJS.isString(n))
n = AJS.TN(n);
if(AJS.isDefined(n))
elm.appendChild(n);
}, 1);
}
return elm;
},
isDefined: function(o) {
return (o != "undefined" && o != null)
},
isIn: function(elm, list) {
var i = AJS.getIndex(elm, list);
if(i != -1)
return true;
else
return false;
},
setHeight: function(/*elm1, elm2..., height*/) {
var args = AJS.flattenList(arguments);
var h = AJS.getLast(args);
AJS.map(args, function(elm) { elm.style.height = AJS.getCssDim(h)}, 0, args.length-1);
},
hideElement: function(elm) {
var args = AJS.flattenList(arguments);
AJS.map(args, function(elm) { elm.style.display = 'none'});
},
createArray: function(v) {
if(AJS.isArray(v) && !AJS.isString(v))
return v;
else if(!v)
return [];
else
return [v];
},
setWidth: function(/*elm1, elm2..., width*/) {
var args = AJS.flattenList(arguments);
var w = AJS.getLast(args);
AJS.map(args, function(elm) { elm.style.width = AJS.getCssDim(w)}, 0, args.length-1);
},
getCssDim: function(dim) {
if(AJS.isString(dim))
return dim;
else
return dim + "px";
},
_listenOnce: function(elm, type, fn) {
var r_fn = function() {
AJS.removeEventListener(elm, type, r_fn);
fn(arguments);
}
return r_fn;
},
flattenList: function(list) {
var r = [];
var _flatten = function(r, l) {
AJS.map(l, function(o) {
if (AJS.isArray(o))
_flatten(r, o);
else
r.push(o);
});
}
_flatten(r, list);
return r;
}
}

AJS.$ = AJS.getElement;
AJS.$$ = AJS.getElements;
AJS.$f = AJS.getFormElement;
AJS.$b = AJS.bind;
AJS.$A = AJS.createArray;
AJS.DI = AJS.documentInsert;
AJS.ACN = AJS.appendChildNodes;
AJS.RCN = AJS.replaceChildNodes;
AJS.AEV = AJS.addEventListener;
AJS.REV = AJS.removeEventListener;
AJS.$bytc = AJS.getElementsByTagAndClassName;

AJS.addEventListener(window, 'unload', AJS._unloadListeners);
AJS._createDomShortcuts()

AJSDeferred = function(req) {
this.callbacks = [];
this.errbacks = [];
this.req = req;
};
AJSDeferred.prototype = {
excCallbackSeq: function(req, list) {
var data = req.responseText;
while (list.length > 0) {
var fn = list.pop();
var new_data = fn(data, req);
if(new_data)
data = new_data;
}
},
callback: function () {
this.excCallbackSeq(this.req, this.callbacks);
},
errback: function() {
if(this.errbacks.length == 0)
alert("Error encountered:\n" + this.req.responseText);
this.excCallbackSeq(this.req, this.errbacks);
},
addErrback: function(fn) {
this.errbacks.unshift(fn);
},
addCallback: function(fn) {
this.callbacks.unshift(fn);
},
addCallbacks: function(fn1, fn2) {
this.addCallback(fn1);
this.addErrback(fn2);
},
sendReq: function(data) {
if(AJS.isObject(data)) {
var post_data = [];
for(k in data) {
post_data.push(k + "=" + AJS.urlencode(data[k]));
}
post_data = post_data.join("&");
this.req.send(post_data);
}
else if(AJS.isDefined(data))
this.req.send(data);
else {
this.req.send("");
}
}
}
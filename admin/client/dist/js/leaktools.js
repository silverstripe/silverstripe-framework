webpackJsonp([5],[function(e,n,o){"use strict"
function t(e){return e&&e.__esModule?e:{"default":e}}var l=o(1),a=t(l),c=function u(e){var n=e.cloneNode(!0),o=(0,a["default"])("<div></div>")
return o.append(n),o.html()}
a["default"].leaktools={logDuplicateElements:function d(){var e=(0,a["default"])("*"),n=!1
e.each(function(o,t){e.not(t).each(function(e,o){c(t)==c(o)&&(n=!0,console.log(t,o))})}),n||console.log("No duplicates found")},logUncleanedElements:function f(e){a["default"].each(a["default"].cache,function(){
var n=this.handle&&this.handle.elem
if(n){for(var o=n;o&&1==o.nodeType;)o=o.parentNode
o?o!==document&&console.log("Attached, but to",o,"not our document",n):(console.log("Unattached",n),console.log(this.events),e&&(0,a["default"])(n).unbind().remove())}})}}}])

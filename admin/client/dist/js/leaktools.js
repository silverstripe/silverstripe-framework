webpackJsonp([4],[function(e,n,o){"use strict"
function t(e){return e&&e.__esModule?e:{default:e}}var l=o(1),a=t(l),c=function e(n){var o=n.cloneNode(!0),t=(0,a.default)("<div></div>")
return t.append(o),t.html()}
a.default.leaktools={logDuplicateElements:function e(){var n=(0,a.default)("*"),o=!1
n.each(function(e,t){n.not(t).each(function(e,n){c(t)==c(n)&&(o=!0,console.log(t,n))})}),o||console.log("No duplicates found")},logUncleanedElements:function e(n){a.default.each(a.default.cache,function(){
var e=this.handle&&this.handle.elem
if(e){for(var o=e;o&&1==o.nodeType;)o=o.parentNode
o?o!==document&&console.log("Attached, but to",o,"not our document",e):(console.log("Unattached",e),console.log(this.events),n&&(0,a.default)(e).unbind().remove())}})}}}])

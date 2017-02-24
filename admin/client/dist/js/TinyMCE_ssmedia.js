webpackJsonp([3],[function(t,e){"use strict"
function i(t,e,i){return e in t?Object.defineProperty(t,e,{value:i,enumerable:!0,configurable:!0,writable:!0}):t[e]=i,t}var n=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var i=arguments[e]


for(var n in i)Object.prototype.hasOwnProperty.call(i,n)&&(t[n]=i[n])}return t}
!function(){var t={getInfo:function e(){return{longname:"Media Dialog for SilverStripe CMS",author:"Sam Minnée",authorurl:"http://www.siverstripe.com/",infourl:"http://www.silverstripe.com/",version:"1.1"
}},init:function a(t){t.addButton("ssmedia",{icon:"image",title:"Insert Media",cmd:"ssmedia"}),t.addMenuItem("ssmedia",{icon:"image",text:"Insert Media",cmd:"ssmedia"}),t.addCommand("ssmedia",function(){
window.jQuery("#"+t.id).entwine("ss").openMediaDialog()}),t.on("BeforeExecCommand",function(e){var i=e.command,n=e.ui,a=e.value
"mceAdvImage"!==i&&"mceImage"!==i||(e.preventDefault(),t.execCommand("ssmedia",n,a))}),t.on("SaveContent",function(t){var e=window.jQuery(t.content),i=function n(t){return Object.keys(t).map(function(e){
return t[e]?e+'="'+t[e]+'"':null}).filter(function(t){return null!==t}).join(" ")}
e.find(".ss-htmleditorfield-file.embed").each(function(){var t=window.jQuery(this),e={width:t.attr("width"),"class":t.attr("cssclass"),thumbnail:t.data("thumbnail")},n="[embed "+i(e)+"]"+t.data("url")+"[/embed]"


t.replaceWith(n)}),e.find("img").each(function(){var t=window.jQuery(this),e={src:t.attr("src"),id:t.data("id"),width:t.attr("width"),height:t.attr("height"),"class":t.attr("class"),title:t.attr("title"),
alt:t.attr("alt")},n="[image "+i(e)+"]"
t.replaceWith(n)}),t.content="",e.each(function(){void 0!==this.outerHTML&&(t.content+=this.outerHTML)})}),t.on("BeforeSetContent",function(t){for(var e=null,a=t.content,r=function l(t){return t.match(/([^\s\/'"=,]+)\s*=\s*(('([^']+)')|("([^"]+)")|([^\s,\]]+))/g).reduce(function(t,e){
var a=e.match(/^([^\s\/'"=,]+)\s*=\s*(?:(?:'([^']+)')|(?:"([^"]+)")|(?:[^\s,\]]+))$/),r=a[1],s=a[2]||a[3]||a[4]
return n({},t,i({},r,s))},{})},s=/\[embed(.*?)](.+?)\[\/\s*embed\s*]/gi,c=function m(){var t=r(e[1]),i=window.jQuery("<img/>").attr({src:t.thumbnail,width:t.width,height:t.height,"class":t["class"],"data-url":e[2]
}).addClass("ss-htmleditorfield-file embed")
t.cssclass=t["class"],Object.keys(t).forEach(function(e){return i.attr("data-"+e,t[e])}),a=a.replace(e[0],window.jQuery("<div/>").append(i).html())};e=s.exec(a);)c()
for(var o=/\[image(.*?)]/gi;e=o.exec(a);){var d=r(e[1]),u=window.jQuery("<img/>").attr({src:d.src,width:d.width,height:d.height,"class":d["class"],alt:d.alt,title:d.title,"data-id":d.id})
a=a.replace(e[0],window.jQuery("<div/>").append(u).html())}t.content=a})}}
tinymce.PluginManager.add("ssmedia",function(e){return t.init(e)})}()}])

webpackJsonp([2],[function(t,e,n){(function(t){"use strict"
!function(){var e={getInfo:function n(){return{longname:"Special buttons for SilverStripe CMS",author:"Sam Minnée",authorurl:"http://www.siverstripe.com/",infourl:"http://www.silverstripe.com/",version:"1.0"
}},init:function i(e){e.addButton("sslink",{icon:"link",title:"Insert Link",cmd:"sslink"}),e.addMenuItem("sslink",{icon:"link",text:"Insert Link",cmd:"sslink"}),e.addButton("ssmedia",{icon:"image",title:"Insert Media",
cmd:"ssmedia"}),e.addMenuItem("ssmedia",{icon:"image",text:"Insert Media",cmd:"ssmedia"}),e.addCommand("sslink",function(e){t("#"+this.id).entwine("ss").openLinkDialog()}),e.addCommand("ssmedia",function(e){
t("#"+this.id).entwine("ss").openMediaDialog()}),e.on("BeforeExecCommand",function(t){var n=t.command,i=t.ui,a=t.value
"mceAdvLink"==n||"mceLink"==n?(t.preventDefault(),e.execCommand("sslink",i,a)):"mceAdvImage"!=n&&"mceImage"!=n||(t.preventDefault(),e.execCommand("ssmedia",i,a))}),e.on("SaveContent",function(e){var n=t(e.content),i=function a(t){
return Object.keys(t).map(function(e){return t[e]?e+'="'+t[e]+'"':null}).filter(function(t){return null!==t}).join(" ")}
n.find(".ss-htmleditorfield-file.embed").each(function(){var e=t(this),n={width:e.attr("width"),"class":e.attr("cssclass"),thumbnail:e.data("thumbnail")},a="[embed "+i(n)+"]"+e.data("url")+"[/embed]"
e.replaceWith(a)}),n.find("img").each(function(){var e=t(this),n={src:e.attr("src"),id:e.data("id"),width:e.attr("width"),height:e.attr("height"),"class":e.attr("class"),title:e.attr("title"),alt:e.attr("alt")
},a="[image "+i(n)+"]"
e.replaceWith(a)}),e.content="",n.each(function(){void 0!==this.outerHTML&&(e.content+=this.outerHTML)})}),e.on("BeforeSetContent",function(e){for(var n,i=e.content,a=function d(t){return t.match(/([^\s\/'"=,]+)\s*=\s*(('([^']+)')|("([^"]+)")|([^\s,\]]+))/g).reduce(function(t,e){
var n=e.match(/^([^\s\/'"=,]+)\s*=\s*(?:(?:'([^']+)')|(?:"([^"]+)")|(?:[^\s,\]]+))$/),i=n[1],a=n[2]||n[3]||n[4]
return t[i]=a,t},{})},s=/\[embed(.*?)\](.+?)\[\/\s*embed\s*\]/gi;n=s.exec(i);){var c=a(n[1]),r
r=t("<img/>").attr({src:c.thumbnail,width:c.width,height:c.height,"class":c["class"],"data-url":n[2]}).addClass("ss-htmleditorfield-file embed"),c.cssclass=c["class"],Object.keys(c).forEach(function(t){
return r.attr("data-"+t,c[t])}),i=i.replace(n[0],t("<div/>").append(r).html())}for(var s=/\[image(.*?)\]/gi;n=s.exec(i);){var c=a(n[1]),r=t("<img/>").attr({src:c.src,width:c.width,height:c.height,"class":c["class"],
alt:c.alt,title:c.title,"data-id":c.id})
i=i.replace(n[0],t("<div/>").append(r).html())}e.content=i})}}
tinymce.PluginManager.add("ssbuttons",function(t){e.init(t)})}()}).call(e,n(1))}])

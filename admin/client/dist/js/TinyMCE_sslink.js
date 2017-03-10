webpackJsonp([2],[function(n,i){"use strict"
!function(){var n={init:function n(i){i.addButton("sslink",{icon:"link",title:"Insert Link",cmd:"sslink"}),i.addMenuItem("sslink",{icon:"link",text:"Insert Link",cmd:"sslink"}),i.addCommand("sslink",function(){
window.jQuery("#"+i.id).entwine("ss").openLinkDialog()}),i.on("BeforeExecCommand",function(n){var e=n.command,t=n.ui,s=n.value
"mceAdvLink"!==e&&"mceLink"!==e||(n.preventDefault(),i.execCommand("sslink",t,s))})}}
tinymce.PluginManager.add("sslink",function(i){return n.init(i)})}()}])

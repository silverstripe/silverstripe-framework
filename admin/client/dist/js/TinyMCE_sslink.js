webpackJsonp([2],[function(n,i){"use strict"
!function(){var n={init:function i(n){n.addButton("sslink",{icon:"link",title:"Insert Link",cmd:"sslink"}),n.addMenuItem("sslink",{icon:"link",text:"Insert Link",cmd:"sslink"}),n.addCommand("sslink",function(){
window.jQuery("#"+n.id).entwine("ss").openLinkDialog()}),n.on("BeforeExecCommand",function(i){var e=i.command,t=i.ui,s=i.value
"mceAdvLink"!==e&&"mceLink"!==e||(i.preventDefault(),n.execCommand("sslink",t,s))})}}
tinymce.PluginManager.add("sslink",function(i){return n.init(i)})}()}])

webpackJsonp([2],[function(n,i){"use strict"
!function(){var n={getInfo:function i(){return{longname:"Insert link for SilverStripe CMS",author:"Sam Minnée",authorurl:"http://www.siverstripe.com/",infourl:"http://www.silverstripe.com/",version:"1.1"
}},init:function e(n){n.addButton("sslink",{icon:"link",title:"Insert Link",cmd:"sslink"}),n.addMenuItem("sslink",{icon:"link",text:"Insert Link",cmd:"sslink"}),n.addCommand("sslink",function(){window.jQuery("#"+n.id).entwine("ss").openLinkDialog()

}),n.on("BeforeExecCommand",function(i){var e=i.command,t=i.ui,o=i.value
"mceAdvLink"!==e&&"mceLink"!==e||(i.preventDefault(),n.execCommand("sslink",t,o))})}}
tinymce.PluginManager.add("sslink",function(i){return n.init(i)})}()}])

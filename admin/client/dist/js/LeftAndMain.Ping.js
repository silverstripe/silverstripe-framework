webpackJsonp([0],[function(n,e,t){"use strict"
function i(n){return n&&n.__esModule?n:{default:n}}var s=t(1),a=i(s)
a.default.entwine("ss.ping",function(n){n(".cms-container").entwine({PingIntervalSeconds:300,onadd:function n(){this._setupPinging(),this._super()},_setupPinging:function e(){var t=function n(e,t){(e.status>400||0==e.responseText)&&(window.open("Security/login")?alert("Please log in and then try again"):alert("Please enable pop-ups for this site"))

}
setInterval(function(){n.ajax({url:"Security/ping",global:!1,type:"POST",complete:t})},1e3*this.getPingIntervalSeconds())}})})}])

/* Greybox Redux
 * Required: http://jquery.com/
 * Written by: John Resig
 * Reworked by: Benjamin Yu (http://foofiles.com/, http://badpopcorn.com/)
 * Based on code by: 4mir Salihefendic (http://amix.dk)
 * License: LGPL (read more in LGPL.txt)
 *
 * @name Greybox
 * @cat Plugins/Greybox
 */

/*
  Defaults, all configurable via options:
  {
    close_img: "close.gif",
    height: 400,
    width: 400,
    animation: false,
    overlay_clickable: true,
    overflow: "auto",
    callback: null,
    caption: ""
  }
*/
(function() {
  var settings = {};

  GB_getPageScrollTop = function(){
    var yScrolltop;
    var xScrollleft;
    if (self.pageYOffset || self.pageXOffset) {
      yScrolltop = self.pageYOffset;
      xScrollleft = self.pageXOffset;
    } else if(document.documentElement&& document.documentElement.scrollTop
      || document.documentElement.scrollLeft ){   // Explorer 6 Strict
      yScrolltop = document.documentElement.scrollTop;
      xScrollleft = document.documentElement.scrollLeft;
    } else if (document.body) {// all other Explorers
      yScrolltop = document.body.scrollTop;
      xScrollleft = document.body.scrollLeft;
    }
    arrayPageScroll = new Array(xScrollleft,yScrolltop);
    return arrayPageScroll;
  }

  GB_overlay_size = function() {
    try {
      if(window.innerHeight && window.scrollMaxY
        || window.innerWidth && window.scrollMaxX) {  
        h = window.innerHeight + window.scrollMaxY;
        w = window.innerWidth + window.scrollMaxX;
        var deff = document.documentElement;
        var wff = (deff&&deff.clientWidth)
          || document.body.clientWidth || window.innerWidth || self.innerWidth;
        var hff = (deff&&deff.clientHeight) || document.body.clientHeight
          || window.innerHeight || self.innerHeight;
        w -= (window.innerWidth - wff);
        h -= (window.innerHeight - hff);
      } else if(document.body.scrollHeight > document.body.offsetHeight
        || document.body.scrollWidth > document.body.offsetWidth) {
          // all but Explorer Mac
        h = document.body.scrollHeight;
        w = document.body.scrollWidth;
      } else {
        // Explorer Mac.would also work in Explorer 6 Strict, Mozilla and Safari
        var left_top = GB_getPageScrollTop();
        h = left_top[1] + settings.height;
        w = left_top[0] + settings.width;
        if( h < document.body.offsetHeight) {
          h = document.body.offsetHeight;
        }
        if( w < document.body.offsetWidth) {
          w = document.body.offsetWidth;
        }
      }
    } catch(err) {
      w = jQuery(document.body).width();
      h = jQuery(document.body).height();
    }
    jQuery("#GB_overlay").css({"height":h+"px", "width":w +"px"});
  }

  GB_position = function() {
    var boxWidth = settings.width;
    var boxHeight = settings.height;
    jQuery('#GB_window').css({
      marginLeft: '-' + parseInt(boxWidth / 2) + 'px',
      width: boxWidth + 'px',
      height: settings.height+"px"
      });
    if( !(jQuery.browser.msie && typeof(XMLHttpRequest) == 'function')) {
      // take away IE6
      jQuery('#GB_window').css({marginTop: '-' + parseInt(boxHeight / 2)+'px'});
    }
    jQuery("#GB_frame").css("height",settings.height - 32 +"px");
  }

  jQuery.GB_hide = function() {
    jQuery("#GB_window,#GB_overlay").remove();
    if(settings.callback && typeof(settings.callback) == 'function') {
      settings.callback.apply();
    }
  }

  jQuery.GB_show = function(url, options) {
    settings = jQuery.extend({
      close_img: "close.gif",
      height: 400,
      width: 400,
      animation: false,
      overlay_clickable: true,
      overflow: "auto",
      callback: null,
      caption: ""
      }, options || {});

    jQuery(document.body)
      .append(
        "<div id='GB_overlay'></div>" +
        "<div id='GB_window'><div id='GB_caption'></div>" +
        "<img src='" + settings.close_img + "' alt='Close window'/></div>");
    jQuery("#GB_window img").click(jQuery.GB_hide);
    if(settings.overlay_clickable) {
      jQuery("#GB_overlay").click(jQuery.GB_hide);
    }

    jQuery("#GB_window")
      .append("<iframe id='GB_frame' src='"+url+"'></iframe>");
    jQuery("#GB_frame").overflow(settings.overflow);

    jQuery("#GB_caption").html(settings.caption);

    GB_overlay_size();
    jQuery(window).resize(GB_overlay_size);
    jQuery(window).scroll(GB_overlay_size);
    jQuery("#GB_overlay").show();

    GB_position();

    if(settings.animation) {
      jQuery("#GB_window").slideDown("slow");
    } else {
      jQuery("#GB_window").show();
    }
  }

})();

/****
 Last Modified: 25/08/06 20:52:59
  
 CAUTION: Modified Version to suit Silverstripe CMS (silverstripe.com).
   Original at http://orangoo.com/labs/uploads/GreyBox_v3_46.zip

 GreyBox - Smart pop-up window
   Copyright Amir Salihefendic 2006
 AUTHOR
   4mir Salihefendic (http://amix.dk) - amix@amix.dk
 VERSION
	 3.46
 LICENSE
  GPL (read more in GPL.txt)
 SITE
  http://orangoo.com/labs/GreyBox/
****/
var GB_CURRENT = null;
var GB_ONLY_ONE = null;
// modified 2006-01-06 by Silverstripe Ltd.
try {
	var theBaseHref = document.getElementsByTagName("base")[0].href;
	var GB_IMG_DIR = theBaseHref + "framework/thirdparty/greybox/"; 
} catch(err) {
	var GB_IMG_DIR = "framework/thirdparty/greybox/"; 
}

function GreyBox() {
  //Use mutator functions (since the internal stuff may change in the future)
  this.type = "page";
  this.overlay_click_close = true;

  if(GB_IMG_DIR)
    this.img_dir = GB_IMG_DIR;
  else
    this.img_dir = "greybox/";

  this.overlay_color = "dark";

  this.center_window = false;

  this.g_window = null;
  this.g_container = null;
  this.iframe = null;
  this.overlay = null;
  this.timeout = null;

  this.defaultSize();
  this.showCloseImage(true);

  this.url = "";
  this.caption = "";

  this.callback_fn = [];
  this.reload_on_close = false;
}

////
// Configuration functions (the functions you can call)
//
/**
  Set the width and height of the GreyBox window.
  Images and notifications are auto-set.
  **/
GreyBox.prototype.setDimension = function(width, height) {
  this.height = height;
  this.width = width;
}

GreyBox.prototype.setFullScreen = function(bool) {
  this.full_screen = bool;
}

/**
  Type can be: page, image
  **/
GreyBox.prototype.setType = function(type) {
  this.type = type;
}

/**
  If bool is true the window will be centered vertically also
  **/
GreyBox.prototype.setCenterWindow = function(bool) {
  this.center_window = bool;
}

/**
  Set the path where images can be found.
  Can be relative: greybox/
  Or absolute: http://yoursite.com/greybox/
  **/
GreyBox.prototype.setImageDir = function(dir) {
  this.img_dir = dir;
}

GreyBox.prototype.showCloseImage = function(bool) {
  this.show_close_img = bool;
}

/**
  If bool is true the grey overlay click will close greybox.
  **/
GreyBox.prototype.setOverlayCloseClick = function(bool) {
  this.overlay_click_close = bool;
}

/**
  Overlay can either be "light" or "dark".
  **/
GreyBox.prototype.setOverlayColor = function(color) {
  this.overlay_color = color;
}

/**
  Set a function that will be called when GreyBox closes
  **/
GreyBox.prototype.setCallback = function(fn) {
  if(fn)
    this.callback_fn.push(fn);
}


////
// Show hide functions
//
/**
  Show the GreyBox with a caption and an url
  **/
GreyBox.prototype.show = function(caption, url) {
  GB_CURRENT = this;

  this.url = url;
  this.caption = caption;

  //Be sure that the old loader and dummy_holder are removed
  AJS.map(AJS.$bytc("div", "GB_dummy"), function(elm) { AJS.removeElement(elm) });
  AJS.map(AJS.$bytc("div", "GB_loader"), function(elm) { AJS.removeElement(elm) });
  
  //If ie, hide select, in others hide flash
  if(AJS.isIe())
    AJS.map(AJS.$bytc("select"), function(elm) {elm.style.visibility = "hidden"});
  AJS.map(AJS.$bytc("object"), function(elm) {elm.style.visibility = "hidden"});

  this.initOverlayIfNeeded();
  
  this.setOverlayDimension();
  AJS.showElement(this.overlay);
  this.setFullScreenOption();

  this.initIfNeeded();

  AJS.hideElement(this.g_window);

  AJS.ACN(this.g_container, this.iframe);

  if(caption == "")
    caption = "&nbsp;";
  this.div_caption.innerHTML = caption;

  AJS.showElement(this.g_window)

  this.setVerticalPosition();
  this.setWidthNHeight();
  this.setTopNLeft();

  GB_CURRENT.startLoading();

  return false;
}

GreyBox.prototype.hide = function() {
  AJS.hideElement(this.g_window, this.overlay);

  try{ AJS.removeElement(this.iframe); }
  catch(e) {}

  this.iframe = null;

  if(this.type == "image") {
    this.width = 200;
    this.height = 200;
  }

  if(AJS.isIe()) 
    AJS.map(AJS.$bytc("select"), function(elm) {elm.style.visibility = "visible"});
  AJS.map(AJS.$bytc("object"), function(elm) {elm.style.visibility = "visible"});

  var c_bs = GB_CURRENT.callback_fn;
  if(c_bs != []) {
    AJS.map(c_bs, function(fn) { 
      fn();
    });
  }

  GB_CURRENT = null;

  if(this.reload_on_close)
    window.location.reload();
}

/** 
  If you only use one instance of GreyBox
  **/
GB_initOneIfNeeded = function() {
  if(!GB_ONLY_ONE) {
    GB_ONLY_ONE = new GreyBox();
  }
}

GB_show = function(caption, url, /* optional */ height, width, callback_fn) {
  GB_initOneIfNeeded();
  GB_ONLY_ONE.defaultSize();
  GB_ONLY_ONE.setFullScreen(false);
  GB_ONLY_ONE.setType("page");
  GB_ONLY_ONE.setCallback(callback_fn);
  GB_ONLY_ONE.setDimension(width, height);
  GB_ONLY_ONE.show(caption, url);
  return false;
}

GB_showFullScreen = function(caption, url, /* optional */ callback_fn) {
  GB_initOneIfNeeded();
  GB_ONLY_ONE.defaultSize();
  GB_ONLY_ONE.setType("page");

  GB_ONLY_ONE.setCallback(callback_fn);
  GB_ONLY_ONE.setFullScreen(true);
  GB_ONLY_ONE.show(caption, url);
  return false;
}

GB_showImage = function(caption, url) {
  GB_initOneIfNeeded();
  GB_ONLY_ONE.defaultSize();
  GB_ONLY_ONE.setFullScreen(false);
  GB_ONLY_ONE.setType("image");

  GB_ONLY_ONE.show(caption, url);
  return false;
}

GB_hide = function() {
  GB_CURRENT.hide();
}

/**
  Preload all the images used by GreyBox. Static function
  **/
GreyBox.preloadGreyBoxImages = function(img_dir) {
  var pics = [];

  if(!img_dir)
    img_dir = GB_IMG_DIR;

  var fn = function(path) { 
    var pic = new Image();
    pic.src = GB_IMG_DIR + path;
    pics.push(pic);
  };
  AJS.map(['indicator.gif', 'blank.gif', 'close.gif', 'header_bg.gif', 'overlay_light.png', 'overlay_dark.png'], AJS.$b(fn, this));
}


////
// Internal functions
//
GreyBox.prototype.getOverlayImage = function() {
  return "overlay_" + this.overlay_color + ".png";
};

/**
  Init functions
  **/
GreyBox.prototype.initOverlayIfNeeded = function() {
  //Create the overlay
  this.overlay = AJS.DIV({'id': 'GB_overlay'});
  if(AJS.isIe()) {
    this.overlay.style.backgroundColor = "#000000";
    this.overlay.style.backgroundColor = "transparent";
    this.overlay.style.backgroundImage = "url("+ this.img_dir +"blank.gif)";
    this.overlay.runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + this.img_dir + this.getOverlayImage() + "',sizingMethod='scale')";
  }
  else 
    this.overlay.style.backgroundImage = "url("+ this.img_dir + this.getOverlayImage() +")";

  if(this.overlay_click_close)
    AJS.AEV(this.overlay, "click", GB_hide);

  AJS.getBody().insertBefore(this.overlay, AJS.getBody().firstChild);
};

GreyBox.prototype.initIfNeeded = function() {
  this.init();
  this.setWidthNHeight = AJS.$b(this.setWidthNHeight, this);
  this.setTopNLeft = AJS.$b(this.setTopNLeft, this);
  this.setFullScreenOption = AJS.$b(this.setFullScreenOption, this);
  this.setOverlayDimension = AJS.$b(this.setOverlayDimension, this);

  GreyBox.addOnWinResize(this.setWidthNHeight, this.setTopNLeft, this.setFullScreenOption, this.setOverlayDimension);

  this.g_container.style.marginBottom = "-3px";

  var fn = function() { 
    this.setOverlayDimension();
    this.setVerticalPosition(); 
    this.setTopNLeft();
    this.setWidthNHeight(); 
  };
  AJS.AEV(window, "scroll", AJS.$b(fn, this));

  if(!this.iframe) {
    var new_frame;
    var d = {'name': 'GB_frame', 'class': 'GB_frame', 'frameBorder': 0};
    new_frame = AJS.IFRAME(d);
    this.iframe = new_frame;
    AJS.hideElement(this.iframe);
  }
}

GreyBox.prototype.init = function() {
  //Create the window
  this.g_window = AJS.DIV({'id': 'GB_window'});

  //Create the table structure
  var table = AJS.TABLE({'class': 'GB_t_frame', 'frameborder': 0});
  var tbody = AJS.TBODY();
  AJS.ACN(table, tbody);

  //Midlle
  var td_middle_m = AJS.TD({'class': 'GB_content'});
  this.td_middle_m = td_middle_m;

  AJS.ACN(tbody, AJS.TR(td_middle_m));

  //Append caption and close
  var header = AJS.TABLE({'class': 'GB_header'});
  this.header = header;

  var caption = AJS.TD({'class': 'GB_caption'});
  this.div_caption = caption;

  /*header.style.backgroundImage = "url("+ this.img_dir +"header_bg.gif)";*/

  tbody_header = AJS.TBODY();
  var close = AJS.TD({'class': 'GB_close'});

  if(this.show_close_img) {
    var img_close = AJS.IMG({'src': this.img_dir + 'close.gif'});
    AJS.ACN(close, img_close, "Close");
    AJS.AEV(close, "click", GB_hide);
  }
  AJS.ACN(tbody_header, AJS.TR(caption, close));

  AJS.ACN(header, tbody_header);

  AJS.ACN(td_middle_m, header);

  //Container
  this.g_container = AJS.DIV({'class': 'GB_container'});
  AJS.ACN(td_middle_m, this.g_container);

  AJS.ACN(this.g_window, table);

  AJS.getBody().insertBefore(this.g_window, this.overlay.nextSibling);
}

GreyBox.prototype.startLoading = function() {
  //Start preloading the object
  this.iframe.src = this.img_dir + 'loader_frame.html';
  AJS.showElement(this.iframe);
}

/**
  Set dimension functions
  **/
GreyBox.prototype.setIframeWidthNHeight = function() {
  try{
    AJS.setWidth(this.iframe, this.width);
    AJS.setHeight(this.iframe, this.height);
  }
  catch(e) {
  }
}

GreyBox.prototype.setOverlayDimension = function() {
  var page_size = AJS.getWindowSize();
  if((navigator.userAgent.toLowerCase().indexOf("firefox") != -1))
   AJS.setWidth(this.overlay, "100%");
  else
   AJS.setWidth(this.overlay, page_size.w);

  var max_height = Math.max(AJS.getScrollTop()+page_size.h, AJS.getScrollTop()+this.height);
  if(max_height < AJS.getScrollTop())
    AJS.setHeight(this.overlay, max_height);
  else
    AJS.setHeight(this.overlay, AJS.getScrollTop()+page_size.h);
}

GreyBox.prototype.setWidthNHeight = function() {
  //Set size
  AJS.setWidth(this.g_window, this.width);
  AJS.setHeight(this.g_window, this.height);

  AJS.setWidth(this.g_container, this.width);
  AJS.setHeight(this.g_container, this.height);

  this.setIframeWidthNHeight();

  //Set size on components
  AJS.setWidth(this.td_middle_m, this.width+10);
}

/**
 * Modified 2006-10-08 by Silverstripe
 */
GreyBox.prototype.setTopNLeft = function() {
	var page_size = AJS.getWindowSize();
	AJS.setLeft(this.g_window, ((page_size.w - this.width)/2)-13);
	
	var fl = ((page_size.h - this.height) /2) - 15 + AJS.getScrollTop();
	AJS.setTop(this.g_window, fl);
}

GreyBox.prototype.setVerticalPosition = function() {
  var page_size = AJS.getWindowSize();
  var st = AJS.getScrollTop();
  if(this.g_window.offsetWidth <= page_size.h || st <= this.g_window.offsetTop) {
    AJS.setTop(this.g_window, st);
  }
}

GreyBox.prototype.setFullScreenOption = function() {
  if(this.full_screen) {
    var page_size = AJS.getWindowSize();

    overlay_h = page_size.h;

    this.width = Math.round(this.overlay.offsetWidth - (this.overlay.offsetWidth/100)*10);
    this.height = Math.round(overlay_h - (overlay_h/100)*10);
  }
}

GreyBox.prototype.defaultSize = function() {
  this.width = 300;
  this.height = 300;
}

////
// Misc.
//
GreyBox.addOnWinResize = function(funcs) {
  funcs = AJS.$A(funcs);
  AJS.map(funcs, function(fn) { AJS.AEV(window, "resize", fn); });
}

/**
 * Additions and improvements to Prototype-code.
 * Some if this is legacy code which is now present in Prototype as well,
 * but has to be kept for older scripts.
 * 
 * @author Silverstripe Ltd., http://silverstripe.com
 */

// Shortcut-function (until we update to Prototye v1.5)
if(typeof $$ != "Function") {
	$$ = document.getElementsBySelector;
}

var SS_DEFAULT_ISO = "en_GB";

Object.extend(Element, {
  setStyle: function(element, styles, camelized) {
    element = $(element);
    var elementStyle = element.style;

    for (var property in styles)
      if (property == 'opacity') element.setOpacity(styles[property])
      else
        elementStyle[(property == 'float' || property == 'cssFloat') ?
          (elementStyle.styleFloat === undefined ? 'cssFloat' : 'styleFloat') :
          (camelized ? property : property.camelize())] = styles[property];

    return element;
  }
});

// This code is in the public domain. Feel free to link back to http://jan.moesen.nu/
function sprintf()
{
	if (!arguments || arguments.length < 1 || !RegExp)
	{
		return;
	}
	var str = arguments[0];
	var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
	var a = b = [], numSubstitutions = 0, numMatches = 0;
	while (a = re.exec(str))
	{
		var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
		var pPrecision = a[5], pType = a[6], rightPart = a[7];
		
		//alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

		numMatches++;
		if (pType == '%')
		{
			subst = '%';
		}
		else
		{
			numSubstitutions++;
			if (numSubstitutions >= arguments.length)
			{
				//alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
			}
			var param = arguments[numSubstitutions];
			var pad = '';
			       if (pPad && pPad.substr(0,1) == "'") pad = leftpart.substr(1,1);
			  else if (pPad) pad = pPad;
			var justifyRight = true;
			       if (pJustify && pJustify === "-") justifyRight = false;
			var minLength = -1;
			       if (pMinLength) minLength = parseInt(pMinLength);
			var precision = -1;
			       if (pPrecision && pType == 'f') precision = parseInt(pPrecision.substring(1));
			var subst = param;
			       if (pType == 'b') subst = parseInt(param).toString(2);
			  else if (pType == 'c') subst = String.fromCharCode(parseInt(param));
			  else if (pType == 'd') subst = parseInt(param) ? parseInt(param) : 0;
			  else if (pType == 'u') subst = Math.abs(param);
			  else if (pType == 'f') subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision): parseFloat(param);
			  else if (pType == 'o') subst = parseInt(param).toString(8);
			  else if (pType == 's') subst = param;
			  else if (pType == 'x') subst = ('' + parseInt(param).toString(16)).toLowerCase();
			  else if (pType == 'X') subst = ('' + parseInt(param).toString(16)).toUpperCase();
		}
		str = leftpart + subst + rightPart;
	}
	return str;
}

/**
 * Use Firebug-like debugging in non-Firefox-browsers
 * @see http://wish.hu/firebug-on-explorer
 */
if (!window.console) {
  window.console = {
    timers: {},
    openwin: function() {
      window.top.debugWindow =
          window.open("",
                      "Debug",
                      "left=0,top=0,width=300,height=700,scrollbars=yes,"
                      +"status=yes,resizable=yes");
      window.top.debugWindow.opener = self;
      window.top.debugWindow.document.open();
      window.top.debugWindow.document.write('<html><head><title>debug window</title></head><body><hr><pre>');
    },

	/**
	 * Caution: Excludes functions in listing
	 */
    log: function() {
		if(Debug.isLive()) return;
	
		if(!window.top.debugWindow) { 
			console.openwin(); 
		}
		
		var i = 0; content = "";
		if(arguments.length == 1 && typeof arguments[0] != "object") {
			content = arguments[0];
		} else if(arguments.length > 1 && typeof arguments[0] == "string"){
			content = sprintf(arguments[0], Array.prototype.slice.call(arguments, 1));
		}
		
		if(window.top.debugWindow.document) { 
			window.top.debugWindow.document.write(content+"\n");
		}
    },
    
    debug: this.log,

    time: function(title) {
      window.console.timers[title] = new Date().getTime();
    },

    timeEnd: function(title) {
      var time = new Date().getTime() - window.console.timers[title];
      console.log(['<strong>', title, '</strong>: ', time, 'ms'].join(''));
    }

  }
}

Number.prototype.CURRENCIES = {
	en_GB: '$ ###,###.##'
};

/**
 * Caution: Not finished!
 * @param iso string (Not used) Please use in combination with Number.CURRENCIES to achieve i18n
 * @return string
 * 
 * @see http://www.jibbering.com/faq/faq_notes/type_convert.html
 * @see http://www.rgagnon.com/jsdetails/js-0063.html
 * @see http://www.mredkj.com/javascript/nfdocs.html 
 */
Number.prototype.toCurrency = function(iso) {
	if(!iso) iso = SS_DEFAULT_ISO;
	// TODO stub, please implement properly
	return "$" + this.toFixed(2);
}

/**
 * Get first letter as uppercase
 */
String.prototype.ucfirst = function () {
   var firstLetter = this.substr(0,1).toUpperCase()
   return this.substr(0,1).toUpperCase() + this.substr(1,this.length);
}

/**
 * Show debug-information in the javascript-console or a popup.
 * Only shows output on dev- or test-environments.
 * Caution: Behaves like a static class (no prototype methods)
 */
Debug = Class.create();
Debug = {
	
	environment_type: "live",
	
	initialize: function() {
		if(window.location.href.match(/\?(.*)debug_javascript/)) {
			this.environment_type = "dev";
		}
		if(window.location.href.match(/\?(.*)debug_behaviour/)) {
			Behaviour.debug();
		}
	},
	
	/**
	 * @param type string "live", "test" or "dev"
	 */
	set_environment_type: function(type) {
		this.environment_type = type;
	},
	
	isDev: function() {
		return (window.location.href.match(/test\.|dev\./) || this.environment_type == "dev" || this.environment_type == "test");
	},
	
	isTest: function() {
		return (window.location.href.match(/test\./) || this.environment_type == "test");
	},

	isLive: function() {
		return !Debug.isDev();
	},

	show: function() {
		if(this.isDev() || this.isTest()) {
			console.debug.apply(console, arguments);
		}
	},

	debug: this.debug,

	log: function() {
		if(this.isDev() || this.isTest()) {
			console.log.apply(console, arguments);
		}
	}
}
Debug.initialize();

// Flash plugin version detection from SWFObject
// http://blog.deconcept.com/swfobject/
getFlashPlayerVersion = function(){
	var pv = new PlayerVersion([0,0,0]);
	if(navigator.plugins && navigator.mimeTypes.length){
		var x = navigator.plugins["Shockwave Flash"];
		if(x && x.description) {
			pv = new PlayerVersion(x.description.replace(/([a-zA-Z]|\s)+/, "").replace(/(\s+r|\s+b[0-9]+)/, ".").split("."));
		}
	}else if (navigator.userAgent && navigator.userAgent.indexOf("Windows CE") >= 0){ // if Windows CE
		var axo = 1;
		var counter = 3;
		while(axo) {
			try {
				counter++;
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash."+ counter);
//				document.write("player v: "+ counter);
				pv = new PlayerVersion([counter,0,0]);
			} catch (e) {
				axo = null;
			}
		}
	} else { // Win IE (non mobile)
		// do minor version lookup in IE, but avoid fp6 crashing issues
		// see http://blog.deconcept.com/2006/01/11/getvariable-setvariable-crash-internet-explorer-flash-6/
		try{
			var axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
		}catch(e){
			try {
				var axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
				pv = new PlayerVersion([6,0,21]);
				axo.AllowScriptAccess = "always"; // error if player version < 6.0.47 (thanks to Michael Williams @ Adobe for this code)
			} catch(e) {
				if (pv.major == 6) {
					return pv;
				}
			}
			try {
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
			} catch(e) {}
		}
		if (axo != null) {
			pv = new PlayerVersion(axo.GetVariable("$version").split(" ")[1].split(","));
		}
	}
	return pv;
}

PlayerVersion = function(arrVersion) {
	this.major = arrVersion[0] != null ? parseInt(arrVersion[0]) : 0;
	this.minor = arrVersion[1] != null ? parseInt(arrVersion[1]) : 0;
	this.rev = arrVersion[2] != null ? parseInt(arrVersion[2]) : 0;
}



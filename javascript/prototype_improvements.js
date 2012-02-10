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
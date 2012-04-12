tinyMCEPopup.requireLangPack();

var charmap = [
	['&#256;',    '&#256;',  true, 'A - macron'],
	['&#274;',    '&#274;',  true, 'E - macron'],
	['&#298;',    '&#298;',  true, 'I - macron'], 
	['&#332;',    '&#332;',  true, 'O - macron'], 
	['&#362;',    '&#362;',  true, 'U - macron'], 
	['&#257;',    '&#257;',  true, 'a - macron'], 
	['&#275;',    '&#275;',  true, 'e - macron'], 
	['&#299;',    '&#299;',  true, 'i - macron'], 
	['&#333;',    '&#333;',  true, 'o - macron'], 
	['&#363;',    '&#363;',  true, 'u - macron']
];

tinyMCEPopup.onInit.add(function() {
	tinyMCEPopup.dom.setHTML('charmapView', renderCharMapHTML());
});

function renderCharMapHTML() {
	var charsPerRow = 5, tdWidth=20, tdHeight=20, i;
	var html = '<table border="0" cellspacing="1" cellpadding="0" width="' + (tdWidth*charsPerRow) + '"><tr height="' + tdHeight + '">';
	var cols=-1;

	for (i=0; i<charmap.length; i++) {
		if (charmap[i][2]==true) {
			cols++;
			html += ''
				+ '<td class="charmap">'
				+ '<a onmouseover="previewChar(\'' + charmap[i][1].substring(1,charmap[i][1].length) + '\',\'' + charmap[i][0].substring(1,charmap[i][0].length) + '\',\'' + charmap[i][3] + '\');" onfocus="previewChar(\'' + charmap[i][1].substring(1,charmap[i][1].length) + '\',\'' + charmap[i][0].substring(1,charmap[i][0].length) + '\',\'' + charmap[i][3] + '\');" href="javascript:void(0)" onclick="insertChar(\'' + charmap[i][1].substring(2,charmap[i][1].length-1) + '\');" onclick="return false;" onmousedown="return false;" title="' + charmap[i][3] + '">'
				+ charmap[i][1]
				+ '</a></td>';
			if ((cols+1) % charsPerRow == 0)
				html += '</tr><tr height="' + tdHeight + '">';
		}
	 }

	if (cols % charsPerRow > 0) {
		var padd = charsPerRow - (cols % charsPerRow);
		for (var i=0; i<padd-1; i++)
			html += '<td width="' + tdWidth + '" height="' + tdHeight + '" class="charmap">&nbsp;</td>';
	}

	html += '</tr></table>';

	return html;
}

function insertChar(chr) {
	tinyMCEPopup.execCommand('mceInsertContent', false, '&#' + chr + ';');

	// Refocus in window
	if (tinyMCEPopup.isWindow)
		window.focus();

	tinyMCEPopup.editor.focus();
	tinyMCEPopup.close();
}

function previewChar(codeA, codeB, codeN) {
	var elmV = document.getElementById('codeV');
	var elmN = document.getElementById('codeN');

	if (codeA=='#160;') {
		elmV.innerHTML = '__';
	} else {
		elmV.innerHTML = '&' + codeA;
	}

	elmN.innerHTML = codeN;
}

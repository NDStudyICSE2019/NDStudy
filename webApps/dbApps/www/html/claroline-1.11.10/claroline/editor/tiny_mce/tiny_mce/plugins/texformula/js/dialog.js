tinyMCEPopup.requireLangPack();

function URLEncode( plaintext )
{
	// The Javascript escape and unescape functions do not correspond
	// with what browsers actually do...
	var SAFECHARS = "0123456789" +					// Numeric
					"ABCDEFGHIJKLMNOPQRSTUVWXYZ" +	// Alphabetic
					"abcdefghijklmnopqrstuvwxyz" +
					"-_.!~*'()";					// RFC2396 Mark characters
	var HEX = "0123456789ABCDEF";

	var encoded = "";
	for (var i = 0; i < plaintext.length; i++ ) {
		var ch = plaintext.charAt(i);
	    if (ch == " ") {
		    encoded += "+";				// x-www-urlencoded, rather than %20
		} else if (SAFECHARS.indexOf(ch) != -1) {
		    encoded += ch;
		} else {
		    var charCode = ch.charCodeAt(0);
			if (charCode > 255) {
			    alert( "Unicode Character '" 
                        + ch 
                        + "' cannot be encoded using standard URL encoding.\n" +
				          "(URL encoding only supports 8-bit characters.)\n" +
						  "A space (+) will be substituted." );
				encoded += "+";
			} else {
				encoded += "%";
				encoded += HEX.charAt((charCode >> 4) & 0xF);
				encoded += HEX.charAt(charCode & 0xF);
			}
		}
	} // for
	
	return encoded;
}

var TexFormulaDialog = {
	init : function() {
		tinyMCEPopup.resizeToInnerSize();
	   
		// get url value of the selected object
		var ed = tinyMCEPopup.editor;
		var fe = ed.selection.getNode();
		var formula = '';
		
		if(ed.dom.getAttrib(fe, 'class') == 'latexFormula' && ed.dom.getAttrib(fe, 'src') )
		{
			src = ed.dom.getAttrib(fe, 'src');
			pos = src.indexOf('.cgi', 1);
			formula = src.substr(pos + 5, src.length);
		}
		
		var f = document.forms[0];

		// Set the selected contents as text and place it in the input
		$("#formula").children().remove();
		$("#formula").append(formula);				
		
	},

	insert : function() {
		
		var formula = document.forms[0].formula.value;
		
		//var code = "[tex]" + formula + "[/tex]";
		var code = '<img src="' + texRendererURL +'?' + document.forms[0].formula.value + '" border="0" align="absmiddle" class="latexFormula" />';
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, code);
		tinyMCEPopup.close();
	},
	
	preview : function() {
		//var code = '<img src="' + texRendererURL +'?' + document.forms[0].formula.value + '" border="0" align="absmiddle" class="latexFormula" />';
		$("#preview").children().remove();
		var formulaVal = URLEncode( $('#formula').val())
		$.ajax({
			type: "POST",
			url: "dialog.php",
			data: "cmd=rqTex&formula=" + formulaVal,
			success : function(response){
				$("#preview").append(response);   
			},
			dataType: 'html'
		      });		
	}
};

tinyMCEPopup.onInit.add(TexFormulaDialog.init, TexFormulaDialog);

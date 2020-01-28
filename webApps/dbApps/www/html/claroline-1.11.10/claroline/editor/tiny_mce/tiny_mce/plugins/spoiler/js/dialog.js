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

var SpoilerDialog = {
	init : function() {
		
		
	},

	insert : function() {
		
		var title = $("#title").val();
		var content = $("#content").val();
		if(title.length || content.length)
		{
			var code = '<p>[spoiler /' + title + '/]</p>' + content + '<p>[/spoiler]</p>';
			// Insert the contents from the input into the document
			tinyMCEPopup.editor.execCommand('mceInsertContent', false, code);
			tinyMCEPopup.close();
		}
		
	},
	
	preview : function() {
		$("#preview").children().remove();
		
		var title = $("#title").val();
		var content = $("#content").val();
		
		$.ajax({
			type: "POST",
			url: "dialog.php",
			data: "cmd=rqSpoiler&title=" + title + "&content=" + content,
			success : function(response){
				$("#preview").append(response);   
			},
			dataType: 'html'
		      });	
	}
};

tinyMCEPopup.onInit.add(SpoilerDialog.init, SpoilerDialog);

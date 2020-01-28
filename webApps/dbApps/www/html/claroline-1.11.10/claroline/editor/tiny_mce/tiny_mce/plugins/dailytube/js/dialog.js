tinyMCEPopup.requireLangPack();

var DailyTubeDialog = {
	init : function() {
        tinyMCEPopup.resizeToInnerSize();
	   
	    // get url value of the selected object
        var ed = tinyMCEPopup.editor;
        var fe = ed.selection.getNode();
        var url = '';

        if (/mceItemFlash/.test(ed.dom.getAttrib(fe, 'class'))) {
            var title = fe.title;
            if (title != "") {
                title = tinymce.util.JSON.parse('{' + title + '}');
                var src = title['src'];

                // Youtube
                if ( src.match(/http:\/\/www.youtube.com\/v\/(.+)(.*)/) ) {
                    var code = src.match(/http:\/\/www.youtube.com\/v\/(.+)(.*)/)[1];
                    url = 'http://www.youtube.com/watch?v=' + code ;
                // Dailymotion
                } else if ( src.match(/http:\/\/www.dailymotion.com\/swf\/(.+)(.*)/) ) {
                    var code = src.match(/http:\/\/www.dailymotion.com\/swf\/(.+)(.*)/)[1];
                    url = 'http://www.dailymotion.com/video/' + code;
                } 
                document.forms[0].dailytubeURL.value = url;
            }
        }
        
        if ((val = ed.dom.getAttrib(fe, "width")) != "")
        {
            oldWidth = val;
	          
	        if( oldWidth < 300 )
	        {
	            setCheckedValue(document.forms[0].dailytubeSize, 'small');
	        }
	        else if ( oldWidth > 500 )
	        {
	            setCheckedValue(document.forms[0].dailytubeSize, 'large');
	        }
        }
	},

	insert : function() {
	   
	    var videoURL = document.forms[0].dailytubeURL.value;
	    
	    if ( videoURL.length == 0 ) return false; 
	    
	    var size = getCheckedValue(document.forms[0].dailytubeSize);    

	    
		if (videoURL.indexOf("youtube.com/watch?") > -1) 
		{
		    // YouTube
		    var medWidth = '425';
            var medHeight = '344';
            
            // example : http://fr.youtube.com/watch?v=w0ffwDYo00Q
            // we need   w0ffwDYo00Q  
            var videoId = videoURL.match(/v=(.*)(.*)/)[0].split('=')[1];

            var embedCode = '<object width="%width%" height="%height%">'
            +    '<param name="movie" value="http://www.youtube.com/v/%id%&hl=fr&fs=1"></param>'
            +    '<param name="allowFullScreen" value="true"></param>'
            +    '<embed src="http://www.youtube.com/v/%id%&hl=fr&fs=1" type="application/x-shockwave-flash" allowfullscreen="true" width="%width%" height="%height%">'
            +    '</embed>'
            +    '</object>';
	    
		}
		else if (videoURL.indexOf("dailymotion.com") > -1) 
		{
		    // DailyMotion
		    var medWidth = '420';
            var medHeight = '301';

            // example : http://www.dailymotion.com/video/x65z2t_simplifying-square-rootsmov_tech
            // we need   x65z2t          
            var videoId = videoURL.match(/video\/(.*)(.*)/)[1].split('_')[0];
            
            var embedCode = '<object width="%width%" height="%height%">'
			+    '<param name="movie" value="http://www.dailymotion.com/swf/%id%&related=0"></param>'
			+    '<param name="allowFullScreen" value="true"></param><param name="allowScriptAccess" value="always"></param>'
			+    '<embed src="http://www.dailymotion.com/swf/%id%&related=0" type="application/x-shockwave-flash" width="%width%" height="%height%" allowFullScreen="true" allowScriptAccess="always">'
			+    '</embed>'
		    +    '</object>';
		    
		}
		else
		{
		    return false;
		}

        if( size == 'small' )
        {
            var width = '220';
            var height = '152';
        }
        else if( size == 'large' )
        {
            var width = '520';
            var height = '368';
        }
        else 
        {
            // medium
            var width = medWidth;
            var height = medHeight;
        }
        
        // set values in default embed code
	    embedCode = embedCode.replace(/%id%/g, videoId);
	    embedCode = embedCode.replace(/%width%/g, width);
	    embedCode = embedCode.replace(/%height%/g, height);
	    
                
        tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
        tinyMCEPopup.close();   
	}
};

tinyMCEPopup.onInit.add(DailyTubeDialog.init, DailyTubeDialog);


/** utils **/

// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function getCheckedValue(radioObj) {
    if(!radioObj)
        return "";
    var radioLength = radioObj.length;
    if(radioLength == undefined)
        if(radioObj.checked)
            return radioObj.value;
        else
            return "";
    for(var i = 0; i < radioLength; i++) {
        if(radioObj[i].checked) {
            return radioObj[i].value;
        }
    }
    return "";
}

// set the radio button with the given value as being checked
// do nothing if there are no radio buttons
// if the given value does not exist, all the radio buttons
// are reset to unchecked
function setCheckedValue(radioObj, newValue) {
    if(!radioObj)
        return;
    var radioLength = radioObj.length;
    if(radioLength == undefined) {
        radioObj.checked = (radioObj.value == newValue.toString());
        return;
    }
    for(var i = 0; i < radioLength; i++) {
        radioObj[i].checked = false;
        if(radioObj[i].value == newValue.toString()) {
            radioObj[i].checked = true;
        }
    }
}
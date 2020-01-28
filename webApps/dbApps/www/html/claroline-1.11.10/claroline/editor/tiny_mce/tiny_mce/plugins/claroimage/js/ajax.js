    $(document).ready( function ()
    {
        // init file list.  After an upload we have to display the correct list so pass it directly 
        setFileList(relativePath);
	    
	    $("#processing").hide();
    });
    
    
    function setFileList(relPath)
    {
        $("#processing").show();
        $.ajax({
            url: "./backend.php",
            data: "cmd=getFileList&relPath=" + relPath,
            success: function(response){
                    if( response != 'undefined' && response != '' )
                    {
                        $("#image_list").empty();
                        $("#image_list").append(response);
                        $("#relativePath").val(relPath);
                        $("#displayedPath #path").html(relPath);
                    }
                    // hide processing icon after receiving ajax response
                    $("#processing").hide();
                },
            error: function(response){
                $("#processing").hide();
            },
            dataType: 'html'
        });

    }
    
    function selectImage(imageUrl)
    {
        // set src 
        $('#src').val(imageUrl);
        // force change event on src field to force claculation of size etc...
        $('#src').trigger('onchange');
        // and force previewing if required
        // normally this shouldn't be needed as the change event on src request a showPreviewImage
        //ImageDialog.showPreviewImage(imageUrl, 1);
    }
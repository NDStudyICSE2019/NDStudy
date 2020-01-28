/*
 * $Id: tooltitle.js 13367 2011-07-26 15:04:54Z abourguignon $
 */

$(document).ready(function(){
    // show/hide tooltitle's commands
    $('.commandList li.hidden').hide();
    
    $('.commandList a.more').click(function() {
        if ($('.commandList a.more').hasClass('clicked'))
        {
            $('.commandList li.hidden').hide();
            $('.commandList a.more').removeClass('clicked').html('&raquo;');
        }
        else
        {
            $('.commandList li.hidden').show();
            $('.commandList a.more').addClass('clicked').html('&laquo;');
        }
    });
});
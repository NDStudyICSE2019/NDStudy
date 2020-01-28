/*
 * $Id: forum.js 13619 2011-09-29 12:43:13Z abourguignon $
 */

$(document).ready(init);

function init()
{
    $(".show").click( showContributor );
    $(".hide").click( hideContributor );
    $(".confirmAnonymous").click( confirmationAnonymous );
}

function showContributor()
{
    $(this).next().show(); 
    $(this).hide();
    $(this.parentNode).next().show();
}

function hideContributor()
{
    $(this).prev().show(); 
    $(this).hide();
    $(this.parentNode).next().hide();
}

function confirmationDel(name)
{
    var arr = {"%name" : name};
    
    if (confirm(Claroline.getLang('Are you sure to delete %name ?', arr)))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function confirmationAnonymous()
{
    if( $("#anonymous_cb").length <= 0 || $("#anonymous_cb").is(":checked") )
    {
        return true;
    }
    else
    {
        if( confirm('Do you really want to sign your contribution ?') )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

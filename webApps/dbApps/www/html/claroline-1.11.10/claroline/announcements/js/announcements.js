/*
 * $Id: announcements.js 13598 2011-09-20 15:44:52Z abourguignon $
 */

var CLANN = {};

CLANN.confirmationDel = function (name)
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

CLANN.confirmationDelAll = function ()
{
    if (confirm(Claroline.getLang('Are you sure you want to delete all the announcements ?')))
    {
        return true;
    }
    else
    {
        return false;
    }
}

$(document).ready(function(){
    $("#visible").click(function(){
        $("#enable_visible_from").attr("disabled", false);
        $("#enable_visible_until").attr("disabled", false);
        $("#visible_from_day").attr("disabled", false);
        $("#visible_from_month").attr("disabled", false);
        $("#visible_from_year").attr("disabled", false);
        $("#visible_until_day").attr("disabled", false);
        $("#visible_until_month").attr("disabled", false);
        $("#visible_until_year").attr("disabled", false);
    });
    
    $("#invisible").click(function(){
        $("#enable_visible_from").attr("disabled", true);
        $("#enable_visible_until").attr("disabled", true);
        $("#visible_from_day").attr("disabled", true);
        $("#visible_from_month").attr("disabled", true);
        $("#visible_from_year").attr("disabled", true);
        $("#visible_until_day").attr("disabled", true);
        $("#visible_until_month").attr("disabled", true);
        $("#visible_until_year").attr("disabled", true);
    });
});
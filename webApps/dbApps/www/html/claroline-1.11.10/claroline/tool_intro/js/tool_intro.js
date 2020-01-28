/*
    $Id: tool_intro.js 13345 2011-07-18 12:38:52Z abourguignon $
 */

var CLTI = {};

CLTI.confirmation = function (name)
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
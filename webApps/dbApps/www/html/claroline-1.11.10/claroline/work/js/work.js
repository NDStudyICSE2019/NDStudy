/*
 * $Id: work.js 13429 2011-08-18 09:15:26Z abourguignon $
 */

var WORK = {};

WORK.confirmationDel = function (name)
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
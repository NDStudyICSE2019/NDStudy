/*
 * $Id: user.js 13466 2011-08-25 14:04:03Z abourguignon $
 */

var CLUSR = {};

CLUSR.confirmation = function (name)
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
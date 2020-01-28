/*
 * $Id: admin.js 14443 2013-05-06 08:11:23Z ldumorti $
 */

var ADMIN = {};

ADMIN.confirmationDel = function (name)
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

ADMIN.confirmationUnReg = function (name)
{
    var arr = {"%name" : name};
    
    if (confirm(Claroline.getLang('Are you sure you want to unregister %name ?', arr)))
    {
        return true;
    }
    else
    {
        return false;
    }
}

ADMIN.confirmationUninstall = function (name)
{
    var arr = {"%name" : name};
    
    if (confirm(Claroline.getLang('Are you sure you want to uninstall the module %name ?', arr)))
    {
        return true;
    }
    else
    {
        return false;
    }
}

ADMIN.confirmationUnRegForAllCourses = function (name)
{
    var arr = {"%name" : name};
    
    if (confirm(Claroline.getLang('Are you sure you want to unregister %name for all courses?', arr)))
    {
        return true;
    }
    else
    {
        return false;
    }
}

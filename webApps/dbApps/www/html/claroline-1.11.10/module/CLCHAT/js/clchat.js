$(document).ready(init);

var msgTimeout;
var logDisplayed = false;

function init()
{
    // attach events on elements
    // form submission
    $("#clchat_form").submit(addMsg);  
    // commands
    $("#clchat_cmd_logs").click(switchDisplayLog);
    $("#clchat_cmd_archive").click(rqArchive);
    $("#clchat_cmd_flush").click(rqFlush);


    // hide some interface elements
    $("#clchat_loading").hide();
    $("#clchat_dialogBox").hide();

    // set event for ajax call start and stop
    $("#clchat_loading").ajaxStart(function(){
        $(this).show();
    });

    $("#clchat_loading").ajaxStop(function(){
        $(this).hide();
    });

    // set interval does not execute function directly but wait for the first interval.
    // so call refresh one time at launch before setting up the interval
    rqRefresh();
    rqRefreshUserList();
    
    setInterval(rqRefresh, refreshRate);
    setInterval(rqRefreshUserList, userListRefresh);

    // give focus to form
    $("#clchat_msg").focus();
}

/* Refresh text */
function exRefresh(response)
{
    $("#clchat_text").empty();
    $("#clchat_text").append(response);

    // only if we can find newlines in updated chatarea
    if( $(".newLine").size() )
    {
        // scroll to bottom of clchat_chatarea
        // do not work with jquery selector
        document.getElementById("clchat_chatarea").scrollTop = document.getElementById("clchat_chatarea").scrollHeight;
        
        // Add a display effect for all lines that are added since last refresh
        $(".newLine").fadeIn("slow");
    }
}

function rqRefresh()
{
    $.ajax({
        url: "ajaxHandler.php?cmd=rqRefresh&cidReq=" + cidReq + getGidReqParam(), 
        ifModified: true, 
        success: function(response){
            exRefresh(response)
            }, 
        dataType: "html"});
}

/* Refresh user list */
function exRefreshUserList(response)
{
    $("#clchat_user_list").empty();
    $("#clchat_user_list").append(response);
}

function rqRefreshUserList()
{
    $.ajax({
        url: "ajaxHandler.php?cmd=rqRefreshUserList&cidReq=" + cidReq + getGidReqParam(), 
        ifModified: true, 
        success: function(response){
            exRefreshUserList(response)
            }, 
        dataType: "html"});
}


/* logs */
function switchDisplayLog()
{
    if(! logDisplayed )
    {
        rqDisplayLogs();
        logDisplayed = true;
    }
    else
    {
        exHideLogs();
        logDisplayed = false;
    }
}

function exDisplayLogs(response)
{
    $("#clchat_log").hide();
    $("#clchat_log").empty();
    $("#clchat_log").append(response);
    $("#clchat_log").show();
}

function rqDisplayLogs()
{
    $.ajax({
        url: "ajaxHandler.php?cmd=rqLogs&cidReq=" + cidReq + getGidReqParam(), 
        success: function(response){
            exDisplayLogs(response);
            }, 
        dataType: 'html'}); 

    
    return false;
}

function exHideLogs()
{
    $("#clchat_log").hide();
    $("#clchat_log").empty();
}

/* dialog box */
function showDialog(msg)
{
    clearTimeout(msgTimeout);
    
    $("#clchat_dialogBox").empty();
    $("#clchat_dialogBox").append(msg);
    $("#clchat_dialogBox").show("slow");

    msgTimeout = setTimeout(hideDialog,refreshRate);
}

function hideDialog()
{
    $("#clchat_dialogBox").hide("slow");
}


/* Submit a text */
function addMsg() 
{ 
    if( $("#clchat_msg").val().length > 0 )
    {
        $.ajax({
            url: "ajaxHandler.php?cmd=rqAdd&cidReq=" + cidReq + getGidReqParam(), 
            data: $("#clchat_msg").serialize(), 
            success: function(response){
                exRefresh(response);
                $("#clchat_msg").val("");
                $("#clchat_msg").focus();
                }, 
            dataType: "html"});
        return false;
    }
    else
    {
        // do nothing
        return false;
    }
}

/* Create html archive file */

function rqArchive()
{
    $.ajax({
        url: "ajaxHandler.php?cmd=rqArchive&cidReq=" + cidReq + getGidReqParam(), 
        success: function(response){
            showDialog(response);
            }, 
        dataType: 'html'}); 

    return false;
}

/* Flush all messages from history */
function rqFlush()
{
    if( confirm(lang["confirmFlush"]) )
    {
        $.ajax({
            url: "ajaxHandler.php?cmd=rqFlush&cidReq=" + cidReq + getGidReqParam(), 
            success: function(response){
                showDialog(response);
                exHideLogs();
                rqRefresh();
                }, 
            dataType: 'html'});
    }
    return false;
}

function getGidReqParam()
{
    if( typeof gidReq != 'undefined' )
    {
        return '&gidReq=' + gidReq;
    }
    else
    {
        return '';
    }
}
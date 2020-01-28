/*
    $Id: admin_users.js 13286 2011-07-04 17:06:07Z abourguignon $
 */

$(document).ready(function() {

    doInitialize();
    // flag to know i
    var isTerminated = false;

    // start timer for session_time
    var d = new Date();
    var startTime = d.getTime(); 
    var completionThreshold = parseFloat(doGetValue("cmi.completion_threshold"));
    var score_raw = parseFloat(doGetValue("cmi.score.raw"));
    
    if(score_raw)
    {
        doSetValue("cmi.score.raw", score_raw);
        $("#progressForm").find("input").removeAttr("checked");
        if(score_raw >= 0 && score_raw < 12.5)
        {
            $("#none").attr("checked","checked");
        }
        else if(score_raw >= 12.5 && score_raw < 37.5)
        {            
            $("#low").attr("checked","checked");
        }
        else if(score_raw >= 37.5 && score_raw < 62.5)
        {
            $("#medium").attr("checked","checked");
        }
        else if(score_raw >= 62.5 && score_raw < 87.5)
        {
            $("#high").attr("checked","checked");
        }
        else if(score_raw >= 87.5 && score_raw <= 100)
        {
            $("#full").attr("checked","checked");
        }
    }
    else
    {
        $("#none").attr("checked","checked");
        doSetValue("cmi.score.raw","0");
    }
          
    doSetValue("cmi.score.min","0");
    doSetValue("cmi.score.max","100");
    doSetValue("cmi.session_time","PT0H0S");
    
    doSetValue("cmi.completion_status","incomplete");

    $(".progressRadio").click( function() {
        if( isTerminated ) return false;
        // score
        var currentProgress = $(this).val();
        doSetValue("cmi.score.raw",currentProgress);
        // completion_status
        if( completionThreshold > 0)
        {
            if( currentProgress >= completionThreshold )
            {    
              doSetValue("cmi.completion_status","completed");
            }
            else
            {
               doSetValue("cmi.completion_status","incomplete");
            }
        }
        else
        {
            if( currentProgress > 50)
            {
                doSetValue("cmi.completion_status","completed");
            }
            else
            {
                doSetValue("cmi.completion_status","incomplete");
            }
        }
      
      // session_time
        var d = new Date();
        var partTime = d.getTime(); 
    
        var time = partTime - startTime; // time in milliseconds
        doSetValue("cmi.session_time", centisecsToISODuration(time/10));
        
        // save
      doCommit();
    });
    
    $("#progressDone").click( function() {
        
        // session_time
        var d = new Date();
        var partTime = d.getTime(); 
    
        var time = partTime - startTime; // time in milliseconds
        doSetValue("cmi.session_time", centisecsToISODuration(time/10));
        
        // save
        doCommit();
        //doTerminate();
        
        isTerminated = true;
        
        return false;
    });
        
});


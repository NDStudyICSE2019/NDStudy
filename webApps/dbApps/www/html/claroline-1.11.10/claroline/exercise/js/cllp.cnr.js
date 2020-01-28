$(document).ready(function() {    

    // there is no mechanism to know if it is already initialized or not
    // so try to initialize then check if it have generate an error
    doInitialize();
    
    // this has an example purpose at the moment.
    if( doGetLastError() == "103" ) // already initialized
    {   
        // not first connection
    }
    else
    {
        // first connection
    }
    
});

<?php 
/**  * DONT EDIT THIS FILE - NE MODIFIEZ PAS CE FICHIER 
 * -------------------------------------------------
 * Generated by 
		
		/var
		/www
		/html
		/claroline-1.11.10
		/claroline
		/inc
		/lib
		/config.class.php 
 * Date January 28, 2020 at 12:44 AM
 * -------------------------------------------------
 * DONT EDIT THIS FILE - NE MODIFIEZ PAS CE FICHIER 
 **/

// $CLICALGenDate is an internal mark
   $CLICALGenDate = "1580172241";

/* enableICalInCourse : Enable iCal in course */
$GLOBALS['enableICalInCourse'] = TRUE;


/* iCalRepositoryCache : Note :  this repository should be protected with a .htaccess or       be placed outside the web. Because there contain data of private courses. */
$GLOBALS['iCalRepositoryCache'] = 'tmp/cache/ical/';


/* iCalUseCache : Enabling the cache may increase performance */
$GLOBALS['iCalUseCache'] = TRUE;


/* iCalGenStandard : When iCal File is regenerated, make the ics version. */
$GLOBALS['iCalGenStandard'] = TRUE;


/* iCalGenXml : When iCal File is regenerated, make the xml version. */
$GLOBALS['iCalGenXml'] = TRUE;


/* iCalGenRdf : When iCal File is regenerated, make the RDF version. */
$GLOBALS['iCalGenRdf'] = FALSE;


/* defaultEventDuration : In iCal an event has a duration but not in claroline. 3600 seconds = 1 Hour. */
$GLOBALS['defaultEventDuration'] = 3600;


/* iCalCacheLifeTime : Time before really compute data. 86400 seconds = 1 day. */
$GLOBALS['iCalCacheLifeTime'] = 86400;



?>
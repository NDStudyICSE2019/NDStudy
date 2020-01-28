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

// $CLCRSGenDate is an internal mark
   $CLCRSGenDate = "1580172241";

/* fill_course_example : Fill courses tools with material example */
$GLOBALS['fill_course_example'] = TRUE;


/* forceCodeCase : You can force the case  of course code */
$GLOBALS['forceCodeCase'] = 'upper';


/* availableLanguagesForCourses : Available languages for course websites */
$GLOBALS['availableLanguagesForCourses'] = array();


/* allowPublicCourses : Set to No to avoid the creation of public, world accessible, course sites */
$GLOBALS['allowPublicCourses'] = TRUE;


/* defaultVisibilityOnCourseCreation : This is probably a bad idea to set as hidden */
$GLOBALS['defaultVisibilityOnCourseCreation'] = TRUE;


/* defaultAccessOnCourseCreation : Default course access */
$GLOBALS['defaultAccessOnCourseCreation'] = 'public';


/* defaultRegistrationOnCourseCreation : Default course enrolment */
$GLOBALS['defaultRegistrationOnCourseCreation'] = TRUE;


/* clcrs_rootCategoryAllowed : By default a course is assigned to the ROOT category if no other category is chosen. If set to No, only the platform administrator can assign a course to the ROOT category. */
$GLOBALS['clcrs_rootCategoryAllowed'] = TRUE;


/* clcrs_displayShortCategoryPath : Same display as in Claroline 1.9 (SC > PHYS) Physics instead of 1.10 Sciences > Physics. Useful if you have long category titles. */
$GLOBALS['clcrs_displayShortCategoryPath'] = FALSE;


/* registrationRestrictedThroughCategories : Category's registration restriction */
$GLOBALS['registrationRestrictedThroughCategories'] = FALSE;


/* human_code_needed : User can leave course code (officialCode) field empty or not */
$GLOBALS['human_code_needed'] = TRUE;


/* human_label_needed : User can leave course title field empty or not */
$GLOBALS['human_label_needed'] = TRUE;


/* course_email_needed : User can leave email field empty or not */
$GLOBALS['course_email_needed'] = FALSE;


/* extLinkNameNeeded : Department name */
$GLOBALS['extLinkNameNeeded'] = FALSE;


/* extLinkUrlNeeded : Department website */
$GLOBALS['extLinkUrlNeeded'] = FALSE;


/* prefixAntiNumber : This string is prepend to course database name if it begins with a number */
$GLOBALS['prefixAntiNumber'] = 'No';


/* prefixAntiEmpty : Prefix for empty code course */
$GLOBALS['prefixAntiEmpty'] = 'Course';


/* nbCharFinalSuffix : Length of course code suffix */
/*
Length of suffix added when key is already exist
*/
$GLOBALS['nbCharFinalSuffix'] = 3;


/* showLinkToDeleteThisCourse : Allow course manager to delete their own courses */
$GLOBALS['showLinkToDeleteThisCourse'] = TRUE;


/* courseSessionAllowed : Course session creation is allowed on the platform */
$GLOBALS['courseSessionAllowed'] = TRUE;


/* clcrs_settings_display_visibility : Allow course manager to set the visibility of a course. An invisible course does not appear in the platform course list or in the course search engine. It only appears in the course list of enrolled users but can still be accessed by a direct URL. */
$GLOBALS['clcrs_settings_display_visibility'] = TRUE;


/* clcrs_settings_display_nbrofstudents : Allow course manager to set the maximum number of students that can enroll to a course */
$GLOBALS['clcrs_settings_display_nbrofstudents'] = TRUE;


/* clcrs_settings_display_status : Allow course manager to change the availability of their course (available or not, available during a given time period...) */
$GLOBALS['clcrs_settings_display_status'] = FALSE;



?>
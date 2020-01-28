<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-VI
 */

$englishLangName = "Vietnamese";

$iso639_1_code = "vi";
$iso639_2_code = "vie";

$langNameOfLang['arabic'        ]="arabian";
$langNameOfLang['brazilian'        ]="brazilian";
$langNameOfLang['english'        ]="english";
$langNameOfLang['finnish'        ]="finnish";
$langNameOfLang['french'        ]="french";
$langNameOfLang['german'        ]="german";
$langNameOfLang['italian'        ]="italian";
$langNameOfLang['japanese'        ]="japanese";
$langNameOfLang['polish'        ]="polish";
$langNameOfLang['simpl_chinese'    ]="simplified chinese";
$langNameOfLang['spanish'        ]="spanish";
$langNameOfLang['swedish'        ]="swedish";
$langNameOfLang['thai'            ]="thai";
$langNameOfLang['turkish'        ]="turkish";

$charset = 'utf-8';
//$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('S', 'M', 'T', 'W', 'T', 'F', 'S');
$langDay_of_weekNames['short'] = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$langDay_of_weekNames['long'] = array('Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$langMonthNames['long'] = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d-%m-%y";
$dateFormatLong  = '%A, %d-%m-%Y';
$dateTimeFormatLong  = '%d - %m, %Y - %I:%M %p';
$timeNoSecFormat = '%I:%M %p';


?>
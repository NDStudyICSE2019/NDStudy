<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package LANG-EN
 *
 * @author Claro team <cvs@claroline.net>
 */

$iso639_1_code = "en";
$iso639_2_code = "eng";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);

$langNameOfLang['arabic']        = "Arabian";
$langNameOfLang['brazilian']     = "Brazilian";
$langNameOfLang['bulgarian']     = "Bulgarian";
$langNameOfLang['catalan']       = "Catalan";
$langNameOfLang['croatian']      = "Croatian";
$langNameOfLang['danish']        = "Danish";
$langNameOfLang['dutch_be']      = "Dutch (Belgium)";
$langNameOfLang['dutch_nl']      = "Dutch (Nederlands)";
$langNameOfLang['english']       = "English";
$langNameOfLang['finnish']       = "Finnish";
$langNameOfLang['french']        = "French";
$langNameOfLang['galician']      = "Galician";
$langNameOfLang['german']        = "German";
$langNameOfLang['greek']         = "Greek";
$langNameOfLang['italian']       = "Italian";
$langNameOfLang['indonesian']    = "Indonesian";
$langNameOfLang['japanese']      = "Japanese";
$langNameOfLang['malay']         = "Malay";
$langNameOfLang['polish']        = "Polish";
$langNameOfLang['portuguese']    = "Portuguese";
$langNameOfLang['russian']       = "Russian";
$langNameOfLang['simpl_chinese'] = "Simplified chinese";
$langNameOfLang['slovenian']     = "Slovenian";
$langNameOfLang['spanish']       = "Spanish";
$langNameOfLang['swedish']       = "Swedish";
$langNameOfLang['thai']          = "Thai";
$langNameOfLang['turkish']       = "Turkish";
$langNameOfLang['vietnamese']    = "Vietnamese";
$langNameOfLang['zh_tw']         = "Traditional chinese";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('S', 'M', 'T', 'W', 'T', 'F', 'S');
$langDay_of_weekNames['short'] = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$langDay_of_weekNames['long'] = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$langMonthNames['long'] = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatCompact = "%B %Y";
$dateFormatShort =  "%b. %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateFormatNumeric =  "%Y/%m/%d";
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$dateTimeFormatShort = "%b. %d, %y %I:%M %p";
$timeNoSecFormat = '%I:%M %p';

?>
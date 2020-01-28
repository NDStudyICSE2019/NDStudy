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
 * @package LANG-HU
 *
 * @author Claro team <cvs@claroline.net>
 */

$iso639_1_code = "hu";
$iso639_2_code = "hun";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic']        = "arab";
$langNameOfLang['brazilian']     = "brazil";
$langNameOfLang['bulgarian']     = "bolgár";
$langNameOfLang['catalan']       = "katalán";
$langNameOfLang['croatian']      = "horvát";
$langNameOfLang['danish']        = "dán";
$langNameOfLang['dutch']         = "holland";
$langNameOfLang['english']       = "angol";
$langNameOfLang['finnish']       = "finn";
$langNameOfLang['french']        = "francia";
$langNameOfLang['galician']      = "galíciai";
$langNameOfLang['hungarian']      = "magyar";
$langNameOfLang['german']        = "német";
$langNameOfLang['greek']         = "görög";
$langNameOfLang['italian']       = "olasz";
$langNameOfLang['indonesian']    = "indonéziai";
$langNameOfLang['japanese']      = "japán";
$langNameOfLang['malay']         = "maláj"; 
$langNameOfLang['polish']        = "lengyel";
$langNameOfLang['portuguese']    = "portugál";
$langNameOfLang['russian']       = "orosz";
$langNameOfLang['simpl_chinese'] = "egyszerûsített kínai";
$langNameOfLang['slovenian']     = "szlovén";
$langNameOfLang['spanish']       = "spanyol";
$langNameOfLang['swedish']       = "svéd";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "török";
$langNameOfLang['vietnamese']    = "vietnámi";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Byte', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('V', 'H', 'K', 'S', 'C', 'P', 'S');
$langDay_of_weekNames['short'] = array('Vas', 'Hét', 'Kedd', 'Sze', 'Csü', 'Pén', 'Szo');
$langDay_of_weekNames['long'] = array('Vasárnap', 'Hétfõ', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat');

$langMonthNames['init']  = array('J', 'F', 'M', 'Á', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('jan', 'feb', 'már', 'ápr', 'máj', 'jún', 'júl', 'aug', 'sze', 'okt', 'nov', 'dec');
$langMonthNames['long'] = array('január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december');

// see http://www.php.net/manual/en/function.strftime.php 

$dateFormatShort =  "%b. %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y %H:%M'; // <- Don't forget to translate _at_
// $dateTimeFormatLong  = '%B %d, %Y at %I:%M %p'; // <- Don't forget to translate _at_
$dateTimeFormatShort = "%b. %d, %y %I:%M %p";
$timeNoSecFormat = '%I:%M %p';

?>
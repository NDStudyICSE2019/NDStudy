<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE 
 * Guarani translation by Manuel F. Fern�ndez - Oct 24, 2006
 * @version 1.7 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-GN
 *
 * @author Claro team <cvs@claroline.net>
 */

$iso639_1_code = "gn";
$iso639_2_code = "grn";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic']        = "aravia�e'&#7869;";
$langNameOfLang['brazilian']     = "rasiu�e'&#7869;";
$langNameOfLang['bulgarian']     = "vugaria�e'&#7869;";
$langNameOfLang['catalan']       = "katalu�a�e'&#7869;";
$langNameOfLang['croatian']      = "kyroasia�e'&#7869;";
$langNameOfLang['danish']        = "ndinamayka�e'&#7869;";
$langNameOfLang['dutch']         = "holanda�e'&#7869;";
$langNameOfLang['english']       = "ingye�e'&#7869;";
$langNameOfLang['finnish']       = "filandia�e'&#7869;";
$langNameOfLang['french']        = "hyr�sia�e'&#7869;";
$langNameOfLang['galician']      = "galicia�e'&#7869;";
$langNameOfLang['german']        = "alema�a�e'&#7869;";
$langNameOfLang['greek']         = "gyresia�e'&#7869;";
$langNameOfLang['italian']       = "italia�e'&#7869;";
$langNameOfLang['indonesian']    = "indonesia�e'&#7869;";
$langNameOfLang['japanese']      = "hap&otilde;�e'&#7869;";
$langNameOfLang['malay']         = "malasia�e'&#7869;";
$langNameOfLang['polish']        = "polo�a�e'&#7869;";
$langNameOfLang['portuguese']    = "poytuga�e'&#7869;";
$langNameOfLang['russian']       = "rusia�e'&#7869;";
$langNameOfLang['simpl_chinese'] = "china�e'&#7869; mbyk�va";
$langNameOfLang['slovenian']     = "elove�a�e'&#7869;";
$langNameOfLang['spanish']       = "epa�a�e'&#7869;";
$langNameOfLang['swedish']       = "suesia�e'&#7869;";
$langNameOfLang['thai']          = "tai�e'&#7869;";
$langNameOfLang['turkish']       = "tuykia�e'&#7869;";
$langNameOfLang['vietnamese']    = "viena�e'&#7869;";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('v�i', 'KV', 'MV', 'GV');

$langDay_of_weekNames['init'] = array('1', '2', '3', '4', '5', '6', '7');
$langDay_of_weekNames['short'] = array('Ar1', 'Ar2', 'Ar3', 'Ar4', 'Ar5', 'Ar6', 'Ar7');
$langDay_of_weekNames['long'] = array('Arate&#297;', 'Arak&otilde;i', 'Araapy', 'Ararundy', 'Arapo', 'Arapote&#297;', 'Arapok&otilde;i');

$langMonthNames['init']  = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
$langMonthNames['short'] = array('J01', 'J02', 'J03', 'J04', 'J05', 'J06', 'J07', 'J08', 'J09', 'J10', 'J11', 'J12');
$langMonthNames['long'] = array('Jasyte&#297;', 'Jasyk&otilde;i', 'Jasyapy', 'Jasyrundy', 'Jasypo', 'Jasypote&#297;', 'Jasypok&otilde;i', 'Jasypoapy', 'Jasyporundy', 'Jasypa', 'Jasypate&#297;', 'Jasypak&otilde;i');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";                 // J10 24, 06
$dateFormatLong  = '%A, %B %d, %Y';              // Araapy, Jasypa 24, 2006
$dateTimeFormatLong  = '%B %d, %Y - %H:%M';      // Jasypa 24, 2006 - 21:05
$dateTimeFormatShort = "%b %d, %y %H:%M";        // J10 24, 06 21:05
$timeNoSecFormat = '%H:%M';                      // 21:05

?>

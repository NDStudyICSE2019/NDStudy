<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE 
 * Guarani translation by Manuel F. Fernández - Oct 24, 2006
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

$langNameOfLang['arabic']        = "araviañe'&#7869;";
$langNameOfLang['brazilian']     = "rasiuñe'&#7869;";
$langNameOfLang['bulgarian']     = "vugariañe'&#7869;";
$langNameOfLang['catalan']       = "kataluñañe'&#7869;";
$langNameOfLang['croatian']      = "kyroasiañe'&#7869;";
$langNameOfLang['danish']        = "ndinamaykañe'&#7869;";
$langNameOfLang['dutch']         = "holandañe'&#7869;";
$langNameOfLang['english']       = "ingyeñe'&#7869;";
$langNameOfLang['finnish']       = "filandiañe'&#7869;";
$langNameOfLang['french']        = "hyrâsiañe'&#7869;";
$langNameOfLang['galician']      = "galiciañe'&#7869;";
$langNameOfLang['german']        = "alemañañe'&#7869;";
$langNameOfLang['greek']         = "gyresiañe'&#7869;";
$langNameOfLang['italian']       = "italiañe'&#7869;";
$langNameOfLang['indonesian']    = "indonesiañe'&#7869;";
$langNameOfLang['japanese']      = "hap&otilde;ñe'&#7869;";
$langNameOfLang['malay']         = "malasiañe'&#7869;";
$langNameOfLang['polish']        = "poloñañe'&#7869;";
$langNameOfLang['portuguese']    = "poytugañe'&#7869;";
$langNameOfLang['russian']       = "rusiañe'&#7869;";
$langNameOfLang['simpl_chinese'] = "chinañe'&#7869; mbykýva";
$langNameOfLang['slovenian']     = "eloveñañe'&#7869;";
$langNameOfLang['spanish']       = "epañañe'&#7869;";
$langNameOfLang['swedish']       = "suesiañe'&#7869;";
$langNameOfLang['thai']          = "taiñe'&#7869;";
$langNameOfLang['turkish']       = "tuykiañe'&#7869;";
$langNameOfLang['vietnamese']    = "vienañe'&#7869;";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('vái', 'KV', 'MV', 'GV');

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

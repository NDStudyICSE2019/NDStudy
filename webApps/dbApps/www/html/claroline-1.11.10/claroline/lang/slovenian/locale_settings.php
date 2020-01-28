<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Slovenian Translation
 * @version 1.7 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-SL
 *
 * @author Sergej Rinc <sergej@rinc.ws>
 */

$englishLangName = "Slovenian";

$iso639_1_code = "sl";
$iso639_2_code = "slv";

$langNameOfLang['arabic']        = "arabski";
$langNameOfLang['brazilian']     = "brazilski";
$langNameOfLang['bulgarian']     = "bolgarski";
$langNameOfLang['catalan']       = "katalonski";
$langNameOfLang['croatian']      = "hrva¹ki";
$langNameOfLang['danish']        = "danski";
$langNameOfLang['dutch']         = "nizozemski";
$langNameOfLang['english']       = "angle¹ki";
$langNameOfLang['finnish']       = "finski";
$langNameOfLang['french']        = "francoski";
$langNameOfLang['galician']      = "galski";
$langNameOfLang['german']        = "nem¹ki";
$langNameOfLang['greek']         = "gr¹ki";
$langNameOfLang['italian']       = "italijanski";
$langNameOfLang['indonesian']    = "indonezijski";
$langNameOfLang['japanese']      = "japonski";
$langNameOfLang['malay']         = "malezijski"; 
$langNameOfLang['polish']        = "poljski";
$langNameOfLang['portuguese']    = "portugalski";
$langNameOfLang['russian']       = "ruski";
$langNameOfLang['simpl_chinese'] = "poenost. kitajski";
$langNameOfLang['slovenian']     = "slovenski";
$langNameOfLang['spanish']       = "¹panski";
$langNameOfLang['swedish']       = "¹vedski";
$langNameOfLang['thai']          = "tajski";
$langNameOfLang['turkish']       = "tur¹ki";
$langNameOfLang['vietnamese']    = "vietnamski";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('zlogov', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');


$langDay_of_weekNames['init'] = array('N', 'P', 'T', 'S', 'È', 'P', 'S');
$langDay_of_weekNames['short'] = array('Ned', 'Pon', 'Tor', 'Sre', 'Èet', 'Pet', 'Sob');
$langDay_of_weekNames['long'] = array('Nedelja', 'Ponedeljek', 'Torek', 'Sreda', 'Èetrtek', 'Petek', 'Sobota');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('jan.', 'feb.', 'mar.', 'apr.', 'maj', 'jun.', 'jul.', 'avg.', 'sep.', 'okt.', 'nov.', 'dec.');
$langMonthNames['long'] = array('januar', 'februar', 'marec', 'april', 'maj', 'junij', 'julij', 'avgust', 'september', 'oktober', 'november', 'december');

// See http://www.php.net/manual/en/function.strftime.php 
$dateFormatShort =  "%d. %b %y";
$dateFormatLong  = '%A, %d. %b %Y';
$dateTimeFormatLong  = '%d. %B %Y ob %H:%M';
$dateTimeFormatShort  = '%d. %b %Y ob %H:%M';
$timeNoSecFormat = '%H:%M';

?>
<?php // $Id: locale_settings.php 14439 2013-04-29 14:25:50Z zefredz $
/**
 *
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14439 $
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package LANG-SK
 *
 * @author Claro team <cvs@claroline.net>
 *
 *
 * Slovak translation Claroline 1.97 
 * author Lubos Balazovic <lubos.balazovic@gmail.com>
 *
 * translated for MVP projekt
 * http://www.modernizaciavzdeavania.sk
 * 
 * Slovak translation Claroline 1.11.4 
 * author Peter Strba <peter.strba@gymtut.edu.sk>
 */

$iso639_1_code = "sk";
$iso639_2_code = "svk";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic']        = "arab¹tina";
$langNameOfLang['brazilian']     = "brazilèina";
$langNameOfLang['bulgarian']     = "bulharèina";
$langNameOfLang['catalan']       = "katalán¹tina";
$langNameOfLang['croatian']      = "chorvát¹tina";
$langNameOfLang['czech']         = "èe¹tina";
$langNameOfLang['danish']        = "dán¹tina";
$langNameOfLang['dutch']         = "holand¹tina";
$langNameOfLang['english']       = "angliètina";
$langNameOfLang['finnish']       = "fínètina";
$langNameOfLang['french']        = "francúz¹tina";
$langNameOfLang['galician']      = "gal¹tina";
$langNameOfLang['german']        = "nemèina";
$langNameOfLang['greek']         = "gréètina";
$langNameOfLang['italian']       = "talianèia";
$langNameOfLang['indonesian']    = "indonézèina";
$langNameOfLang['japanese']      = "japonèina";
$langNameOfLang['malay']         = "malaj¹tina"; 
$langNameOfLang['polish']        = "pol¹tina";
$langNameOfLang['portuguese']    = "portugaèina";
$langNameOfLang['russian']       = "ru¹tina";
$langNameOfLang['simpl_chinese'] = "zjednodu¹ená èín¹tina";
$langNameOfLang['slovenian']     = "slovinèina";
$langNameOfLang['slovak']        = "slovenèina";
$langNameOfLang['spanish']       = "¹panielèina";
$langNameOfLang['swedish']       = "¹véd¹tina";
$langNameOfLang['thai']          = "thaièina";
$langNameOfLang['turkish']       = "tureètina";
$langNameOfLang['vietnamese']    = "vietnamèina";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, sans-serif';
$right_font_family = 'helvetica, arial, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bajtov', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('N','P', 'U', 'S', '©', 'P', 'S');
$langDay_of_weekNames['short'] = array('Ne','Po', 'Ut', 'St', '©t', 'Pi', 'So');
$langDay_of_weekNames['long'] = array('Nedeµa', 'Pondelok', 'Utorok', 'Streda', '©tvrtok', 'Piatok', 'Sobota');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('jan', 'feb', 'mar', 'apr', 'máj', 'jún', 'júl', 'aug', 'sept', 'okt', 'nov', 'dec');
$langMonthNames['long'] = array('január', 'február', 'marec', 'april', 'máj', 'jún', 'júl', 'august', 'september', 'október', 'november', 'december');

$dateFormatShort =  "%d. %b %y";
$dateFormatLong  = '%A, %d. %B %Y';
$dateTimeFormatLong  = '%d. %B %Y v %H:%M';
$dateTimeFormatShort = "%d %b. %y, %H:%M";
$timeNoSecFormat = '%H:%M';

?>
<?php // $Id: locale_settings.php 14437 2013-04-29 14:22:34Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14437 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-PL
*/

$englishLangName = "Polish";
$localLangName = "polski";

$iso639_1_code = "pl";
$iso639_2_code = "pol";

$langNameOfLang['english']         = 'angielski';
$langNameOfLang['arabic']          = 'arabski';
$langNameOfLang['brazilian']       = 'brazylijski';
$langNameOfLang['bulgarian']       = 'bu�garski';
$langNameOfLang['zh_tw']           = 'chi�ski tradycyjny';
$langNameOfLang['simpl_chinese']   = 'chi�ski uproszczony';
$langNameOfLang['croatian']        = 'chorwacki';
$langNameOfLang['czech']           = 'czeski';
$langNameOfLang['czechSlovak']     = 'czesko-s�owacki';
$langNameOfLang['danish']          = 'du�ski';
$langNameOfLang['esperanto']       = 'esperanto';
$langNameOfLang['estonian']        = 'esto�ski';
$langNameOfLang['finnish']         = 'fi�ski';
$langNameOfLang['french']          = 'francuski';
$langNameOfLang['french_corp']     = 'francuski Korp.';
$langNameOfLang['galician']        = 'galicyjski';
$langNameOfLang['greek']           = 'grecki';
$langNameOfLang['georgian']        = 'gruzi�ski';
$langNameOfLang['guarani']         = 'guarani';
$langNameOfLang['spanish']         = 'hiszpa�ski';
$langNameOfLang['spanish_latin']   = 'hiszpa�ski (Amer.�aci�ska)';
$langNameOfLang['dutch']           = 'holenderski';
$langNameOfLang['indonesian']      = 'indonezyjski';
$langNameOfLang['japanese']        = 'japo�ski';
$langNameOfLang['catalan']         = 'katalo�ski';
$langNameOfLang['lao']             = 'laota�ski';
$langNameOfLang['malay']           = 'malajski';
$langNameOfLang['german']          = 'niemiecki';
$langNameOfLang['armenian']        = 'ormia�ski';
$langNameOfLang['persian']         = 'perski';
$langNameOfLang['polish']          = 'polski';
$langNameOfLang['portuguese']      = 'portugalski';
$langNameOfLang['russian']         = 'rosyjski';
$langNameOfLang['romanian']        = 'rumu�ski';
$langNameOfLang['slovenian']       = 's�owe�ski';
$langNameOfLang['swedish']         = 'szwedzki';
$langNameOfLang['thai']            = 'tajski';
$langNameOfLang['turkish']         = 'turecki';
$langNameOfLang['turkce']          = 'turecki';
$langNameOfLang['ukrainian']       = 'ukrai�ski';
$langNameOfLang['vietnamese']      = 'wietnamski';
$langNameOfLang['hungarian']       = 'w�gierski';
$langNameOfLang['italian']         = 'w�oski';

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('bajt�w', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'); // shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa

$langDay_of_weekNames['init'] = array('N', 'P', 'W', '�', 'C', 'Pt', 'S');
$langDay_of_weekNames['short'] = array('nie', 'pon', 'wt', '�r', 'czw', 'pt', 'sob');
$langDay_of_weekNames['long'] = array('niedziela', 'poniedzia�ek', 'wtorek', '�roda', 'czwartek', 'pi�tek', 'sobota');

$langMonthNames['init']  = array('S', 'L', 'M', 'K', 'M', 'C', 'L', 'S', 'W', 'P', 'L', 'G');
$langMonthNames['short'] = array('sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'pa�', 'lis', 'gru');
$langMonthNames['long'] = array('stycze�', 'luty', 'marzec', 'kwiecie�', 'maj', 'czerwiec', 'lipiec', 'sierpie�', 'wrzesie�', 'pa�dziernik', 'listopad', 'grudzie�');

// See http://www.php.net/manual/en/function.strftime.php for the variable 
// below

$dateFormatShort =  "%#d %B %Y";
$dateFormatLong  = '%A, %#d %B %Y';
$dateFormatNumeric =  "%#d.%m.%Y";
$dateTimeFormatShort = '%#d %b %Y, %H:%M';
$dateTimeFormatLong  = '%#d %B %Y, %H:%M';
$timeNoSecFormat = '%H:%M';

?>
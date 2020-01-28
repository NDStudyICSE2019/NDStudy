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
$langNameOfLang['bulgarian']       = 'bu³garski';
$langNameOfLang['zh_tw']           = 'chiñski tradycyjny';
$langNameOfLang['simpl_chinese']   = 'chiñski uproszczony';
$langNameOfLang['croatian']        = 'chorwacki';
$langNameOfLang['czech']           = 'czeski';
$langNameOfLang['czechSlovak']     = 'czesko-s³owacki';
$langNameOfLang['danish']          = 'duñski';
$langNameOfLang['esperanto']       = 'esperanto';
$langNameOfLang['estonian']        = 'estoñski';
$langNameOfLang['finnish']         = 'fiñski';
$langNameOfLang['french']          = 'francuski';
$langNameOfLang['french_corp']     = 'francuski Korp.';
$langNameOfLang['galician']        = 'galicyjski';
$langNameOfLang['greek']           = 'grecki';
$langNameOfLang['georgian']        = 'gruziñski';
$langNameOfLang['guarani']         = 'guarani';
$langNameOfLang['spanish']         = 'hiszpañski';
$langNameOfLang['spanish_latin']   = 'hiszpañski (Amer.£aciñska)';
$langNameOfLang['dutch']           = 'holenderski';
$langNameOfLang['indonesian']      = 'indonezyjski';
$langNameOfLang['japanese']        = 'japoñski';
$langNameOfLang['catalan']         = 'kataloñski';
$langNameOfLang['lao']             = 'laotañski';
$langNameOfLang['malay']           = 'malajski';
$langNameOfLang['german']          = 'niemiecki';
$langNameOfLang['armenian']        = 'ormiañski';
$langNameOfLang['persian']         = 'perski';
$langNameOfLang['polish']          = 'polski';
$langNameOfLang['portuguese']      = 'portugalski';
$langNameOfLang['russian']         = 'rosyjski';
$langNameOfLang['romanian']        = 'rumuñski';
$langNameOfLang['slovenian']       = 's³oweñski';
$langNameOfLang['swedish']         = 'szwedzki';
$langNameOfLang['thai']            = 'tajski';
$langNameOfLang['turkish']         = 'turecki';
$langNameOfLang['turkce']          = 'turecki';
$langNameOfLang['ukrainian']       = 'ukraiñski';
$langNameOfLang['vietnamese']      = 'wietnamski';
$langNameOfLang['hungarian']       = 'wêgierski';
$langNameOfLang['italian']         = 'w³oski';

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('bajtów', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'); // shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa

$langDay_of_weekNames['init'] = array('N', 'P', 'W', '¦', 'C', 'Pt', 'S');
$langDay_of_weekNames['short'] = array('nie', 'pon', 'wt', '¶r', 'czw', 'pt', 'sob');
$langDay_of_weekNames['long'] = array('niedziela', 'poniedzia³ek', 'wtorek', '¶roda', 'czwartek', 'pi±tek', 'sobota');

$langMonthNames['init']  = array('S', 'L', 'M', 'K', 'M', 'C', 'L', 'S', 'W', 'P', 'L', 'G');
$langMonthNames['short'] = array('sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'pa¼', 'lis', 'gru');
$langMonthNames['long'] = array('styczeñ', 'luty', 'marzec', 'kwiecieñ', 'maj', 'czerwiec', 'lipiec', 'sierpieñ', 'wrzesieñ', 'pa¼dziernik', 'listopad', 'grudzieñ');

// See http://www.php.net/manual/en/function.strftime.php for the variable 
// below

$dateFormatShort =  "%#d %B %Y";
$dateFormatLong  = '%A, %#d %B %Y';
$dateFormatNumeric =  "%#d.%m.%Y";
$dateTimeFormatShort = '%#d %b %Y, %H:%M';
$dateTimeFormatLong  = '%#d %B %Y, %H:%M';
$timeNoSecFormat = '%H:%M';

?>
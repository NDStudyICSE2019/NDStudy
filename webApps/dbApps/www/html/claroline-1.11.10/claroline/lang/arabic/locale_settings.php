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
 * @package LANG-AR
 *
 * @author Yassine Jelmam 8 oct 2002 <yjelmam@myrealbox.com>
 * @author Christophe Gesch� <moosh@claroline.net>
 */

$englishLangName = " �?رنسية ";
$localLangName = " �?رنسية ";

$iso639_1_code = "ar";
$iso639_2_code = "ara";

$langNameOfLang['arabic']        = "العربية";
$langNameOfLang['brazilian']     = "brazilian";
$langNameOfLang['bulgarian']     = "bulgarian";
$langNameOfLang['catalan']       = "catalan";
$langNameOfLang['croatian']      = "croatian";
$langNameOfLang['danish']        = "danish";
$langNameOfLang['dutch']         = "dutch";
$langNameOfLang['english']       = "english";
$langNameOfLang['finnish']       = "finnish";
$langNameOfLang['french']        = "french";
$langNameOfLang['galician']      = "galician";
$langNameOfLang['german']        = "german";
$langNameOfLang['greek']         = "greek";
$langNameOfLang['italian']       = "italian";
$langNameOfLang['indonesian']    = "indonesian";
$langNameOfLang['japanese']      = "japanese";
$langNameOfLang['malay']         = "malay"; 
$langNameOfLang['polish']        = "polish";
$langNameOfLang['portuguese']    = "portuguese";
$langNameOfLang['russian']       = "russian";
$langNameOfLang['simpl_chinese'] = "simplified chinese";
$langNameOfLang['slovenian']     = "slovenian";
$langNameOfLang['spanish']       = "spanish";
$langNameOfLang['swedish']       = "swedish";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "turkish";
$langNameOfLang['vietnamese']    = "vietnamese";


$charset = 'utf-8';
$text_dir = 'rtl';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = '"Windows UI", Tahoma, verdana, arial, helvetica, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Octets', 'Ko', 'Mo', 'Go');

$langDay_of_weekNames['init'] = array('الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت');
$langDay_of_weekNames['short'] = array('الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت');
$langDay_of_weekNames['long'] = array('الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت');

$langMonthNames['init']  = array('يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر');
$langMonthNames['short'] = array('يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر');
$langMonthNames['long'] = array('يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر');


// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%d %B %Y الساعة %H:%M';
$timeNoSecFormat = '%H:%M';

?>
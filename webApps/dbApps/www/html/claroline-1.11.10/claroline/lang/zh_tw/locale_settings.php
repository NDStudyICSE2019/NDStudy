<?php
/**
 * CLAROLINE 
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-zh_tw
 *
 * @author Finjon Kiang http://twpug.net
 */

$iso639_1_code = "zh"; //zh-tw
$iso639_2_code = "chi";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic']        = "arabian";
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
$langNameOfLang['simpl_chinese'] = "簡體中文";
$langNameOfLang['slovenian']     = "slovenian";
$langNameOfLang['spanish']       = "spanish";
$langNameOfLang['swedish']       = "swedish";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "turkish";
$langNameOfLang['vietnamese']    = "vietnameseV";
$langNameOfLang['zh_tw']    = "正體中文";

$charset = 'UTF-8';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = ''; //There are few Chinese fonts, it's not necessary to set font-famly
$right_font_family = '';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('日', '一', '二', '三', '四', '五', '六');
$langDay_of_weekNames['short'] = array('日', '一', '二', '三', '四', '五', '六');
$langDay_of_weekNames['long'] = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');

$langMonthNames['init']  = array(1,2,3,4,5,6,7,8,9,10,11,12);
$langMonthNames['short'] = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二');
$langMonthNames['long'] = array('一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月');

// http://www.php.net/manual/en/function.strftime.php

$dateFormatShort =  "%y %b. %d";
$dateFormatLong  = '%Y %B %d, %A';
$dateTimeFormatLong  = '%Y %B %d, %p %I:%M';
$dateTimeFormatShort = "%y %b. %d, %p %I:%M";
$timeNoSecFormat = '%p %I:%M';

?>
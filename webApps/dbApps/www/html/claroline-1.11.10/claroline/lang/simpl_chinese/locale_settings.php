<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Simplified Chinese Translation
 * @version 1.9 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-ZH
 *
 * @author Derek Joe <zhous1998@gmail.com> (www.claroline.net.cn)
 */
$englishLangName = "Chinese";

$iso639_1_code = "zh";
$iso639_2_code = "zho";

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
$langNameOfLang['simpl_chinese'] = "简体中文";
$langNameOfLang['slovenian']     = "slovenian";
$langNameOfLang['spanish']       = "spanish";
$langNameOfLang['swedish']       = "swedish";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "turkish";
$langNameOfLang['vietnamese']    = "vietnamese";
$langNameOfLang['zh_tw']         = "繁体中文";

$charset = 'GB2312';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'simsun, 宋体';
$right_font_family = 'simsun';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('字节', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$langDay_of_weekNames['init'] = array('日', '一', '二', '三', '四', '五', '六');
$langDay_of_weekNames['short'] = array('日', '一', '二', '三', '四', '五', '六');
$langDay_of_weekNames['long'] = array('周日', '周一', '周二', '周三', '周四', '周五', '周六');

$langMonthNames['init']  = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
$langMonthNames['short'] = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
$langMonthNames['long'] = array('1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%y年%b月%d日";
$dateFormatLong  = '%Y 年 %B  %d 日, %A';
$dateTimeFormatLong  = '%Y 年 %B  %d 日, %I:%M %p';
$dateTimeFormatShort = "%y 年 %b 月 %d 日, %I:%M %p";
$timeNoSecFormat = '%I:%M %p';
?>
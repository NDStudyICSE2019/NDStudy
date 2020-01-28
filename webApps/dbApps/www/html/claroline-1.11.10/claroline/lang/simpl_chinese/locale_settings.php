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
$langNameOfLang['simpl_chinese'] = "��������";
$langNameOfLang['slovenian']     = "slovenian";
$langNameOfLang['spanish']       = "spanish";
$langNameOfLang['swedish']       = "swedish";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "turkish";
$langNameOfLang['vietnamese']    = "vietnamese";
$langNameOfLang['zh_tw']         = "��������";

$charset = 'GB2312';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'simsun, ����';
$right_font_family = 'simsun';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('�ֽ�', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$langDay_of_weekNames['init'] = array('��', 'һ', '��', '��', '��', '��', '��');
$langDay_of_weekNames['short'] = array('��', 'һ', '��', '��', '��', '��', '��');
$langDay_of_weekNames['long'] = array('����', '��һ', '�ܶ�', '����', '����', '����', '����');

$langMonthNames['init']  = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
$langMonthNames['short'] = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');
$langMonthNames['long'] = array('1��', '2��', '3��', '4��', '5��', '6��', '7��', '8��', '9��', '10��', '11��', '12��');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%y��%b��%d��";
$dateFormatLong  = '%Y �� %B  %d ��, %A';
$dateTimeFormatLong  = '%Y �� %B  %d ��, %I:%M %p';
$dateTimeFormatShort = "%y �� %b �� %d ��, %I:%M %p";
$timeNoSecFormat = '%I:%M %p';
?>
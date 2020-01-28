<?php // $Id: locale_settings.php 14433 2013-04-29 07:38:11Z zefredz $
/**
 *
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14433 $
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package LANG-CS
 *
 * @author Claro team <cvs@claroline.net>
 *
 * Czech translate for Claroline 1.75
 * http://www.claroline.cz
 *
 * author Zdenek Machek <zdenek.machek@atlas.cz>
 *
 * translated for ZUS Police
 * http://www.zuspolice.cz
 *
 */

$iso639_1_code = "cs";
$iso639_2_code = "cze";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic']        = "arab�tina";
$langNameOfLang['brazilian']     = "brazil�tina";
$langNameOfLang['bulgarian']     = "bulhar�tina";
$langNameOfLang['catalan']       = "katal�n�tina";
$langNameOfLang['croatian']      = "chorvat�tina";
$langNameOfLang['czech']         = "�e�tina";
$langNameOfLang['danish']        = "d�n�tina";
$langNameOfLang['dutch']         = "holand�tina";
$langNameOfLang['english']       = "angli�tina";
$langNameOfLang['finnish']       = "fin�tina";
$langNameOfLang['french']        = "francou��tina";
$langNameOfLang['galician']      = "gal�tina";
$langNameOfLang['german']        = "n�m�ina";
$langNameOfLang['greek']         = "�e�tina";
$langNameOfLang['italian']       = "ital�tina";
$langNameOfLang['indonesian']    = "indon�tina";
$langNameOfLang['japanese']      = "japon�tina";
$langNameOfLang['malay']         = "malaj�tina"; 
$langNameOfLang['polish']        = "pol�tina";
$langNameOfLang['portuguese']    = "portugal�tina";
$langNameOfLang['russian']       = "ru�tina";
$langNameOfLang['simpl_chinese'] = "zjednodu�en� ��n�tina";
$langNameOfLang['slovenian']     = "slovin�tina";
$langNameOfLang['spanish']       = "�pan�l�tina";
$langNameOfLang['swedish']       = "�v�d�tina";
$langNameOfLang['thai']          = "thai�tina";
$langNameOfLang['turkish']       = "ture�tina";
$langNameOfLang['vietnamese']    = "vietnam�tina";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, sans-serif';
$right_font_family = 'helvetica, arial, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bajt�', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('P', '�', 'S', '�', 'P', 'S', 'N');
$langDay_of_weekNames['short'] = array('Pon', '�t', 'St', '�t', 'P�', 'So', 'Ne');
$langDay_of_weekNames['long'] = array('Pond�l�', '�ter�', 'St�eda', '�tvrtek', 'P�tek', 'Sobota', 'Ned�le');

$langMonthNames['init']  = array('L', '�', 'B', 'D', 'K', '�', '�', 'S', 'Z', '�', 'L', 'P');
$langMonthNames['short'] = array('Led', '�no', 'B�e', 'Dub', 'Kv�', '�erv', '�erc', 'Srp', 'Z��', '��j', 'List', 'Pros');
$langMonthNames['long'] = array('Leden', '�nor', 'B�ezen', 'Duben', 'Kv�ten', '�erven', '�ervenec', 'Srpen', 'Z���', '��jen', 'Listopad', 'Prosinec');

$dateFormatShort =  "%d. %b %y";
$dateFormatLong  = '%A, %d. %B %Y';
$dateTimeFormatLong  = '%d. %B %Y v %H:%M';
$dateTimeFormatShort = "%d %b. %y, %H:%M";
$timeNoSecFormat = '%H:%M';

?>
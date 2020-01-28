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

$langNameOfLang['arabic']        = "arab�tina";
$langNameOfLang['brazilian']     = "brazil�ina";
$langNameOfLang['bulgarian']     = "bulhar�ina";
$langNameOfLang['catalan']       = "katal�n�tina";
$langNameOfLang['croatian']      = "chorv�t�tina";
$langNameOfLang['czech']         = "�e�tina";
$langNameOfLang['danish']        = "d�n�tina";
$langNameOfLang['dutch']         = "holand�tina";
$langNameOfLang['english']       = "angli�tina";
$langNameOfLang['finnish']       = "f�n�tina";
$langNameOfLang['french']        = "franc�z�tina";
$langNameOfLang['galician']      = "gal�tina";
$langNameOfLang['german']        = "nem�ina";
$langNameOfLang['greek']         = "gr��tina";
$langNameOfLang['italian']       = "talian�ia";
$langNameOfLang['indonesian']    = "indon�z�ina";
$langNameOfLang['japanese']      = "japon�ina";
$langNameOfLang['malay']         = "malaj�tina"; 
$langNameOfLang['polish']        = "pol�tina";
$langNameOfLang['portuguese']    = "portuga�ina";
$langNameOfLang['russian']       = "ru�tina";
$langNameOfLang['simpl_chinese'] = "zjednodu�en� ��n�tina";
$langNameOfLang['slovenian']     = "slovin�ina";
$langNameOfLang['slovak']        = "sloven�ina";
$langNameOfLang['spanish']       = "�paniel�ina";
$langNameOfLang['swedish']       = "�v�d�tina";
$langNameOfLang['thai']          = "thai�ina";
$langNameOfLang['turkish']       = "ture�tina";
$langNameOfLang['vietnamese']    = "vietnam�ina";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, sans-serif';
$right_font_family = 'helvetica, arial, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bajtov', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('N','P', 'U', 'S', '�', 'P', 'S');
$langDay_of_weekNames['short'] = array('Ne','Po', 'Ut', 'St', '�t', 'Pi', 'So');
$langDay_of_weekNames['long'] = array('Nede�a', 'Pondelok', 'Utorok', 'Streda', '�tvrtok', 'Piatok', 'Sobota');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('jan', 'feb', 'mar', 'apr', 'm�j', 'j�n', 'j�l', 'aug', 'sept', 'okt', 'nov', 'dec');
$langMonthNames['long'] = array('janu�r', 'febru�r', 'marec', 'april', 'm�j', 'j�n', 'j�l', 'august', 'september', 'okt�ber', 'november', 'december');

$dateFormatShort =  "%d. %b %y";
$dateFormatLong  = '%A, %d. %B %Y';
$dateTimeFormatLong  = '%d. %B %Y v %H:%M';
$dateTimeFormatShort = "%d %b. %y, %H:%M";
$timeNoSecFormat = '%H:%M';

?>
<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Russian Translation
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-RU
 *
 * @author Claro team <cvs@claroline.net>
 */
$englishLangName = "Russian";
$localLangName = "русский";

$iso639_1_code = "ru";
$iso639_2_code = "rus";

$langNameOfLang['arabic']        = "арабский";
$langNameOfLang['brazilian']    = "бразильский";
$langNameOfLang['croatian']    = "хорватский";
$langNameOfLang['catalan']    = "каталнаский";
$langNameOfLang['dutch']        = "нидерландский";
$langNameOfLang['english']    = "английский";
$langNameOfLang['finnish']    = "финский";
$langNameOfLang['french']        = "французский";
$langNameOfLang['german']        = "немецкий";
$langNameOfLang['greek']        = "греческий";
$langNameOfLang['italian']    = "итальянский";
$langNameOfLang['japanese']    = "японский";
$langNameOfLang['polish']        = "польский";
$langNameOfLang['simpl_chinese']="упрощенный китайский";
$langNameOfLang['spanish']    = "испанский";
$langNameOfLang['swedish']    = "шведский";
$langNameOfLang['thai']        = "тайский";
$langNameOfLang['turkish']    = "турецкий";

$charset = 'KOI8-R';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('байт', 'Кб', 'Мб', 'Гб');

$langDay_of_weekNames['init'] = array('В', 'П', 'В', 'С', 'Ч', 'П', 'С');
$langDay_of_weekNames['short'] = array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
$langDay_of_weekNames['long'] = array('Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота');

$langMonthNames['init']  = array('Я', 'Ф', 'М', 'A', 'M', 'И', 'И', 'A', 'С', 'O', 'Н', 'Д');
$langMonthNames['short'] = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
$langMonthNames['long'] = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 
'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%A %d %B %Y а %H:%M';
$timeNoSecFormat = '%H:%M';
?>
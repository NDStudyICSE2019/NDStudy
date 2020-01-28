<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-FI
 */


$iso639_1_code = "fi";
$iso639_2_code = "fin";

$langNameOfLang['brazilian']="brazilian";
$langNameOfLang['english']="english";
$langNameOfLang['finnish']="finnish";
$langNameOfLang['french']="french";
$langNameOfLang['german']="german";
$langNameOfLang['italian']="italian";
$langNameOfLang['japanese']="japanese";
$langNameOfLang['polish']="polish";
$langNameOfLang['simpl_chinese']="simplified chinese";
$langNameOfLang['spanish']="spanish";
$langNameOfLang['swedish']="swedish";
$langNameOfLang['thai']="thai";
$langNameOfLang['arabic']="arabian";
$langNameOfLang['croatian']="croatian";
$langNameOfLang['dutch']="dutch";
$langNameOfLang['turkish']="turkish";
$langNameOfLang['greek']="greek";


$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('tavua', 'kt', 'Mt', 'Gt');

//kepp this  name  and  change  in sources
$langDay_of_weekNames['init'] = array('S', 'M', 'T', 'K', 'T', 'P', 'L');
$langDay_of_weekNames['short'] = array('Su', 'Ma', 'Ti', 'Ke', 'To', 'Pe', 'La');
$langDay_of_weekNames['long'] = array('Su', 'Ma', 'Ti', 'Ke', 'To', 'Pe', 'La');

$langMonthNames['init']  = array('T', 'H', 'M', 'H', 'T', 'K', 'H', 'E', 'S', 'L', 'M', 'J');
$langMonthNames['short'] = array('Tammi', 'Helmi', 'Maalis', 'Huhti', 'Touko', 'Kesä', 'Heinä', 'Elo', 'Syys', 'Loka', 'Marras', 'Joulu');
$langMonthNames['long'] = array('Tammikuu', 'Helmikuu', 'Maaliskuu', 'Huhtikuu', 'Toukokuu', 'Kesäkuu', 'Heinäkuu', 'Elokuu', 'Syyskuu', 'Lokakuu', 'Marraskuu', 'Joulukuu');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d.%m.%Y";
$dateFormatLong  = '%A %d.%m.%Y';
$dateTimeFormatLong  = '%d.%m.%Y klo %H:%M';
$timeNoSecFormat = '%H:%M';


?>
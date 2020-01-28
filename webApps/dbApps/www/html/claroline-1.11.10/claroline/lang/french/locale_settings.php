<?php // $Id: locale_settings.php 13805 2011-11-09 09:39:21Z jrm_ $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 13805 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-FR
 */

$englishLangName = "french";
$localLangName = "français";

$iso639_1_code = "fr";
$iso639_2_code = "fre";

$langNameOfLang['arabic']        = "arabe";
$langNameOfLang['brazilian']     = "brésilien";
$langNameOfLang['bulgarian']     = "bulgare";
$langNameOfLang['catalan']       = "catalan";
$langNameOfLang['croatian']      = "croate";
$langNameOfLang['danish']        = "danois";
$langNameOfLang['dutch_be']      = "néerlandais (Belgique)";
$langNameOfLang['dutch_nl']      = "néerlandais (Pays-Bas)";
$langNameOfLang['english']       = "anglais";
$langNameOfLang['finnish']       = "finlandais";
$langNameOfLang['french']        = "français";
$langNameOfLang['galician']      = "galicien";
$langNameOfLang['german']        = "allemand";
$langNameOfLang['greek']         = "grec";
$langNameOfLang['indonesian']    = "indonesien";
$langNameOfLang['italian']       = "italien";
$langNameOfLang['japanese']      = "japonais"; // JCC
$langNameOfLang['malay']         = "malais";
$langNameOfLang['polish']        = "polonais";
$langNameOfLang['portuguese']    = "portugais";
$langNameOfLang['russian']       = "russe";
$langNameOfLang['simpl_chinese'] = "chinois simple";
$langNameOfLang['slovenian']     = "slovène";
$langNameOfLang['spanish']       = "espagnol";
$langNameOfLang['swedish']       = "suédois";
$langNameOfLang['thai']          = "thaïlandais";
$langNameOfLang['turkish']       = "turc";
$langNameOfLang['vietnamese']    = "vietnamien";
$langNameOfLang['zh_tw']         = "chinois traditionnel";


$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('Octets', 'Ko', 'Mo', 'Go');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'); // JCC
$langDay_of_weekNames['long'] = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc');
$langMonthNames['long'] = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateFormatNumeric =  "%d/%m/%Y";
$dateTimeFormatLong  = '%A %d %B %Y à %H:%M';
$dateTimeFormatShort = "%d/%m/%y %H:%M";
$timeNoSecFormat = '%H:%M';

?>
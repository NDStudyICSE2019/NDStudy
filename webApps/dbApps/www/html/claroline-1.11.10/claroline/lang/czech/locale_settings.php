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

$langNameOfLang['arabic']        = "arab¹tina";
$langNameOfLang['brazilian']     = "brazil¹tina";
$langNameOfLang['bulgarian']     = "bulhar¹tina";
$langNameOfLang['catalan']       = "katalán¹tina";
$langNameOfLang['croatian']      = "chorvat¹tina";
$langNameOfLang['czech']         = "èe¹tina";
$langNameOfLang['danish']        = "dán¹tina";
$langNameOfLang['dutch']         = "holand¹tina";
$langNameOfLang['english']       = "angliètina";
$langNameOfLang['finnish']       = "fin¹tina";
$langNameOfLang['french']        = "francou¾¹tina";
$langNameOfLang['galician']      = "gal¹tina";
$langNameOfLang['german']        = "nìmèina";
$langNameOfLang['greek']         = "øeètina";
$langNameOfLang['italian']       = "ital¹tina";
$langNameOfLang['indonesian']    = "indoné¹tina";
$langNameOfLang['japanese']      = "japon¹tina";
$langNameOfLang['malay']         = "malaj¹tina"; 
$langNameOfLang['polish']        = "pol¹tina";
$langNameOfLang['portuguese']    = "portugal¹tina";
$langNameOfLang['russian']       = "ru¹tina";
$langNameOfLang['simpl_chinese'] = "zjednodu¹ená èín¹tina";
$langNameOfLang['slovenian']     = "slovin¹tina";
$langNameOfLang['spanish']       = "¹panìl¹tina";
$langNameOfLang['swedish']       = "¹véd¹tina";
$langNameOfLang['thai']          = "thai¹tina";
$langNameOfLang['turkish']       = "tureètina";
$langNameOfLang['vietnamese']    = "vietnam¹tina";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, sans-serif';
$right_font_family = 'helvetica, arial, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bajtù', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('P', 'Ú', 'S', 'È', 'P', 'S', 'N');
$langDay_of_weekNames['short'] = array('Pon', 'Út', 'St', 'Èt', 'Pá', 'So', 'Ne');
$langDay_of_weekNames['long'] = array('Pondìlí', 'Úterý', 'Støeda', 'Ètvrtek', 'Pátek', 'Sobota', 'Nedìle');

$langMonthNames['init']  = array('L', 'Ú', 'B', 'D', 'K', 'È', 'È', 'S', 'Z', 'Ø', 'L', 'P');
$langMonthNames['short'] = array('Led', 'Úno', 'Bøe', 'Dub', 'Kvì', 'Èerv', 'Èerc', 'Srp', 'Záø', 'Øíj', 'List', 'Pros');
$langMonthNames['long'] = array('Leden', 'Únor', 'Bøezen', 'Duben', 'Kvìten', 'Èerven', 'Èervenec', 'Srpen', 'Záøí', 'Øíjen', 'Listopad', 'Prosinec');

$dateFormatShort =  "%d. %b %y";
$dateFormatLong  = '%A, %d. %B %Y';
$dateTimeFormatLong  = '%d. %B %Y v %H:%M';
$dateTimeFormatShort = "%d %b. %y, %H:%M";
$timeNoSecFormat = '%H:%M';

?>
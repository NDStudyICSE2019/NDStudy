<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.7 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author claro team <cvs@claroline.net>
 * @author Antonio Apostoliu <antonio.apostoliu@meteo.inmh.ro>
 * @author Christophe Gesché <cvs@claroline.net>
 *
 * @package LANG-RO
 */

$englishLangName = "romanian";

$iso639_1_code = "ro";
$iso639_2_code = "ron";

$localLangName = "romanian";
$langNameOfLang['arabic']        = "arabic";
$langNameOfLang['brazilian']     = "brazilian";
$langNameOfLang['bulgarian']     = "bulgarian";
$langNameOfLang['catalan']       = "catalog";
$langNameOfLang['croatian']      = "croatian";
$langNameOfLang['danish']        = "danez";
$langNameOfLang['dutch']         = "dutch";
$langNameOfLang['english']       = "englez";
$langNameOfLang['finnish']       = "finnish";
$langNameOfLang['french']        = "francez";
$langNameOfLang['galician']      = "galician";
$langNameOfLang['german']        = "german";
$langNameOfLang['greek']         = "verde";
$langNameOfLang['indonesian']    = "indonesian";
$langNameOfLang['italian']       = "italian";
$langNameOfLang['japanese']      = "japonez"; // JCC
$langNameOfLang['malay']         = "malay";
$langNameOfLang['polish']        = "polonez";
$langNameOfLang['portuguese']    = "portughez";
$langNameOfLang['russian']       = "russian";
$langNameOfLang['simpl_chinese'] = "chinez simple";
$langNameOfLang['slovenian']     = "slovenian";
$langNameOfLang['spanish']       = "spaniol";
 $langNameOfLang['swedish']       = "swedish";
$langNameOfLang['thai']          = "thai";
$langNameOfLang['turkish']       = "curcan";
$langNameOfLang['vietnamese']    = "vietnamese";

$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('Octets', 'Ko', 'Mo', 'Go');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sim'); // JCC
$langDay_of_weekNames['long'] = array('Duminic', 'Luni' , 'Marai' , 'Miercuri' , 'Joi' , 'Vineri' , 'Sîmbata');

$langMonthNames['init']  = array('I', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Ian' , 'Feb' , 'Mar' , 'Apr', 'Mai', 'Iun', 'Iul', 'Aot', 'Sep', 'Oct', 'Noi', 'Dec');
$langMonthNames['long'] = array('Ianuarie' , 'Februarie' , 'Martie' , 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'Aot', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  '"%a %d %b %y';
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%A %d %B %Y - %H:%M';
$dateTimeFormatShort = "%d/%m/%y %H:%M";
$timeNoSecFormat = '%H:%M';

?>
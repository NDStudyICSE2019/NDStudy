<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author: Xavier de Pedro   <xavidp@confluencia.net>                 
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-CA
 */

$iso639_1_code = "ca"; 
$iso639_2_code = "cat";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
$langNameOfLang['brazilian'] = "brasileny";
$langNameOfLang['english'  ]="anglès";
$langNameOfLang['finnish'  ]="finlandès";
$langNameOfLang['french'   ]="francès";
$langNameOfLang['german'   ]="alemany";
$langNameOfLang['italian'  ]="italià";
$langNameOfLang['japanese' ]="japonès";
$langNameOfLang['polish'   ]="polonès";
$langNameOfLang['simpl_chinese'        ]="xinès simplificat";
$langNameOfLang['spanish'  ]="castellà";
$langNameOfLang['swedish'  ]="suec";
$langNameOfLang['thai'     ]="tailandès";
$langNameOfLang['catalan'  ]="català";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('U', 'L', 'M', 'C', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Diu', 'Dll', 'Dts', 'Dcr', 'Dij', 'Div', 'Dis');
$langDay_of_weekNames['long'] = array('Diumenge', 'Dilluns', 'Dimarts', 'Dimecres', 'Dijous', 'Divendres', 'Dissabte');

$langMonthNames['init']  = array('G', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Gen', 'Feb', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Des');
$langMonthNames['long'] = array('Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny', 'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y a les %I:%M %p';
$timeNoSecFormat = '%I:%M %p';

?>
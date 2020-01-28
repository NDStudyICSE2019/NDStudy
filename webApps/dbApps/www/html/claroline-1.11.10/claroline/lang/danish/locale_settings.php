<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author: Helle Meldgaard <helle@iktlab.au.dk>
 * @author: claro team <cvs@claroline.net>
 *
 * @package LANG-DA
 *
 */

$iso639_1_code = "da";
$iso639_2_code = "dan";

$langNameOfLang['arabic'        ] = "arabian";
$langNameOfLang['brazilian'        ] = "brazilian";
$langNameOfLang['bulgarian'        ] = "bulgarian";
$langNameOfLang['croatian'        ] = "croatian";
$langNameOfLang['dutch'            ] = "duits";
$langNameOfLang['english'        ] = "engelse";
$langNameOfLang['finnish'        ] = "finnish";
$langNameOfLang['french'        ] = "frans";
$langNameOfLang['german'        ] = "german";
$langNameOfLang['greek'            ] = "greeks";
$langNameOfLang['italian'        ] = "italianse";
$langNameOfLang['japanese'        ] = "japanese";
$langNameOfLang['polish'        ] = "polish";
$langNameOfLang['simpl_chinese'    ] = "simplified chinese";
$langNameOfLang['spanish'        ] = "spanish";
$langNameOfLang['swedish'        ] = "swedish";
$langNameOfLang['thai'            ] = "thai";
$langNameOfLang['turkish'        ] = "turkish";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('S', 'M', 'T', 'O', 'T', 'F', 'L');
$langDay_of_weekNames['short'] = array('SØn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør');
$langDay_of_weekNames['long'] = array('Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');
$langMonthNames['long'] = array('Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$timeNoSecFormat = '%I:%M %p';

?>
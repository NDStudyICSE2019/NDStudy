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
 * @package LANG-HR
 */

$iso639_1_code = "hr";
$iso639_2_code = "hrv";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);
unset($byteUnits);

$langNameOfLang['arabic'        ]="arapski";
$langNameOfLang['brazilian'        ]="brazilski";
$langNameOfLang['bulgarian'        ]="bugarski";
$langNameOfLang['croatian'        ]="hrvatski";
$langNameOfLang['dutch'            ]="nizozemski";
$langNameOfLang['english'        ]="engleski";
$langNameOfLang['finnish'        ]="finski";
$langNameOfLang['french'        ]="francuski";
$langNameOfLang['german'        ]="njemaèli";
$langNameOfLang['greek'            ]="grèki";
$langNameOfLang['italian'        ]="talijanski";
$langNameOfLang['japanese'        ]="japanski";
$langNameOfLang['polish'        ]="poljski";
$langNameOfLang['simpl_chinese'    ]="pojednostavljeni kineski";
$langNameOfLang['spanish'        ]="španjolski";
$langNameOfLang['swedish'        ]="švedski";
$langNameOfLang['thai'            ]="thai";
$langNameOfLang['turkish'        ]="turski";

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('N', 'P', 'U', 'S', 'È', 'P', 'S');
$langDay_of_weekNames['short'] = array('Ned', 'Pon', 'Ut', 'Sri', 'Èet', 'Pet', 'Sub');
$langDay_of_weekNames['long'] = array('Nedjelja', 'Ponedjeljak', 'Utorak', 'Srijeda', 'Èetvrtak', 'Petak', 'Subota');

$langMonthNames['init']  = array('S', 'V', 'O', 'T', 'S', 'L', 'S', 'K', 'R', 'L', 'S', 'P');
$langMonthNames['short'] = array('Sij', 'Velj', 'Ožu', 'Tra', 'Svi', 'Lip', 'Srp', 'Kol', 'Ruj', 'Lis', 'Stu', 'Pro');
$langMonthNames['long'] = array('Sijeèanj', 'Veljaèa', 'Ožujak', 'Travanj', 'Svibanj', 'Lipanj', 'Srpanj', 'Kolovoz', 'Rujan', 'Listopad', 'Studeni', 'Prosinac');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d %b , %y";
$dateFormatLong  = '%A %d %B, %Y';
$dateTimeFormatLong  = '%d %B, %Y u %H:%M';
$dateTimeFormatShort = "%d-%m-%y %H:%M";
$timeNoSecFormat = '%H:%M';

?>
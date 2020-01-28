<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Turkish Translation
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-TR
 *
 * @author Claro team <cvs@claroline.net>
 */
$englishLangName = "Turkish";

$iso639_1_code = "tr";
$iso639_2_code = "tur";

$langNameOfLang['arabic'		]="arabian";
$langNameOfLang['brazilian'		]="brezilya dili";
$langNameOfLang['bulgarian'		]="bulgarian";
$langNameOfLang['croatian'		]="croatian";
$langNameOfLang['dutch'			]="dutch";
$langNameOfLang['english'		]="ingilizce";
$langNameOfLang['finnish'		]="fince";
$langNameOfLang['french'		]="frans&#305;zca";
$langNameOfLang['german'		]="almanca";
$langNameOfLang['greek'			]="greek";
$langNameOfLang['italian'		]="italyanca";
$langNameOfLang['japanese'		]="japonca";
$langNameOfLang['polish'		]="polca";
$langNameOfLang['simpl_chinese'	]="�ince";
$langNameOfLang['spanish'		]="ispanyolca";
$langNameOfLang['swedish'		]="isve�ce";
$langNameOfLang['thai'			]="tayca";
$langNameOfLang['turkish'		]="t�rk�e";

$charset = 'iso-8859-9';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$langDay_of_weekNames['init'] = array('P', 'Pt', 'S', '�', 'P', 'C', 'Ct');
$langDay_of_weekNames['short'] = array('Paz', 'Pts', 'Sal', '�ar', 'Per', 'Cum', 'Cts');
$langDay_of_weekNames['long'] = array('Pazar', 'Pazartesi', 'Sal�', '�ar�amba', 'Per�embe', 'Cuma', 'Cumartesi');

$langMonthNames['init']  = array('O', '�', 'M', 'N', 'M', 'H', 'T', 'A', 'E', 'E', 'K', 'A');
$langMonthNames['short'] = array('Oca', '�ub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'A�u', 'Eyl', 'Eki', 'Kas', 'Ara');
$langMonthNames['long'] = array('Ocak', '�ubat', 'Mart', 'Nisan', 'May�s', 'Haziran', 'Temmuz', 'A�ustos', 'Eyl�l', 'Ekim', 'Kas�m', 'Aral�k');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y Saat %I:%M %p';
$timeNoSecFormat = '%I:%M %p';

?>
<?php // $Id: locale_settings.php 14587 2013-11-08 12:47:41Z zefredz $
/**
 * CLAROLINE
 * Spanish Translation
 * @version 1.9 $Revision: 14587 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-ES
 *
 * @author Claro team <cvs@claroline.net>
 */
$englishLangName = 'spanish';
$localLangName = 'español';

$iso639_1_code = 'es';
$iso639_2_code = 'spa';

$langNameOfLang['arabic'		]='árabe';
$langNameOfLang['brazilian'		]='portugués';
$langNameOfLang['bulgarian'		]='búlgaro';
$langNameOfLang['croatian'		]='croata';
$langNameOfLang['dutch'			]='holandés';
$langNameOfLang['english'		]='inglés';
$langNameOfLang['finnish'		]='finlandés';
$langNameOfLang['french'		]='francés';
$langNameOfLang['german'		]='alemán';
$langNameOfLang['greek'			]='griego';
$langNameOfLang['italian'		]='italiano';
$langNameOfLang['japanese'		]='japonés';
$langNameOfLang['polish'		]='polaco';
$langNameOfLang['simpl_chinese'		]='chino';
$langNameOfLang['spanish'		]='español';
$langNameOfLang['spanish_latin'		]='español (latin1)';
$langNameOfLang['spanish-utf8'		]='español (UTF-8)';
$langNameOfLang['swedish'		]='sueco';
$langNameOfLang['thai'			]='tailandés';
$langNameOfLang['turkish'		]='turco';

$charset = 'utf-8';
$text_dir = 'ltr'; // ('ltr' para izq a der, 'rtl' para der a izq)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'X', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb');
$langDay_of_weekNames['long'] = array('domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado');

$langMonthNames['init']  = array('E', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
$langMonthNames['long'] = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  '%d/%m/%Y';
$dateFormatLong  = '%A, %e de %B de %Y';
$dateFormatNumeric =  '%d/%m/%Y';
$dateTimeFormatLong  = '%e de %B de %Y a las %H:%M';
$timeNoSecFormat = '%H:%M';

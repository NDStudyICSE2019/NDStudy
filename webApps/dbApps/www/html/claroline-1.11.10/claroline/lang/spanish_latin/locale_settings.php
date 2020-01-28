<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Spanish Translation
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-ES
 *
 * @author Claro team <cvs@claroline.net>
 */

$englishLangName = "Spanish";
$localLangName = "espa�ol";

$iso639_1_code = "es";
$iso639_2_code = "spa";

$langNameOfLang['arabic'		]="�rabe";
$langNameOfLang['brazilian'		]="portugu�s";
$langNameOfLang['bulgarian'		]="bulgarian";
$langNameOfLang['croatian'		]="croato";
$langNameOfLang['dutch'			]="dutch";
$langNameOfLang['english'		]="ingl�s";
$langNameOfLang['finnish'		]="finland�s";
$langNameOfLang['french'		]="franc�s";
$langNameOfLang['german'		]="alem�n";
$langNameOfLang['greek'			]="griego";
$langNameOfLang['italian'		]="italiano";
$langNameOfLang['japanese'		]="japon�s";
$langNameOfLang['polish'		]="polaco";
$langNameOfLang['simpl_chinese'		]="chino";
$langNameOfLang['spanish'		]="espa�ol";
$langNameOfLang['spanish_latin'		]="espa�ol latinoamericano";
$langNameOfLang['swedish'		]="sueco";
$langNameOfLang['thai'			]="thailand�s";
$langNameOfLang['turkish'		]="turco";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' para izq a der, 'rtl' para der a izq)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Dom', 'Lun', 'Mar', 'Mier', 'Jue', 'Vie', 'Sab');
$langDay_of_weekNames['long'] = array('Domingo', 'Lunes', 'Martes', 'Mi�rcoles', 'Jueves', 'Viernes', 'S�bado');

$langMonthNames['init']  = array('E', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec');
$langMonthNames['long'] = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Dicembre');

// see http://www.php.net/manual/en/function.strftime.php pour la variable

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$timeNoSecFormat = '%I:%M %p';


?>
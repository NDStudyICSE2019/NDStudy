<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Spanish Translation
 * @version 1.9 $Revision: 12923 $
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
$localLangName = "español";

$iso639_1_code = "es";
$iso639_2_code = "spa";

$langNameOfLang['arabic'		]="árabe";
$langNameOfLang['brazilian'		]="portugués";
$langNameOfLang['bulgarian'		]="búlgaro";
$langNameOfLang['croatian'		]="croata";
$langNameOfLang['dutch'			]="holandés";
$langNameOfLang['english'		]="inglés";
$langNameOfLang['finnish'		]="finlandés";
$langNameOfLang['french'		]="francés";
$langNameOfLang['german'		]="alemán";
$langNameOfLang['greek'			]="griego";
$langNameOfLang['italian'		]="italiano";
$langNameOfLang['japanese'		]="japonés";
$langNameOfLang['polish'		]="polaco";
$langNameOfLang['simpl_chinese'		]="chino";
$langNameOfLang['spanish'		]="español";
$langNameOfLang['spanish_latin'		]="español latino";
$langNameOfLang['swedish'		]="sueco";
$langNameOfLang['thai'			]="tailandés";
$langNameOfLang['turkish'		]="turco";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' para izq a der, 'rtl' para der a izq)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'X', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab');
$langDay_of_weekNames['long'] = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

$langMonthNames['init']  = array('E', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
$langMonthNames['long'] = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d/%m/%Y";
$dateFormatLong  = '%A, %e de %B de %Y';
$dateTimeFormatLong  = '%e de %B de %Y a las %H:%M';
$timeNoSecFormat = '%H:%M';


?>
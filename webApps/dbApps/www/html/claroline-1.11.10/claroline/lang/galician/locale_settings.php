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
 * @package LANG-GL
 *
 * Copyright (c) 2002, High Sierra Networks, Inc.                       
 * This module was modifyed 2002-02-21 by                               
 *     Mayra Angeles     <mayra.angeles@eduservers.com>            
 *     Jorge Gonzalez    <jgonzalez@eduservers.com>                
 *
 * Translation to Galician                                              
 *     e-learning dept CESGA <teleensino@cesga.es >                         
 *
 * Translation to Galician v.1.6                                        
 *     Servizo de teledocencia. Universidade de Vigo                        
 *     Gerardo Albela González   galbela@uvigo.es - gerardoalbela@yahoo.es  
 */



$englishLangName = "Galician";
$localLangName = "galego";

$iso639_1_code = "gl";
$iso639_2_code = "glg";


$langNameOfLang['arabic'        ]="arabian";
$langNameOfLang['brazilian'        ]="brazilian";
$langNameOfLang['bulgarian'        ]="bulgarian";
$langNameOfLang['croatian'        ]="croatian";
$langNameOfLang['dutch'            ]="dutch";
$langNameOfLang['english'        ]="english";
$langNameOfLang['finnish'        ]="finnish";
$langNameOfLang['french'        ]="french";
$langNameOfLang['galician'        ]="galician";
$langNameOfLang['german'        ]="german";
$langNameOfLang['greek'            ]="greek";
$langNameOfLang['italian'        ]="italian";
$langNameOfLang['japanese'        ]="japanese";
$langNameOfLang['polish'        ]="polish";
$langNameOfLang['simpl_chinese'    ]="simplified chinese";
$langNameOfLang['spanish'        ]="spanish";
$langNameOfLang['swedish'        ]="swedish";
$langNameOfLang['thai'            ]="thai";
$langNameOfLang['turkish'        ]="turkish";



$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'X', 'V', 'S');
$langDay_of_weekNames['short'] = array('Dom', 'Lun', 'Mar', 'Mer', 'Xov', 'Ven', 'Sab');
$langDay_of_weekNames['long'] = array('Domingo', 'Luns', 'Martes', 'M&eacute;rcores', 'Xoves', 'Venres', 'S&aacute;bado');

$langMonthNames['init']  = array('X', 'F', 'M', 'A', 'M', 'X', 'X', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('xan', 'feb', 'mar', 'abr', 'mai', 'xu&ntilde;', 'xul', 'ago', 'set', 'out', 'nov', 'dec');
$langMonthNames['long'] = array('xaneiro', 'febreiro', 'marzo', 'abril', 'maio', 'xu&ntilde;o', 'xullo', 'agosto', 'setembro', 'outubro', 'novembro', 'decembro');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$timeNoSecFormat = '%I:%M %p';

?>
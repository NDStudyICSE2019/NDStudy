<?php // $Id: locale_settings.php 9706 2007-12-12 13:30:11Z mlaurent $

//----------------------------------------------------------------------
// CLAROLINE
//----------------------------------------------------------------------
// Copyright (c) 2001-2004 Universite catholique de Louvain (UCL)
//----------------------------------------------------------------------
// This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
// as published by the FREE SOFTWARE FOUNDATION. The GPL is available
// through the world-wide-web at http:
//www.gnu.org/copyleft/gpl.html
//----------------------------------------------------------------------
// Authors: see 'credits' file
//----------------------------------------------------------------------
/* Original was : Pietro Danesi <danone@aruba.it>  07.09.2001 init version  in PHPMyAdmin */
$englishLangName = "Italian";
$localLangName = "Italiano";
$iso639_1_code = "it";
$iso639_2_code = "ita";
$langNameOfLang['arabic']="arabo";
$langNameOfLang['brazilian']="brasiliano";
$langNameOfLang['english']="inglese";
$langNameOfLang['finnish']="finlandese";
$langNameOfLang['french']="francese";
$langNameOfLang['german']="tedesco";
$langNameOfLang['italian']="italiano";
$langNameOfLang['japanese']="giapponese";
$langNameOfLang['polish']="polacco";
$langNameOfLang['greek']="greco";
$langNameOfLang['simpl_chinese']="cinese semplificato";
$langNameOfLang['spanish']="spagnolo";
$langNameOfLang['swedish']="svedese";
$langNameOfLang['thai']="tailandese";
$langNameOfLang['turkish']="turco";
$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');
$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'G', 'V', 'S');
 
//italian days
$langDay_of_weekNames['short'] = array('Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab');
 
//italian days
$langDay_of_weekNames['long'] = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato');
 
//italian days
$day_of_weekNames = $langDay_of_weekNames;
$langMonthNames['init'] = array('G', 'F', 'M', 'A', 'M', 'G', 'L', 'A', 'S', 'O', 'N', 'D');
 
//italian months
$langMonthNames['short'] = array('Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic');
 
//italian months
$langMonthNames['long'] = array('Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre');
 
//italian months
$monthNames = $langMonthNames;

// Voir http:
//www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous
$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%A %d %B %Y ore %H:%M';
$timeNoSecFormat = '%H:%M';
?>
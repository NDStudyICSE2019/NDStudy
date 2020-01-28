<?php // $Id: locale_settings.php 9706 2007-12-12 13:30:11Z mlaurent $
/* $Id: locale_settings.php 9706 2007-12-12 13:30:11Z mlaurent $ */

/*                 Portuguese                */

/*
      +----------------------------------------------------------------------|
      | Translation to European Portuguese (pt_PT):                          |
      | Dionisio Martínez Soler  <dmsoler@edu.xunta.es >                     |
      |     (Escola Oficial de Idiomas de Vigo, Spain)                   |
      +----------------------------------------------------------------------|
*/

$englishLangName = "Portuguese";
$localLangName = "portugu&ecirc;s";

$iso639_1_code = "pt";
$iso639_2_code = "por";

$langNameOfLang['brazilian']="brazilian";
$langNameOfLang['english']="english";
$langNameOfLang['finnish']="finnish";
$langNameOfLang['french']="french";
$langNameOfLang['german']="german";
$langNameOfLang['galician']="galician";
$langNameOfLang['italian']="italian";
$langNameOfLang['japanese']="japanese";
$langNameOfLang['polish']="polish";
$langNameOfLang['portuguese']="portuguese";
$langNameOfLang['simpl_chinese']="simplified chinese";
$langNameOfLang['spanish']="spanish";
$langNameOfLang['swedish']="swedish";
$langNameOfLang['thai']="thai";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'Kb', 'Mb', 'Gb');

$langDay_of_weekNames['init'] = array('D', '2', '3', '4', '5', '6', 'S');
$langDay_of_weekNames['short'] = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'S&aacute;b');
$langDay_of_weekNames['long'] = array('Domingo', 'Segunda-feira', 'Ter&ccedil;a-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'S&aacute;bado');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
$langMonthNames['long'] = array('Janeiro', 'Fevereiro', 'Mar&ccedil;o', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d %b %y";
$dateFormatLong  = '%A %d de %B de %Y';
$dateTimeFormatLong  = '%d de %B de %Y &agrave;s %H:%M';
$timeNoSecFormat = '%H:%M';

?>
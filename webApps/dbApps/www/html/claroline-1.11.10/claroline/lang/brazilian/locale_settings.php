<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Brazilian Portuguese Translation
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-PT
 *
 * @Translator : Marcelo R. Minholi <minholi@unipar.br>
 * @author : Marcelo R. Minholi <minholi@unipar.br>
 * @author Claro team <cvs@claroline.net>
 */
$iso639_1_code = "pt";
$iso639_2_code = "por";
$langNameOfLang['arabic']="árabe";
$langNameOfLang['brazilian']="português do brasil";
$langNameOfLang['bulgarian']="búlgaro";
$langNameOfLang['croatian']="croata";
$langNameOfLang['dutch']="holandês";
$langNameOfLang['english']="inglês";
$langNameOfLang['finnish']="finlandês";
$langNameOfLang['french']="francês";
$langNameOfLang['german']="alemão";
$langNameOfLang['greek']="grego";
$langNameOfLang['italian']="italiano";
$langNameOfLang['japanese']="japonês";
$langNameOfLang['polish']="polonês";
$langNameOfLang['simpl_chinese']="chinês simplificado";
$langNameOfLang['spanish']="espanhol";
$langNameOfLang['swedish']="sueco";
$langNameOfLang['thai']="tailandês";
$langNameOfLang['turkish']="turco";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('D', 'S', 'T', 'Q', 'Q', 'S', 'S');
$langDay_of_weekNames['short'] = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab');
$langDay_of_weekNames['long'] = array('Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
$langMonthNames['long'] = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%d de %b de %y";
$dateFormatLong  = '%A, %d de %B de %Y';
$dateTimeFormatLong  = '%A, %d de %B de %Y às %H:%Mh';
$timeNoSecFormat = '%H:%Mh';

?>
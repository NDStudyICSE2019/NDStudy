<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 * Russian Translation
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @package LANG-RU
 *
 * @author Claro team <cvs@claroline.net>
 */
$englishLangName = "Russian";
$localLangName = "�������";

$iso639_1_code = "ru";
$iso639_2_code = "rus";

$langNameOfLang['arabic']        = "��������";
$langNameOfLang['brazilian']    = "�����������";
$langNameOfLang['croatian']    = "����������";
$langNameOfLang['catalan']    = "�����������";
$langNameOfLang['dutch']        = "�������������";
$langNameOfLang['english']    = "����������";
$langNameOfLang['finnish']    = "�������";
$langNameOfLang['french']        = "�����������";
$langNameOfLang['german']        = "��������";
$langNameOfLang['greek']        = "���������";
$langNameOfLang['italian']    = "�����������";
$langNameOfLang['japanese']    = "��������";
$langNameOfLang['polish']        = "��������";
$langNameOfLang['simpl_chinese']="���������� ���������";
$langNameOfLang['spanish']    = "���������";
$langNameOfLang['swedish']    = "��������";
$langNameOfLang['thai']        = "�������";
$langNameOfLang['turkish']    = "��������";

$charset = 'KOI8-R';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('����', '��', '��', '��');

$langDay_of_weekNames['init'] = array('�', '�', '�', '�', '�', '�', '�');
$langDay_of_weekNames['short'] = array('��', '��', '��', '��', '��', '��', '��');
$langDay_of_weekNames['long'] = array('�����������', '�����������', '�������', '�����', '�������', '�������', '�������');

$langMonthNames['init']  = array('�', '�', '�', 'A', 'M', '�', '�', 'A', '�', 'O', '�', '�');
$langMonthNames['short'] = array('���', '���', '���', '���', '���', '����', '����', '���', '���', '���', '���', '���');
$langMonthNames['long'] = array('������', '�������', '����', '������', '���', '����', '����', 
'������', '��������', '�������', '������', '�������');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%A %d %B %Y � %H:%M';
$timeNoSecFormat = '%H:%M';
?>
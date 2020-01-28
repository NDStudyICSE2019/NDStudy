<?php // $Id: locale_settings.php 12923 2011-03-03 14:23:57Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package LANG-TH
 *
 * @author Claro team <cvs@claroline.net>
 * @author Clarolinethai team <prachid@clarolinethai.info>
 
/*โครงการศึกษาวิจััยและพัฒนา โปรแกรมสนับสนุนระบบบริหารจัดการเรียนการสอนผ่านระบบเครือข่าย ประเภทเปิดเผยรหัสต้นฉบับ 
ด้วยโปรแกรมระบบคลาโรไลน์ อีเลิร์นนิ่ง : ฉบับภาษาไทย โดย ผศ.ประชิด ทิณบุตร อาจารย์ประจำสาขาวิชาศิลปกรรม คณะมนุษยศาสตร์และสังคมศาสตร์ 
มหาวิทยาลัยราชภัฏจันทรเกษม ถนนรัชดาภิเษก กทม.10900. โทร.02 942 6900 ต่อ 3011,3014, 
เว็บไซต์สำนักงาน URL:http://artnet.chandra.ac.th,  เว็บไซต์ทดสอบและการนำเสนอเผยแพร่ผลงานจริง http://art.chandra.ac.th/claroline  
http://chandraonline.chandra.ac.th,  http://www.chandraonline.info, http://www.thaiteachers.info ,http://www.thairesearcher.info , 
http://www.wittycomputer.com, http://www.clarolinethai.info

โปรแกรมนี้ ออกแบบกราฟิก แปลและพัฒนาระบบส่วนภาษาไทย โดย ผศ.ประชิด ทิณบุตร นับแต่เมื่อวันที่ 23 ตุลาคม 2549  ปรับปรุงล่าสุดเมื่อ 25 ตุลาคม
2552. ติดต่อโทรศัพท์มือถือ. 08 9667 0091, เว็บไซต์ส่วนตัว http://www.prachid.com, อีเมล prachid@prachid.com. บ้านพัก โทร/โทรสาร.02 962 9505
Customized ,Graphics Design  and Thai Translation by : Assistant Professor Prachid Tinnabutr, prachid@prachid.com, Since 23 Oct 2008 ,Last 
Update 25 October 2009.

This work is a part of the project title:The research and development of educational media innovation supporting the art and design online learning 
courses by using Claroline Learning Management system :Thai Version. by Assistant Professor Prachid Tinnabutr . Division of Fine and Applied Arts. 
Faculty of Humanities and Social Sciences.Chandrakasem Rajabhat University,Bangkok Thailand 10900,Office URL: http://artnet.chandra.ac.th, http://art.chandra.ac.th/claroline
Private URL:http://www.prachid.com, email : prachid@prachid.com , Mobile Phone (66)08 9667 0091.
*/
$iso639_1_code = "th";
$iso639_2_code = "tha";

unset($langNameOfLang);
unset($langDay_of_weekNames);
unset($langMonthNames);

$langNameOfLang['arabic']        = "Arabian";
$langNameOfLang['brazilian']     = "Brazilian";
$langNameOfLang['bulgarian']     = "Bulgarian";
$langNameOfLang['catalan']       = "Catalan";
$langNameOfLang['croatian']      = "Croatian";
$langNameOfLang['danish']        = "Danish";
$langNameOfLang['dutch_be']      = "Dutch (Belgium)";
$langNameOfLang['dutch_nl']      = "Dutch (Nederlands)";
$langNameOfLang['english']       = "English";
$langNameOfLang['finnish']       = "Finnish";
$langNameOfLang['french']        = "French";
$langNameOfLang['galician']      = "Galician";
$langNameOfLang['german']        = "German";
$langNameOfLang['greek']         = "Greek";
$langNameOfLang['italian']       = "Italian";
$langNameOfLang['indonesian']    = "Indonesian";
$langNameOfLang['japanese']      = "Japanese";
$langNameOfLang['malay']         = "Malay";
$langNameOfLang['polish']        = "Polish";
$langNameOfLang['portuguese']    = "Portuguese";
$langNameOfLang['russian']       = "Russian";
$langNameOfLang['simpl_chinese'] = "Simplified chinese";
$langNameOfLang['slovenian']     = "Slovenian";
$langNameOfLang['spanish']       = "Spanish";
$langNameOfLang['swedish']       = "Swedish";
$langNameOfLang['thai']          = "Thai";
$langNameOfLang['turkish']       = "Turkish";
$langNameOfLang['vietnamese']    = "Vietnamese";
$langNameOfLang['zh_tw']         = "Traditional chinese";

$charset = 'UTF-8';
$text_dir = 'ltr';// ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'MS Sans Serif,Tahoma,verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'MS Sans Serif,Tahoma, verdana, helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส');
$langDay_of_weekNames['short'] = array('อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส');
$langDay_of_weekNames['long'] = array('วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์');

$langMonthNames['init']  = array('เดือนมกราคม', 'เดือนกุมภาพันธ์', 'เดือนมีนาคม',  'เดือนเมษายน', 'เดือนพฤษภาคม', 'เดือนมิถุนายน', 'เดือนกรกฎาคม', 
'เดือนสิงหาคม', 
'เดือนกันยายน', 'เดือนตุลาคม', 'เดือนพฤศจิกายน', 'เดือนธันวาคม');
$langMonthNames['short'] = array('เดือนมกราคม', 'เดือนกุมภาพันธ์', 'เดือนมีนาคม',  'เดือนเมษายน', 'เดือนพฤษภาคม', 'เดือนมิถุนายน', 'เดือนกรกฎาคม', 
'เดือนสิงหาคม', 
'เดือนกันยายน', 'เดือนตุลาคม', 'เดือนพฤศจิกายน', 'เดือนธันวาคม');
$langMonthNames['long'] = array('เดือนมกราคม', 'เดือนกุมภาพันธ์', 'เดือนมีนาคม',  'เดือนเมษายน', 'เดือนพฤษภาคม', 'เดือนมิถุนายน', 'เดือนกรกฎาคม', 
'เดือนสิงหาคม', 
'เดือนกันยายน', 'เดือนตุลาคม', 'เดือนพฤศจิกายน', 'เดือนธันวาคม');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatCompact = "%B %Y";
$dateFormatShort =  "%b. %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateFormatNumeric =  "%Y/%m/%d";
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$dateTimeFormatShort = "%b. %d, %y %I:%M %p";
$timeNoSecFormat = '%I:%M %p';

?>
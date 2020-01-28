<?php
/*

                    (c) GNU GENERAL PUBLIC LICENSE
                       Version 3, 29 June 2007

* EXCERPT from Wikipedia

An Identicon is a visual representation of a hash value, usually of the
IP address, serving to identify a user of a computer system. The
original Identicon is a 9-block graphic, which has been extended to
other graphic forms by third parties some of whom have used MD5 instead
of the IP address as the identifier. In summary, an Identicon is a
privacy protecting derivative of each user's IP address built into a
9-block image and displayed next the user's name. A visual
representation is thought to be easier to compare than one which uses
only numbers and more importantly, it maintains the person's privacy.
The Identicon graphic is unique since it's based on the users IP, but
it is not possible to recover the IP by looking at the Identicon.


* ABOUT PHP-Identicons

PHP-Identicons is a lightweight PHP implementation of Don Park's
original identicon code for visual representation of MD5 hash values.
The program uses the PHP GD library for image processing.

The code can be used to generate unique identicons, avatars, and
system-assigned images based on a user's e-mail address, user ID, etc.


* INSTALLATION

Simply save identicon.php somewhere accessible thru a Web URL.


* USAGE

PHP-Identicons requires the size (in pixels) and an MD5 hash of
anything that will uniquely identify a user - usually an e-mail address
or a user ID. You can also use MD5 hashes of IP addresses, but bear in
mind that NATed endpoints will usually have the same IP.

Insert the URL in your HTML image tag that looks something like:

<img src="path/to/identicon.php?size=48&hash=e4d909c290d0fb1ca068ffaddf22cbd0" />

And that's all there is to it!

If you're not satisfied with the millions of image variations that the
program generates, additional variation in final image can be done by
image rotation. Simply uncomment the following line in the program:
// $identicon=imagerotate($identicon,$angle,$bg);

* DONATIONS

If you find this program useful, please pass it along to your friends
or donate any amount you feel will motivate the contributor/s to make
the program better. Send your donations by following this link:

https://sourceforge.net/donate/index.php?group_id=271757#blurb

*/

/* generate sprite for corners and sides */
function getsprite($shape,$R,$G,$B,$rotation) {
	global $spriteZ;
	$sprite=imagecreatetruecolor($spriteZ,$spriteZ);
	imageantialias($sprite,TRUE);
	$fg=imagecolorallocate($sprite,$R,$G,$B);
	$bg=imagecolorallocate($sprite,255,255,255);
	imagefilledrectangle($sprite,0,0,$spriteZ,$spriteZ,$bg);
	switch($shape) {
		case 0: // triangle
			$shape=array(
				0.5,1,
				1,0,
				1,1
			);
			break;
		case 1: // parallelogram
			$shape=array(
				0.5,0,
				1,0,
				0.5,1,
				0,1
			);
			break;
		case 2: // mouse ears
			$shape=array(
				0.5,0,
				1,0,
				1,1,
				0.5,1,
				1,0.5
			);
			break;
		case 3: // ribbon
			$shape=array(
				0,0.5,
				0.5,0,
				1,0.5,
				0.5,1,
				0.5,0.5
			);
			break;
		case 4: // sails
			$shape=array(
				0,0.5,
				1,0,
				1,1,
				0,1,
				1,0.5
			);
			break;
		case 5: // fins
			$shape=array(
				1,0,
				1,1,
				0.5,1,
				1,0.5,
				0.5,0.5
			);
			break;
		case 6: // beak
			$shape=array(
				0,0,
				1,0,
				1,0.5,
				0,0,
				0.5,1,
				0,1
			);
			break;
		case 7: // chevron
			$shape=array(
				0,0,
				0.5,0,
				1,0.5,
				0.5,1,
				0,1,
				0.5,0.5
			);
			break;
		case 8: // fish
			$shape=array(
				0.5,0,
				0.5,0.5,
				1,0.5,
				1,1,
				0.5,1,
				0.5,0.5,
				0,0.5
			);
			break;
		case 9: // kite
			$shape=array(
				0,0,
				1,0,
				0.5,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		case 10: // trough
			$shape=array(
				0,0.5,
				0.5,1,
				1,0.5,
				0.5,0,
				1,0,
				1,1,
				0,1
			);
			break;
		case 11: // rays
			$shape=array(
				0.5,0,
				1,0,
				1,1,
				0.5,1,
				1,0.75,
				0.5,0.5,
				1,0.25
			);
			break;
		case 12: // double rhombus
			$shape=array(
				0,0.5,
				0.5,0,
				0.5,0.5,
				1,0,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		case 13: // crown
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1,
				1,0.5,
				0.5,0.25,
				0.5,0.75,
				0,0.5,
				0.5,0.25
			);
			break;
		case 14: // radioactive
			$shape=array(
				0,0.5,
				0.5,0.5,
				0.5,0,
				1,0,
				0.5,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		default: // tiles
			$shape=array(
				0,0,
				1,0,
				0.5,0.5,
				0.5,0,
				0,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
	}
	/* apply ratios */
	for ($i=0;$i<count($shape);$i++)
		$shape[$i]=$shape[$i]*$spriteZ;
	imagefilledpolygon($sprite,$shape,count($shape)/2,$fg);
	/* rotate the sprite */
	for ($i=0;$i<$rotation;$i++)
		$sprite=imagerotate($sprite,90,$bg);
	return $sprite;
}

/* generate sprite for center block */
function getcenter($shape,$fR,$fG,$fB,$bR,$bG,$bB,$usebg) {
	global $spriteZ;
	$sprite=imagecreatetruecolor($spriteZ,$spriteZ);
	imageantialias($sprite,TRUE);
	$fg=imagecolorallocate($sprite,$fR,$fG,$fB);
	/* make sure there's enough contrast before we use background color of side sprite */
	if ($usebg>0 && (abs($fR-$bR)>127 || abs($fG-$bG)>127 || abs($fB-$bB)>127))
		$bg=imagecolorallocate($sprite,$bR,$bG,$bB);
	else
		$bg=imagecolorallocate($sprite,255,255,255);
	imagefilledrectangle($sprite,0,0,$spriteZ,$spriteZ,$bg);
	switch($shape) {
		case 0: // empty
			$shape=array();
			break;
		case 1: // fill
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1
			);
			break;
		case 2: // diamond
			$shape=array(
				0.5,0,
				1,0.5,
				0.5,1,
				0,0.5
			);
			break;
		case 3: // reverse diamond
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1,
				0,0.5,
				0.5,1,
				1,0.5,
				0.5,0,
				0,0.5
			);
			break;
		case 4: // cross
			$shape=array(
				0.25,0,
				0.75,0,
				0.5,0.5,
				1,0.25,
				1,0.75,
				0.5,0.5,
				0.75,1,
				0.25,1,
				0.5,0.5,
				0,0.75,
				0,0.25,
				0.5,0.5
			);
			break;
		case 5: // morning star
			$shape=array(
				0,0,
				0.5,0.25,
				1,0,
				0.75,0.5,
				1,1,
				0.5,0.75,
				0,1,
				0.25,0.5
			);
			break;
		case 6: // small square
			$shape=array(
				0.33,0.33,
				0.67,0.33,
				0.67,0.67,
				0.33,0.67
			);
			break;
		case 7: // checkerboard
			$shape=array(
				0,0,
				0.33,0,
				0.33,0.33,
				0.66,0.33,
				0.67,0,
				1,0,
				1,0.33,
				0.67,0.33,
				0.67,0.67,
				1,0.67,
				1,1,
				0.67,1,
				0.67,0.67,
				0.33,0.67,
				0.33,1,
				0,1,
				0,0.67,
				0.33,0.67,
				0.33,0.33,
				0,0.33
			);
			break;
	}
	/* apply ratios */
	for ($i=0;$i<count($shape);$i++)
		$shape[$i]=$shape[$i]*$spriteZ;
	if (count($shape)>0)
		imagefilledpolygon($sprite,$shape,count($shape)/2,$fg);
	return $sprite;
}

/* parse hash string */

$csh=hexdec(substr($_GET["hash"],0,1)); // corner sprite shape
$ssh=hexdec(substr($_GET["hash"],1,1)); // side sprite shape
$xsh=hexdec(substr($_GET["hash"],2,1))&7; // center sprite shape

$cro=hexdec(substr($_GET["hash"],3,1))&3; // corner sprite rotation
$sro=hexdec(substr($_GET["hash"],4,1))&3; // side sprite rotation
$xbg=hexdec(substr($_GET["hash"],5,1))%2; // center sprite background

/* corner sprite foreground color */
$cfr=hexdec(substr($_GET["hash"],6,2));
$cfg=hexdec(substr($_GET["hash"],8,2));
$cfb=hexdec(substr($_GET["hash"],10,2));

/* side sprite foreground color */
$sfr=hexdec(substr($_GET["hash"],12,2));
$sfg=hexdec(substr($_GET["hash"],14,2));
$sfb=hexdec(substr($_GET["hash"],16,2));

/* final angle of rotation */
$angle=hexdec(substr($_GET["hash"],18,2));

/* size of each sprite */
$spriteZ=128;

/* start with blank 3x3 identicon */
$identicon=imagecreatetruecolor($spriteZ*3,$spriteZ*3);
imageantialias($identicon,TRUE);

/* assign white as background */
$bg=imagecolorallocate($identicon,255,255,255);
imagefilledrectangle($identicon,0,0,$spriteZ,$spriteZ,$bg);

/* generate corner sprites */
$corner=getsprite($csh,$cfr,$cfg,$cfb,$cro);
imagecopy($identicon,$corner,0,0,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,0,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,$spriteZ*2,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,$spriteZ*2,0,0,0,$spriteZ,$spriteZ);

/* generate side sprites */
$side=getsprite($ssh,$sfr,$sfg,$sfb,$sro);
imagecopy($identicon,$side,$spriteZ,0,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,0,$spriteZ,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,$spriteZ,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,$spriteZ*2,$spriteZ,0,0,$spriteZ,$spriteZ);

/* generate center sprite */
$center=getcenter($xsh,$cfr,$cfg,$cfb,$sfr,$sfg,$sfb,$xbg);
imagecopy($identicon,$center,$spriteZ,$spriteZ,0,0,$spriteZ,$spriteZ);

// $identicon=imagerotate($identicon,$angle,$bg);

/* make white transparent */
imagecolortransparent($identicon,$bg);

/* create blank image according to specified dimensions */
$resized=imagecreatetruecolor($_GET["size"],$_GET["size"]);
imageantialias($resized,TRUE);

/* assign white as background */
$bg=imagecolorallocate($resized,255,255,255);
imagefilledrectangle($resized,0,0,$_GET["size"],$_GET["size"],$bg);

/* resize identicon according to specification */
imagecopyresampled($resized,$identicon,0,0,(imagesx($identicon)-$spriteZ*3)/2,(imagesx($identicon)-$spriteZ*3)/2,$_GET["size"],$_GET["size"],$spriteZ*3,$spriteZ*3);

/* make white transparent */
imagecolortransparent($resized,$bg);

/* and finally, send to standard output */
header("Content-Type: image/png");
imagepng($resized);

?>

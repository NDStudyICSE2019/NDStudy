<?php // $Id: phpinfo.lib.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4 foldmethod=marker:

/**
 * CLAROLINE
 *
 * PHP Info utility library.
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Frédéric Minne <zefredz@claroline.net>
 * @package     MAINTENANCE
 */

/**
 * Returns phpinfo without html, head and body tags
 * @return  string
 */
function phpinfoNoHtml()
{
    ob_start();
    phpinfo();
    $content = ob_get_contents();
    ob_end_clean();
    
    return phpinfo_extractContent( $content );
}

/**
 * Returns phpinfo without html, head and body tags
 * @return  string
 */
function phpcreditsNoHtml()
{
    ob_start();
    phpcredits();
    $content = ob_get_contents();
    ob_end_clean();
    
    return phpcredits_extractContent( $content );
}

/**
 * Returns string without html, head and body tags (based on phpinfo output)
 * @return  string
 */
function phpinfo_extractContent( $str )
{
    $contentArr =preg_split( '~(\r\n|\r|\n)~', $str );
    
    $extract = array();
    $startCapture = false;
    
    foreach ( $contentArr as $line )
    {
        $line = trim( $line );
        
        if ( preg_match( '~^\<table~', $line ) )
        {
            $startCapture = true;
            $extract[] = $line;
        }
        elseif ( preg_match( '~\</table\>~', $line ) )
        {
            $startCapture = false;
            $extract[] = $line;
        }
        elseif ( $startCapture && !empty( $line ) )
        {
            $extract[] = $line;
        }
        else
        {
            //skip
        }
    }
    
    $extract = implode( "\n", $extract );
    
    return $extract;
}

/**
 * Returns string without html, head and body tags (based on phpinfo output)
 * @return  string
 */
function phpcredits_extractContent( $str )
{
    $contentArr =preg_split( '~(\r\n|\r|\n)~', $str );
    
    $extract = array();
    $startCapture = false;
    
    foreach ( $contentArr as $line )
    {
        $line = trim( $line );
        
        if ( preg_match( '~\<body~', $line ) )
        {
            $startCapture = true;
        }
        elseif ( preg_match( '~\</body~', $line ) )
        {
            $startCapture = false;
        }
        elseif ( $startCapture && !empty( $line ) )
        {
            $extract[] = $line;
        }
        else
        {
            //skip
        }
    }
    
    $extract = implode( "\n", $extract );
    
    return $extract;
}

/**
 * Get phpinfo style sheet
 * @return  string
 */
function phpinfo_getStyle()
{
    return '<style type="text/css">
.phpInfoContents table {border-collapse: collapse;}
.phpInfoContents .center {text-align: center;}
.phpInfoContents .center table { margin-left: auto; margin-right: auto; text-align: left;}
.phpInfoContents .center th { text-align: center !important; }
.phpInfoContents td, .phpInfoContents th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
.phpInfoContents h1 {font-size: 150%;}
.phpInfoContents h2 {font-size: 125%;}
.phpInfoContents .p {text-align: left;}
.phpInfoContents .e {background-color: #ccccff; font-weight: bold; color: #000000;}
.phpInfoContents .h {background-color: #9999cc; font-weight: bold; color: #000000;}
.phpInfoContents .v {background-color: #cccccc; color: #000000;}
.phpInfoContents .vr {background-color: #cccccc; text-align: right; color: #000000;}
.phpInfoContents img {float: right; border: 0px;}
.phpInfoContents hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}

.phpInfoContents .v-ok {background-color:#009900;color:#ffffff;}
.phpInfoContents .v-notice {background-color:orange;color:#000000;}
.phpInfoContents .v-warn {background-color:#990000;color:#ffffff;}
.phpInfoContents .v-notrun {background-color:#cccccc;color:#000000;}
.phpInfoContents .v-error {background-color:#F6AE15;color:#000000;font-weight:bold;}
</style>';
}

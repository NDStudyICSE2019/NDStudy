<?php // $Id: compat.lib.php 14318 2012-11-09 08:36:34Z zefredz $

/**
 * CLAROLINE
 *
 * PHP COMPAT For PHP backward compatibility.
 *
 * @version     $Revision: 14318 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

/**
 * This lib provide html collection  of compat functions.
 *
 * all these function are prepend by function_exists
 *
 *
 * if (!function_exists('the_function'))
 * {
 *     function the_function($foo)
 *     {
 *        ....
 *        return $bar;
 *     }
 * }
 *
 *
 * @package PHP_Compat
 *
 */

/**
 * Not to developper, in this  lib, try to comment some info about
 * aivailibility of original function
 *
 */


/**
 * Define ctype_digit()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.ctype_digit
 * @since       CLAROLINE 1.8
 * @note        Beginning with PHP 4.2.0 these functions are enabled by default.
 *              For older versions you have to configure and compile PHP
 *              with --enable-ctype.
 *              You can disable ctype support with --disable-ctype.
 */

if (!function_exists('ctype_digit'))
{
    function ctype_digit($var)
    {
        return ((int) $var == $var);
    }
}


/**
 * Replace str_ireplace()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.str_ireplace
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 14318 $
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 * @note        count not by returned by reference, to enable
 *              change '$count = null' to '&$count'
 */
if (!function_exists('str_ireplace')) {
    function str_ireplace($search, $replace, $subject, $count = null)
    {
        // Sanity check
        if (is_string($search) && is_array($replace)) {
            user_error('Array to string conversion', E_USER_NOTICE);
            $replace = (string) $replace;
        }

        // If search isn't an array, make it one
        if (!is_array($search)) {
            $search = array ($search);
        }
        $search = array_values($search);

        // If replace isn't an array, make it one, and pad it to the length of search
        if (!is_array($replace)) {
            $replace_string = $replace;

            $replace = array ();
            for ($i = 0, $c = count($search); $i < $c; $i++) {
                $replace[$i] = $replace_string;
            }
        }
        $replace = array_values($replace);

        // Check the replace array is padded to the correct length
        $length_replace = count($replace);
        $length_search = count($search);
        if ($length_replace < $length_search) {
            for ($i = $length_replace; $i < $length_search; $i++) {
                $replace[$i] = '';
            }
        }

        // If subject is not an array, make it one
        $was_array = false;
        if (!is_array($subject)) {
            $was_array = true;
            $subject = array ($subject);
        }

        // Loop through each subject
        $count = 0;
        foreach ($subject as $subject_key => $subject_value) {
            // Loop through each search
            foreach ($search as $search_key => $search_value) {
                // Split the array into segments, in between each part is our search
                $segments = explode(strtolower($search_value), strtolower($subject_value));

                // The number of replacements done is the number of segments minus the first
                $count += count($segments) - 1;
                $pos = 0;

                // Loop through each segment
                foreach ($segments as $segment_key => $segment_value) {
                    // Replace the lowercase segments with the upper case versions
                    $segments[$segment_key] = substr($subject_value, $pos, strlen($segment_value));
                    // Increase the position relative to the initial string
                    $pos += strlen($segment_value) + strlen($search_value);
                }

                // Put our original string back together
                $subject_value = implode($replace[$search_key], $segments);
            }

            $result[$subject_key] = $subject_value;
        }

        // Check if subject was initially a string and return it as a string
        if ($was_array === true) {
            return $result[0];
        }

        // Otherwise, just return the array
        return $result;
    }
}

/**
 * define file_put_contents()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.file-put-contents.php
 * @since       CLAROLINE 1.8
 * @require     PHP 4.0.0 (user_error)
 * @note
 */

if ( ! function_exists( 'file_put_contents' ) )
{
    if ( !defined( 'FILE_APPEND' ) )
    {
        define( 'FILE_APPEND', 8 );
    }

    function file_put_contents( $file, $content, $flags = null )
    {
        if ( is_array( $content ) )
        {
            $content = implode( '', $content );
        }

        if ( !is_scalar( $content ) )
        {
            trigger_error( 'file_put_contents() The 2nd parameter should be'
            . ' either a string or an array',
            E_USER_WARNING );
            return false;
        }

        if ( FILE_APPEND === $flags )
        {
            $fd = fopen( $file, 'a' );
        }
        else
        {
            $fd = fopen( $file, 'wb' );
        }

        if ( false === $fd )
        {
            return false;
        }
        else
        {
            $nb_bytes = fwrite( $fd, $content );
            fclose( $fd );
            return $nb_bytes;
        }
    }
}

/**
 * define array_intersect_key() of PHP 5
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.file-put-contents.php
 * @since       CLAROLINE 1.8.1
 * @require     PHP 4.0.0 (user_error)
 * @note
 */


if(!function_exists('array_intersect_key'))
{
    function array_intersect_key($array1,$array2)
    {
        $array3=array();
        foreach (array_keys($array2) as $keyToKeep)
        {
            if (array_key_exists($keyToKeep,$array1))
            $array3[$keyToKeep] = $array1[$keyToKeep];
        }
        return $array3;
    }
}


/**
 * Replace scandir()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.scandir
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 14318 $
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_scandir($directory, $sorting_order = 0)
{
    if (!is_string($directory)) {
        user_error('scandir() expects parameter 1 to be string, ' .
            gettype($directory) . ' given', E_USER_WARNING);
        return;
    }

    if (!is_int($sorting_order) && !is_bool($sorting_order)) {
        user_error('scandir() expects parameter 2 to be long, ' .
            gettype($sorting_order) . ' given', E_USER_WARNING);
        return;
    }

    if (!is_dir($directory) || (false === $fh = @opendir($directory))) {
        user_error('scandir() failed to open dir: Invalid argument', E_USER_WARNING);
        return false;
    }

    $files = array ();
    while (false !== ($filename = readdir($fh))) {
        $files[] = $filename;
    }

    closedir($fh);

    if ($sorting_order == 1) {
        rsort($files);
    } else {
        sort($files);
    }

    return $files;

}


// Define
if (!function_exists('scandir')) {
    function scandir($directory, $sorting_order = 0)
    {
        return php_compat_scandir($directory, $sorting_order = 0);
    }
}

if ( !function_exists('htmlspecialchars_decode') )
{
    // for version previous to PHP 5.1.0RC1
    function htmlspecialchars_decode($text)
    {
        return strtr( $text,
            array_flip(
                get_html_translation_table( HTML_SPECIALCHARS ) ) );
    }
}

// Future-friendly json_encode
if( !function_exists('json_encode') ) {
    require_once dirname(__FILE__) . '/thirdparty/JSON.php';
    
    function json_encode($data) {
        $json = new Services_JSON();
        return( $json->encode($data) );
    }
    
    function json_decode($data) {
        $json = new Services_JSON();
        return( $json->decode($data) );
    }
}

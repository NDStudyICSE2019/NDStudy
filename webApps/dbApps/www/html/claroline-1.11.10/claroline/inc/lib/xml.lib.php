<?php // $Id: xml.lib.php 14315 2012-11-08 14:51:17Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 * 
 * @version     1.9
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      claro team <cvs@claroline.net>
 */

/**
 * transform all entities of $string to their hexadecimal representation
 *
 * @param $string string original string to entify
 * @param $quote_style string Constant choosen among ENT_COMPAT, ENT_QUOTES OR ENT_NOQUOTES (see claro_htmlentities doc for more info)
 *
 * @return string entified string
 */
function xmlentities( $string, $quote_style = ENT_QUOTES )
{
    static $trans;

    // remove all html entities before xml encoding
    // must convert all quotes to avoid remaining html entity in code
    $string = claro_html_entity_decode($string, ENT_QUOTES);

    // xml encoding
    if ( ! isset( $trans ) )
    {
        $trans = get_html_translation_table( HTML_ENTITIES, $quote_style );
        foreach ( array_keys($trans) as $key )
        {
            $trans[$key] = '&#'.ord( $key ).';';
        }
        // dont translate the '&' in case it is part of &xxx;
        $trans[chr(38)] = '&';
    }

    // after the initial translation, _do_ map standalone '&' into '&#38;'
    return preg_replace( "/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/"
        , "&#38;"
        , strtr( $string, $trans )
        );
}

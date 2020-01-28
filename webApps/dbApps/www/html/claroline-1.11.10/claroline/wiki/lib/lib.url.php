<?php // $Id: lib.url.php 14093 2012-03-22 10:22:57Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14093 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 *
 * @package Wiki
 */

/**
 * add a GET request variable to the given URL
 * @param string url url
 * @param string name name of the variable
 * @param string value value of the variable
 * @return string url
 */
function add_request_variable_to_url( $url, $name, $value )
{
    $urlObj = new Url( $url );
    $urlObj->addParam( $name, $value );

    return $urlObj->toUrl();
}

/**
 * add a GET request variable list to the given URL
 * @param string url url
 * @param array variableList list of the request variables to add
 * @return string url
 */
function add_request_variable_list_to_url( $url, $variableList )
{
    $urlObj = new Url( $url );
    $urlObj->addParamList( $variableList );

    return $urlObj->toUrl();
}

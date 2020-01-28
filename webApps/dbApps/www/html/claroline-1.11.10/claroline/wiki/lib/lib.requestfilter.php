<?php // $Id: lib.requestfilter.php 14093 2012-03-22 10:22:57Z zefredz $

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
 * Get filtered value for a given variable in request tables based on
 * a regular expression
 *
 * @access public
 * @param varName (string) variable to search in request tables
 * @param regexp (string) regular expression
 * @param order (string) order in which the script passes through tables
 *  g = GET, p = POST, c = COOKIE. Example of valid order string :"gpc"
 *  NB : order is not case sensitive
 * @throw E_USER_ERROR if wrong tables in order string
 * @global _CLEAN
 */
function filter_regexp($varName, $regexp, $order)
{
    global $_CLEAN;

    if (!isset($_CLEAN))
    {
        $_CLEAN = array ();
    }

    $order = strtolower($order);

    $value = false;

    for ($i = 0; $i < strlen($order); $i++)
    {
        $value = filter_regexp_table($varName, $regexp, $order[$i]);

        if ($value != false)
        {
            $_CLEAN[$varName] = $value;
        }
    }

    return $_CLEAN;
}

/**
 * Get filtered value for a given var in a given request table based on a
 * regular expression
 *
 * @access private
 * @param varName (string) variable to search in request tables
 * @param regexp (string) regular expression
 * @param tblName (string) request table filtered by the function
 *  g = GET, p = POST, c = COOKIE
 * @return (string) filtered value
 * @throw E_USER_ERROR if wrong table
 */
function filter_regexp_table($varName, $regexp, $tblName)
{
    $value = false;

    switch ($tblName)
    {
        case 'g' :
            {
                if (isset($_GET[$varName]))
                {
                    $value = $_GET[$varName];
                }
                break;
            }
        case 'p' :
            {
                if (isset($_POST[$varName]))
                {
                    $value = $_POST[$varName];
                }
                break;
            }
        case 'c' :
            {
                if (isset($_COOKIE[$varName]))
                {
                    $value = $_COOKIE[$varName];
                }
                break;
            }
        case 'r' :
            {
                if (isset($_REQUEST[$varName]))
                {
                    $value = $_REQUEST[$varName];
                }
                break;
            }
        default :
            {
                trigger_error("Wrong table in "
                    . __CLASS__ . "->" . __FUNCTION__
                    , E_USER_ERROR
                );
            }
    } // end switch

    if (preg_match($regexp, $value))
    {
        return $value;
    }
    else
    {
        return false;
    }
}

/**
 * Get filtered value for a given var in request tables based on a table
 * of allowed values
 *
 * @access public
 * @param varName (string) variable to search in request tables
 * @param validValuesList (array) array containing allowed values for varName
 * @param order (string) order in which the script passes through tables
 *  g = GET, p = POST, c = COOKIE. Example of valid order string :"gpc"
 *  NB : order is not case sensitive
 * @param case_insensitive (boolean) searches request tables without taking
 *  care of case of value
 * @throw E_USER_ERROR if wrong tables in order string
 * @global _CLEAN
 */
function filter($varName, $validValuesList, $order, $case_insensitive = false)
{
    global $_CLEAN;

    if (!isset($_CLEAN))
    {
        $_CLEAN = array ();
    }

    $order = strtolower($order);

    $value = false;

    for ($i = 0; $i < strlen($order); $i++)
    {
        $value = filter_table($varName, $validValuesList, $order[$i], $case_insensitive);

        if ($value != false)
        {
            $_CLEAN[$varName] = $value;
        }
    }

    return $_CLEAN;
}

/**
 * Get filtered value for a given var in request tables when request var is
 * given by an array key instead of its value.
 *
 * for example if $_GET['foo'] contains array( 'edit' => 'Editer' ) the
 * filter works on 'edit' instead of 'Editer'.
 *
 * This function was added to use request filter with language variables.
 *
 * @access public
 * @since 0.2
 * @param varName (string) variable to search in request tables
 * @param validValuesList (array) array containing allowed values for varName
 * @param order (string) order in which the script passes through tables
 *  g = GET, p = POST, c = COOKIE. Example of valid order string :"gpc"
 *  NB : order is not case sensitive
 * @param case_insensitive (boolean) searches request tables without taking
 *  care of case of value
 * @throw E_USER_ERROR if wrong tables in order string
 */
function filter_by_key($varName, $validValuesList, $order, $case_insensitive = false)
{
    if (isset($_GET[$varName]) && is_array($_GET[$varName]))
    {
        $_GET[$varName] = key($_GET[$varName]);
    }

    if (isset($_POST[$varName]) && is_array($_POST[$varName]))
    {
        $_POST[$varName] = key($_POST[$varName]);
    }

    if (isset($_COOKIE[$varName]) && is_array($_COOKIE[$varName]))
    {
        $_COOKIE[$varName] = key($_COOKIE[$varName]);
    }

    if (isset($_REQUEST[$varName]) && is_array($_REQUEST[$varName]))
    {
        $_REQUEST[$varName] = key($_REQUEST[$varName]);
    }

    return filter($varName, $validValuesList, $order, $case_insensitive);
}

/**
 * Get filtered value for a given var in a given request table based on a
 * table of allowed values
 *
 * @access private
 * @param varName (string) variable to search in request tables
 * @param validValuesList (array) array containing allowed values for varName
 * @param tblName (string) request table filtered by the function
 *  g = GET, p = POST, c = COOKIE
 * @param case_insensitive (boolean) searches request tables without taking
 *  care of case of value
 * @return (string) filtered value
 * @throw E_USER_ERROR if wrong table
 */
function filter_table($varName, $validValuesList, $tblName, $case_insensitive = false)
{
    $value = false;

    switch ($tblName)
    {
        case 'g' :
            {
                if (isset($_GET[$varName]))
                {
                    $value = $_GET[$varName];
                }
                break;
            }
        case 'p' :
            {
                if (isset($_POST[$varName]))
                {
                    $value = $_POST[$varName];
                }
                break;
            }
        case 'c' :
            {
                if (isset($_COOKIE[$varName]))
                {
                    $value = $_COOKIE[$varName];
                }
                break;
            }
        case 'r' :
            {
                if (isset($_REQUEST[$varName]))
                {
                    $value = $_REQUEST[$varName];
                }
                break;
            }
        default :
            {
                trigger_error("Wrong table in "
                    . __CLASS__ . "->" . __FUNCTION__
                    , E_USER_ERROR
                );
            }
    } // end switch

    if ($case_insensitive)
    {
        $value = strtolower($value);
    }

    if (in_array($value, $validValuesList))
    {
        return $value;
    }
    else
    {
        return false;
    }
}

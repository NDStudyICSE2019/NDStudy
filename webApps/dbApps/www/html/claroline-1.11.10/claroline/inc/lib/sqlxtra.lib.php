<?php // $Id: sqlxtra.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *              version 2 or later
 * @author      see 'credits' file
 * @package     KERNEL
 */

/**
 * CLAROLINE mySQL query wrapper. It allows to send multiple query at once to SQl server in a single string
 *
 * @author Guillaume Lederer    <guillaume@claroline.net>,
 * @param  string  $sqlQueries   - the string containing sql queries to apply
 * @param  bool $breakOnFailure  - stop query execution if one query failed (default true)
 * @return true on success, false on failure
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/extra.lib.php instead
 */
function claro_sql_multi_query( $sqlQueries, $breakOnFailure = true )
{
   $queryArray = PMA_splitSqlFile( $sqlQueries );
   
   foreach ($queryArray as $theQuery)
    {
        if (!$theQuery['empty'])
        {
            if ( true === $breakOnFailure
                && false === claro_sql_query($theQuery['query'] ) )
            {
                return false;
            }
        }
    }
    
   return true;
}

/**
 * FUNCTION TAKEN FROM PHPMYADMIN TO ALLOW MULTIPLE SQL QUERIES AT ONCE
 * Removes comment lines and splits up large sql files into individual queries
 *
 * Last revision: September 23, 2001 - gandon
 * @param   string  $sql the sql commands
 * @return  array   the splitted queries
 * @access  public
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/extra.lib.php instead
 */
function PMA_splitSqlFile( $sql )
{
    $ret = array();
    // do not trim, see bug #1030644
    //$sql          = trim($sql);
    $sql          = rtrim($sql, "\n\r");
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $nothing      = TRUE;
    for ($i = 0; $i < $sql_len; ++$i)
    {
        $char = $sql[$i];
        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string)
        {
            for (;;)
            {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i)
                {
                    $ret[] = array('query' => $sql, 'empty' => $nothing);
                    return $ret;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\')
                {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else
                {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash)
                    {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else
                    {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)
        
        // lets skip comments (/*, -- and #)
        else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ')
            || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*'))
        {
            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === FALSE)
            {
                break;
            }
            if ($char == '/') $i++;
        }
        
        // We are not in a string, first check for delimiter...
        else if ($char == ';')
        {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing    = TRUE;
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len)
            {
                $i      = -1;
            }
            else
            {
                // The submited statement(s) end(s) here
                return $ret;
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`'))
        {
            $in_string    = TRUE;
            $nothing      = FALSE;
            $string_start = $char;
        } // end else if (is start of string)
        elseif ($nothing)
        {
            $nothing = FALSE;
        }
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql))
    {
        $ret[] = array('query' => $sql, 'empty' => $nothing);
    }

    return $ret;
}

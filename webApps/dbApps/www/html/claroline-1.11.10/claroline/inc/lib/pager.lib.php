<?php // $Id: pager.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * Pager class allowing to manage the paging system into claroline
 *
 * example : $myPager = new claro_pager($totalItemCount, $offset, $step);
 *           $myPager->set_pager_call_param_name('myOffset') // optionnal
 *           echo $myPager->disp_pager_tool_bar();
 *           
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 * @since 1.6
 */

class claro_pager
{
    var $offsetCount = null;

    /**
     * Constructor
     *
     * @param string $sql current SQL query
     * @param int $offset requested offset
     * @param int $step current step paging
     * @return void
     * @since
     */

    function claro_pager($totalItemCount, $offset = 0, $step = 20)
    {
        $this->offset         = (int) $offset;
        $this->step           = (int) $step;
        $this->totalItemCount = (int) $totalItemCount;
        $this->set_pager_call_param_name('offset');
    }

    /**
     * Allows to change the parameter name in the url for page change request.
     * By default, this parameter name is 'offset'.
     * @param string paramName
     * @return null
     * @since
     */

    function set_pager_call_param_name($paramName)
    {
        $this->pagerParamName = $paramName;
    }

    /**
     * get the total number of the complete the results
     * @return int
     * @since
     */

    function get_total_item_count()
    {
       return $this->totalItemCount;
    }

    /**
     * get the number of offsets needed to build a complete pager
     * @return int
     */

    function get_offset_count()
    {
        if ( ! $this->offsetCount )
        {
            $this->offsetCount = ceil( $this->get_total_item_count() / $this->step );
        }

        return $this->offsetCount;
    }

    /**
     * return the offset needed to get the previous page
     *
     * @return int
     */

    function get_previous_offset()
    {
        $previousOffset = $this->offset - $this->step;

        if ($previousOffset >= 0) return $previousOffset;
        else                      return false;
    }

    /**
     * return the offset needed to get the next page
     *
     * @return int
     */

    function get_next_offset()
    {
        $nextOffset = $this->offset + $this->step;

        if ($nextOffset < $this->get_total_item_count() ) return $nextOffset;
        else                                              return false;
    }

    /**
     * return the offset needed to get the first page
     *
     * @return int
     */

    function get_first_offset()
    {
        return 0;
    }

    /**
     * return the offset needed to get the last page
     *
     * @return int
     */

    function get_last_offset()
    {
        return (int)($this->get_offset_count() - 1) * $this->step;
    }

    /**
     * return the offsets list needed for each page
     *
     * @return array of int
     */

    function get_offset_list()
    {

        $offsetList = array();

        for ($i = 0, $currentOffset = 0, $offsetCount = $this->get_offset_count();
             $i < $offsetCount;
             $i ++)
        {
            $offsetList [] = $currentOffset;
            $currentOffset = $currentOffset + $this->step;
        }

        return $offsetList;
    }


    /**
     * Display a standart pager tool bar
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @param  string $url - where the pager tool bar commands need to point to
     * @param  int $linkMax - (optionnal) maximum of page links in the pager tool bar
     * @return string
     */

    function disp_pager_tool_bar($url, $linkMax = 10)
    {
        $startPage    = $this->get_first_offset();
        $previousPage = $this->get_previous_offset();
        $pageList     = $this->get_offset_list();
        $nextPage     = $this->get_next_offset();
        $endPage      = $this->get_last_offset();

        if ( strrpos($url, '?') === false) $url .= '?'    .$this->pagerParamName.'=';
        else                               $url .= '&amp;'.$this->pagerParamName.'=';


        $output = "\n\n"
        . '<div class="claroPager">' . "\n"
        . '<span class="pagerBefore">' . "\n"
        ;

        if ($previousPage !== false)
        {
            $output .= '<b>'
            . '<a href="' . $url . $startPage . '">'.claro_html_icon('pager_first').'</a>&nbsp;'
            . '<a href="' . $url . $previousPage . '">'.claro_html_icon('pager_previous').'</a>'
            . '</b>'
            ;
        }
        else
        {
            $output .= '&nbsp;';
        }

        $output .= "\n"
        . '</span>' . "\n"
        . '<span class="pagerPages">' . "\n"
        ;

        // current page
        $currentPage = (int) $this->offset / $this->step ;

        // total page
        $pageCount = $this->get_offset_count();

        // start page
        if ( $currentPage > $linkMax ) $firstLink = $currentPage - $linkMax;
        else                           $firstLink = 0;

        // end page
        if ( $currentPage + $linkMax < $pageCount ) $lastLink = $currentPage + $linkMax;
        else                                        $lastLink = $pageCount;

        // display 1 ... {start_page}

        if ( $firstLink > 0 )
        {
            $output .= '<a href="' . $url . $pageList[0] . '">' . (0+1) . '</a>&nbsp;';
            if ( $firstLink > 1 ) $output .= '...&nbsp;';
        }

        if ( $pageCount > 1)
        {
            // display page
            for ($link = $firstLink; $link < $lastLink ; $link++)
            {
                if ( $currentPage == $link )
                {
                    $output .= '<b>' . ($link + 1) . '</b> '; // current page
                }
                else
                {
                    $output .= '<a href="' . $url . $pageList[$link] . '">' . ($link + 1) . '</a> ';
                }
            }
        }

        // display 1 ... {start_page}
        if ( $lastLink < $pageCount )
        {
            if ( $lastLink + 1 < $pageCount ) $output .= '...';

            $output .= '&nbsp;<a href="'. $url . $pageList[$pageCount-1] . '">'.($pageCount).'</a>';
        }

        $output .= "\n"
        . '</span>'. "\n"
        . '<span class="pagerAfter">'. "\n"
        ;

        if ($nextPage !== false)
        {
            $output .= '<b>'
            . '<a href="' . $url . $nextPage . '">'.claro_html_icon('pager_next').'</a>&nbsp;'
            . '<a href="' . $url . $endPage  . '">'.claro_html_icon('pager_last').'</a>'
            . '</b>'
            ;
        }
        else
        {
            $output .= '&nbsp;';
        }

        $output .= "\n"
        . '</span>' ."\n"
        . '</div>' ."\n\n"
        ;

        return $output;
    }
}


//////////////////////////////////////////////////////////////////////////////


/**
 * Pager class allowing to manage a paging system from a simple SQL query
 *
 *  example 1 : $myPager = new claro_sql_pager('SELECT * FROM USER', $offset, $step);
 *
 *            echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);
 *
 *            $resultList = $myPager->get_result_list();
 *
 *            echo '<table>';
 *
 *            foreach($resultList as $thisresult)
 *            {
 *              echo '<tr><td>$thisresult[...]</td></tr>';
 *            }
 *
 *            echo '</table>';
 *
 * The
 *  example 2 :
 *
 *            $myPager = new claro_sql_pager('SELECT * FROM USER', $offset, $step);
 *
 *            $myPager->set_sort('column_1', SORT_DESC);
 *
 *            echo $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);
 *
 *            echo '<table>';
 *
 *            $sortUrlList = $myPager->get_sort_url_list($_SERVER['PHP_SELF']);
 *
 *            echo '<tr>';
 *
 *            foreach ($sortUrlList as $thisColName => $thisColUrl)
 *            {
 *              echo '<th>< a href="'.thisColUrl.'">' . $thisColName . '</th>';
 *            }
 *
 *            echo '</tr>';
 *
 *            $resultList = $myPager->get_result_list();
 *
 *            foreach($resultList as $thisresult)
 *            {
 *              echo '<tr><td>$thisresult[...]</td></tr>';
 *            }
 *
 *            echo '</table>';
 *
 *
 *
 * Note : The pager will request page change by the $_GET['offset'] variable
 * If it conflicts with other variable you can change this name with the
 * set_pager_call_param_name($paramName) method.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 */

class claro_sql_pager extends claro_pager // implements sortable
{
    var $sortKeyList = array(),
        $totalItemCount = null ,  $offsetCount = null  ,
        $resultList       = null;

    /**
     * Constructor
     *
     * @param string $sql current SQL query
     * @param int $offset requested offset
     * @param int $step current step paging
     */

    function claro_sql_pager($sql, $offset = 0, $step = 20)
    {
        $this->sql       = trim($sql);
        $this->offset    = max(0,(int) $offset);
        $this->step      = (int) $step;
        $this->set_pager_call_param_name('offset');
        $this->set_sort_key_call_param_name('sort');
        $this->set_sort_dir_call_param_name('dir');
    }

    /**
     * Allows to change the parameter name in the url for sort key request.
     * By default, this parameter name is 'sort'.
     * @param string paramName
     */

    function set_sort_key_call_param_name($paramName)
    {
        $this->sortKeyParamName = $paramName;
    }

    /**
     * Allows to change the parameter name in the url for sort direction
     * request. By default, this parameter name is 'dir'.
     * @param string paramName
     */

    function set_sort_dir_call_param_name($paramName)
    {
        $this->sortDirParamName = $paramName;
    }

    /**
     * Add incrementaly sort key to the sorting.
     *
     * @param  string sort key
     * @param  int direction (use PHP constant SORT_ASC, SORT_DESC)
     * @return boolean true if it suceeds, false otherwise (it probably means
     *                 that the key is already set in the sort sequence
     */

    function add_sort_key($key, $direction)
    {
         if ($this->resultList)
              claro_die('add_sort_key() IMPOSSIBLE : QUERY ALREADY COMMITED TO DATABASE SERVER.');

        if ( ! array_key_exists($key, $this->sortKeyList) )
        {
            $this->sortKeyList[$key] = $direction;
            return true;
        }

        return false;
    }

    /**
     * Set a specificic sorting for the result returned by the query.
     * Note. If a previous sorting was set, this function erase it and reset
     * a new one
     *
     * @param string $key - has to be something understable by the SQL parser.
     * @param string $direction use PHP constants SORT_ASC and SORT_DESC
     */

    function set_sort_key($key, $direction)
    {
        $this->set_multiple_sort_keys( array($key => $direction) );
    }

    /**
     * Set multiple sorting for the result returned by the query.
     * Note. If a previous sorting was set, this function erase it and reste
     * a new one.
     *
     * @param array $keyList - each array key are the sort keys
     *        it has to be something understable by the SQL parser.
     *        while each array values are sort direction of the concerned key
     */

    function set_multiple_sort_keys($keyList)
    {
        $this->sortKeyList = array(); // reset the sort key list
        $this->sortKeyList = $keyList;
    }


    /**
     * Rewrite the SQL query to allowing paging. It adds LIMIT parameter to the
     * end of the query end SQL_CALC_FOUND_ROWS between the SELECT statement
     * and the column list
     *
     * @access private
     * @param  string $sql current SQL query
     * @param  int $offset requested offset
     * @param int $step current step paging
     * @return string the rewrote query
     */

    function _get_prepared_query($sql, $offset, $step, $sortKeyList)
    {
        if ( count($sortKeyList) > 0 )
        {
            $orderByList = array();
            foreach( $sortKeyList as $thisSortKey => $thisSortDirection)
            {
                if     ( $thisSortDirection == SORT_DESC) $direction = 'DESC';
                elseif ( $thisSortDirection == SORT_ASC ) $direction = 'ASC';
                else                                      $direction = '';

                $orderByList[] = claro_sql_escape($thisSortKey) . ' ' . $direction ;
            }

            $sql .= "\n\t" . 'ORDER BY '. implode(', ', $orderByList) ;
        }

        if ( $step > 0 )
        {
            // Include SQL_CALC_FOUND_ROWS inside the query
            // This mySQL clause permit to know how many rows the statement
            // would have returned with no LIMIT clause, without running the
            // statement again. To retrieve this rows count, one invokes
            // FOUND_ROWS() afterward (see get_total_result_count method).

            $sql = substr_replace ($sql, 'SELECT SQL_CALC_FOUND_ROWS ',
                                  0   , strlen('SELECT '))
                   . "\n\t" . ' LIMIT ' . $offset . ', ' . $step;
        }

        return $sql;
    }

    /**
     * Trig the execution of the SQL queries
     *
     * @access private
     */

    function _execute_pager_queries()
    {
        $preparedQuery = $this->_get_prepared_query($this->sql,
                                                   $this->offset, $this->step,
                                                   $this->sortKeyList);

       $this->resultList        = claro_sql_query_fetch_all( $preparedQuery );

       // The query below has to be executed immediateley after the previous one.
       // Otherwise other potential queries could impair the reliability
       // of mySQL FOUND_ROWS() function.

       $this->totalItemCount  = claro_sql_query_get_single_value('SELECT FOUND_ROWS()');
    }


    /**
     * get the total number of the complete the results
     * @return int
     */

    function get_total_item_count()
    {
       if ( ! $this->totalItemCount ) $this->_execute_pager_queries();

       return $this->totalItemCount;
    }

    /**
     * return the result of the SQL query exectued into the constructor
     *
     * @return string
     */

    function get_result_list()
    {
        if ( ! $this->resultList ) $this->_execute_pager_queries();

        return $this->resultList;
    }

    /**
     * returns prepared url able to require sorting for each column
     * of the pager results
     *
     * @param  string $url
     * @return array
     */

    function get_sort_url_list($url, $context=null)
    {
        $urlList        = array();
        $sortArgList    = array();

        if ( count($this->get_result_list() ) )
        {
            list($firstResultRow) = $this->get_result_list();
            $sortArgList          = array_keys($firstResultRow);
        }
        else
        {
            $sortArgList = claro_sql_field_names($this->sql);
        }

        foreach($sortArgList as $thisArg)
        {
            if (   array_key_exists($thisArg, $this->sortKeyList)
                && $this->sortKeyList[$thisArg] != SORT_DESC)
            {
                $direction = SORT_DESC;
            }
            else
            {
                $direction = SORT_ASC;
            }

            $urlList[$thisArg] = $url
                       . ( ( strstr($url, '?') !== false ) ? '&amp;' : '?' )
                       . $this->sortKeyParamName . '=' . urlencode($thisArg)
                       . '&amp;' . $this->sortDirParamName . '=' . $direction
                       . claro_url_relay_context('&amp;',$context)
                       ;
        }

        return $urlList;
    }

    /**
     * Display a standart pager tool bar
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @param  string $url - where the pager tool bar commands need to point to
     * @param  int $linkMax - (optionnal) maximum of page links in the pager tool bar
     * @return string
     */

    function disp_pager_tool_bar($url, $linkMax = 10)
    {

        if ( count($this->sortKeyList) > 0 )
        {
            // Add optionnal sorting calls.
            // IT KEEPS ONLY THE FIRST SORT KEY !

            reset($this->sortKeyList);
            list($sortKey, $sortDir) = each($this->sortKeyList);

            $url .= ( ( strrpos($url, '?') === false) ? '?' : '&amp;')
                 .  $this->sortKeyParamName.'=' . urlencode($sortKey)
                 .  '&amp;'.$this->sortDirParamName.'=' . $sortDir;
        }

        return parent::disp_pager_tool_bar($url, $linkMax);
    }
}


//////////////////////////////////////////////////////////////////////////////

/**
 * Pager class allowing to manage a paging system from a an array containing
 * all the concerned items
 */

class claro_array_pager extends claro_pager
{
    var $sortKeyList    = array(),
        $totalItemCount = null ,  $offsetCount = null  ,
        $resultList     = null;

    /**
     * constructor
     */

    function claro_array_pager($array, $offset = 0, $step = 20)
    {
        $this->baseArray = $array;
        parent::claro_pager( count($array), $offset, $step);
        $this->set_sort_key_call_param_name('sort');
        $this->set_sort_dir_call_param_name('dir');
    }

    /**
     * Allows to change the parameter name in the url for sort key request.
     * By default, this parameter name is 'sort'.
     * @param string paramName
     */

    function set_sort_key_call_param_name($paramName)
    {
        $this->sortKeyParamName = $paramName;
    }

    /**
     * Allows to change the parameter name in the url for sort direction
     * request. By default, this parameter name is 'dir'.
     * @param string paramName
     */

    function set_sort_dir_call_param_name($paramName)
    {
        $this->sortDirParamName = $paramName;
    }

    function get_result_list()
    {
        if ( ! $this->resultList )
        {
            if ( count($this->sortKeyList) > 0 )
            {
                usort($this->baseArray, array( &$this, 'compare_array_rows') );
            }

            $this->resultList = array_slice($this->baseArray, $this->offset, $this->step);
        }

        return $this->resultList;
    }

    /**
     * This method is dedicated to the usort() process
     * into get_result_list() method
     * @access private
     */

    function compare_array_rows($row1, $row2)
    {
        foreach($this->sortKeyList as $thisSortKey => $thisSortDir)
        {
            $direction = ($thisSortDir == SORT_ASC) ? 1 : -1;

            $result = $direction * (int) strnatcasecmp($row1[$thisSortKey], $row2[$thisSortKey]);

            if ($result != 0) return $result;
        }
        // return ???? ?
        // TODO ADD a return
    }

    /**
     * Set a specificic sorting for the result returned by the query.
     *
     * @param string $key - has to be something understable by the SQL parser.
     * @param string $direction use PHP constants SORT_ASC and SORT_DESC
     */

    function set_sort_key($key, $direction)
    {
        $this->set_multiple_sort_keys( array($key => $direction) );
    }

    /**
     * Set multiple sorting for the result returned by the query.
     *
     * @param array $keyList - each array key are the sort keys
     *        it has to be something understable by the SQL parser.
     *        while each array values are sort direction of the concerned key
     */

    function set_multiple_sort_keys($keyList)
    {
        $this->sortKeyList = array(); // reset the sort key list
        $this->sortKeyList = $keyList;
    }

    function add_sort_key($key, $direction)
    {
         if ($this->resultList)
              claro_die('add_sort_key() IMPOSSIBLE : SORT ALREADY PROCESSED.');

        if ( ! array_key_exists($key, $this->sortKeyList) )
        {
            $this->sortKeyList[$key] = $direction;
            return true;
        }

        return false;
    }

    function get_sort_url_list($url, $defaultArrayKeyList = array() , $context=null)
    {
        $urlList        = array();
        $sortArgList    = array();

        if ( count($this->get_result_list() ) )
        {
            list($firstResultRow) = $this->get_result_list();
            $sortArgList          = array_keys($firstResultRow);
        }
        else
        {
             $sortArgList = $defaultArrayKeyList;
        }

        foreach($sortArgList as $thisArg)
        {
            if (   array_key_exists($thisArg, $this->sortKeyList)
                && $this->sortKeyList[$thisArg] != SORT_DESC)
            {
                $direction = SORT_DESC;
            }
            else
            {
                $direction = SORT_ASC;
            }

            $urlList[$thisArg] = $url
                       . ( ( strstr($url, '?') !== false ) ? '&amp;' : '?' )
                       . $this->sortKeyParamName . '=' . urlencode($thisArg)
                       . '&amp;' . $this->sortDirParamName . '=' . $direction
                       . claro_url_relay_context('&amp;',$context);
        }

        return $urlList;
    }

    /**
     * Display a standart pager tool bar
     *
     * @param  string $url - where the pager tool bar commands need to point to
     * @param  int $linkMax - (optionnal) maximum of page links in the pager tool bar
     * @return string
     */

    function disp_pager_tool_bar($url, $linkMax = 10)
    {

        if ( count($this->sortKeyList) > 0 )
        {
            // Add optionnal sorting calls.
            // IT KEEPS ONLY THE FIRST SORT KEY !

            reset($this->sortKeyList);
            list($sortKey, $sortDir) = each($this->sortKeyList);

            $url .= ( ( strrpos($url, '?') === false) ? '?' : '&amp;')
                 .  $this->sortKeyParamName.'=' . urlencode($sortKey)
                 .  '&amp;'.$this->sortDirParamName.'=' . $sortDir;
        }

        return parent::disp_pager_tool_bar($url, $linkMax);
    }
}


//////////////////////////////////////////////////////////////////////////////

/**
 * Pager class allowing to manage a paging system from a any object containing
 * provided this object implment the get_total_item_count() method
 */

class claro_object_pager extends claro_pager
{
    /**
     * constructor
     */

    function claro_object_pager( &$object, $offset = 0, $step = 20)
    {
        $this->baseObject = & $object;

        parent::claro_pager( $this->baseObject->get_total_item_count(), $offset, $step);
    }
}


//////////////////////////////////////////////////////////////////////////////

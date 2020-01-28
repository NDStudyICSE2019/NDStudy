<?php // $Id: tools.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * some function used for internal messaging system
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


    /**
     * create a argument string for a link
     *
     * @param array of string $paramList array of all argument
     * @param array of string $without array of argument name to don't add to the link
     * @return string arguments of the url
     */
    function makeArgLink($paramList,$without = array())
    {
        $argString = "";
        
        foreach ($paramList as $key => $arg)
        {
            if (!in_array($key, $without))
            {
                if ($argString != "")
                {
                    $argString .= "&amp;";
                }
                $argString .= $key."=".rawurlencode($arg);
            }
        }
    
        return $argString;
    }
    
    /**
     * return the HTML source for the menu bar (to navigate between message box)
     *
     * @param int $currentUserId user identification (the admin read message box of an other user)
     * @return string HTML source
     */
    function getBarMessageBox( $currentUserId, $currentSection )
    {
        require_once dirname(__FILE__) . '/messagebox/inbox.lib.php';
        
        $inboxWithoutFilter = new InBox($currentUserId);

        $sectionList = array(
            'inbox' => get_lang(get_lang('Inbox').'('.$inboxWithoutFilter->numberOfUnreadMessage().')'),
            'outbox' => get_lang('Outbox'),
            'trashbox' => get_lang('Trashbox')
        );
        
        if ( !in_array( $currentSection, array_keys( $sectionList ) ) )
        {
            $currentSection = 'inbox';
        }
        
        $parameter = array();
        
        if (isset($_REQUEST['userId']))
        {
            $parameter['userId'] = (int)$_REQUEST['userId'];
        }
        
        return claro_html_tab_bar($sectionList,$currentSection, $parameter, 'box', get_path('clarolineRepositoryWeb') . "messaging/messagebox.php");
    }
    
    /**
     * return true if the user in parameter is admin, false is the user in parameter is not admin
     *
     * @param int $userId
     * @return bool true if the user is admin
     *                 false if the user is not admin
     */
    function claro_is_user_platform_admin($userId)
    {
        static $uidAdmin = false;

        require_once get_path('incRepositorySys') . '/lib/user.lib.php';

        if ( ! $uidAdmin )
        {
            $uidAdmin = claro_get_uid_of_platform_admin();
        }
        
        return (in_array($userId,$uidAdmin));
    }
    
    /**
     * return true if the user in parameter is manager of the course in 2nd parameters
     *
     * @param int $userId user id
     * @param string $courseCode syscode du cours
     * @return boolean true if the user is manager of the course
     *                    false if the user is not manager of the course
     */
    function claro_is_user_course_manager($userId,$courseCode)
    {
        $tableName = get_module_main_tbl(array('rel_course_user'));
        
        $sql = "SELECT count(*)"
            ." FROM `".$tableName['rel_course_user']."`"
            ." WHERE code_cours = '" . claro_sql_escape($courseCode) . "'"
            ." AND user_id = " . (int)$userId
            ." AND isCourseManager = 1"
        ;
        
        return ( claro_sql_query_fetch_single_value($sql) > 0 );
    }

    /**
     * return the pager
     *
     * @param string $link link of the page to display(without page=x)
     * @param int $currentPage current page
     * @param int $totalPage number of page
     * @return string HTML source of the pager
     */
    function getPager($link,$currentPage,$totalPage)
    {
        // number of page to display in the page before and after thecurrent page
        $nbPageToDisplayBeforeAndAfterCurrentPage = 10;        
        
        $content = '<div id="im_paging">';
        
        // prepare list of page
        $beginPager = max(array(1,$currentPage-$nbPageToDisplayBeforeAndAfterCurrentPage));
        $endPager = min(array($totalPage,$currentPage+$nbPageToDisplayBeforeAndAfterCurrentPage));
        
        if ($beginPager != 1)
        {
            $content .= '&nbsp;<a href="'.$link.'1">1</a>'."\n";
            if ($beginPager-1 != 1)
            {
                $content .= '...'."\n";
            }
        }
        
        for ($countPage = $beginPager; $countPage <= $endPager; $countPage++)
        {
            if ($countPage == $currentPage)
            {
                $content .= $countPage."\n";
            }
            else
            {
                $content .= '<a href="'.$link.$countPage.'">'.$countPage.'</a>'."\n";
            }
        }
        if ($endPager != $totalPage)
        {
            if ($endPager+1 != $totalPage)
            {
                $content .= '...'."\n";
            }
            
            $content .= '<a href="'.$link.$totalPage.'">'.$totalPage.'</a>'."\n";
            
        }
        $content .= '</div>';
        
        return $content;
    }
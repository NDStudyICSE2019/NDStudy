<?php // $Id: defaultTrackingRenderer.class.php 14314 2012-11-07 09:09:19Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.9 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLTRACK
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

/*
 *
 */
class CLTRACK_CourseAccess extends CourseTrackingRenderer
{
    private $tbl_user;
    private $tbl_rel_course_user;
    private $tbl_course_tracking_event;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;

        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tbl_user = $tbl_mdb_names['user'];
        $this->tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
    }

    protected function renderHeader()
    {
        return get_lang('Users access to course');
    }

    protected function renderContent()
    {
        $html = '';
        $html .= '<ul>' . "\n";

        //-- Total access
        $sql = "SELECT count(*)
                  FROM `".$this->tbl_course_tracking_event."`
                 WHERE `type` = 'course_access'";
        $count = claro_sql_query_get_single_value($sql);
        $html .= '<li>' . get_lang('Total').' : '.$count.'</li>'."\n";

        // last 31 days
        $sql = "SELECT count(*)
                  FROM `".$this->tbl_course_tracking_event."`
                 WHERE `type` = 'course_access'
                   AND `date` > DATE_ADD(CURDATE(), INTERVAL -31 DAY)";
        $count = claro_sql_query_get_single_value($sql);
		if ($count)
		{
			$html .= '<li><a href="#" class="showDetailsLast31Days" id="last31DaysDetails">' . get_lang('Last 31 days').' : '.$count.'</a></li>'."\n";
			$html .= '<div id="trackDetailsLast31Days" class="hidden">' . "\n" . '<blockquote>';
	        //-- students connected last week
	        $sql = "SELECT U.`user_id`, U.`nom` AS `lastname`, U.`prenom` AS `firstname`, MAX(CTE.`date`) AS `last_access_date`
	            FROM `".$this->tbl_user."` AS U, `".$this->tbl_rel_course_user."` AS CU
	            LEFT JOIN `".$this->tbl_course_tracking_event."` AS `CTE`
	            ON `CTE`.`user_id` = CU.`user_id`
	            WHERE U.`user_id` = CU.`user_id`
	            AND CU.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'
	            GROUP BY U.`user_id`
	            HAVING  `last_access_date` > ( NOW() - INTERVAL 31 DAY )
	            ORDER BY `lastname`, `firstname`";
	        $html .=  get_lang('Students connected since last month:');
			$results = claro_sql_query_fetch_all($sql);
			$html .= '<ul>'."\n";
            foreach( $results as $result )
            {
                $html .= '<li>'
                .   '<a href="../user/userInfo.php?uInfo='.$result['user_id'].'">'
                .   $result['firstname'].' '.$result['lastname']
                .   '</a> ';

                if( is_null($result['last_access_date']) )
                {
                    $html .= '( <b>'.get_lang('Never connected').'</b> )';
                }
                else
                {
                    $html .= '( '.get_lang('Last access').' : '.$result['last_access_date'].' )';
                }

                $html .= '</li>'."\n";
            }
            $html .= '</ul>' . "\n"
            		. '</blockquote>' . "\n"
            		.'</div>';
		}
		else
		{
			 $html .= '<li>' . get_lang('Last 31 days').' : '.$count.'</li>'."\n";
		}

        // last week
        $sql = "SELECT count(*)
                  FROM `".$this->tbl_course_tracking_event."`
                 WHERE `type` = 'course_access'
                   AND `date` > ( NOW() - INTERVAL 8 DAY )";
        $count = claro_sql_query_get_single_value($sql);
        $html .= '<li><a href="#" class="showDetailsLastWeek" id="lastWeekDetails">' . get_lang('Last week').' : '.$count . '</a></li>'."\n";

        $html .= '<div id="trackDetailsLastWeek" class="hidden">' . "\n" . '<blockquote>';
        //-- students connected last week
        $sql = "SELECT U.`user_id`, U.`nom` AS `lastname`, U.`prenom` AS `firstname`, MAX(CTE.`date`) AS `last_access_date`
            FROM `".$this->tbl_user."` AS U, `".$this->tbl_rel_course_user."` AS CU
            LEFT JOIN `".$this->tbl_course_tracking_event."` AS `CTE`
            ON `CTE`.`user_id` = CU.`user_id`
            WHERE U.`user_id` = CU.`user_id`
            AND CU.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'
            GROUP BY U.`user_id`
            HAVING  `last_access_date` > ( NOW() - INTERVAL 8 DAY )
            ORDER BY `lastname`, `firstname`";
        $html .=  get_lang('Students connected since last week:');

        $results = claro_sql_query_fetch_all($sql);
        if( !empty($results) && is_array($results) )
        {
            $html .= '<ul>'."\n";
            foreach( $results as $result )
            {
                $html .= '<li>'
                .   '<a href="../user/userInfo.php?uInfo='.$result['user_id'].'">'
                .   $result['firstname'].' '.$result['lastname']
                .   '</a> ';

                if( is_null($result['last_access_date']) )
                {
                    $html .= '( <b>'.get_lang('Never connected').'</b> )';
                }
                else
                {
                    $html .= '( '.get_lang('Last access').' : '.$result['last_access_date'].' )';
                }

                $html .= '</li>'."\n";
            }
            $html .= '</ul>' . "\n";
        }
        else
        {
            $html .= ' <small>'.get_lang('No result').'</small><br />'."\n";
        }
        $html .= '</blockquote>' . "\n" . '</div>';

        // today
        $sql = "SELECT count(*)
                  FROM `".$this->tbl_course_tracking_event."`
                 WHERE `type` = 'course_access'
                   AND `date` > CURDATE()";
        $count = claro_sql_query_get_single_value($sql);
        $html .= '<li><a href="#" class="showDetailsToday" id="todayDetails">' . get_lang('Today').' : '.$count . '</a></li>'."\n";
        //-- students connected today

        $html .= '<div id="trackDetailsToday" class="hidden">' . "\n"
        		. '<blockquote>';
        $sql = "SELECT U.`user_id`, U.`nom` AS `lastname`, U.`prenom` AS `firstname`, MAX(CTE.`date`) AS `last_access_date`
            FROM `".$this->tbl_user."` AS U, `".$this->tbl_rel_course_user."` AS CU
            LEFT JOIN `".$this->tbl_course_tracking_event."` AS `CTE`
            ON `CTE`.`user_id` = CU.`user_id`
            WHERE U.`user_id` = CU.`user_id`
            AND CU.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'
            GROUP BY U.`user_id`
            HAVING  `last_access_date` > CURDATE()
             ORDER BY `lastname`, `firstname`";
        $html .=  get_lang('Students connected today:');
        $results = claro_sql_query_fetch_all($sql);
        if( !empty($results) && is_array($results) )
        {
            $html .= '<ul>'."\n";
            foreach( $results as $result )
            {
                $html .= '<li>'
                .   '<a href="../user/userInfo.php?uInfo='.$result['user_id'].'">'
                .   $result['firstname'].' '.$result['lastname']
                .   '</a> ';

                if( is_null($result['last_access_date']) )
                {
                    $html .= '( <b>'.get_lang('Never connected').'</b> )';
                }
                else
                {
                    $html .= '( '.get_lang('Last access').' : '.$result['last_access_date'].' )';
                }

                $html .= '</li>'."\n";
            }
            $html .= '</ul>' . "\n";
        }
        else
        {
            $html .= ' <small>'.get_lang('No result').'</small><br />'."\n";
        }
        $html .= '</blockquote>' . "\n" . '</div>';

        //-- students not connected for more than 1/2 month
        $sql = "SELECT  U.`user_id`, U.`nom` AS `lastname`, U.`prenom` AS `firstname`, MAX(CTE.`date`) AS `last_access_date`
            FROM `".$this->tbl_user."` AS U, `".$this->tbl_rel_course_user."` AS CU
            LEFT JOIN `".$this->tbl_course_tracking_event."` AS `CTE`
            ON `CTE`.`user_id` = CU.`user_id`
            WHERE U.`user_id` = CU.`user_id`
            AND CU.`code_cours` = '" . claro_sql_escape(claro_get_current_course_id()) . "'
            GROUP BY U.`user_id`
            HAVING  `last_access_date` IS NULL
                OR  `last_access_date` < ( NOW() - INTERVAL 15 DAY )
             ORDER BY `lastname`, `firstname`";
        $results = claro_sql_query_fetch_all($sql);

        if( !empty($results) && is_array($results) )
        {
        	$html .= '<li><a href="#" id="noTrack" class="showNoTrackDetails">' . get_lang('Not recently connected students :');
			$html .= sizeof($results) . '</a>';
			$html .= '<div id="noTrackDetails" class="hidden">' . "\n"
        		. '<blockquote>';
            $html .= '<ul>'."\n";
            foreach( $results as $result )
            {
                $html .= '<li>'
                .   '<a href="../user/userInfo.php?uInfo='.$result['user_id'].'">'
                .   $result['firstname'].' '.$result['lastname']
                .   '</a> ';

                if( is_null($result['last_access_date']) )
                {
                    $html .= '( <b>'.get_lang('Never connected').'</b> )';
                }
                else
                {
                    $html .= '( '.get_lang('Last access').' : '.$result['last_access_date'].' )';
                }

                $html .= '</li>'."\n";
            }
            $html .= '</ul>' . "\n"
            	. '</blockquote>';
        }
        else
        {
        	  $html .= '<li>' . get_lang('Not recently connected students :');
            $html .= ' <small>'.get_lang('No result').'</small><br />'."\n";
        }
        $html .= '</li>' . "\n";

        $html .= '<li><a href="course_access_details.php">'.get_lang('Traffic Details').'</a></li>'
        .    '</ul>' . "\n";

        return $html;
    }

    protected function renderFooter()
    {
        return '';
    }
}

TrackingRendererRegistry::registerCourse('CLTRACK_CourseAccess');

/*
 *
 */
class CLTRACK_CourseToolAccess extends CourseTrackingRenderer
{
    private $tbl_course_tracking_event;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
    }

    protected function renderHeader()
    {
        return get_lang('Users access to tools');
    }

    protected function renderContent()
    {
        $html = '';

        $sql = "SELECT `tool_id`,
                COUNT(DISTINCT `user_id`) AS `nbr_distinct_users_access`,
                COUNT( `tool_id` )            AS `nbr_access`
                    FROM `" . $this->tbl_course_tracking_event . "`
                    WHERE `type` = 'tool_access'
                      AND `tool_id` IS NOT NULL
                      AND `tool_id` <> ''
                    GROUP BY `tool_id`";

        $results = claro_sql_query_fetch_all($sql);

        $html .= '<table class="claroTable" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">'."\n"
        .   '<thead><tr class="headerX">'."\n"
        .   '<th>&nbsp;'.get_lang('Name of the tool').'&nbsp;</th>'."\n"
        .   '<th>&nbsp;'.get_lang('Users\' Clicks').'&nbsp;</th>'."\n"
        .   '<th>&nbsp;'.get_lang('Total Clicks').'&nbsp;</th>'."\n"
        .   '</tr></thead>'."\n"
        .   '<tbody>'."\n";

        if( !empty($results) && is_array($results))
        {
            foreach( $results as $result )
            {
                $thisTid = (int) $result['tool_id'];
                // FIXME check that claro_get_tool_name returns a toolname... check that tool exists
                $thisToolName = claro_get_tool_name(claro_get_tool_id_from_course_tid($thisTid));

                $html .= '<tr>' . "\n"
                .    '<td>'
                .    '<a href="tool_access_details.php?toolId='.$thisTid.'">'
                .    $thisToolName . '</a></td>' . "\n"
                .    '<td align="right"><a href="user_access_details.php?cmd=tool&amp;id='.$thisTid.'">'.(int) $result['nbr_distinct_users_access'] . '</a></td>' . "\n"
                .    '<td align="right">' . (int) $result['nbr_access'] . '</td>' . "\n"
                .    '</tr>'
                .    "\n\n";
            }

        }
        else
        {
            $html .= '<tr>'."\n"
            .    '<td colspan="3"><div align="center">'.get_lang('No result').'</div></td>'."\n"
            .    '</tr>'."\n";
        }
        $html .= '</tbody>'
        .    '</table>'."\n";

        return $html;
    }

    protected function renderFooter()
    {
        return '';
    }
}

TrackingRendererRegistry::registerCourse('CLTRACK_CourseToolAccess');




/*
 *
 */
class CLTRACK_userCourseAccess extends UserTrackingRenderer
{
    private $tbl_course_tracking_event;

    public function __construct($courseId, $userId)
    {
        $this->courseId = $courseId;
        $this->userId = (int) $userId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];

    }

    protected function renderHeader()
    {
        return get_lang('Access to course');
    }

    protected function renderContent()
    {
        $courseAccess = $this->prepareContent();

        $html = '';

        $html = '<table class="claroTable emphaseLine" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">' . "\n"
        .    '<thead>' . "\n"
        .    '<tr class="headerX">' . "\n"
        .    '<th>' . get_lang('Month') . '</th>' . "\n"
        .    '<th>' . get_lang('Number of access') . '</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n"
        ;

        $total = 0;
        if( !empty($courseAccess) && is_array($courseAccess) )
        {
            $langLongMonthNames = get_lang_month_name_list('long');
            $_html = "";
            foreach( $courseAccess as $access )
            {
                $_html .= '<tr>' . "\n"
                .    '<td>' . "\n"
                .    '<a href="'
                .    claro_htmlspecialchars(Url::Contextualize('user_course_access.php?userId='.$this->userId . '&amp;reqdate='.$access['unix_date'] ))
                .    '">' . $langLongMonthNames[date('n', $access['unix_date'])-1].' '.date('Y', $access['unix_date']).'</a>' . "\n"
                .    '</td>' . "\n"
                .    '<td valign="top" align="right">'
                .    (int) $access['nbr_access']
                .    '</td>' . "\n"
                .    '</tr>' . "\n";

                $total += (int) $access['nbr_access'];
            }

            $html .= '<tfoot>' . "\n"
            .    '<tr>' . "\n"
            .    '<td>'
            .    get_lang('Total')
            .    '</td>' . "\n"
            .    '<td align="right">'
            .    $total
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tfoot>' . "\n"
            .    '<tbody>' . "\n"
            .    $_html
            .    '</tbody>' . "\n"
            ;
        }
        else
        {
            $html .= '<tbody>' . "\n"
            .    '<tr>' . "\n"
            .    '<td colspan="2">' . "\n"
            .    '<center>'
            .    get_lang('No result')
            .    '</center>' . "\n"
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tbody>' . "\n";
        }
        $html .= '</table>' . "\n";

        return $html;
    }

    protected function renderFooter()
    {
        return get_lang('Click on the month name for tool access details');
    }

    private function prepareContent()
    {
        $sql = "SELECT UNIX_TIMESTAMP(`date`) AS `unix_date`,
                   count(`date`)          AS `nbr_access`
                FROM `" . $this->tbl_course_tracking_event . "`
                WHERE `user_id` = " . $this->userId . "
                  AND `type` = 'course_access'
                GROUP BY MONTH(`date`), YEAR(`date`)
                ORDER BY `date` ASC";

        $results = claro_sql_query_fetch_all($sql);

        return $results;
    }
}

TrackingRendererRegistry::registerUser('CLTRACK_userCourseAccess');

/*
 *
 */
class CLTRACK_userPlatformAccess extends UserTrackingRenderer
{
    private $tbl_course_tracking_event;

    public function __construct($courseId, $userId)
    {
        $this->courseId = $courseId;
        $this->userId = (int) $userId;

        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tbl_course_tracking_event = $tbl_mdb_names['tracking_event'];

    }

    protected function renderHeader()
    {
        return get_lang('Access to platform');
    }

    protected function renderContent()
    {
        $courseAccess = $this->prepareContent();

        $html = '';

        $html = '<table class="claroTable emphaseLine" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">' . "\n"
        .    '<thead>' . "\n"
        .    '<tr class="headerX">' . "\n"
        .    '<th>' . get_lang('Month') . '</th>' . "\n"
        .    '<th>' . get_lang('Number of access') . '</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n"
        ;

        $total = 0;
        if( !empty($courseAccess) && is_array($courseAccess) )
        {
            $langLongMonthNames = get_lang_month_name_list('long');
            $_html = "";
            foreach( $courseAccess as $access )
            {
                $_html .= '<tr>' . "\n"
                .    '<td>' . "\n"
                .    $langLongMonthNames[date('n', $access['unix_date'])-1].' '.date('Y', $access['unix_date']) . "\n"
                .    '</td>' . "\n"
                .    '<td valign="top" align="right">'
                .    (int) $access['nbr_access']
                .    '</td>' . "\n"
                .    '</tr>' . "\n";

                $total += (int) $access['nbr_access'];
            }

            $html .= '<tfoot>' . "\n"
            .    '<tr>' . "\n"
            .    '<td>'
            .    get_lang('Total')
            .    '</td>' . "\n"
            .    '<td align="right">'
            .    $total
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tfoot>' . "\n"
            .    '<tbody>' . "\n"
            .    $_html
            .    '</tbody>' . "\n"
            ;
        }
        else
        {
            $html .= '<tfoot>' . "\n"
            .    '<tr>' . "\n"
            .    '<td colspan="2">' . "\n"
            .    '<center>'
            .    get_lang('No result')
            .    '</center>' . "\n"
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tfoot>' . "\n";
        }
        $html .= '</table>' . "\n";

        return $html;
    }

    protected function renderFooter()
    {
        return '';
    }

    private function prepareContent()
    {
        $sql = "SELECT UNIX_TIMESTAMP(`date`) AS `unix_date`,
                   count(`date`)          AS `nbr_access`
                FROM `" . $this->tbl_course_tracking_event . "`
                WHERE `user_id` = " . $this->userId . "
                  AND `type` = 'user_login'
                GROUP BY MONTH(`date`), YEAR(`date`)
                ORDER BY `date` ASC";

        $results = claro_sql_query_fetch_all($sql);

        return $results;
    }
}

TrackingRendererRegistry::registerUser('CLTRACK_userPlatformAccess',TrackingRendererRegistry::PLATFORM);

<?php // $Id: startModule.php 14407 2013-02-25 15:30:41Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14407 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Piraux Sebastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 *
 * @package CLLNP
 * @subpackage navigation
 *
  * This script is the main page loaded when user start viewing a module in the browser.
  * We define here the frameset containing the launcher module (SCO if it is a SCORM conformant one)
  * and a top and bottom frame to display the claroline banners.
  * If the module is an exercise of claroline, no frame is created,
  * we redirect to exercise_submit.php page in a path mode
  *
  */

/*======================================
       CLAROLINE MAIN
  ======================================*/
$tlabelReq = 'CLLNP';
require '../../inc/claro_init_global.inc.php';

// Tables names
/*
 * DB tables definition
 */
$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
$tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
$tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
$tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

$TABLELEARNPATH         = $tbl_lp_learnPath;
$TABLEMODULE            = $tbl_lp_module;
$TABLELEARNPATHMODULE   = $tbl_lp_rel_learnPath_module;
$TABLEASSET             = $tbl_lp_asset;
$TABLEUSERMODULEPROGRESS= $tbl_lp_user_module_progress;

$tbl_main_names = claro_sql_get_main_tbl();
$tbl_tracking_event = $tbl_main_names['tracking_event'];

$TABLETRACKINGEVENT = $tbl_tracking_event;

// lib of this tool
require_once(get_path('incRepositorySys')."/lib/learnPath.lib.inc.php");

if(isset ($_GET['viewModule_id']) && $_GET['viewModule_id'] != '')
    $_SESSION['module_id'] = $_GET['viewModule_id'];

// SET USER_MODULE_PROGRESS IF NOT SET
if(claro_is_user_authenticated()) // if not anonymous
{
    // check if we have already a record for this user in this module
    $sql = "SELECT COUNT(LPM.`learnPath_module_id`)
            FROM `".$TABLEUSERMODULEPROGRESS."` AS UMP, `".$TABLELEARNPATHMODULE."` AS LPM
           WHERE UMP.`user_id` = '" . (int)claro_get_current_user_id() . "'
             AND UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
             AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id']."
             AND LPM.`module_id` = ". (int)$_SESSION['module_id'];
    $num = claro_sql_query_get_single_value($sql);

    $sql = "SELECT `learnPath_module_id`
            FROM `".$TABLELEARNPATHMODULE."`
           WHERE `learnPath_id` = ". (int)$_SESSION['path_id']."
             AND `module_id` = ". (int)$_SESSION['module_id'];
    $learnPathModuleId = claro_sql_query_get_single_value($sql);

    // if never intialised : create an empty user_module_progress line
    if( !$num || $num == 0 )
    {
        $sql = "INSERT INTO `".$TABLEUSERMODULEPROGRESS."`
                ( `user_id` , `learnPath_id` , `learnPath_module_id`, `suspend_data` )
                VALUES ( " . (int)claro_get_current_user_id() . " , ". (int)$_SESSION['path_id']." , ". (int)$learnPathModuleId.", '')";
        claro_sql_query($sql);
        
        // Generate an event to notify that the module has been started for the first time in the current learnPath
        $learnPathEventArgs = array( 'userId' => (int)claro_get_current_user_id(),
                                     'courseCode' => claro_get_current_course_id(),
                                     'learnPathId' => (int)$_SESSION['path_id'],
                                     'learnPathModuleId' => (int)$learnPathModuleId,
                                     'type' => "init",
                                     'status' => "NOT ATTEMPTED"
                                   );
        $learnPathEvent = new Event( 'lp_user_module_progress_modified', $learnPathEventArgs );
        EventManager::notify( $learnPathEvent );
    }
}  // else anonymous : record nothing !


// Get info about launched module

$sql = "SELECT `contentType`,`startAsset_id`,`launch_data`
          FROM `".$TABLEMODULE."`
         WHERE `module_id` = ". (int)$_SESSION['module_id'];

$module = claro_sql_query_get_single_row($sql);

$sql = "SELECT `path`
               FROM `".$TABLEASSET."`
              WHERE `asset_id` = ". (int)$module['startAsset_id'];

$assetPath = claro_sql_query_get_single_value($sql);

// Get path of file of the starting asset to launch

$withFrames = false;

$moduleStartAssetPage = '';

switch ($module['contentType'])
{
    case CTDOCUMENT_ :
        if(claro_is_user_authenticated())
        {
            // Retrieve default time associated to a document-type module
            $documentDefaultTime = $module['launch_data'];
            // if no default time is specifically associated to the module
            // then use standard default time defined in learnPath module conf
            if( empty( $documentDefaultTime ) )
            {
                $documentDefaultTime = get_conf( 'cllnp_documentDefaultTime', 0 );
            }
            // As the default time is defined in minute ( integer )
            // convert it to 'hhhh:mm:ss' format
            $hours = (int)( $documentDefaultTime / 60 );
            $minutes = $documentDefaultTime % 60;
            $timeSpentOnDoc = '';
            if( $hours > 9999 )
            {
                $timeSpentOnDoc .= '9999:59:59.99';
            }
            else
            {
                if( $hours < 10 )
                {
                    $timeSpentOnDoc .= '0';
                }
                $timeSpentOnDoc .= $hours . ':';
                if( $minutes < 10 )
                {
                    $timeSpentOnDoc .= '0';
                }
                $timeSpentOnDoc .= $minutes . ':00';
            }
            
            $currentDate = date( "Y-m-d H:i:s" );
            
            if( !get_conf( 'cllnp_documentDefaultTimeOnce' ) )
            {
                $dataBeginText = (int)$_SESSION['path_id'] . ';' . (int)$_SESSION['module_id'] . ';';

                // Check if the module has already been launched during the last X minutes
                // ( X is the default time associated to the module )
                $sql = "SELECT `id`, `data`, TIMEDIFF( '" . $currentDate . "', `date` ) AS `diff`
                          FROM `" . $TABLETRACKINGEVENT . "`
                         WHERE `type` = 'document_start'
                           AND `course_code` = '" . claro_sql_escape( claro_get_current_course_id() ) . "'
                           AND `user_id` = " . claro_sql_escape( (int)claro_get_current_user_id() ) . "
                           AND `data` LIKE '" . claro_sql_escape( $dataBeginText ) . "%'
                           AND TIMEDIFF( '" . $currentDate . "', `date` ) < '" . claro_sql_escape( $timeSpentOnDoc ) . "'";

                $moduleStart = claro_sql_query_fetch_single_row( $sql );

                // if it is the case update the tracking with the time spent
                // between now and the time the module has been executed the previous time
                if( isset( $moduleStart['id'] ) )
                {
                    $trackingTime = $moduleStart['diff'];
                    $dataTab = explode( ';', $moduleStart['data']);
                    $dataTimeTab = explode( ':', $dataTab[2] );
                    $diffTimeTab = explode( ':', $moduleStart['diff']);
                    $dataTimeInSec = $dataTimeTab[0] * 3600 + $dataTimeTab[1] * 60 + $dataTimeTab[2];
                    $diffTimeInSec = $diffTimeTab[0] * 3600 + $diffTimeTab[1] * 60 + $diffTimeTab[2];
                    $totalTimeInSec = $dataTimeInSec + $diffTimeInSec;
                    $totalTimeTab = array();
                    $totalTimeTab[0] = (int)( $totalTimeInSec / 3600 );
                    $totalTimeInSec %= 3600;
                    $totalTimeTab[1] = (int)( $totalTimeInSec / 60 );
                    $totalTimeInSec %= 60;
                    $totalTimeTab[2] = $totalTimeInSec;
                    $totalTime = '';
                    if( $totalTimeTab[0] > 9999 )
                    {
                        $totalTime .= '9999:59:59';
                    }
                    else
                    {
                        if( $totalTimeTab[0] < 10 )
                        {
                            $totalTime .= 0;
                        }
                        $totalTime .= $totalTimeTab[0] . ':';
                        if( $totalTimeTab[1] < 10 )
                        {
                            $totalTime .= 0;
                        }
                        $totalTime .= $totalTimeTab[1] . ':';
                        if( $totalTimeTab[2] < 10 )
                        {
                            $totalTime .= 0;
                        }
                        $totalTime .= $totalTimeTab[2];
                    }
                    $dataText = $dataBeginText . $totalTime;

                    $sql = "UPDATE `" . $TABLETRACKINGEVENT ."`
                               SET `date` = '" . claro_sql_escape( $currentDate ) . "',
                                   `data` = '" . claro_sql_escape( $dataText ) . "'
                             WHERE `id` = " . claro_sql_escape( (int)$moduleStart['id'] );
                    claro_sql_query( $sql );
                }
                // else add a new tracking for this module with the default time as spent time
                else
                {
                    $trackingTime = $timeSpentOnDoc;
                    $dataText = $dataBeginText . $timeSpentOnDoc;
                    $sql = "INSERT INTO `" . $TABLETRACKINGEVENT ."`
                            ( `course_code`, `user_id`, `date`, `type`, `data` )
                            VALUES ( '" . claro_sql_escape( claro_get_current_course_id() ) . "', "
                                       . claro_sql_escape( (int)claro_get_current_user_id() ) . ", '"
                                       . claro_sql_escape( $currentDate ) . "', 'document_start', '"
                                       . claro_sql_escape( $dataText ) . "' )";
                    claro_sql_query( $sql );
                }
            }
            else
            {
                $trackingTime = $timeSpentOnDoc;
            }

            // Get total time spent on the module
            $sql = "SELECT `user_module_progress_id`, `total_time`
                      FROM `" . $TABLEUSERMODULEPROGRESS . "`
                     WHERE `user_id` = " . (int)claro_get_current_user_id() . "
                       AND `learnPath_module_id` = " . (int)$learnPathModuleId;
            
            $progressRow = claro_sql_query_fetch_single_row( $sql );
            $currentTime = $progressRow['total_time'];
            $currentTimeTab = explode( ':', $currentTime );
            $currentTimeSecTab = explode( ',', $currentTimeTab[2] );
            $currentTimeInSec = $currentTimeTab[0] * 3600 + $currentTimeTab[1] * 60 + $currentTimeSecTab[0];
                
            if( (int)$currentTimeInSec == 0 )
            {
                $updateTime = $trackingTime;
            }
            elseif( get_conf( 'cllnp_documentDefaultTimeOnce' ) )
            {
                $updateTime = $progressRow['total_time'];
                $trackingTime = '0000:00:00';
            }
            else
            {
                $trackingTimeTab = explode( ':', $trackingTime );
                $trackingTimeInSec = $trackingTimeTab[0] * 3600 + $trackingTimeTab[1] * 60 + $trackingTimeTab[2];
                $updateTimeInSec = $currentTimeInSec + $trackingTimeInSec;
                
                $updateTimeInHour = (int)( $updateTimeInSec / 3600 );
                $updateTimeInSec %= 3600;
                $updateTimeInMinute = (int)( $updateTimeInSec / 60 );
                $updateTimeInSec %= 60;
                $updateTime = '';
                if( $updateTimeInHour > 9999 )
                {
                    $updateTime .= '9999:59:59';
                }
                else
                {
                    if( $updateTimeInHour < 10 )
                    {
                        $updateTime .= 0;
                    }
                    $updateTime .= $updateTimeInHour . ':';
                    if( $updateTimeInMinute < 10 )
                    {
                        $updateTime .= 0;
                    }
                    $updateTime .= $updateTimeInMinute . ':';
                    if( $updateTimeInSec < 10 )
                    {
                        $updateTime .= 0;
                    }
                    $updateTime .= $updateTimeInSec;
                }
            }
            
            // if credit was already set this query changes nothing else it update the query made at the beginning of this script
            $sql = "UPDATE `".$TABLEUSERMODULEPROGRESS."`
                       SET `credit` = 1,
                           `raw` = 100,
                           `lesson_status` = 'completed',
                           `scoreMin` = 0,
                           `scoreMax` = 100,
                           `total_time` = '" . claro_sql_escape( $updateTime ) . "',
                           `session_time` = '" . claro_sql_escape( $trackingTime ) . "'
                     WHERE `user_module_progress_id` = " . (int)$progressRow['user_module_progress_id'];

            claro_sql_query($sql);

            // Generate an event to notify that the status of the document has been set to "Complete"
            $learnPathEventArgs = array( 'userId' => (int)claro_get_current_user_id(),
                                         'courseCode' => claro_get_current_course_id(),
                                         'scoreRaw' => 100,
                                         'scoreMin' => 0,
                                         'scoreMax' => 100,
                                         'sessionTime' => $trackingTime,
                                         'learnPathId' => (int)$_SESSION['path_id'],
                                         'moduleId' => (int)$_SESSION['module_id'],
                                         'learnPathModuleId' => (int)$learnPathModuleId,
                                         'type' => "update",
                                         'date' => $currentDate
                                       );
            $learnPathEvent = new Event( 'lp_user_module_progress_modified', $learnPathEventArgs );
            EventManager::notify( $learnPathEvent );
            $documentTrackingData = array( 'documentStartDate' => $currentDate,
                                           'documentPreviousTotalTime' => $currentTime,
                                           'documentSessionTime' => $trackingTime,
                                           'documentUserModuleProgressId' => $progressRow['user_module_progress_id']
                                         );
            $_SESSION['documentTrackingData'] = $documentTrackingData;
        } // else anonymous : record nothing

        $startAssetPage = rawurlencode($assetPath);
        
        $pathInfo = get_path('coursesRepositorySys') . claro_get_course_path(). '/document/' . ltrim($assetPath,'/');
        $pathContents = file_get_contents($pathInfo);       
        $extension = get_file_extension($pathInfo);
        
        // add a variable to set CLLNP mode in downloader
        // @todo : use a token and pass it as extra parameter to claro_get_file_download_url
        $_SESSION['fromCLLNP'] = true;

        if ( $extension == 'url' )
        {
            // 

            $matches = array();

            if ( preg_match( '/<meta http-equiv="refresh" content="0;url=(.*?)">/', $pathContents, $matches ) && isset( $matches[1] ) )
            {
                $redirectionURL = $matches[1];
                $moduleStartAssetPage = 'viewExternalPage.php?url='.rawurlencode($redirectionURL);
            }
            else
            {
                $moduleStartAssetPage = claro_get_file_download_url( $startAssetPage, null, 'CLLNP' );
            }
            
        }
        else
        {
            $moduleStartAssetPage = claro_get_file_download_url( $startAssetPage, null, 'CLLNP' );
        }
        

          $withFrames = true;
        break;

    case CTEXERCISE_ :
        // clean session vars of exercise
        unset($_SESSION['serializedExercise']);
        unset($_SESSION['serializedQuestionList']);
        unset($_SESSION['exeStartTime'    ]);

        $_SESSION['inPathMode'] = true;
        $startAssetpage = get_module_url('CLQWZ') . '/exercise_submit.php';
        $moduleStartAssetPage = $startAssetpage . '?exId=' . $assetPath;
        break;
    case CTSCORM_ :
        // real scorm content method
        $startAssetPage = $assetPath;
        $modulePath     = 'path_' . $_SESSION['path_id'];
        $moduleStartAssetPage = get_path('coursesRepositoryWeb')
        . claro_get_course_path()
        . '/scormPackages/'
        . $modulePath
        . $startAssetPage;
        break;
    case CTCLARODOC_ :
        break;
} // end switch

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<html>

  <head>

<?php
   // add the update frame if this is a SCORM module
   if ( $module['contentType'] == CTSCORM_ )
   {

      include("scormAPI.inc.php");
      echo '<frameset border="0" cols="0,20%,80%" frameborder="no">
            <frame src="'. claro_htmlspecialchars(Url::Contextualize('updateProgress.php')).'" name="upFrame">';

   }
   else
   {
      echo '<frameset border="0" cols="20%,80%" frameborder="yes">';
   }
?>
    <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize('tableOfContent.php'));?>" name="tocFrame" />
    <frame src="<?php echo claro_htmlspecialchars(Url::Contextualize($moduleStartAssetPage));?>" name="scoFrame" />

    </frameset>
  <noframes>
<body>
<?php
  echo get_lang('Your browser cannot see frames.');
?>
</body>
</noframes>
</html>

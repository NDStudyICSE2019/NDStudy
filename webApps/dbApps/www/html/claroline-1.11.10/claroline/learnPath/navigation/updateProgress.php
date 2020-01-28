<?php // $Id: updateProgress.php 14386 2013-02-08 13:03:23Z kitan1982 $
/**
 * CLAROLINE 
 *
 * @version 1.11 $Revision: 14386 $
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
 */

require '../../inc/claro_init_global.inc.php'; 

require_once get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php';

/**
 * DB tables definition
 */
$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user            = $tbl_mdb_names['user'             ];
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

$TABLEUSERS                    = $tbl_user;


$TOCurl = Url::Contextualize(get_module_url('CLLNP') . '/navigation/tableOfContent.php'); 

/*********************/
/* HANDLING API FORM */
/*********************/

// handling of the API form if posted by the SCORM API
if($_POST['ump_id']) 
{
  // set values for some vars because we are not sure we will change it later
  $lesson_status_value = strtoupper($_POST['lesson_status']);
  $credit_value = strtoupper($_POST['credit']);
  
  // next visit of the sco will not be the first so entry must be setted to RESUME
  $entry_value = "RESUME"; 
  
  // Set lesson status to COMPLETED if the SCO didn't change it itself.
  if ( $lesson_status_value == "NOT ATTEMPTED" )
      $lesson_status_value = "COMPLETED";

  // set credit if needed
  if ( $lesson_status_value == "COMPLETED" || $lesson_status_value == "PASSED")
  {
      if ( strtoupper($_POST['credit']) == "CREDIT" )
        $credit_value = "CREDIT";
  }

  if(isScormTime($_POST['session_time']))
  {
    $total_time_value = addScormTime($_POST['total_time'], $_POST['session_time']);
  }
  else
  {
    $total_time_value = $_POST['total_time'];
  }
  
  $sql = "UPDATE `".$TABLEUSERMODULEPROGRESS."` 
            SET 
                `lesson_location` = '". claro_sql_escape($_POST['lesson_location'])."',
                `lesson_status` = '". claro_sql_escape($lesson_status_value) ."',
                `entry` = '". claro_sql_escape($entry_value) ."',
                `raw` = '". (int)$_POST['raw']."',
                `scoreMin` = '".(int)$_POST['scoreMin']."',
                `scoreMax` = '". (int)$_POST['scoreMax']."',
                `total_time` = '". claro_sql_escape($total_time_value) ."',
                `session_time` = '". claro_sql_escape($_POST['session_time']) ."',
                `suspend_data` = '". claro_sql_escape($_POST['suspend_data'])."',
                `credit` = '". claro_sql_escape($credit_value) ."'
          WHERE `user_module_progress_id` = ". (int)$_POST['ump_id'];
  claro_sql_query($sql);
  
    // Generate an event to notify that the module tracking has been updated
    $learnPathEventArgs = array( 'userId' => (int)claro_get_current_user_id(),
                                 'courseCode' => claro_get_current_course_id(),
                                 'scoreRaw' => (int)$_POST['raw'],
                                 'scoreMin' => (int)$_POST['scoreMin'],
                                 'scoreMax' => (int)$_POST['scoreMax'],
                                 'sessionTime' => claro_sql_escape( $_POST['session_time'] ),
                                 'userModuleProgressId' => (int)$_POST['ump_id'],
                                 'type' => "update",
                                 'status' => claro_sql_escape($lesson_status_value)
                               );
    $learnPathEvent = new Event( 'lp_user_module_progress_modified', $learnPathEventArgs );
    EventManager::notify( $learnPathEvent );
}

// display the form to accept new commit and
// refresh TOC frame, has to be done here to show recorded progression as soon as it is recorded
            
?>

<!-- API form -->
<html>
<head>
   <title>update progression</title>
<?php
if($_POST['ump_id']) 
{
?>
    <script type="text/javascript">
    <!--//
      parent.tocFrame.location.href="<?php echo $TOCurl; ?>";
    //--> 
    </script>
<?php
}
?>
</head>
<body>
    <form name="cmiForm" method="post" action="<?php echo Url::Contextualize($_SERVER["PHP_SELF"]) ?>"> 
    <input type="hidden" name="ump_id" />
    <input type="hidden" name="lesson_status" />
    <input type="hidden" name="lesson_location" />
    <input type="hidden" name="credit" />
    <input type="hidden" name="entry" />
    <input type="hidden" name="raw" />
    <input type="hidden" name="total_time" />
    <input type="hidden" name="session_time" />
    <input type="hidden" name="suspend_data" />
    <input type="hidden" name="scoreMin" />
    <input type="hidden" name="scoreMax" />
</form>
</body>
</html>
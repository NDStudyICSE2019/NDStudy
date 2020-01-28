<?php

$tlabelReq = 'CLLNP';
require '../../inc/claro_init_global.inc.php';

if( claro_is_user_authenticated() )
{
    echo 'User is authenticated';
    $spentTime = isset( $_GET['spentTime'] ) ? $_GET['spentTime'] : null;
    $userModuleProgressId = isset( $_GET['userModuleProgressId'] ) ? (int)$_GET['userModuleProgressId'] : null;
    $previousTotalTime = isset( $_GET['previousTotalTime'] ) ? $_GET['previousTotalTime'] : null;
    $date = isset( $_GET['date'] ) ? $_GET['date'] : null;
    $userId = isset( $_GET['userId'] ) ? (int)$_GET['userId'] : null;
    $courseCode = isset( $_GET['courseCode'] ) ? $_GET['courseCode'] : null;
    $learnPathId = isset( $_GET['learnPathId'] ) ? (int)$_GET['learnPathId'] : null;
    $moduleId = isset( $_GET['moduleId'] ) ? (int)$_GET['moduleId'] : null;
    
    if( !is_null( $spentTime ) && !is_null( $userModuleProgressId )
        && !is_null( $previousTotalTime ) && !is_null( $date )
        && !is_null( $userId ) && !is_null( $courseCode )
        && !is_null( $learnPathId ) && !is_null( $moduleId ) )
    {
        echo 'No null param';
        if( claro_get_current_user_id() == $userId && claro_get_current_course_id() == $courseCode )
        {
            echo 'user & course : OK';
            $sessionTimeHour = (int)( $spentTime / 60 );
            $sessionTimeMin = $spentTime % 60;
            $sessionTime = '';
            if( $sessionTimeHour > 9999 )
            {
                $sessionTime = '9999:59:59';
            }
            else
            {
                if( $sessionTimeHour < 10 )
                {
                    $sessionTime .= 0;
                }
                $sessionTime .= $sessionTimeHour . ':';
                if( $sessionTimeMin < 10 )
                {
                    $sessionTime .= 0;
                }
                $sessionTime .= $sessionTimeMin . ':00';
            }
            
            $previousTotalTimeTab = explode( ':', $previousTotalTime );
            $previousTotalTimeTab[1] += $spentTime;
            $previousTotalTimeTab[0] += (int)( $previousTotalTimeTab[1] / 60 );
            $previousTotalTimeTab[1] %= 60;
            $newTotalTime = '';
            if( $previousTotalTimeTab[0] > 9999 )
            {
                $newTotalTime = '9999:59:59';
            }
            else
            {
                if( $previousTotalTimeTab[0] < 10 )
                {
                    $newTotalTime .= 0;
                }
                $newTotalTime .= $previousTotalTimeTab[0] . ':';
                if( $previousTotalTimeTab[1] < 10 )
                {
                    $newTotalTime .= 0;
                }
                $newTotalTime .= $previousTotalTimeTab[1] . ':' . $previousTotalTimeTab[2];
            }
            
            $tblUserModuleProgress = get_module_course_tbl( array( 'lp_user_module_progress' ), $courseCode );
            Claroline::getDatabase()->exec(
                "UPDATE `{$tblUserModuleProgress['lp_user_module_progress']}`
                    SET total_time = " . Claroline::getDatabase()->quote( $newTotalTime ) . ",
                        session_time = " . Claroline::getDatabase()->quote( $sessionTime ) . "
                  WHERE user_module_progress_id = " . Claroline::getDatabase()->escape( (int)$userModuleProgressId )
            );
            
            $documentTimeUpdateArgs = array( 'sessionTime' => $sessionTime,
                                             'date' => $date,
                                             'userId' => $userId,
                                             'courseCode' => $courseCode,
                                             'learnPathId' => $learnPathId,
                                             'moduleId' => $moduleId
                                           );
            $documentTimeUpdateEvent = new Event( 'lp_document_time_update', $documentTimeUpdateArgs );
            EventManager::notify( $documentTimeUpdateEvent );
            echo 'Document spent time updated';
        }
        else
        {
            echo 'Wrong userId or wrong courseCode';
        }
    }
    else
    {
        echo 'There is at least one null param';
    }
}
else
{
    echo 'User not authenticated';
}

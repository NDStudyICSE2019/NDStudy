<?php

class CLWRK_MergeUser implements Module_MergeUser
{
    public function mergeCourseUsers( $uidToRemove, $uidToKeep, $courseId )
    {
        $moduleCourseTbl = get_module_course_tbl( array('wrk_submission'), $courseId );
        
        $sql = "UPDATE `{$moduleCourseTbl['wrk_submission']}`
                SET   user_id = ".(int)$uidToKeep."
                WHERE user_id = ".(int)$uidToRemove;

        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot update wrk_submission from -{$uidToRemove} to +{$uidToKeep} in {$courseId}");
            return false;
        }
        else
        {
            return true;
        }
    }
    
    public function mergeUsers( $uidToRemove, $uidToKeep )
    {
        // empty
        return true;
    }
}

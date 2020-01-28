<?php // $Id: chatMsgList.class.php 668 2009-03-17 13:27:50Z dimitrirambout $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 668 $
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CHAT
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

class ChatMsgList
{
    private $msgList = array();
    
    private $courseId = null;
    private $groupId = null;
    
    private $tblChatMsg = '';
    private $tblUser = '';
    
    public function __construct($courseId, $groupId = null)
    {
        $this->courseId = $courseId;
        $this->groupId = $groupId;
        
        $tblNameList = array(
            'chat'
        );

        $tbl_chat_names = get_module_course_tbl( $tblNameList, $this->courseId ); 
        $this->tblChatMsg = $tbl_chat_names['chat'];
        
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tblUser = $tbl_mdb_names['user'];
    }
    
    
    /**
     * load message list of this course from DB
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @param int $from unixtime 
     * @param int $to unixtime
     * @return result of operation
     */ 
    public function load($from = '', $to = '')
    {
        $sql = "SELECT UNIX_TIMESTAMP(`JC`.`post_time`) as `unixPostTime`, 
                    `JC`.`message`, 
                    `U`.`nom` as `lastname`,
                    `U`.`prenom` as `firstname`, 
                    `U`.`isCourseCreator` 
                FROM `".$this->tblChatMsg."` as `JC`, 
                    `".$this->tblUser."` as `U` 
                WHERE `JC`.`user_id` = `U`.`user_id` ";

        if( !is_null($this->groupId) )  
        {
            $sql .= " AND `JC`.`group_id` = ".(int) $this->groupId . " ";
        }
        else
        {
            $sql .= " AND `JC`.`group_id` IS NULL ";
        }
        
        
        if( $from != '' )
        {
            $sql .= " HAVING ". (int) $from . " < `unixPostTime` ";
            if( $to != '' )
            {
                $sql .= " AND `unixPostTime` < ". (int) $to . " ";
            }
        }
        
        $sql .=    " ORDER BY `post_time`";

        $messageList = claro_sql_query_fetch_all_rows($sql);
        
        if( $messageList )
        {
            $this->msgList = $messageList;
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Produce html to display the message list
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string html output
     */ 
    public function render()
    {
        $resetLastReceivedMsg = false;
        
        $html = '';
        $previousDayTimestamp = 0; // keep track of the day of the last displayed message
        
        foreach( $this->msgList as $message )
        {
            if( get_days_from_timestamp($previousDayTimestamp) < get_days_from_timestamp($message['unixPostTime']) )
            {
                // display day separator
                $html .= "\n" . '<span class="clchat_dayLimit">'.claro_html_localised_date(get_locale('dateFormatLong'), $message['unixPostTime']).'</span>' . "\n";
                
                $previousDayTimestamp = $message['unixPostTime'];
            }
                
            if( $_SESSION['chat_lastReceivedMsg'] < $message['unixPostTime'] )
            {    
                $spanClass = ' newLine'; 
                $resetLastReceivedMsg = true;
            }
            else
            {
                $spanClass = '';    
            }
            
            $html .= "\n" . '<span class="clchat_msgLine' . $spanClass . '">'.$this->renderSingleMsg($message).'</span>' . "\n";
        }
        
        // keep track of the last display time 
        if( $resetLastReceivedMsg ) $_SESSION['chat_lastReceivedMsg'] = time();

        return $html;
    }
    
    /**
     * Get html to display one message with clickable links
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @param array $message('unixPostTime','message','lastname','firstname','isCourseCreator')
     * @return string html output for $message
     */ 
    private function renderSingleMsg($message)
    {
        $userName = $message['firstname'] . ' '. $message['lastname'];
        if (strlen($userName) > get_conf('max_nick_length') ) $userName = $message['firstname'] . ' '. $message['lastname'][0] . '.';
    
        // transform url to clickable links
        $chatLine = claro_parse_user_text($message['message']);    
        
        $html = '';
            
        $html .= '<span class="clchat_msgDate">' . claro_html_localised_date('%H:%M:%S', $message['unixPostTime']) . '&nbsp;|</span>'
        .     ' <span class="clchat_userName">' . $userName 
        .     '</span>&nbsp;: ' . $chatLine . "\n";
        
        return $html;
    }
    
    /**
     * Delete all messages from DB for current course
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean if query was successfull
     */ 
    function flush()
    {
        $sql = "DELETE FROM `".$this->tblChatMsg."`";
        
        return claro_sql_query($sql);
    }
    
    /**
     * Add a message to chat
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @param string $message
     * @return boolean
     */ 
    function addMsg($message, $userId)
    {
        if( !empty($message) && $userId )
        {
            $sql = "INSERT INTO `".$this->tblChatMsg."`
                    SET `user_id` = '".(int) $userId."', ";
            
            if( !is_null($this->groupId) )  
            {
                $sql .= " `group_id` = ".(int) $this->groupId . ", ";
            }
                    
            $sql .= "`message` = '".addslashes(htmlspecialchars($message))."',
                        `post_time` = NOW()";

            return claro_sql_query($sql);
        }
        else
        {
            return false;
        }
    }

    /**
     * Generate a file with all messages and copy it in the document tool
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return mixed filename if successfull, false if failed
     */ 
     
    function archive()
    {
        // Load CSS
        $cssPath = './css/clchat.css';
        $cssContent = '';
        if( file_exists($cssPath) && is_readable($cssPath) )
        {
            $cssContent = file_get_contents( $cssPath );            
        }
        // Prepare archive file content
        $htmlContentHeader = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">' . "\n"
        .    '<html>' . "\n"
        .    '<head>' . "\n"
        .    '<title>' . get_lang('Chat') . '</title>'
        .    (!empty($cssContent) ? '<style type="text/css">'.$cssContent.'</style>' : '')
        .    '</head>' . "\n"
        .    '<body>' . "\n\n";
    
        $htmlContentFooter = '</body>' . "\n\n"
        .    '</html>' . "\n";
    
        
        $htmlContent = '<div id="clchat_chatarea" style="height: auto;">'.$this->render().'</div>';
        
        $htmlContent = $htmlContentHeader . $htmlContent . $htmlContentFooter; 
        
        
        // filepath
        $courseDir = claro_get_course_path() .'/document';
        $baseWorkDir = get_path('coursesRepositorySys') . $courseDir;
    
        // Try to determine a filename that does not exist anymore
        // in the directory where the chat file will be stored
    
        $chatDate = 'chat.'.date('Y.m.j-H.i.s') . '.html';
        $i = 1;
        
        //while ( file_exists($baseWorkDir.'/'.$chatDate.$i.'.html') ) $i++;
    
        $chatFilename = $baseWorkDir.'/'. $chatDate;
        
        $fp = fopen($chatFilename, 'w');
    
        if( fwrite($fp, $htmlContent) )
        {
            return $chatFilename;
        }
        else
        {
            return false;
        }
    }
}
?>
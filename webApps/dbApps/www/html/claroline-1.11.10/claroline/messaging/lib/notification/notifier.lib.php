<?php // $Id: notifier.lib.php 13498 2011-09-01 11:19:18Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Notifier class
 *
 * @version     1.9 $Revision: 13498 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

FromKernel::uses('utils/finder.lib.php');

class MessagingUserNotifier
{
    /**
     * call all gateway notification
     *
     * @param int $uidList
     * @param messageToSend $message
     * @param int $messageId
     */
    public static function notify ($uidList, $message, $messageId)
    {
        // list all file in ./notifier/
        $notifierFile = new Claro_FileFinder_Extension(dirname(__FILE__) . '/notifier/', '.notifier.lib.php', false);
        
        $classNotLoad = '';
        
        foreach ( $notifierFile as $file )
        {
            require_once $file->getPathname();
            
            //take the name of the class  
            // convention file: name.lib.php classe: name
            $className = substr($file->getFilename(),0,strlen($file->getFilename())-strlen(".notifier.lib.php")).'Notifier';
            
            if (class_exists($className))
            {
                $notifier = new $className();
                $notifier->notify($uidList, $message,$messageId);
            }
            else
            {
                if ($classNotLoad != '')
                {
                    $classNotLoad .= ', ';
                }
                
                $classNotLoad .= $className;
            }
        }
        
        if ($classNotLoad != '')
        {
            claro_die(get_lang("The message sent but the notification by " . $classNotLoad . " failed"));
        }
    }
}

/**
 * messagingnotifier interface
 *
 * @version     1.9 $Revision: 13498 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

interface MessagingNotifier
{
    public function notify ($userDataList,$message,$messageId);
}

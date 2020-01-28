<?php // $Id: mymessages.class.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * User desktop : internal messaging portlet
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline Team <info@claroline.net>
 */

require_once get_path( 'clarolineRepositorySys' ) . '/messaging/lib/tools.lib.php';
require_once get_path( 'clarolineRepositorySys' ) . '/messaging/lib/messagebox/inbox.lib.php';

class MyMessages extends UserDesktopPortlet
{
    protected $inbox;
    
    public function __construct($label)
    {
        parent::__construct($label);
        
        $this->name = 'My last messages';
        $this->label = 'mymessages';
        
        $this->inbox = new InBox;
        $this->inbox->getMessageStrategy()->setNumberOfMessagePerPage( get_conf('myboxNumberOfMessage',5) );
    }
    
    
    public function renderContent()
    {
        $output = '';
        
        $output .= '<table class="claroTable emphaseLine" width="99%" border="0" cellspacing="2">' . "\n"
                 . '<thead>' . "\n"
                 . '<tr align="center" valign="top">' . "\n"
                 . '<th>&nbsp;</th>' . "\n"
                 . '<th>' . get_lang('Subject') . '</th>' . "\n"
                 . '<th>' . get_lang('Sender') . '</th>' . "\n"
                 . '<th>' . get_lang('Date') . '</th>' . "\n"
                 . '</tr>' . "\n"
                 . '</thead>' . "\n"
                 . '<tbody>' . "\n";
        
        if( $this->inbox->getNumberOfMessage() > 0 )
        {
            foreach( $this->inbox as $message )
            {
                if ( $message->isPlatformMessage() )
                {
                    $classMessage = 'class="platformMessage"';
                    $iconMessage = '<img src="' . get_icon_url('important') . '" alt="' . get_lang('Important') . '" />';
                }
                else
                {
                    $classMessage = ( $message->isRead() ? 'class="readMessage"' : 'class="unreadMessage"' );
                    $iconMessage = ( $message->isRead() ? '<img src="' . get_icon_url('mail_open') . '" alt="" />' : '<img src="' . get_icon_url('mail_close') . '" alt="" />' );
                }
                
                $output .= "\n"
                         . '<tr ' . $classMessage . '>' . "\n"
                         . '<td>' . $iconMessage . '</td>' . "\n"
                         . '<td>'
                         . '<a href="' . get_path( 'clarolineRepositoryWeb' ) . 'messaging/readmessage.php?messageId=' . $message->getId() . '&amp;type=received">'
                         . claro_htmlspecialchars( $message->getSubject() )
                         . '</a>' . "\n"
                         . '</td>' . "\n"
                         . '<td>' . claro_htmlspecialchars( $message->getSenderLastName() ) . '&nbsp;' . claro_htmlspecialchars( $message->getSenderFirstName() ) . '</td>' . "\n"
                         . '<td align="center">' . claro_html_localised_date( get_locale( 'dateFormatLong' ), strtotime( $message->getSendTime() ) ) . '</td>' . "\n"
                         . '</tr>' . "\n";
            }
        }
        else
        {
                $output .= "\n"
                         . '<tr>' . "\n"
                         . '<td colspan="4" align="center">' . get_lang('Empty') . '</td>' . "\n"
                         . '</tr>' . "\n";
        }
        
        $output .= "\n"
                 . '</tbody>' . "\n"
                 . '</table>' . "\n"
                 . '<a href="'.get_path('clarolineRepositoryWeb')
                 . 'messaging/index.php' . '">'
                 . get_lang('View all my messages')
                 . '</a>';
        
        return $output;
    }
    
    
    public function renderTitle()
    {
        $output = get_lang('My %numberOfMessages last messages', array( '%numberOfMessages' => get_conf('myboxNumberOfMessage',5) ) );
        
        return $output;
    }
}

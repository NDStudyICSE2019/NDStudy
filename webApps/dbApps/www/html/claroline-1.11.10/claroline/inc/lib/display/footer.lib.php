<?php // $Id: footer.lib.php 14373 2013-01-31 08:26:34Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Claroline page footer.
 *
 * @version     $Revision: 14373 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */
 
class ClaroFooter extends CoreTemplate
{
    private static $instance = false;
    
    private $hidden = false;
    
    public function __construct()
    {
        parent::__construct('footer.tpl.php');
    }
    
    public static function getInstance()
    {
        if ( ! self::$instance )
        {
            self::$instance = new ClaroFooter;
        }
        
        return self::$instance;
    }
    
    public function hide()
    {
        $this->hidden = true;
    }
    
    public function show()
    {
        $this->hidden = false;
    }
    
    public function render()
    {
        if ( $this->hidden )
        {
            return '<!-- footer hidden -->' . "\n";
        }
        
        $currentCourse =  claro_get_current_course_data();
        
        if ( claro_is_in_a_course() )
        {
            $courseManagerOutput = '<div id="courseManager">'
                . get_lang('Manager(s) for %course_code'
                    , array('%course_code' => $currentCourse['officialCode']) )
                . ' : '
                ;
                
            $currentCourseTitular = empty ( $currentCourse['titular'] )
                ? get_lang ( 'Course manager' )
                : $currentCourse['titular']
                ;
            
            if ( empty($currentCourse['email']) )
            {
                $courseManagerOutput .= '<a href="' . get_module_url('CLUSR') . '/user.php">'. $currentCourseTitular.'</a>';
            }
            else
            {
                $courseManagerOutput .= '<a href="mailto:' . $currentCourse['email'] . '?body=' . $currentCourse['officialCode'] . '&amp;subject=[' . rawurlencode( get_conf('siteName')) . ']' . '">' . $currentCourseTitular . '</a>';
            }
            
            $courseManagerOutput .= '</div>';
            
            $this->assign( 'courseManager', $courseManagerOutput );
        }
        else
        {
            $this->assign( 'courseManager', '' );
        }
        
        $platformManagerOutput = '<div id="platformManager">'
            . get_lang('Administrator for %site_name'
                , array('%site_name'=>get_conf('siteName'))). ' : '
            . '<a href="mailto:' . get_conf('administrator_email')
            . '?subject=[' . rawurlencode( get_conf('siteName') ) . ']'.'">'
            . get_conf('administrator_name')
            . '</a>'
            ;
        
        if ( get_conf('administrator_phone') != '' )
        {
            $platformManagerOutput .= '<br />' . "\n"
                . get_lang('Phone : %phone_number'
                    , array('%phone_number' => get_conf('administrator_phone'))) ;
        }
        
        $platformManagerOutput .= '</div>';
        
        $this->assign( 'platformManager', $platformManagerOutput );
        
        $poweredByOutput = '<span class="poweredBy">'
            . get_lang('Powered by')
            . ' <a href="http://www.claroline.net" target="_blank">Claroline</a> '
            . '&copy; 2001 - 2013'
            . '</span>';
        
        $this->assign( 'poweredBy', $poweredByOutput );
        
        return parent::render();
    }
}
<?php // $Id: banner.lib.php 13812 2011-11-14 14:30:24Z jrm_ $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Class used to configure and display the page banners.
 *
 * @version     $Revision: 13812 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */

FromKernel::uses ( 'display/breadcrumbs.lib', 'display/viewmode.lib' );

class ClaroBanner extends CoreTemplate
{
    protected static $instance = false;
    
    protected $hidden = false;
    public $breadcrumbs;
    public $viewmode;
    
    public function __construct()
    {
        $this->breadcrumbs = ClaroBreadCrumbs::getInstance();
        $this->viewmode = ClaroViewMode::getInstance();
        parent::__construct('banner.tpl.php');
        
        $this->breadcrumbLine = true;
    }
    
    public static function getInstance()
    {
        if ( ! self::$instance )
        {
            self::$instance = new ClaroBanner;
        }

        return self::$instance;
    }
    
    /**
     * Hide the banners
     */
    public function hide()
    {
        $this->hidden = true;
    }
    
    /**
     * Show the banners
     */
    public function show()
    {
        $this->hidden = false;
    }
    
    /**
     * Hide breadcrump line
     */
    public function hideBreadcrumbLine()
    {
        $this->breadcrumbLine = false;
    }
    
    /**
     * Render the banners
     * @return  string
     */
    public function render()
    {
        if ( $this->hidden )
        {
            return '<!-- banner hidden -->' . "\n";
        }
        
        $this->_prepareCampusBanner();
        $this->_prepareUserBanner();
        
        return parent::render();
    }
    
    /**
     * Prepare the user banner
     */
    private function _prepareUserBanner()
    {
        if( claro_is_user_authenticated() )
        {
            $userToolUrlListLeft    = array();
            $userToolUrlListRight   = array();
            
            if (get_conf('display_former_homepage'))
            {
                
            }
            
            $userToolUrlListLeft[]  = '<a href="'
                . get_path('clarolineRepositoryWeb')
                . 'desktop/index.php" target="_top">'
                . get_lang('My desktop').'</a>'
                ;
            
            $userToolUrlListLeft[]  = '<a href="'
                . get_path('clarolineRepositoryWeb')
                . 'messaging" target="_top">'
                . get_lang('My messages').'</a>'
                ;
            
            if(claro_is_platform_admin())
            {
                $userToolUrlListLeft[] = '<a href="'
                    . get_path('clarolineRepositoryWeb')
                    .'admin/" target="_top">'
                    . get_lang('Platform administration'). '</a>'
                    ;
            }
            
            $userToolUrlListRight[]  = '<a href="'
                . get_path('clarolineRepositoryWeb')
                . 'auth/profile.php" target="_top">'
                . get_lang('Manage my account').'</a>'
                ;
            
            $userToolUrlListRight[] = '<a href="'.  get_path('url')
                . '/index.php?logout=true" target="_top">'
                . get_lang('Logout').'</a>'
                ;
            
            $this->assign('userToolListRight', $userToolUrlListRight);
            
            $this->assign('userToolListLeft', $userToolUrlListLeft);
            
            $this->showBlock('userBanner');
        }
        else
        {
            $this->hideBlock('userBanner');
        }
    }
    
    /**
     * Prepare the campus banner
     */
    private function _prepareCampusBanner()
    {
        $campus = array();
        
        $campus['siteName'] =  get_conf('siteLogo') != ''
            ? '<img src="' . get_conf('siteLogo') . '" alt="'.get_conf('siteName').'"  />'
            : get_conf('siteName')
            ;

        $institutionNameOutput = '';

        $bannerInstitutionName = (get_conf('institutionLogo') != '')
            ? '<img src="' . get_conf('institutionLogo')
                . '" alt="' . get_conf('institution_name') . '" />'
            : get_conf('institution_name')
            ;

        if( !empty($bannerInstitutionName) )
        {
            if( get_conf('institution_url') != '' )
            {
                $institutionNameOutput .= '<a href="'
                    . get_conf('institution_url').'" target="_top">'
                    . $bannerInstitutionName.'</a>'
                    ;
            }
            else
            {
                $institutionNameOutput .= $bannerInstitutionName;
            }
        }

        /* --- External Link Section --- */
        if( claro_get_current_course_data('extLinkName') != '' )
        {
            $institutionNameOutput .= get_conf('institution_url') != ''
                ? ' / '
                : ' '
                ;

            if( claro_get_current_course_data('extLinkUrl') != '' )
            {
                $institutionNameOutput .= '<a href="'
                    . claro_get_current_course_data('extLinkUrl')
                    . '" target="_top">'
                    . claro_get_current_course_data('extLinkName')
                    . '</a>'
                    ;
            }
            else
            {
                $institutionNameOutput .= claro_get_current_course_data('extLinkName');
            }
        }
        
        $campus['institution'] = $institutionNameOutput;

        $this->assign( 'campus', $campus );
    }
}
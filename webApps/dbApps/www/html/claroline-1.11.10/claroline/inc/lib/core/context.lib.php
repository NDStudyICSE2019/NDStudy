<?php // $Id: context.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Context handling library.
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 * @since       1.9
 */

defined('CLARO_CONTEXT_PLATFORM')
    || define('CLARO_CONTEXT_PLATFORM',     'platform');

defined('CLARO_CONTEXT_COURSE')
    || define('CLARO_CONTEXT_COURSE',       'course');

defined('CLARO_CONTEXT_GROUP')
    || define('CLARO_CONTEXT_GROUP',        'group');

defined('CLARO_CONTEXT_USER')
    || define('CLARO_CONTEXT_USER',         'user');

defined('CLARO_CONTEXT_TOOLINSTANCE')
    || define('CLARO_CONTEXT_TOOLINSTANCE', 'toolInstance');

defined('CLARO_CONTEXT_TOOLLABEL')
    || define('CLARO_CONTEXT_TOOLLABEL',    'toolLabel');

defined('CLARO_CONTEXT_MODULE')
    || define('CLARO_CONTEXT_MODULE',       'moduleLabel');

/**
 * Claroline Execution Context
 */
class Claro_Context
{
    /**
     * Get the current execution context
     * @return array
     */
    public static function getCurrentContext()
    {
        $context = array();
        
        if (claro_is_in_a_course())
        {
            $context[CLARO_CONTEXT_COURSE] = claro_get_current_course_id();
        }

        if (claro_is_in_a_group())
        {
            $context[CLARO_CONTEXT_GROUP] = claro_get_current_group_id();
        }
        
        if ( claro_is_in_a_tool() )
        {
            if ( isset($GLOBALS['tlabelReq']) && $GLOBALS['tlabelReq'] )
            {
                $context[CLARO_CONTEXT_TOOLLABEL] = $GLOBALS['tlabelReq'];
            }
            
            if ( claro_get_current_tool_id() )
            {
                $context[CLARO_CONTEXT_TOOLINSTANCE] = claro_get_current_tool_id();
            }
        }
        
        if ( get_current_module_label() )
        {
            $context[CLARO_CONTEXT_MODULE] = get_current_module_label();
        }
        
        return $context;
    }
    
    /**
     * Get the current context array formated for use in urls
     * @return array
     */
    public static function getCurrentUrlContext()
    {
        $givenContext = self::getCurrentContext();
        
        return self::getUrlContext( $givenContext );
    }

    /**
     * Get the given context array formated for use in urls
     * @param array $givenContext
     * @return array
     */
    public static function getUrlContext( $givenContext )
    {
        $context = array();
        
        if ( ( claro_is_in_a_group() && !isset($givenContext[CLARO_CONTEXT_GROUP]) )
            || isset($givenContext[CLARO_CONTEXT_GROUP]) )
        {
            $context['gidReset'] = 'true';
        }
        
        if ( ( claro_is_in_a_course() && !isset($givenContext[CLARO_CONTEXT_COURSE]) )
            || isset($givenContext[CLARO_CONTEXT_COURSE]))
        {
            $context['cidReset'] = 'true';
        }
        
        if ( isset($givenContext[CLARO_CONTEXT_COURSE]) )
        {
            $context['cidReq'] = $givenContext[CLARO_CONTEXT_COURSE];
        }
        
        if ( isset($givenContext[CLARO_CONTEXT_GROUP]) )
        {
            $context['gidReq'] = $givenContext[CLARO_CONTEXT_GROUP];
        }
        
        if( isset( $_REQUEST['inPopup'] ) )
        {
            $context['inPopup'] = $_REQUEST['inPopup'];
        }
        
        if( isset( $_REQUEST['inFrame'] ) )
        {
            $context['inFrame'] = $_REQUEST['inFrame'];
        }
        
        if( isset( $_REQUEST['embedded'] ) )
        {
            $context['embedded'] = $_REQUEST['embedded'];
        }
        
        if( isset( $_REQUEST['hide_banner'] ) )
        {
            $context['hide_banner'] = $_REQUEST['hide_banner'];
        }
        
        if( isset( $_REQUEST['hide_footer'] ) )
        {
            $context['hide_footer'] = $_REQUEST['hide_footer'];
        }
        
        if( isset( $_REQUEST['hide_body'] ) )
        {
            $context['hide_body'] = $_REQUEST['hide_body'];
        }
        
        if ( $moduleLabel = claro_called_from() )
        {
            $context['calledFrom'] = $moduleLabel;
        }
        
        return $context;
    }
}
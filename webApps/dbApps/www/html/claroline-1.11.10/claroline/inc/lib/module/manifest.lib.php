<?php // $Id: manifest.lib.php 14315 2012-11-08 14:51:17Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 * manifest parser class and utility functions
 * 
 * @version 1.9 $Revision: 14315 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see     http://www.claroline.net/wiki/index.php/Install
 * @author  Claro Team <cvs@claroline.net>
 * @package KERNEL
 */

require_once dirname(__FILE__) . '/../backlog.class.php';

/**
 *  Module manifest parser
 */
class ModuleManifestParser
{
    protected $elementPile;
    protected $moduleInfo;
    protected $backlog;

    public function __construct()
    {
        $this->backlog = new Backlog;
        $this->elementPile = array();
        $this->moduleInfo = array();
    }

    // TODO handle other module types
    /**
     *  Parse the manifest file given in argument
     *  @param   string manifestPath, path to the manifest file
     *  @return  bool false on failure, array moduleInfo on success
     */
    public function parse( $manifestPath )
    {
        // reset state
        $this->elementPile = array();
        $this->moduleInfo = array();
        
        $this->backlog->info( 'Parsing manifest file ' . $manifestPath );
        
        if ( !file_exists( $manifestPath ) )
        {
            $this->backlog->failure(
                  get_lang('Manifest missing : %filename'
                , array('%filename' => $manifestPath)));
            return false;
        }

        $xmlParser = xml_parser_create();

        xml_set_element_handler(
              $xmlParser
            , array(&$this, 'startElement')
            , array(&$this, 'endElement') );

        xml_set_character_data_handler(
              $xmlParser
            , array(&$this, 'elementData') );

        // read manifest file

        if ( false === ( $data = @file_get_contents( $manifestPath ) ) )
        {
            $this->backlog->failure(get_lang('Cannot open manifest file'));
            return false;
        }
        else
        {
            $this->backlog->debug('Manifest open : '.$manifestPath);
            $data = claro_html_entity_decode(urldecode($data));
        }

        if ( !xml_parse( $xmlParser, $data ) )
        {
            // if reading of the xml file in not successfull :
            // set errorFound, set error msg, break while statement
            $this->backlog->failure(get_lang('Error while parsing manifest'));
            return false;
        }

        // liberate parser ressources
        xml_parser_free( $xmlParser );
        
        // complete module info for missing optional elements
        
        if ( ! array_key_exists( 'ENTRY', $this->moduleInfo ) )
        {
            $this->moduleInfo['ENTRY'] = 'entry.php';
        }
        
        // Module License
        if ( ! array_key_exists( 'LICENSE', $this->moduleInfo ) )
        {
            $this->moduleInfo['LICENSE'] = '';
        }
        
        // Module version
        if ( ! array_key_exists( 'VERSION', $this->moduleInfo ) )
        {
            $this->moduleInfo['VERSION'] = '';
        }
        
        // Module description
        if ( ! array_key_exists( 'DESCRIPTION', $this->moduleInfo ) )
        {
            $this->moduleInfo['DESCRIPTION'] = '';
        }
        
        // Author informations
        if ( ! array_key_exists( 'AUTHOR', $this->moduleInfo ) )
        {
            $this->moduleInfo['AUTHOR'] = array();
        }
        
        // Author name
        if ( ! array_key_exists( 'NAME', $this->moduleInfo['AUTHOR'] ) )
        {
            $this->moduleInfo['AUTHOR']['NAME'] = '';
        }
        
        // Author email
        if ( ! array_key_exists( 'EMAIL', $this->moduleInfo['AUTHOR'] ) )
        {
            $this->moduleInfo['AUTHOR']['EMAIL'] = '';
        }
        
        // Author website
        if ( ! array_key_exists( 'WEB', $this->moduleInfo['AUTHOR'] ) )
        {
            $this->moduleInfo['AUTHOR']['WEB'] = '';
        }
        
        // Module website
        if ( ! array_key_exists( 'WEB', $this->moduleInfo ) )
        {
            $this->moduleInfo['WEB'] = '';
        }
        
        // Set default module context
        if ( ! array_key_exists( 'CONTEXTS', $this->moduleInfo )
            || empty($this->moduleInfo['CONTEXTS']) )
        {
            if ( strtoupper( $this->moduleInfo['TYPE'] ) == 'TOOL' )
            {
                $this->moduleInfo['CONTEXTS'] = array('course');
            }
            else
            {
                $this->moduleInfo['CONTEXTS'] = array('platform');
            }
        }

        return $this->moduleInfo;
    }

    /**
     *  SAX Parser Callback method : end of an element
     */
    public function endElement($parser,$name)
    {
        array_pop( $this->elementPile );
    }

    /**
     *  SAX Parser Callback method : start of an element
     */
    public function startElement($parser, $name, $attributes)
    {
        array_push( $this->elementPile, $name );
    }

    /**
     *  SAX Parser Callback method : data handler
     */
    public function elementData($parser,$data)
    {
        $currentElement = end( $this->elementPile );

        if ( claro_debug_mode() )
        {
            $this->backlog->debug( 'The metadata ' . $currentElement
                . ' as been found with value ' . var_export($data,true) );
        }

        switch ( $currentElement )
        {
            case 'TYPE' :
            {
                $this->moduleInfo['TYPE'] = $data;
                break;
            }
            case 'DESCRIPTION' :
            {
                $this->moduleInfo['DESCRIPTION'] = $data;
                break;
            }
            case 'EMAIL':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'AUTHOR':
                    {
                        $this->moduleInfo['AUTHOR']['EMAIL'] = $data;
                        break;
                    }
                }
                break;
            }
            case 'LABEL':
            {
                $this->moduleInfo['LABEL'] = $data;
                break;
            }
            case 'ENTRY':
            {
                $this->moduleInfo['ENTRY'] = $data;
                break;
            }
            case 'LICENSE':
            {
                $this->moduleInfo['LICENSE'] = $data;
                break;
            }
            case 'ICON':
            {
                $this->moduleInfo['ICON'] =  $data;
                break;
            }
            case 'NAME':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'MODULE':
                    {
                        $this->moduleInfo['NAME'] = $data;
                        break;
                    }
                    case 'AUTHOR':
                    {
                        $this->moduleInfo['AUTHOR']['NAME'] = $data;
                        break;
                    }
                }
                break;
            }
            case 'DEFAULT_DOCK' :
            {
                if ( claro_debug_mode() )
                {
                    $this->backlog->debug(
                        'The use of default_dock is deprecated in manifest file, please use defaultDock instead' );
                }

                // nobreak
            }
            case 'DEFAULTDOCK':
            {
                if ( ! array_key_exists( 'DEFAULT_DOCK', $this->moduleInfo ) )
                {
                    $this->moduleInfo['DEFAULT_DOCK'] = array();
                }

                $this->moduleInfo['DEFAULT_DOCK'][] = $data;
                break;
            }
            case 'WEB':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'MODULE':
                    {
                        $this->moduleInfo['WEB'] = $data;
                        break;
                    }
                    case 'AUTHOR':
                    {
                        $this->moduleInfo['AUTHOR']['WEB'] = $data;
                        break;
                    }
                }

                break;
            }
            // PHP/MySQL/Claroline versions dependencies
            // TODO check in install
            case 'MINVERSION':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'PHP':
                    {
                        $this->moduleInfo['PHP_MIN_VERSION'] = $data;
                        break;
                    }
                    case 'MYSQL':
                    {
                        $this->moduleInfo['MYSQL_MIN_VERSION'] = $data;
                        break;
                    }
                    case 'CLAROLINE' :
                    {
                        $this->moduleInfo['CLAROLINE_MIN_VERSION'] = $data;
                        break;
                    }
                }
                break;
            }
            case 'MAXVERSION':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'PHP':
                    {
                        $this->moduleInfo['PHP_MAX_VERSION'] = $data;
                        break;
                    }
                    case 'MYSQL':
                    {
                        $this->moduleInfo['MYSQL_MAX_VERSION'] = $data;
                        break;
                    }
                    case 'CLAROLINE' :
                    {
                        $this->moduleInfo['CLAROLINE_MAX_VERSION'] = $data;
                        break;
                    }
                }
                break;
            }
            // FIXME I'm not sure this is cool...
            // if VERSION is 1.8 what about 1.8.* ?
            // check the behaviour of version_compare...
            case 'VERSION':
            {
                $parent = prev($this->elementPile);
                switch ($parent)
                {
                    case 'MODULE':
                    {
                        $this->moduleInfo['VERSION'] = $data;
                        break;
                    }
                    case 'CLAROLINE' :
                    {
                        $this->moduleInfo['CLAROLINE_MIN_VERSION'] = $data;
                        $this->moduleInfo['CLAROLINE_MAX_VERSION'] = $data;
                        break;
                    }
                    case 'PHP':
                    {
                        $this->moduleInfo['PHP_MIN_VERSION'] = $data;
                        $this->moduleInfo['PHP_MAX_VERSION'] = $data;
                        break;
                    }
                    case 'MYSQL':
                    {
                        $this->moduleInfo['MYSQL_MIN_VERSION'] = $data;
                        $this->moduleInfo['MYSQL_MAX_VERSION'] = $data;
                        break;
                    }
                }
                break;
            }
            case 'CONTEXT':
            {
                if ( ! array_key_exists( 'CONTEXTS', $this->moduleInfo ) )
                {
                    $this->moduleInfo['CONTEXTS'] = array();
                }
                
                if ( ! in_array( $data, $this->moduleInfo['CONTEXTS'] ) )
                {
                    $this->moduleInfo['CONTEXTS'][] = $data;
                }
                
                break;
            }
        }
    }
}

/**
 *  Check if the module information are valid :
 *      - is an array
 *      - in not empty
 *      - contains required elements (label, name, type)
 */
function checkModuleInfo ( $module_info )
{
    if ( ! is_array( $module_info ) || count( $module_info ) == 0 )
    {
        return claro_failure::set_failure(get_lang('Empty manifest'));
    }
    
    $missingElement = array_diff(
        array('LABEL','NAME','TYPE'),
        array_keys($module_info)
    );

    if ( !empty($missingElement) )
    {
        return claro_failure::set_failure(
            get_lang( 'Missing elements in module Manifest : %MissingElements'
                     , array('%MissingElements' => implode(',',$missingElement)) ) );
    }
    else
    {
        return true;
    }
}

/**
 *  Helper function to read, validate and complete module information from
 *  a manifest file
 */
function readModuleManifest($modulePath)
{
    $manifestPath = $modulePath. '/manifest.xml';
    
    if (! file_exists ($manifestPath) )
    {
        return claro_failure::set_failure(
            get_lang( 'Manifest missing : %filename'
                     , array('%filename' => $manifestPath) ) );
    }
    else
    {
        $parser = new ModuleManifestParser;
        $moduleInfo = $parser->parse($manifestPath);
        
        if ( !checkModuleInfo( $moduleInfo ) )
        {
            return false;
        }
        else
        {
            return $moduleInfo;
        }
    }
}

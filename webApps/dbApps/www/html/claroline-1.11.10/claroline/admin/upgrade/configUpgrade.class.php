<?php // $Id: configUpgrade.class.php 13708 2011-10-19 10:46:34Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Config class to upgrade configuration file.
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/config_def/
 * @package     CONFIG
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 */

require_once dirname(__FILE__) . '/../../inc/lib/config.class.php';

class ConfigUpgrade extends Config
{
    
    /**
     * load definition and configuration file
     */

    function load()
    {
        // search config file
        $def_filename = $this->def_dirname . '/' . $this->config_code . '.def.conf.inc.php';
       
        if ( file_exists($def_filename) )
        {
            // set definition filename
            $this->def_filename = $def_filename;

            // load definition file
            $this->load_def_file();
            $this->def_loaded = true;

            // set configuration filename
            $this->config_filename = $this->build_config_filename();

            $new_config_filename = $this->config_filename ;

            /*
             * Try to retrieve properties in the old configuration file,
             * if the configuration is not updated
             */

            if ( ! file_exists($new_config_filename) )
            {
                $old_config_filename = $this->build_old_config_filename();

                if ( file_exists($old_config_filename) )
                {
                    $this->config_filename = $old_config_filename;
                }
            }

            // init list of properties
            $this->init_property_list();

            // set config filename to new for the upgrade
            $this->config_filename = $new_config_filename ;

            // init md5
            $this->init_md5();

            return true;
        }
        else
        {
            // error definition file doesn't exist
            $this->backlog->failure(get_lang('Definition file doesn\'t exist'));
            return false;
        }
    }

    /**
     * Build the path and filename of the config file in version of claroline < 1.8
     *
     * @return string : complete path and name of config file
     */

    function build_old_config_filename()
    {
        if ( !empty($this->conf_def['config_file']) )
        {
            // get the name of config file in definition file
            return get_path('incRepositorySys') . '/conf/' . $this->conf_def['config_file'];
        }
        else
        {
            // build the filename with the config_code
            return get_path('incRepositorySys') . '/conf/' . $this->config_code . '.conf.php';
        }
    }

}

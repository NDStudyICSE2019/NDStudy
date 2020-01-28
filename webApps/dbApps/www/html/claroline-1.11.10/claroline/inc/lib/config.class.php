<?php // $Id: config.class.php 14713 2014-02-17 08:30:54Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Config lib contain function to manage conf file
 *
 * @version 1.9 $Revision: 14713 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see http://www.claroline.net/wiki/config_def/
 * @package CONFIG
 * @author Claro Team <cvs@claroline.net>
 * @author Christophe Gesch� <moosh@claroline.net>
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

require_once dirname(__FILE__) . '/config.lib.inc.php';
require_once dirname(__FILE__) . '/backlog.class.php';
require_once dirname(__FILE__) . '/language.lib.php';
require_once dirname(__FILE__) . '/user.lib.php';

/**
 * To use this class.
 *
 * Example :
 * $fooConfig = new Config('CLFOO');
 *
 * $fooConfig->load(); Load property with actual values in configs files.
 * $fooConfig->save(); write a new config file if (following def file),
 *                     a property would be in the config file,
 *                     and this property is in memory,
 *                     the value would be write in the new config file)
 */

class Config
{
    // config code
    var $config_code;

    // path of the configuration file
    var $config_filename;

    // path of the definition file
    var $def_filename;

    // list of properties and values
    var $property_list = array();

    // definition of configuration file
    var $conf_def = array();

    // dirname of config folder
    var $conf_dirname = '';

    // dirname of def folder
    var $def_dirname = '';

    // list of property definition
    var $conf_def_property_list = array();

    // md5 of the properties
    var $md5;

    // backlog object
    var $backlog;

    // definition file loaded
    var $def_loaded;

    /**
     * constructor, build a config object
     *
     * @param string $config_code
     */

    function Config($config_code)
    {
        $this->config_code = $config_code;
        $this->conf_dirname = claro_get_conf_repository(); // in 1.8 is 'platform/conf' folder
        $this->def_dirname = claro_get_conf_def_file($config_code) ;
        $this->backlog = new Backlog();
        $this->def_loaded = false;
    }

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

            // init list of properties
            $this->init_property_list();

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
     * Initialise property list : get default values in definition file and overwrite then with values in configuration file
     */

    function init_property_list()
    {
        $this->property_list = array();

        // get default value from definition file
        foreach ( $this->conf_def_property_list as $property_name => $property_def )
        {
            if ( isset($property_def['default']) )
            {
                if ( 'boolean' == $property_def['type'] )
                {
                    $this->property_list[$property_name] = trueFalse($property_def['default']);
                }
                elseif ( 'multi' == $property_def['type'] && ! is_array($property_def['default']) )
                {
                    $this->property_list[$property_name][] = $property_def['default'];
                }
                else
                {
                    $this->property_list[$property_name] = $property_def['default'];
                }
            }
            else
            {
                $this->property_list[$property_name] = null;
            }
        }

        // get values from configuration file
        if ( file_exists($this->config_filename) )
        {
            $config_filename = $this->config_filename;

            include($config_filename);

            foreach ( $this->conf_def_property_list as $property_name => $property_def )
            {
                if ( isset($property_def['container']) && 'CONST' == $property_def['container'])
                {
                    if ( defined($property_name) )
                    {
                        $this->property_list[$property_name] = constant($property_name);
                    }
                }
                else
                {
                    if ( isset($GLOBALS[$property_name]) )
                    {
                        $this->property_list[$property_name] = $GLOBALS[$property_name];
                    }

                    if ( isset($$property_name) )
                    {
                        $this->property_list[$property_name] = $$property_name;
                    }
                }
            }
        }
        return $this->property_list;
    }

    /**
     * Read defintion file and set value of $conf_def and $conf_def_property_list
     */

    function load_def_file()
    {
        // get $conf_def and $conf_def_property_list from definition file
        $def_filename = $this->def_filename;

        $conf_def = array();
        $conf_def_property_list = array();

        include $def_filename ;

        $this->conf_def = $conf_def;
        $this->conf_def_property_list = $conf_def_property_list;
    }

    /**
     * Build the path and filename of the config file
     *
     * @return string : complete path and name of config file
     */

    function build_config_filename()
    {
        if ( !empty($this->conf_def['config_file']) )
        {
            // get the name of config file in definition file
            return $this->conf_dirname . '/' . $this->conf_def['config_file'];
        }
        else
        {
            // build the filename with the config_code
            return $this->conf_dirname . $this->config_code . '.conf.php';
        }
    }

    /**
     * Get the name of configuration in definition
     *
     * @return string name of the current config
     */

    function get_conf_name()
    {
        if ( !empty($this->conf_def['config_name']) )
        {
            // name is the config name
            $name = $this->conf_def['config_name'];
        }
        else
        {
            // name is the config_file name
            $name = $this->config_filename;
        }
        return $name;
    }

    /**
     * Get the value of a property
     *
     * @param string $name value name
     * @return value of the givent property | null if not found
     */

    function get_property($name)
    {
        if ( isset($this->property_list[$name]) )
        {
            return $this->property_list[$name];
        }
        else
        {
            return null;
        }
    }

    /**
     * Set the value of a property with validation.
     *
     * @param string $name value name
     * @param mixed $value content for the property to set
     *
     * @return boolean true on success | false if unvalid or unknow value
     */

    function set_property($name,$value)
    {
        if ( isset($this->conf_def_property_list[$name]) )
        {
            if ( validate_property($name,$value) )
            {
                $this->property_list[$name] = $value;
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $this->backlog->failure(str_replace('Property %name unknow',array('%name'=>$name)));
            return false;
        }
    }

    /**
     * Get the property/value list
     *
     * @return array associative list property name/value
     */

    function get_property_list()
    {
        return $this->property_list;
    }

    /**
     * Validate value of the list of new properties
     *
     * Given property are checked only if defined in def file.
     *
     * Other are ignored and don't fail the validation
     *
     * @param array $newPropertyList array of property => new values
     * @return boolean : true if ALL know value are valid.
     *
     */

    function validate($newPropertyList)
    {
        $valid = true;

        $property_name_list = array_keys($this->conf_def_property_list);

        foreach ( $property_name_list as $name )
        {
            if ( isset($newPropertyList[$name]) )
            {

                if ( $this->validate_property($name,$newPropertyList[$name]) )
                {
                    $this->property_list[$name] = $newPropertyList[$name];
                }
                else
                {
                    $valid = false;
                }
            }
        }

        return $valid ;
    }

    /**
     * Validate value of a property
     *
     * @param string $name
     * @param string $value
     *
     */

    function validate_property($name,$value)
    {
        $valid = true;

        $property_def = $this->conf_def_property_list[$name];
        // init property type
        if ( isset($property_def['type']) ) $type = $property_def['type'];
        else                                $type = null;

        // init property label
        if ( isset($property_def['label']) ) $label = $property_def['label'];
        else                                $label = $name;

        // init property accepted value
        if ( ! empty($property_def['acceptedValue']) ) $acceptedValue = $property_def['acceptedValue'];
        else                                           $acceptedValue = array();

        if ( isset($property_def['acceptedValueType']) )
        {
            switch ( $property_def['acceptedValueType'] )
            {
                case 'css':
                    $acceptedValue = array_merge( $acceptedValue, $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'web/css/','folder','.css') );
                    $acceptedValue = array_merge( $acceptedValue, $this->retrieve_accepted_values_from_folder(get_path('rootSys').'platform/css','folder','.css') );
                    break;
                case 'lang':
                    $acceptedValue = array_merge( $acceptedValue, $this->retrieve_accepted_values_from_folder(get_path('rootSys').'claroline/lang','folder') );
                    break;
                case 'auth':
                    $acceptedValue = array_merge( $acceptedValue, $this->retrieve_accepted_values_from_folder(get_path('rootSys').'claroline/auth/extauth/drivers','file','.php') );
                    break;
                case 'editor':
                    $acceptedValue = array_merge( $acceptedValue, $this->retrieve_accepted_values_from_folder(get_path('rootSys').'claroline/editor','folder') );
                    break;
                case 'timezone':
                    $acceptedValue = array_merge( $acceptedValue, $this->get_timezone_list() );
                    break;
            }
        }

        // validate property
        switch ($type)
        {
            case 'boolean' :
                if ( ! is_bool ($value ) && ! in_array(strtoupper($value), array ('TRUE', 'FALSE','1','0' )))
                {
                    $this->backlog->failure(get_lang('%name should be boolean',array('%name'=>$label)));
                    $valid = false;
                }
                break;

            case 'integer' :
                if ( !preg_match('/\d+/i',$value) )
                {
                    $this->backlog->failure( get_lang('%name should be integer',array('%name'=>$label)));
                    $valid = false;
                }
                elseif ( isset($acceptedValue['max']) && $value > $acceptedValue['max'] )
                {
                    $this->backlog->failure( get_lang('%name should be integer inferior or equal to %value', array('%name'=>$label,'%value'=>$acceptedValue['max'])) );
                    $valid = false;
                }
                elseif ( isset($acceptedValue['min']) && $value < $acceptedValue['min'] )
                {
                    $this->backlog->failure( get_lang('%name should be integer superior or equal to %value', array('%name'=>$label,'%value'=>$acceptedValue['min'])));
                    $valid = false;
                }
                break;

            case 'enum' :

                if ( isset($acceptedValue) && is_array($acceptedValue) )
                {
                    if ( !in_array($value, array_keys($acceptedValue)) )
                    {
                        $this->backlog->failure( get_lang('%value should be in enum list of %name', array('%value'=>$value,'%name'=>$label)) );
                        $valid = false;
                    }
                }
                break;

            case 'multi' :

                if ( is_array($value) )
                {
                    foreach ( $value as $item_value)
                    {
                        if ( !in_array($item_value,array_keys($acceptedValue)) )
                        {
                            $this->backlog->failure(get_lang('%value should be in the accepted value list of %name',array('%value' => $item_value, '%name' => $label)) );
                            $valid = false;
                        }
                    }
                }
                else
                {
                    if ( ! empty($value) )
                    {
                        $this->backlog->failure(get_lang('%name should be an array',array('%name' => $label) ));
                        $valid = false;
                    }
                }
                break;

            case 'relpath' :
                break;

            case 'syspath' :
            case 'wwwpath' :
                if ( empty($value) )
                {
                    $this->backlog->failure( get_lang('Field \'%name\' is required', array('%name' => $label)) );
                    $valid = false;
                }
                break;

            case 'regexp' :
                if ( isset($acceptedValue) && !preg_match( '/' . $acceptedValue . '/i', $value ))
                {
                    $this->backlog->failure( get_lang('%name should be match %regular_expression', array('%name' => $label,'%regular_expression'=> $acceptedValue) ));
                    $valid = false;
                }
                break;
            case 'email' :
                if( ! empty( $value ) )
                {
                    if( ! is_well_formed_email_address( $value ) )
                    {
                        $this->backlog->failure( get_lang(' %email is not a valid e-mail address.', array('%email' => $value)));
                        $valid = false;
                    }
                }
                break;
            case 'string' :
            case 'text' :
            default :
                $valid = true;
        }

        return $valid;

    }

    /**
     * Save all properties in config file
     */

    function save($generatorFile=__FILE__)
    {
        // split generation file

        if ( strlen($generatorFile)>50 )
        {
            $generatorFile = str_replace("\\","/",$generatorFile);
            $generatorFile = "\n\t\t".str_replace("/","\n\t\t/",$generatorFile);
        }

        $fileHeader = '<?php '."\n"
        . '/** '
        . ' * DONT EDIT THIS FILE - NE MODIFIEZ PAS CE FICHIER '."\n"
        . ' * -------------------------------------------------'."\n"
        . ' * Generated by '.$generatorFile.' '."\n"
        . ' * Date '.claro_html_localised_date(get_locale('dateTimeFormatLong'))."\n"
        . ' * -------------------------------------------------'."\n"
        . ' * DONT EDIT THIS FILE - NE MODIFIEZ PAS CE FICHIER '."\n"
        . ' **/'."\n\n";

        $fileHeader .=  '// $'.$this->config_code.'GenDate is an internal mark'."\n"
        . '   $'.$this->config_code.'GenDate = "'.time().'";'."\n\n";


        if ( ! empty($this->conf_def['technicalInfo']) )
        {
            $fileHeader .= '/*' . str_replace('*/', '* /', $this->conf_def['technicalInfo']) . '*/' ;
        }

        // open configuration file
        if ( false !== ($handle = @fopen($this->config_filename,'w') ) )
        {

            // write header
            fwrite($handle,$fileHeader);

            foreach ( $this->property_list as $name => $value )
            {

                // build comment about the property
                $propertyComment = '';

                // comment : add description
                if ( !empty($this->conf_def_property_list[$name]['description']) )
                {
                    $propertyComment .= '/* ' . $name . ' : ' . str_replace("\n",'',$this->conf_def_property_list[$name]['description']) . ' */' . "\n";
                }
                else
                {
                    if ( isset($this->conf_def_property_list[$name]['label']) )
                    {
                        $propertyComment .= '/* '.$name.' : '.str_replace("\n","",$this->conf_def_property_list[$name]['label']).' */'."\n";
                    }

                }

                // comment : add technical info
                if ( !empty($this->conf_def_property_list[$name]['technicalInfo']) )
                {
                    $propertyComment .= '/*'."\n"
                    . str_replace('*/', '* /', $this->conf_def_property_list[$name]['technicalInfo'] )."\n"
                    . '*/'."\n";
                }

                // property type define how to write the value
                switch ($this->conf_def_property_list[$name]['type'])
                {

                    case 'boolean':
                        if (is_bool($value)) $valueToWrite = trueFalse($value);
                        else
                        switch (strtoupper($value))
                        {
                            case 'TRUE' :
                            case '1' :
                                $valueToWrite = 'TRUE';
                                break;
                            case 'FALSE' :
                            case '0' :
                                $valueToWrite = 'FALSE';
                                break;
                            default:
                                trigger_error('$value is not a boolean ',E_USER_NOTICE);
                                return false;
                        }

                        break;
                    case 'integer':
                        $valueToWrite = $value;
                        break;
                    case 'multi':
                        $valueToWrite = 'array(';
                        if ( !empty($value) && is_array($value) ) $valueToWrite .= '\''. implode('\',\'',$value) . '\'';
                        $valueToWrite .= ')';
                        break;
                    default:
                        $valueToWrite = "'". str_replace("'","\'",$value) . "'";
                        break;
                }

                // container : Constance or variable
                if ( isset($this->conf_def_property_list[$name]['container'])
                && strtoupper($this->conf_def_property_list[$name]['container']) == 'CONST' )
                {
                    $propertyLine = 'if (!defined("'.$name.'")) define("'.$name.'",'.$valueToWrite.');'."\n";
                }
                else
                {
                    $propertyLine = '$GLOBALS[\''.$name.'\'] = '. $valueToWrite .';'."\n";
                    // $propertyLine = '$'.$name.' = '. $valueToWrite .';'."\n";
                    // in the next version, config would change to
                    // $propertyLine .= '$_conf[\''.$name.'\'] = '. $valueToWrite .';'."\n";
                }
                $propertyLine .= "\n\n";

                fwrite($handle,$propertyComment);
                fwrite($handle,$propertyLine);

            }
            fwrite($handle,"\n".'?>');
            fclose($handle);

            // save the new md5 value
            $this->save_md5();

            return true;
        }
        else
        {
            $this->backlog->failure(get_lang('Cannot open %filename',array('%filename'=>$this->config_filename)));
            return false;
        }
    }

    /**
     * Init the value of md5
     */

    function init_md5()
    {
        $tbl = claro_sql_get_main_tbl();

        $sql = "SELECT config_hash
                FROM `" . $tbl['config_file'] . "`
                WHERE config_code = '". claro_sql_escape($this->config_code) . "'";

        $result = claro_sql_query($sql);

        if ( false !== ($row = mysql_fetch_row($result) ) )
        {
            // return hash value
            $this->md5 = $row[0];
        }
        else
        {
            // no hash value in db
            $this->md5 = '';
        }
        return $this->md5;
    }

    /**
     * Get the md5 value
     */

    function get_md5()
    {
        return $this->md5;
    }

    /**
     * Calculate the md5 value of the config file
     */

    function calculate_md5()
    {
        return md5_file($this->config_filename);
    }

    /**
     * Save md5 in database and re-initialise the value of md5
     */

    function save_md5()
    {
        // get table name of config file
        $mainTbl = claro_sql_get_main_tbl();
        $tbl_config_file = $mainTbl['config_file'];

        // caculate new md5
        $new_md5 = $this->calculate_md5();

        if ( empty($this->md5) )
        {
            // insert md5 in database
            $sql = "INSERT IGNORE INTO `" . $tbl_config_file  . "`
                    SET config_hash = '" . claro_sql_escape($new_md5) . "',
                        config_code = '" . claro_sql_escape($this->config_code) . "'";
        }
        else
        {
            // update md5 in database
            $sql = "UPDATE `" . $tbl_config_file  . "`
                    SET config_hash = '" . claro_sql_escape($new_md5) . "'
                    WHERE config_code = '" . claro_sql_escape($this->config_code) . "'" ;
        }

        // execute sql query
        if ( claro_sql_query($sql) )
        {
            $this->md5 = $new_md5;
            return true;
        }
        else
        {
            $this->backlog->failure('');
            return false;
        }
    }

    /**
     * Verify if config file is manually updated
     */

    function is_modified()
    {
        $current_md5 = '';

        if ( file_exists($this->config_filename) )
        {
            $current_md5 = $this->calculate_md5();
        }

        if ( $current_md5 != $this->md5 )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function get_timezone_list ()
    {
        $timezone_identifiers = DateTimeZone::listIdentifiers ();

        foreach ( $timezone_identifiers as $val )
        {
            $atz   = new DateTimeZone ( $val );
            $aDate = new DateTime ( "now", $atz );
            $timeArray[ "$val" ] = $val;
        }

        asort ( $timeArray );

        return $timeArray;

    }

    /**
     * Retrieve accepted value from a folder (ie : lang folder, css, folder, ...)
     */

    function retrieve_accepted_values_from_folder($path,$elt_type,$elt_extension=null,$elt_disallowed=null)
    {
        // init accepted_values list
        $accepted_values = array();

        $dirname = realpath($path);
        
        if ( !$dirname || ! is_dir($dirname) )
        {
            $this->backlog->failure('Directory not found or not a directory');
            return array();
        }
        else
        {
            $dirname .= '/';
            
            $handle = opendir($dirname);
            while ( false !== ($elt = readdir($handle) ) )
            {
                // skip '.', '..' and 'CVS' and .svn
                if ( $elt == '.' || $elt == '..' || $elt == 'CVS' || $elt == '.svn' ) continue;

                // skip disallowed elt
                if ( !empty($elt_disallowed) && in_array($elt,$elt_disallowed) ) continue;

                if ( $elt_type == 'folder' )
                {
                    // skip no folder
                    if ( ! is_dir($dirname.$elt) ) continue ;
                }

                if ( $elt_type == 'file' )
                {
                    // skip no file
                    if ( ! is_file($dirname.$elt) ) continue;

                    if ( isset($elt_extension) )
                    {
                        // skip file with wrong extension
                        $ext = strrchr($elt, '.');

                        if ( is_array($elt_extension) )
                        {
                            if ( ! in_array(strtolower($ext),$elt_extension) )
                            continue;
                        }
                        elseif ( strtolower($ext) != $elt_extension ) continue;



                    }
                }

                // add elt to array
                $elt_name = $elt;
                $elt_value = $elt;

                $accepted_values[$elt_name] = $elt_value;
            }
            ksort($accepted_values);
            return $accepted_values;
        }

    }

    /**
     * Return the name (public label) of the config class
     */

    function get_conf_class()
    {
        $class = isset($this->conf_def['config_class'])
        ? strtolower($this->conf_def['config_class'])
        : 'other';

        return $class;
    }

    /**
     * Return the filename of configuration file
     */

    function get_config_filename()
    {
        return $this->config_filename;
    }

}

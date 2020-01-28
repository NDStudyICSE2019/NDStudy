<?php // $Id: configHtml.class.php 14713 2014-02-17 08:30:54Z zefredz $

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
 * @see     http://www.claroline.net/wiki/config_def/
 * @package CONFIG
 * @author  Claro Team <cvs@claroline.net>
 * @author  Christophe Gesch� <moosh@claroline.net>
 * @author  Mathieu Laurent <laurent@cerdecam.be>
 */

require_once dirname(__FILE__) . '/config.class.php';

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

class ConfigHtml extends Config
{
    var $back_url = null;

    function ConfigHtml($config_code, $back_url)
    {
        parent::Config($config_code);

        $this->backUrl = $back_url;
    }
    /**
     * Function to create the different elements of the configuration form to display
     *
     * @param array $property_list
     * @param string $section_selected
     * @param string $url_params appeded to POST query
     * @return the HTML code to display web form to edit config file
     */

    function display_form($property_list = null,$section_selected = null,$url_params = null)
    {
        $form = '';

        // get section list
        $section_list = $this->get_def_section_list();

        if ( !empty($section_list) )
        {
            if ( empty($section_selected) || ! in_array($section_selected,$section_list) )
            {
                $section_selected = current($section_list);
            }

            // display start form
            $form .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?config_code=' . $this->config_code .claro_htmlspecialchars($url_params). '" name="editConfClass" >' . "\n"
            .        claro_form_relay_context()
            .        '<input type="hidden" name="config_code" value="' . claro_htmlspecialchars($this->config_code) . '" />' . "\n"
            .        '<input type="hidden" name="section" value="' . claro_htmlspecialchars($section_selected) . '" />' . "\n"
            .        '<input type="hidden" name="cmd" value="save" />' . "\n"
            ;

            $form .= '<table border="0" cellpadding="5" width="100%">' . "\n";
            if ($section_selected!='viewall') $section_list = array($section_selected);

            foreach ($section_list as $thisSection)
            {
                if ($thisSection=='viewall') continue;

                // section array
                $section = $this->conf_def['section'][$thisSection];

                if ($section_selected=='viewall')
                {
                    $form .= '<tr><td colspan="3">' . "\n";
                    $form .= '<ul class="tabTitle">' . "\n";
                    $form .= '<li><a href="#">' . claro_htmlspecialchars( get_lang($this->conf_def['section'][$thisSection]['label'])) . '</a></li>' . "\n";
                    $form .= '</ul>' . "\n";
                    $form .= '</td></tr>' . "\n";

                }

                // display description of the section
                if ( !empty($section['description']) ) $form .= '<tr><td colspan="3"><p class="configSectionDesc" ><em>' . get_lang($section['description']) . '</em></p></td></tr>';

                // display each property of the section
                if ( is_array($section['properties']) )
                {

                    foreach ( $section['properties'] as $name )
                    {
                        if ( key_exists($name,$this->conf_def_property_list) )
                        {
                            if ( is_array($this->conf_def_property_list[$name]) )
                            {

                                if ( isset($property_list[$name]) )
                                {
                                    // display elt with new content
                                    $form .= $this->display_form_elt($name,$property_list[$name]);
                                }
                                else
                                {
                                    // display elt with current content
                                    $form .= $this->display_form_elt($name,$this->property_list[$name]);
                                }
                            }
                        }
                        else
                        {
                            $form .= 'Error in section "'.$thisSection .'", "' . $name . '" doesn\'t exist in property list';
                        }
                    } // foreach $section['properties'] as $name
                } // is_array($section['properties'])

            }

            // display submit button
            $form .= '<tr>' ."\n"
            . '<td style="text-align: right">' . get_lang('Save') . '&nbsp;:</td>' . "\n"
            . '<td colspan="2"><input type="submit" value="' . get_lang('Ok') . '" />&nbsp; '
            . claro_html_button($this->backUrl, get_lang('Cancel')) . '</td>' . "\n"
            . '</tr>' . "\n";

            // display end form
            $form .= '</table>' . "\n"
            . '</form>' . "\n";
        }

        return $form ;
    }

    /**
     * Display the form elt of a property
     */

    function display_form_elt($name,$value)
    {

        $elt_form = '';

        // array with html-safe variable
        $html = array();

        $property_def = $this->conf_def_property_list[$name];

        // convert boolean value to true or false string
        if ( is_bool($value) )
        {
            $value = $value?'TRUE':'FALSE';
        }

        // property type
        $type = !empty($property_def['type'])?$property_def['type']:'string';

        // form name of property
        $input_name = 'property['.$name.']';

        // label of property
        $html['label'] = !empty($property_def['label'])?claro_htmlspecialchars( get_lang($property_def['label'])):claro_htmlspecialchars(get_lang($name));

        // value of property
        if ( ! is_array($value) ) $html['value'] = claro_htmlspecialchars($value);

        // description of property
        $html['description'] = !empty($property_def['description'])?nl2br(claro_htmlspecialchars( get_lang($property_def['description']))):'';

        // unit of property
        $html['unit'] = !empty($property_def['unit'])?claro_htmlspecialchars( get_lang($property_def['unit'])):'';

        // type of property
        $html['type'] = !empty($property_def['type'])?' <small>('.claro_htmlspecialchars (get_lang($property_def['type'])).')</small>':'';

        // evaluate the size of input box
        if(!is_array($value))
        {
            $input_size = (int) strlen($value);
            $input_size = min(150,2+(($input_size > 50)?50:(($input_size < 15)?15:$input_size)));
        }

        // build element form

        if ( isset($property_def['display']) && $property_def['display'] == false )
        {
            // no display, do nothing
        }
        else
        {
            $form_title = '';
            $form_value = '';

            if ( isset($property_def['readonly']) && $property_def['readonly'] )
            {
                // read only display
                $form_title = $html['label'];

                switch ( $type )
                {
                    case 'boolean' :
                    case 'enum' :

                        if ( isset($property_def['acceptedValue'][$value]) )
                        {
                            $form_value = $property_def['acceptedValue'][$value];
                        }
                        else
                        {
                            $form_value = $html['value'];
                        }
                        break;
                    case 'multi' :
                        if ( empty($value) || ! is_array($value) )
                        {
                            $form_value = get_lang('Empty');
                        }
                        else
                        {
                            $value_list = array();;
                            foreach ( $value as $value_item )
                            {
                                $value_list[] = claro_htmlspecialchars(get_lang($property_def['acceptedValue'][$value_item]));
                            }
                            $form_value = implode(', ',$value_list);
                        }
                        break;
                    case 'integer' :
                    case 'string' :
                    case 'text' :
                    default :
                        {
                            // probably a string or integer
                            if ( empty($html['value']) )
                            {
                                $form_value = get_lang('Empty');
                            }
                            else
                            {
                                $form_value = $html['value'];
                            }
                        }
                }
            }
            else
            {

                if ( isset($property_def['acceptedValueType']) )
                {
                    if ( !isset( $property_def['acceptedValue'] ) || !is_array( $property_def['acceptedValue'] ) )
                    {
                        $property_def['acceptedValue'] = array();
                    }
                    
                    switch ( $property_def['acceptedValueType'] )
                    {
                        case 'css' :
                            $property_def['acceptedValue'] =  array_merge( $property_def['acceptedValue'], $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'web/css','folder','.css') );
                            $property_def['acceptedValue'] =  array_merge( $property_def['acceptedValue'], $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'platform/css','folder','.css' ) );
                            break;
                        case 'lang' :
                            $property_def['acceptedValue'] = $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'claroline/lang','folder');
                            break;
                        case 'auth':
                            $property_def['acceptedValue'] = $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'claroline/auth/extauth/drivers','file','.php');
                            break;
                        case 'editor' :
                            $property_def['acceptedValue'] = $this->retrieve_accepted_values_from_folder(get_path('rootSys') . 'claroline/editor','folder');
                            break;
                        case 'timezone':
                            $property_def['acceptedValue'] = $this->get_timezone_list();
                            break;
                    }
                    
                    ksort( $property_def['acceptedValue'] );
                }

                // display property form element

                switch( $type )
                {
                    case 'boolean' :

                        $form_title = $html['label'] ;

                        // display true/false radio button
                        $form_value = '<input id="label_'. $name .'_TRUE"  type="radio" name="'. $input_name.'" value="TRUE"  '
                        . ( $value=='TRUE'?' checked="checked" ':' ') . '  />'
                        . '<label for="label_'. $name .'_TRUE"  >'
                        . ($property_def['acceptedValue']['TRUE']?get_lang($property_def['acceptedValue']['TRUE' ]):'TRUE')
                        . '</label>'
                        . '<br />'
                        . '<input id="label_'. $name .'_FALSE" type="radio" name="'. $input_name.'" value="FALSE" '
                        . ($value=='FALSE'?' checked="checked" ': ' ') . '  />'
                        . '<label for="label_'. $name.'_FALSE" >'
                        . ($property_def['acceptedValue']['FALSE']?get_lang($property_def['acceptedValue']['FALSE']):'FALSE')
                        . '</label>';

                        break;

                    case 'enum' :

                        $total_accepted_value = count($property_def['acceptedValue']);

                        if ( $total_accepted_value == 0 || $total_accepted_value == 1 )
                        {
                            $form_title = $html['label'] ;

                            if ( $total_accepted_value == 0 )
                            {
                                $form_value = get_lang('Empty');
                            }
                            else
                            {
                                if ( isset($property_def['acceptedValue'][$value]) )
                                {
                                    $form_value = $property_def['acceptedValue'][$value];
                                }
                                else
                                {
                                    $form_value = $html['value'];
                                }
                            }

                        }
                        elseif ( $total_accepted_value == 2 )
                        {
                            $form_title = $html['label'] ;

                            foreach ( $property_def['acceptedValue'] as  $keyVal => $labelVal)
                            {
                                $form_value .= '<input id="label_'.$name.'_'.$keyVal.'"  type="radio" name="'.$input_name.'" value="'.$keyVal.'"  '
                                . ($value==$keyVal?' checked="checked" ':' ').'  />'
                                . '<label for="label_'.$name.'_'.$keyVal.'"  >'.get_lang(($labelVal?$labelVal:$keyVal )).'</label>'
                                . '<span class="propUnit">'.get_lang($html['unit']).'</span>'
                                . '<br />'."\n";
                            }
                        }
                        elseif ( $total_accepted_value > 2 )
                        {
                            // display label
                            $form_title = '<label for="label_'.$name.'"  >'.get_lang($html['label']).'</label>' ;

                            // display select box with accepted value
                            $form_value = '<select id="label_' . $name . '" name="'.$input_name.'">' . "\n";

                            foreach ( $property_def['acceptedValue'] as  $keyVal => $labelVal )
                            {
                                if ( $keyVal == $value )
                                {
                                    $form_value .= '<option value="'. claro_htmlspecialchars($keyVal) .'" selected="selected">' . get_lang(($labelVal?$labelVal:$keyVal )).get_lang( $html['unit']) .'</option>' . "\n";
                                }
                                else
                                {
                                    $form_value .= '<option value="'. claro_htmlspecialchars($keyVal) .'">' .get_lang( ($labelVal?$labelVal:$keyVal )). get_lang($html['unit']) .'</option>' . "\n";
                                }
                            } // end foreach

                            $form_value .= '</select>' . "\n";
                        }

                        break;

                    case 'multi' :

                        $form_title = $html['label'] ;

                        $form_value = '<input type="hidden" name="'.$input_name.'" value="" />' . "\n";

                        foreach ( $property_def['acceptedValue'] as  $keyVal => $labelVal)
                        {
                            $form_value .= '<input id="label_'.$name.'_'.$keyVal.'"  type="checkbox" name="'.$input_name.'[]" value="'.$keyVal.'"  '
                            . (is_array($value)&&in_array($keyVal,$value)?' checked="checked" ':' ').'  />'
                            . '<label for="label_'.$name.'_'.$keyVal.'"  >'.get_lang(($labelVal?$labelVal:$keyVal )).'</label>'
                            . '<span class="propUnit">'.get_lang($html['unit']).'</span>'
                            . '<br />'."\n";
                        }

                        break;

                    case 'integer' :

                        $form_title = '<label for="label_'.$name.'"  >'.$html['label'].'</label>';

                        $form_value = '<input size="'.$input_size.'" align="right" id="label_'.$name.'" '
                        . ' type="text" name="'.$input_name.'" value="'. $html['value'] .'" /> '."\n"
                        . '<span class="propUnit">'.$html['unit'].'</span>'
                        . '<span class="propType">'.$html['type'].'</span>';

                        break;

                    case 'text' :

                        $form_title = '<label for="label_'.$name.'"  >' . $html['label'] . '</label>' ;

                        $form_value = '<textarea cols="40" rows="5" id="label_'.$name.'" name="'.$input_name.'">'. $html['value'] .'</textarea>';

                        break;

                    default:
                        // by default is a string
                        $form_title = '<label for="label_'.$name.'"  >' . $html['label'] . '</label>' ;

                        $form_value = '<input size="'.$input_size.'" id="label_'.$name.'" type="text" name="' . $input_name . '" value="' . $html['value'] . '" /> '
                        . '<span class="propUnit">'.$html['unit'].'</span>'
                        . '<span class="propType">'.$html['type'].'</span>'. "\n" ;

                } // end switch on property type
            }

            // display elt
            $elt_form = '<tr style="vertical-align: top">' . "\n"
                . '<td style="text-align: right" width="25%">' . $form_title . '&nbsp;:</td>' . "\n"
                . '<td nowrap="nowrap" width="25%">' . $form_value . '</td>' . "\n"
                . '<td width="50%">' . ( !empty($html['description'])  ? '<em><small>' . $html['description']. '</small></em>' : '&nbsp;' ) .'</td>' . "\n"
                . '</tr>' . "\n" ;
        }

        return $elt_form;
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
     * Return list of displayed section
     */

    function get_def_section_list()
    {
        $section_list = array();

        if(!array_key_exists('section',$this->conf_def) || ($this->conf_def['section']))
        {
            $this->conf_def['section']['viewall']['label'] = get_lang('View all');
            $this->conf_def['section']['viewall']['properties'] = array_keys($this->conf_def_property_list);
        }

        foreach ( $this->conf_def['section'] as $id => $section )
        {
            if ( ! isset($section['display']) || $section['display'] != false )
            {
                $section_list[] = $id ;
            }
        }


        return $section_list ;
    }

    /**
     * Display section menu
     */

    function display_section_menu($section_selected,$url_params = null)
    {
        $menu = '';

        $section_list = $this->get_def_section_list();

        if ( !empty($section_list) && count($section_list)>2)
        {
            if ( empty($section_selected) || ! in_array($section_selected,$section_list) )
            {
                $section_selected = current($section_list);
            }

            $menu  = '<div >' . "\n";
            $menu .= '<ul id="navlist">' . "\n";

            foreach ( $section_list as $section )
            {
                $section_name = $this->conf_def['section'][$section]['label'];

                $menu .=  '<li>'
                . '<a ' . ( $section == $section_selected ? 'class="current"' : '' )
                . ' href="' . $_SERVER['PHP_SELF'] . '?config_code=' . claro_htmlspecialchars($this->config_code)
                . '&amp;section=' . claro_htmlspecialchars($section) . claro_htmlspecialchars($url_params). '">'
                . get_lang($section_name) . '</a></li>' . "\n";

            }
            $menu .= '</ul>' . "\n";
            $menu .= '</div>' . "\n" ;
        }
        return $menu;
    }

}

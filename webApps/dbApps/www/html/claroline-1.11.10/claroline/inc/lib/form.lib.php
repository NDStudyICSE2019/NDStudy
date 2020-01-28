<?php // $Id: form.lib.php 14314 2012-11-07 09:09:19Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLCRS/
 * @package     FORM
 * @author      Claro Team <cvs@claroline.net>
 *
 */

$formSize = 40;

/**
 * @param string  $dayFieldName attribute name of the input DAY
 * @param string  $monthFieldName attribute name of the input MONTH
 * @param string  $yearFieldName attribute name of the input YEAR
 * @param boolean $unixDate unix timestamp of date to display
 * @param string  $formatMonth display type of month select box : numeric, long, short
 *
 * @author Sébastien Piraux <pir@cerdecam.be>
 *
 * @return string html stream to output input tag for a date
 *
 */

function claro_disp_date_form($dayFieldName, $monthFieldName, $yearFieldName, $unixDate = 0, $formatMonth = 'numeric' )
{
    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
             ? claro_html_debug_backtrace()
             : 'claro_html_debug_backtrace() not defined'
             )
             .'claro_disp_date_form() is deprecated , use claro_html_date_form()','error');

    return claro_html_date_form($dayFieldName, $monthFieldName, $yearFieldName, $unixDate, $formatMonth );
}

function claro_html_date_form($dayFieldName, $monthFieldName, $yearFieldName, $unixDate = 0, $formatMonth = 'numeric' )
{
    if( $unixDate == 0) $selectedDate = date('Y-m-d');
    else                $selectedDate = date('Y-m-d', $unixDate);

    // split selectedDate
    list($selYear, $selMonth, $selDay) = explode('-', $selectedDate);

    // day field
    for ($dayCounter=1;$dayCounter <=31; $dayCounter++)
      $available_days[$dayCounter] = $dayCounter;
    $dayField = claro_html_form_select( $dayFieldName
                                   , $available_days
                                   , $selDay
                                   , array('id'=> $dayFieldName)
                                   );

    // month field
    if( $formatMonth == 'numeric' )
    {
        for ($monthCounter=1;$monthCounter <= 12; $monthCounter++)
          $available_months[$monthCounter] = $monthCounter;
    }
    elseif( $formatMonth == 'long' )
    {
        $langMonthNames['long'] = get_lang_month_name_list('long');

        for ($monthCounter=1;$monthCounter <= 12; $monthCounter++)
          $available_months[$langMonthNames['long'][$monthCounter-1]] = $monthCounter;
    }
    elseif( $formatMonth == 'short' )
    {
        $langMonthNames['short'] = get_lang_month_name_list('short');
        for ($monthCounter=1;$monthCounter <= 12; $monthCounter++)
          $available_months[$langMonthNames['short'][$monthCounter-1]] = $monthCounter;
    }
    $monthField = claro_html_form_select( $monthFieldName
                                   , $available_months
                                   , $selMonth
                                   , array('id'=> $monthFieldName)
                                   );
    // year field
    $thisYear = date('Y');
    for ($yearCounter= $thisYear - 2; $yearCounter <= $thisYear+5; $yearCounter++)
        $available_years[$yearCounter] = $yearCounter;
    $yearField = claro_html_form_select( $yearFieldName
                                   , $available_years
                                   , $selYear
                                   , array('id'=> $yearFieldName)
                                   );

    return $dayField . '&nbsp;' . $monthField . '&nbsp;' . $yearField;
}

/**
 * build htmlstream for input form of a time
 *
 * @param string $hourFieldName attribute name of the input Hour
 * @param string $minuteFieldName attribute name of the input minutes
 * @param string $unixDate unix timestamp of date to display
 *
 * @return string html stream to output input tag for an hour
 *
 * @author S�bastien Piraux <pir@cerdecam.be>
 *
 */


function claro_disp_time_form($hourFieldName, $minuteFieldName, $unixDate = 0)
{

    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
             ? claro_html_debug_backtrace()
             : 'claro_html_debug_backtrace() not defined'
             )
             .'claro_disp_time_form() is deprecated , use claro_html_time_form()','error');


    return claro_html_time_form($hourFieldName, $minuteFieldName, $unixDate);
}

function claro_html_time_form($hourFieldName, $minuteFieldName, $unixDate = 0)
{
    if( $unixDate == 0) $selectedTime = date("H:i");
    else                $selectedTime = date("H:i",$unixDate);


    //split selectedTime
    list($selHour, $selMinute) = explode(':',$selectedTime);


    if ($hourFieldName != '')
    {
        for($hour=0;$hour < 24; $hour++)  $aivailable_hours[$hour] = $hour;
        $hourField = claro_html_form_select( $hourFieldName
                                           , $aivailable_hours
                                           , $selHour
                                           , array('id'=> $hourFieldName)
                                           );
    }

    if($minuteFieldName != "")
    {
        for($minuteCounter=0;$minuteCounter < 60; $minuteCounter++)
            $available_minutes[$minuteCounter] = $minuteCounter;

        $minuteField = claro_html_form_select( $minuteFieldName
                                           , $available_minutes
                                           , $selMinute
                                           , array('id'=> $minuteFieldName)
                                           );
    }

    return '&nbsp;' . $hourField . '&nbsp;' . $minuteField;
}

/**
 *
 * @param string $select_name name of the form (other param can be adds with $attr
 * @param array $list_option 2D table where key are labels and value are values
 *  with reverted set to false (default) or key are values and value are labels
 *  with reverted set to true
 * @param string $preselect name of the key in $list_option would be preselected
 * @param bool $reverted set the function in reverted mode to use value => label
 *  instead of label => value arrays (default false)
 * @return html output from a 2D table where key are name and value are label
 *
 * @author Christophe Gesché <moosh@claroline.net>
 *
 */
function claro_html_form_select($select_name,$list_option,$preselect=null,$attr=null, $reverted = false)
{
    $html_select = '<select name="' . $select_name . '" ';
    if (is_array($attr)) foreach($attr as $attr_name=>$attr_value)
    $html_select .=' ' . $attr_name . '="' . $attr_value . '" ';
    $html_select .= '>' . "\n"
    .                claro_html_option_list($list_option,$preselect, $reverted)
    .               '</select>' . "\n"
    ;

    return $html_select;
}


/**
 * return a string as html form option list to plce in a <select>
 * @param array $list_option 2D table where key are labels and value are values
 *  with reverted set to false (default) or key are values and value are labels
 *  with reverted set to true
 * @param string $preselect name of the key in $list_option would be preselected
 * @param bool $reverted set the function in reverted mode to use value => label
 *  instead of label => value arrays (default false)
 * @return html output of the select options
 *
 * @author Christophe Gesché <moosh@claroline.net>
 *
 */
function claro_html_option_list($list_option, $preselect, $reverted = false)
{
    $html_option_list ='';
    if(is_array($list_option))
    {
        if ( ! $reverted )
        {
            foreach($list_option as $option_label => $option_value)
            {
                $html_option_list .= '<option value="' . $option_value . '"'
                .                    ($option_value ==  $preselect ?' selected="selected" ':'') . '>'
                .                    claro_htmlspecialchars($option_label)
                .                    '</option >' . "\n"
                ;
            }
        }
        else
        {
            foreach($list_option as $option_value => $option_label)
            {
                $html_option_list .= '<option value="' . $option_value . '"'
                .                    ($option_value ==  $preselect ?' selected="selected" ':'') . '>'
                .                    claro_htmlspecialchars($option_label)
                .                    '</option >' . "\n"
                ;
            }
        }
        return $html_option_list;
    }
    else
    {
        trigger_error('$list_option would be array()', E_USER_NOTICE);
        return false;
    }

}

/**
 * Return html for a field label wich is required.
 *
 * @param string $field field label
 * @return string html for a field label wich is required.
 * @since 1.8
 */

function form_required_field($field)
{
    return '<span class="required">*</span>&nbsp;' . $field;
}

/**
 * Return html for a table row for claro_form_table.
 *
 * @param string $field field label
 * @return string html for a field label wich is required.
 * @since 1.8
 */

function form_row($legend, $element)
{
    return '<tr valign="top">' . "\n"
    .      '<td align="right">' . "\n"
    .      $legend
    .      '</td>' . "\n"
    .      '<td align="left">' . "\n"
    .      $element . "\n"
    .      '</td>' . "\n"
    .      '</tr>' . "\n"
    ;
}

/**
 * Prepare an html output of an input wich  would be include in a <form>
 *
 * @param string  $name
 * @param string  $value
 * @param string  $displayedName (default '')
 * @param boolean $required      (default false)
 * @return string html content
 * @since 1.8
 */
function form_input_text($name, $value, $displayedName = '', $required = false)
{
    if ( empty($displayedName) ) $displayedName = $name;
    if ( $required )             $displayedName = form_required_field($displayedName);

    return form_row( '<label for="' . $name . '">' . $displayedName . '</label>&nbsp;: '
    ,                '<input type="text" size="' . get_conf('formSize',40) . '"'
    .                ' id="'.$name.'" name="'.$name.'"'
    .                ' value="'.claro_htmlspecialchars($value).'" />')
    ;
}

/**
 * Prepare an html output of an input wich  would be include in a <form>
 *
 * @param string  $name
 * @param string  $value
 * @param string  $displayedName (default '')
 * @param boolean $required      (default false)
 * @return string html content
 * @since 1.8
 */
function form_readonly_text($name, $value, $displayedName = '')
{
    if ( empty($displayedName) ) $displayedName = $name;

    if ( empty($value) ) $value = '-';

    return form_row( $displayedName . '&nbsp;: '
    ,                claro_htmlspecialchars($value) ) ;
}

/**
 * Prepare an html output of an input wich  would be include in a <form>
 *
 * @param string  $name
 * @param string  $value
 * @param string  $displayedName (default '')
 * @param boolean $required      (default false)
 * @return string html content
 * @since 1.8
 */
function form_input_password($name, $value, $displayedName = '', $required = false)
{
    if ( empty($displayedName) ) $displayedName = $name;
    if ( $required )             $displayedName = form_required_field($displayedName);

    return form_row( '<label for="'.$name.'">'.$displayedName . '</label>&nbsp;: '
    ,                '<input type="password" size="' . get_conf('formSize',40) . '"'
    .                ' id="' . $name . '" name="' . $name . '"'
    .                ' value="' . claro_htmlspecialchars($value) . '" />')
    ;
}

/**
 * Prepare an html output of an input hidden wich  would be include in a <form>
 *
 * @param string $name use for name and  by default, id
 * @param string $value
 * @return string : html stream
 */
function form_input_hidden($name, $value)
{
    return '<input type="hidden"' . ' id="'.$name.'" name="'.$name.'"' . ' value="'.claro_htmlspecialchars($value).'" />';
}

/**
 * Prepare an html output of an textarea wich  would be include in a <form>
 *
 * @param string  $name
 * @param string  $value
 * @param string  $displayedName (default '')
 * @param boolean $required      (default false)
 * @return string html content
 * @since 1.8
 */
function form_input_textarea($name, $value, $displayedName = '', $required = false, $rows=6)
{
    if ( empty($displayedName) ) $displayedName = $name;
    if ( $required )             $displayedName = form_required_field($displayedName);

    $rows = (int) $rows;
    return form_row( '<label for="' . $name . '">' . $displayedName . '</label>&nbsp;: '
    ,                '<textarea cols="' . get_conf('formSize',40) . '" rows="' . $rows . '"  '
                   . ' id="' . $name . '" name="' . $name . '" >' . claro_htmlspecialchars($value) . '</textarea>' )
    ;
}

/**
 * Prepare an html output of an input file wich  would be include in a <form>
 *
 * @param string $name use for name and for id
 * @return string : html stream
 */
function form_input_file($name, $displayedName = '', $required = false)
{
    if ( empty($displayedName) ) $displayedName = $name;
    if ( $required )             $displayedName = form_required_field($displayedName);

    return form_row( '<label for="' . $name . '">' . $displayedName . '</label>&nbsp;: '
    ,                '<input type="file" '
    .                ' id="'.$name.'" name="'.$name.'"'
    .                ' />')
    ;
}

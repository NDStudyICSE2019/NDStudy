<?php // $Id: html.lib.php 14642 2014-01-20 07:56:18Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * This lib provide html stream for various
 * uniformised output.
 *
 * @version     1.9 $Revision: 14642 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      see 'credits' file
 * @package     HTML
 */


/**
* Return an HTML item list (<ul>).
* Add CSS classes or other attributes through the $attrBloc array.
*
* @param array $itemList
* @param array $attrBloc
*
* @return string $htmlStream
*/
function claro_html_list($itemList, $attrBloc=array())
{
    $classBlocAttr          = '';
    $otherBlocAttrString    = '';
    $htmlStream             = '';
    
    foreach ($attrBloc as $attrName => $attrValue)
    {
        if ($attrName == 'class')
            $classBlocAttr = ' ' . trim($attrValue);
        else
            $otherBlocAttrString .= ' ' . $attrName . '="' . $attrValue . '"';
    }
    
    if (!empty($classBlocAttr))
        $classBlocAttr = 'class="'.trim($classBlocAttr).'"';
    
    
    if (! empty($itemList) && is_array($itemList))
    {
        $htmlList = '';
        
        foreach ($itemList as $item)
        {
            $htmlList .= '<li>'.$item.'</li>';
        }
        
        $htmlStream = '<ul ' . $classBlocAttr . ' ' . $otherBlocAttrString . '>'
                    . $htmlList
                    . '</ul>';
    }
    
    return $htmlStream;
}


/**
 * Display a item list as vertical menu.
 *
 * @param array $itemList each item are include in a list.
 *
 * @return string : list content as an horizontal menu.
 */

function claro_html_menu_horizontal($itemList)
{
    if( !empty($itemList) && is_array($itemList))
    {
        return "\n\n"
        . '<span>' . "\n"
        . implode( "\n" . ' | ' . "\n",$itemList) . "\n"
        . '</span>'
        . "\n\n";
    }
    else
    {
        return '';
    }
}


/**
 * Prepare an array of link following a list of section
 *
 * @param array $section_list array('section_name'=> of section array ('label','description','properties','..'.));
 * @param string $section_selected  section_name
 * @param string $url_params query string append
 * @param string $currentClassName css class name
 * @return array
 */
 
 function prepared_section_to_tabs($section_list, $section_selected='',$url_params = null, $currentClassName='current')
{
    $tabList=array();
        
    // Need at least  2 items
    if ( !empty($section_list) && count($section_list)>2)
    {
        //  if no selected take first
        if ( empty($section_selected) || ! in_array($section_selected,array_keys($section_list)) )
        {
            $section_selected = key($section_list);
        }
        
        foreach ( $section_list as $section=>$section_def )
        {
            $section_name = $section_def['label'];
            
            $tabList[]= '<a ' . ( $section == $section_selected ? ('class="' . $currentClassName . '"') : '' )
            . ' href="' . $_SERVER['PHP_SELF'] . '?section=' . claro_htmlspecialchars($section)
                                               . claro_htmlspecialchars($url_params). '">'
            . get_lang($section_name) . '</a>';

        }
    }
    return $tabList;
    
}


/**
* Return the claroline sytled url for a link to a tool
*
* @param string $url
* @param string $label
* @param array $attributeList array of array(attributeName,attributeValue)
* @return string html stream
*/
function claro_html_cmd_link($url,$label,$attributeList=array())
{

    if(array_key_exists('class',$attributeList))
    {
        $attributeList['class'] .= ' claroCmd';
    }
    else
    {
        $attributeList['class'] = ' claroCmd';
    }

    return claro_html_link($url,$label,$attributeList);

}


/**
* Return the claroline sytled url for a link to a tool
*
* @param string $url
* @param string $label
* @param array $attributeList array of array(attributeName,attributeValue)
* @return string html stream
*/
function claro_html_link($url,$label,$attributeList=array())
{
    $attributeConcat ='';

    if (is_array($attributeList))
    {
        foreach ($attributeList as $key => $attribute)
        {
            $attributeConcat .= (is_array($attribute) ? $attribute['name'].'="'.$attribute['value'].'" ' : $key.'="'.$attribute.'" ');
        }

    }
    else trigger_error('$attributeList would be an array', E_USER_WARNING);
    return '<a href="' . $url . '" ' . $attributeConcat . ' >'
    .       $label
    .       '</a>' . "\n"
    ;

}


/**
* Prepare the display of a clikcable button
*
* This function is needed because claroline buttons rely on javascript.
* The function return an optionnal behavior fo browser where javascript
* isn't  available.
*
* @author Hugues Peeters <hugues.peeters@claroline.net>
*
* @param string $url url inserted into the 'href' part of the tag
* @param string $text text inserted between the two <a>...</a> tags (note : it
*        could also be an image ...)
* @param string $confirmMessage (optionnal) introduce a javascript confirmation popup
* @return string the button
*/
function claro_html_button($url, $text, $confirmMessage = '')
{
    $url = secure_backlink_url($url);

    if ($confirmMessage != '')
    {
        $onClickCommand = "if(confirm('" . clean_str_for_javascript($confirmMessage) . "')){document.location='" . $url . "';return false}";
    }
    else
    {
        $onClickCommand = "document.location='".$url."';return false";
    }

    return '<a href="'.$url.'">'
    . '<input type="button" onclick="' . $onClickCommand . '" '
    .      'value="'.$text.'" />'
    .      '</a>' . "\n"
    ;
}


/**
 * Displays a title inc claroline wich can be relooked by css
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @param  string $title
 * @param  string $level 1->7
 *
 * @return void
 */
function claro_html_title($title, $level)
{
    return '<h'.$level.' class="claroTitle claroTitle' . $level . '" >' . $title . '</h'.$level.' >';
}


/**
* Displays the title of a tool. Optionally, there can be a subtitle below
* the normal title, a supra title above the normal title and a list of
* tools links following the title.
*
* e.g. supra title:
* group
* GROUP PROPERTIES
*
* e.g. subtitle:
* AGENDA
* calender & events tool
*
* e.g. tools:
* AGENDA | (tool link 1) (tool link 2) (tool link 3)
*
* @author Hugues Peeters <hugues.peeters@claroline.net>
* @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
* @param  mixed $titleElement - it could either be a string or an array
*                               containing 'supraTitle', 'mainTitle',
*                               'subTitle'
* @param string $helpUrl
* @param array $toolList
* @return void
*/
function claro_html_tool_title($titleParts, $helpUrl = null, $commandList = array(), $advancedCommandList = array())
{
    if ( ! is_array($advancedCommandList) )
    {
        pushClaroMessage('advanced command list is not an array ' . var_export( $advancedCommandList, true ), 'error' );
        $advancedCommandList = array();
    }
    
    if ( get_conf('displayAllCommandsLinkByDefault', false ) )
    {
        $commandList = array_merge( $commandList, $advancedCommandList );
        $advancedCommandList = array();
    }
    
    $toolTitle = new ToolTitle($titleParts, $helpUrl, $commandList, $advancedCommandList);
    
    return $toolTitle->render();
}


/**
* Prepare display of the message box appearing on the top of the window,
* just    below the tool title. It is recommended to use this function
* to display any confirmation or error messages, or to ask to the user
* to enter simple parameters.
*
* @author Hugues Peeters <hugues.peeters@claroline.net>
* @param string $message include your self any additionnal html
*                        tag if you need them
* @since 1.8
*
* @return string html string for a message box
* @deprecated since Claroline 1.11 use DialogBox class instead
*/
function claro_html_message_box($message)
{
    $effectiveContent = trim(strip_tags($message));

    if(!empty($effectiveContent))
    return "\n" . '<table class="claroMessageBox" border="0" cellspacing="0" cellpadding="10">'
    .      '<tr>'
    .      '<td>'
    .      $message
    .      '</td>'
    .      '</tr>'
    .      '</table>' . "\n\n"
    ;
    else return '';
}


/**
* Allows to easily display a breadcrumb trail
*
* @param array $nameList bame of each breadcrumb
* @param array $urlList url corresponding to the breadcrumb name above
* @param string $separator (optionnal) element which segregate the breadcrumbs
* @param string $homeImg (optionnal) source url for a home icon at the trail start
* @return string : the build breadcrumb trail
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
*/
function claro_html_breadcrumbtrail($nameList, $urlList, $separator = ' &gt; ', $homeImg = null)
{
    // trail of only one element has no sense ...
    if (count ($nameList) < 2 ) return '<div class="breadcrumbTrail">&nbsp;</div>';

    $breadCrumbList = array();

    foreach($nameList as $thisKey => $thisName)
    {
        if (   array_key_exists($thisKey, $urlList)
        && ! is_null($urlList[$thisKey])       )
        {
            $startAnchorTag = '<a href="' . $urlList[$thisKey] . '" target="_top">';
            $endAnchorTag   = '</a>' . "\n";
        }
        else
        {
            $startAnchorTag = '';
            $endAnchorTag   = '';
        }

        $htmlizedName = is_htmlspecialcharized($thisName)
        ? $thisName
        : claro_htmlspecialchars($thisName);

        $breadCrumbList [] = $startAnchorTag
        . $htmlizedName
        . $endAnchorTag;
    }

    // Embed the last bread crumb entry of the list.

    $breadCrumbList[count($breadCrumbList)-1] = '<strong>'
    .end($breadCrumbList)
    .'</strong>' . "\n";

    return  '<div class="breadcrumbTrail">' . "\n"
    . ( is_null($homeImg) ? '' : '<img src="' . get_icon_url( $homeImg ) . '" alt="" /> ' . "\n" )
    . implode($separator . "\n", $breadCrumbList)
    . '</div>' . "\n";
}


/**
* Function used to draw a progression bar
*
* @author Piraux Sebastien <pir@cerdecam.be>
*
* @param integer $progress progression in pourcent
* @param integer $factor will be multiply by 100 to have the full size of the bar
* (i.e. 1 will give a 100 pixel wide bar)
*/
function claro_html_progress_bar ($progress, $factor)
{
    $maxSize  = $factor * 100; //pixels
    $barwidth = $factor * $progress ;

    // display progress bar
    // origin of the bar
    $progressBar = '<img src="' . get_icon_url('bar_1') . '" width="1" height="12" alt="" />';

    if($progress != 0)
    $progressBar .= '<img src="' . get_icon_url('bar_1u') . '" style="width:' . $barwidth . 'px; height: 12px;" alt="" />';
    // display 100% bar

    if($progress!= 100 && $progress != 0)
    $progressBar .= '<img src="' . get_icon_url('bar_1m') . '" style="width: 1px; height: 12px;" alt="" />';

    if($progress != 100)
    $progressBar .= '<img src="' . get_icon_url('bar_1r') . '" style="width: ' . ($maxSize - $barwidth) . 'px; height: 12px;" alt="" />';
    // end of the bar
    $progressBar .=  '<img src="' . get_icon_url('bar_1') . '" style="width:1px; height:12px;" alt="" />';

    return $progressBar;
}


/**
* Display list of messages in substyled boxes in a message_box
*
* In most of cases  function message_box() is enough.
*
* @param array $msgArrBody of array of blocs containing array of messages
* @author Christophe Gesche <moosh@claroline.net>
* @version 1.0
* @see  message_box()
*
*  code for using this    in your    tools:
*  $msgArrBody["nameOfCssClass"][]="foo";
*  css    class can be defined in    script but try to use
*  class from    generic    css    ()
*  error success warning
*  ...
*
* @todo this must be a message object where code add messages with a priority,
* and the rendering is set by by priority
*
*/
function claro_html_msg_list($msgArrBody, $return=true)
{
    $msgBox = '';

    if (is_array($msgArrBody) && count($msgArrBody) > 0)
    {
        foreach ($msgArrBody as $classMsg => $thisMsgArr)
        {
            if( is_array($thisMsgArr) )
            {
                $msgBox .= '<div class="' . $classMsg . '">';
                foreach ($thisMsgArr as $anotherThis) $msgBox .= '<div class="msgLine" >' . $anotherThis . '</div>';
                $msgBox .= '</div>';
            }
            else
            {
                $msgBox .= '<div class="' . $classMsg . '">';
                $msgBox .= '<div class="msgLine" >' . $thisMsgArr . '</div>';
                $msgBox .= '</div>';
            }
        }
    }
    $dialogBox = new DialogBox();
    if( $msgBox )
    {
        $dialogBox->form( $msgBox );
    }
    if ($return) return $dialogBox->render();
    else         echo   $dialogBox->render();
    return true;
}


/**
* prepare the 'option' html tag for the claro_disp_nested_select_menu()
* function
*
* @author Christophe Gesche <moosh@claroline.net>
* @author Hugues Peeters <hugues.peeters@claroline.net>
* @param array $elementList
* @param integer  $deepness (optionnal, default is 0)
* @return array of option list
*/
function claro_html_nestedArrayToOptionList($elementList, $deepness = 0)
{
    foreach($elementList as $thisElement)
    {
        $tab = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $deepness);

        $optionTagList[$thisElement['value']] =  $tab.$thisElement['name'] ;
        if (   isset( $thisElement['children'] )
        && sizeof($thisElement['children'] ) > 0)
        {
            $optionTagList = array_merge( $optionTagList,
            prepare_option_tags($thisElement['children'],
            $deepness + 1 ) );
        }
    }

    return  $optionTagList;
}


/**
* prepare a mailto link
*
* @param string $mail
* @param string $mailLabel
* @return string : html stream
*/
function claro_html_mailTo($mail,$mailLabel=null)
{
    if (is_null($mailLabel)) $mailLabel = $mail;
    $mailHtml = '<a href="mailto:' . $mail . '" class="email" >' . $mailLabel . '</a>';
    return $mailHtml;
}


/**
* Insert a Wysiwyg editor inside a form instead of a textarea
* A standard textarea is displayed if the Wysiwyg editor is disabled or if
* the user's browser have no activated javascript support
*
* @param string $name content for name attribute in textarea tag
* @param string $content optional content previously inserted into    the    area
* @param int     $rows optional    textarea rows
* @param int    $cols optional    textarea columns
* @param string $optAttrib    optional - additionnal tag attributes
*                                       (wrap, class, ...)
* @return string html output for standard textarea or Wysiwyg editor
*
* @author Hugues Peeters <hugues.peeters@claroline.net>
* @author Sebastien Piraux <pir@cerdecam.be>
*/
function claro_html_textarea_editor($name, $content = '', $rows=20, $cols=80, $optAttrib='',$type='advanced')
{
    if( !get_conf('claro_editor') ) $claro_editor = 'tiny_mce';
    else                            $claro_editor = get_conf('claro_editor');
    
    $possibleTypeList = array('advanced', 'simple');
    if( ! in_array($type, $possibleTypeList) ) $type = 'advanced';
        
    $returnString = '';

    // $claro_editor is the directory name of the editor
    $incPath = get_path('rootSys') . 'claroline/editor/' . $claro_editor;
    $editorPath = get_conf('urlAppend') . '/claroline/editor/';
    $webPath = $editorPath . $claro_editor;
    
    $isSafariOn_iPhone = preg_match("!Mobile/.*?Safari/.*?!", $_SERVER['HTTP_USER_AGENT']);

    if( !$isSafariOn_iPhone && file_exists($incPath . '/editor.class.php') )
    {
        // include editor class
        include_once $incPath . '/editor.class.php';

        // editor instance
        $editor = new editor($name,$content,$rows,$cols,$optAttrib,$webPath);

        if( $type == 'advanced' )
        {
            $returnString .= $editor->getAdvancedEditor();
        }
        else
        {
            $returnString .= $editor->getSimpleEditor();
        }
    }
    else
    {
        // if the editor class doesn't exists we cannot rely on it to display
        // the standard textarea
        $returnString .=
        '<textarea '
        .'id="'.$name.'" '
        .'name="'.$name.'" '
        .'style="width:100%" '
        .'rows="'.$rows.'" '
        .'cols="'.$cols.'" '
        .$optAttrib.' >'
        .claro_htmlspecialchars($content)
        .'</textarea>'."\n";
    }

    return $returnString;
}


function claro_html_simple_textarea($name, $content = '')
{
    return claro_html_textarea_editor($name, $content, 20, 80, '', 'simple');
}


function claro_html_advanced_textarea($name, $content = '')
{
    return claro_html_textarea_editor($name, $content, 20, 80, '', 'advanced');
}


/**
 *
 *
 */
DEFINE('DG_ORDER_COLS_BY_GRID','DG_ORDER_COLS_BY_GRID'.__FILE__.__LINE__);
DEFINE('DG_ORDER_COLS_BY_TITLE','DG_ORDER_COLS_BY_TITLE'.__FILE__.__LINE__);


/**
 * datagrid is actually a function but can became an object.
 *
 * function claro_disp_datagrid($dataGrid, $option = null)
 *
 * would became a static method.
 *
 * but in dynamic work,
 * new datagrid($dataGrid = null, $option_list = null)
 * set_grid(array of array $datagrid)
 * set_option_list(array $option_list)
 * set_idLineType(string $line_type)
 * set_idLineShift(integer $line_shift)
 * set_colTitleList(array('colName'=>'colTitle'));
 * set_colAttributeList(array('colName'=> array('attribName'=>'attribValue'))
 * set_caption(string 'caption');
 * set_counterLine(bool 'dispCounter')
 * set_colDecoration(string columnName,string pattern, array param)
 *
 * @package HTML
 * @author Christophe Gesche <moosh@claroline.net>
 *
 */
class claro_datagrid
{
    private $datagrid;

    private $idLineType =  'numeric';
    private $idLineShift = 1;
    private $colTitleList =null;
    private $colAttributeList = array();
    private $caption = '';
    private $counterLine;
    private $dispCounter = false;
    private $colHead =null;
    private $htmlNoRowMessage = null;
    private $hideColsWithoutTitle = false;
    private $orderCols=DG_ORDER_COLS_BY_GRID;
    private $decorationList = array();
    private $dispIdCol = true;
    private $internalKey = 0;


    function claro_datagrid($datagrid = null)
    {
        if (!is_null($datagrid))    $this->set_grid($datagrid);

        $this->set_idLineType('none');
    }


    /**
     * set data grid
     *
     * @param array $datagrid
     */
    function set_grid($datagrid)
    {
        if (is_array($datagrid))
        {
            $this->internalKey = 0;
            $this->datagrid = $datagrid ;
        }
        else                     trigger_error('set_grid need an array : ' .var_export($datagrid,1). ' is not array' ,E_USER_NOTICE);

    }


    function set_option_list($option_list)
    {
        foreach ( $option_list as $option => $value )
        {
            switch ( $option )
            {
                case 'idLineShift':
                    $this->set_idLineShift($value);
                    break;
                case 'colTitleList':
                    $this->set_colTitleList($value);
                    break;
                case 'colAttributeList':
                    $this->set_colAttributeList($value);
                    break;
                case 'caption':
                    $this->set_caption($value);
                    break;
            }
        }
    }


    /**
     * set the  isLineType option
     *
     * @param string $line_type 'blank' 'numeric' 'key' 'none' default:'none'
     *
     */
    function set_idLineType( $idLineType)
    {
        //* manage idLine option
        $this->idLineType = $idLineType;
        switch (strtolower($idLineType))
        {
            case 'blank'   : $this->dispIdCol = true; $this->idLineType = '';   break;
            case 'numeric' : $this->dispIdCol = true; $this->internalKey = 0;   break;
            case 'key'     : $this->dispIdCol = true; break;
            case 'none'    : $this->dispIdCol = false; break;
            default        : $this->dispIdCol = false;
        }
    }


    /**
     * set the  idLineShift option
     *
     * @param integer $idLineShift
     */
    function set_idLineShift( $idLineShift)
    {
        $this->idLineShift = $idLineShift;
    }


    /**
     * set the  hideColsWithoutTitle option
     * if hideColsWithoutTitle is true, only cols present in the colTitleList are shown in the rendered grid
     * @param boolean $hideColsWithoutTitle set if  the cols of grid
     * withouth title would be displayed
     */
    function set_hideColsWithoutTitle( $hideColsWithoutTitle)
    {
        if (is_bool($hideColsWithoutTitle)) $this->hideColsWithoutTitle = $hideColsWithoutTitle;
        else                                trigger_error('boolean attempt',E_USER_NOTICE);
    }


    /**
     * set the  hideColsWithoutTitle option
     * if hideColsWithoutTitle is true, only cols present in the colTitleList are shown in the rendered grid
     * @param boolean $hideColsWithoutTitle set if  the cols of grid
     * withouth title would be displayed
     */
    function set_orderColBy( $orderCols)
    {
        if (in_array($orderCols,array(DG_ORDER_COLS_BY_GRID,DG_ORDER_COLS_BY_TITLE)))
        {
            $this->orderCols = $orderCols;
        }
        else
        {
            trigger_error('boolean attempt',E_USER_NOTICE);
        }
    }


    /**
     * set the  colTitleList option
     *
     * @param array $colTitleList array('colName'=>'colTitle')
     */
    function set_colTitleList( $colTitleList, $hideColsWithoutTitle=false)
    {
        $this->set_hideColsWithoutTitle($hideColsWithoutTitle);
        if (is_array($colTitleList)) $this->colTitleList = $colTitleList;
        else                         trigger_error('array attempt',E_USER_NOTICE);
    }


    /**
     * set the  colAttributeList option
     *
     * @param array $colAttributeList array('colName'=> array('attribName'=>'attribValue'))
     */
    function set_colAttributeList( $colAttributeList)
    {
        $this->colAttributeList = $colAttributeList;

    }


    /**
     * set the  colAttributeList option
     *
     * @param array $colAttributeList array('colName'=> array('attribName'=>'attribValue'))
     */
    function set_noRowMessage( $htmlNoRowMessage)
    {
        $this->htmlNoRowMessage = $htmlNoRowMessage;

    }


    /**
     * set the caption
     *
     * @param string $caption array('colName'=>'colTitle')
     */
    function set_caption($caption)
    {
        $this->caption =  '<caption>' . $caption . '</caption>';
    }


    /**
     * set the  caption option
     *
     * @param string $caption array('colName'=>'colTitle')
     */
    function set_colHead( $colHeadName)
    {
        $this->colHead = $colHeadName;
    }


    /**
     * set the  counterLine option
     *
     * @param integer $counterLine
     */
    function showCounterLine()
    {
        $this->dispCounter = true;
    }


    /**
     * Add a decoration on a column
     *
     * add, or overide a column by a content build from a template and where
     * tags are replace by data from each lines
     *
     * $myDataList[]=array('id'=>1,'pid'=>'foo',);
     * $dg = new claro_datagrid($myDataList);
     * $dg->set_colDecoration('edit','<a href="?cmd=edit&amp;id=%id&amp;pid=%pid">edit</a>', array('id','pid'));
     * $dg->set_colDecoration('foo','<strong>%foo</strong>', array('foo'));
     *
     * The first decoration add a third column called edit. and  using id and pid from line to fill %id and %pid;
     * The second decoration overite the existing column 'foo' by the same content between <strong> tags
     *
     *
     * @param string $colName
     * @param string $decorationPattern
     * @param array $tag
     *
     * @since 1.9
     * @return the current list
     */
    function set_colDecoration($colName,$decorationPattern, $tag)
    {
        $this->decorationList[$colName] = array( 'decorationPattern' => $decorationPattern
                                         , 'tagList' => $tag);
        return $this->decorationList;
    }
    
    
    function render()
    {
        $stream = '';
        if (is_array($this->datagrid) )//&& count($this->datagrid))
        {
            /**
             * Build attributes for column
             * In  W3C <COL> seems be the good usage but browser don't follow the tag
             * So all attribute would be in each td of column.
             */
            if (!is_array($this->colTitleList) && count($this->datagrid))
            {
                if (is_array($this->datagrid) && isset($this->datagrid[0]) && is_array($this->datagrid[0]))
                $this->colTitleList = array_keys($this->datagrid[0]);
            }
            elseif (!is_array($this->colTitleList))
            {
                $this->colTitleList = array();
            }

            if (isset($this->colAttributeList))
            foreach (array_keys($this->colAttributeList) as $col)
            {
                $attrCol[$col]='';
                foreach ($this->colAttributeList[$col] as $attriName => $attriValue )
                {
                    $attrCol[$col] .=' ' . $attriName . '="' . $attriValue . '" ';
                }
            }

            $stream .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
            // THEAD LINE
            .          '<thead>' . "\n"
            .          $this->caption
            .          '<tr class="headerX" align="center" valign="top">' . "\n"
            ;

            if ($this->dispIdCol) $stream .= '<th width="10"></th>' . "\n";

            $i=0;
            if(is_array($this->colTitleList))
            foreach ($this->colTitleList as $colTitle)
            {
                $stream .= '<th scope="col" id="c' . $i++ . '" >' . $colTitle . '</th>' . "\n";
            }
            $stream .= '</tr>' . "\n"
            .          '</thead>' . "\n"
            ;

            if ($this->dispCounter)
            {
                $stream .= '<tfoot>' . "\n"
                .          '<tr class="headerX" align="center" valign="top">' . "\n"
                .          '<td>' . "\n"
                .          '</td>' . "\n"
                .          '<td>' . "\n"
                .          count($this->datagrid) . ' ' . get_lang('Lines')
                .          '</td>' . "\n"
                .          '</tr>' . "\n"
                .          '</tr>' . "\n"
                .          '</tfoot>' . "\n"
                ;

            }

            $stream .= '<tbody>' . "\n";
            if(count($this->datagrid))
            {
                foreach ($this->datagrid as $key => $dataLine )
                {
                    switch ($this->idLineType)
                    {
                        case 'key'     : $idLine = $key;                                       break;
                        case 'numeric' : $idLine = $this->idLineShift + $this->internalKey++ ; break;
                        default        : $idLine = '';
                    }

                    $stream .= '<tr>' . "\n";

                    if ($this->dispIdCol) $stream .= '<td align="right" valign="middle">' . $idLine . '</td>' . "\n";

                    $i=0;
                    if($this->orderCols == DG_ORDER_COLS_BY_TITLE)
                    {
                        $keyOrder=array_keys(array_merge($this->colTitleList,$dataLine,$this->decorationList));
                    }
                    else
                    {
                        $keyOrder=array_keys(array_merge($dataLine,$this->decorationList));
                    }
                    
                    foreach ($keyOrder as $colId)
                    {
                        // a protection if there is no cell for the current col
                        if(array_key_exists($colId,$dataLine)) $dataCell= $dataLine[$colId];
                        else                                   $dataCell = '';

                        if(array_key_exists($colId,$this->decorationList))
                        {
                            // Decore content
                            $dataCell = $this->decorationList[$colId]['decorationPattern'];
                            foreach ($this->decorationList[$colId]['tagList'] as $tagName)
                            {
                                if (isset($dataLine[$tagName]))
                                $dataCell = str_replace('%'.$tagName,$dataLine[$tagName],$dataCell);
                            }
                        }
                        

                        if ( !$this->hideColsWithoutTitle
                             || (    is_array($this->colTitleList)
                                  && count($this->colTitleList) > 0
                                  && in_array($colId,array_keys($this->colTitleList))
                             )
                        )

                        {
                            if ($this->colHead == $colId)
                            {
                                $stream .= '<td scope="row" id="L' . $key . '" headers="c' . $i++ . '" ' . ( isset($attrCol[$colId])?$attrCol[$colId]:'') . '>';
                                $stream .= $dataCell;
                                $stream .= '</td>' . "\n";
                            }
                            else
                            {
                                $stream .= '<td headers="c' . $i++ . ' L' . $key . '" ' . ( isset($attrCol[$colId])?$attrCol[$colId]:'') . '>';
                                $stream .= $dataCell;
                                $stream .= '</td>' . "\n";
                            }
                        }
                    }
                    $stream .= '</tr>' . "\n";

                }
            }
            else
            {
                if (is_null($this->htmlNoRowMessage )) $this->htmlNoRowMessage = get_lang('No result');
                $stream .= '<tr class="dgnoresult" >'
                .          '<td class="dgnoresult" colspan="' . count(array_keys($this->colTitleList)) . '">'
                .          $this->htmlNoRowMessage
                .          '</td>'
                .          '</tr>'
                ;
            }
            $stream .= '</tbody>' . "\n"
            .          '</table>' . "\n"
            ;

        }
        return $stream;
    }
}

//////////////////////////////////////////////////////////////////////////////
//                              DISPLAY OPTIONS
//                            student    view, title, ...
//////////////////////////////////////////////////////////////////////////////


/**
 * Route the script to an auhtentication form if user id is missing.
 * Once authenticated, the system get back to the source where the form
 * was trigged
 *
 * @param boolean $cidRequired - if the course id is required to leave the form
 * @author Christophe gesche <moosh@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */
function claro_disp_auth_form($cidRequired = false)
{
    if ( isset($_SESSION['login_already_claimed']) && $_SESSION['login_already_claimed'] === true )
    {
        $_SESSION['login_already_claimed'] = false;
        return;
    }
    
    $_SESSION['login_already_claimed'] = true;
    
    // TODO check if it does not break the CAS mechanism
    if( get_conf('claro_secureLogin', false) )
    {
        $sourceUrl = base64_encode( rtrim( get_path( 'rootWeb' ), '/' ) . $_SERVER['REQUEST_URI'] );
    }
    else
    {
        $sourceUrl = base64_encode($_SERVER['REQUEST_URI']);
    }

    if ( ! headers_sent () )
    {
        $urlCmd = ($cidRequired && ! claro_is_in_a_course() ? '&cidRequired=true' : '');
        header('Location:' . get_path('url') . '/claroline/auth/login.php?sourceUrl=' . urlencode($sourceUrl) . $urlCmd );
    }
    else // HTTP header has already been sent - impossible to relocate
    {
        Claroline::getDisplay()->body->appendContent( '<p align="center">'
        .    'WARNING ! Login Required <br />'
        .    'Click '
        .    '<a href="' . get_path('url') . '/claroline/auth/login.php'
        .    '?sourceUrl=' . urlencode($sourceUrl) . '">'
        .    'here'
        .    '</a>'
        .    '</p>'
        );

        Claroline::getDisplay()->render();
    }

    die(); // necessary to prevent any continuation of the application
}


/**
 * function claro_build_nested_select_menu($name, $elementList)
 * Build in a relevant way 'select' menu for an HTML form containing nested data
 *
 * @param string $name, name of the select tag
 * @param array nested data in a composite way
 *
 * @return string the HTML flow
 *
 *  $elementList[1]['name'    ] = 'level1';
 *  $elementList[1]['value'   ] = 'level1';
 *
 *  $elementList[1]['children'][1]['name' ] = 'level2';
 *  $elementList[1]['children'][1]['value'] = 'level2';
 *
 *  $elementList[1]['children'][2]['name' ] = 'level2';
 *  $elementList[1]['children'][2]['value'] = 'level2';
 *
 *  $elementList[2]['name' ]  = 'level1';
 *  $elementList[2]['value']  = 'level1';
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 */
function claro_build_nested_select_menu($name, $elementList)
{
    return '<select name="' . $name . '">' . "\n"
    .      implode("\n", prepare_option_tags($elementList) )
    .      '</select>' .  "\n"
    ;
}


/**
 * prepare the 'option' html tag for the claro_disp_nested_select_menu()
 * function
 *
 * @param array $elementList
 * @param int   $deepness (optionnal, default is 0)
 * @return array of option tag list
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 */
function prepare_option_tags($elementList, $deepness = 0)
{
    foreach($elementList as $thisElement)
    {
        $tab = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $deepness);

        $optionTagList[] = '<option value="'.$thisElement['value'].'">'
        .                  $tab.$thisElement['name']
        .                  '</option>'
        ;
        if (   isset( $thisElement['children'] )
        && sizeof($thisElement['children'] ) > 0)
        {
            $optionTagList = array_merge( $optionTagList,
            prepare_option_tags($thisElement['children'],
            $deepness + 1 ) );
        }
    }

    return  $optionTagList;
}


/**
 * Checks if the string has been written html style (ie &eacute; etc)
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param string $string
 * @return boolean true if the string is written in html style, false otherwise
 */
function is_htmlspecialcharized($string)
{
    return (bool) preg_match('/(&[a-z]+;)|(&#[0-9]+;)/', $string);
}


/**
 * function that cleans php string for javascript
 *
 * This function is needed to clean strings used in javascript output
 * Newlines are prohibited in the script, specialchar  are prohibited
 * quotes must be addslashes
 *
 * @param $str string original string
 * @return string : cleaned string
 *
 * @author Piraux Sebastien <pir@cerdecam.be>
 *
 */
function clean_str_for_javascript( $str )
{
    $output = $str;
    // 1. addslashes, prevent problems with quotes
    // must be before the str_replace to avoid double backslash for \n
    $output = addslashes($output);
    // 2. turn windows CR into *nix CR
    $output = str_replace("\r", '', $output);
    // 3. replace "\n" by uninterpreted '\n'
    $output = str_replace("\n",'\n', $output);
    // 4. convert special chars into html entities
    $output = claro_htmlspecialchars($output);

    return $output;
}


/**
 * Remove comments and noise from MS Office 2007 pasted-text that causes
 * Internet Explorer rendering engine to halt
 * @param   string original text
 * @return  string clean text
 */
function cleanup_mso2007_text ( $string )
{
    // remove comments from mso2007 that cause IE rendering engine to halt
    $regexp_if = "/\<\!--\[if(.*?)(\<\!--|\<\!)\[endif\]--\>/mi";
    $string = preg_replace( $regexp_if, '', $string );
    
    //remove comments missed by the previous rule
    $regexp_rm_if = "/(\<\!--\[if(.*?)\]\>|\<\!-*\[endif\]--\>)/mi";
    $string = preg_replace( $regexp_rm_if, '', $string );
    
    // remove noisy font definitions
    $regexp_font = "~<p>&lt;\!--  /\* Font Definitions(.*?)--&gt;</p>~i";
    $string = preg_replace( $regexp_font, '', $string );
    
    return $string;
}


/**
 * Parse the user text (e.g. stored in database)
 * before displaying it to the screen
 * For example it change new line charater to <br> tag etc.
 *
 * @param string $userText original user text
 * @return string : parsed user text
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */
function claro_parse_user_text($userText)
{
    $userText = cleanup_mso2007_text( $userText );
    $userText = cleanUpLaTeX( $userText );
    
    $userText = renderTex($userText);
    $userText = make_clickable($userText);
    $userText = make_spoiler($userText);
    
    if( !claro_is_html($userText) )
    {
        // only if the content isn't HTML change new line to <br>
        // Note the '<!-- content: html -->' is introduced by HTML Area
        $userText = nl2br($userText);
    }

    return $userText;
}


/**
 * Return true if the given text is HTML
 * @param string $userText
 * @return bool 
 */
function claro_is_html($userText)
{
    return ( preg_match('/<!-- content:[^(\-\->)]*-->/', $userText)
        || preg_match( '#(?<=<)\w+(?=[^<]*?>)#', $userText ) );
}


/**
 * Find all spoiler tags in text and replace them by html
 *
 * @param string $text text in which replace spoiler tags
 * @return string text with spoiler tags replaced by html
 */
function make_spoiler($text)
{
    $reg0a = "%(<p>\s*\[spoiler.*(?:/([^'/]*)/)?\s*\]([^'/]+)\s*</p>|<p>\s*\[spoiler.*(?:/([^'/]*)/)?\s*\]\s*</p>)%isU";
    
    $text = preg_replace_callback( $reg0a, 'clean_spoilerStart', $text);
    
    $reg0b = "%(<p>\s*\[/spoiler\]\s*</p>|\[/spoiler\]\s*</p>)%isU";
    
    $text = preg_replace_callback( $reg0b, 'clean_spoilerEnd', $text);
    
    $reg1 = "%(<p>\[spoiler.*(?:/([^'/]*)/)?\s*\]|\[spoiler.*(?:/([^'/]*)/)?\s*\])%isU";
    
    $reg2 = "%(\[/spoiler\]\s*</p>|\[/spoiler\])%isU";
    $replace2 = '</div>' . "\n"
    .   '</div>' . "\n"
    ;
    
    $out = preg_replace_callback ($reg1, 'add_spoiler', $text);
    $out = preg_replace($reg2, $replace2, $out);
    
    return $out;
}


function clean_spoilerStart($match)
{
    if(isset($match[4]))
    {
        return '[spoiler /'.$match[4].'/]';
    }
    else
    {
        return false;
    }
    
}


function clean_spoilerEnd($match)
{
    return '[/spoiler]';
}


/**
 * Callback function used by make_spoiler function
 *
 * @param array $match
 * @return string replacement for matched spoiler tags
 */
function add_spoiler($match)
{
    // show and hide text
    $spoiler_show_text = (!empty($match[3]) ? $match[3] : get_lang('Show') );
    $out = '<div class="spoiler">'
    .   '<a href="#" class="reveal showSpoiler" onclick="javascript:Claroline.spoil($(this)); return false;">'
    .   $spoiler_show_text
    .   '</a>' . "\n"
    .   '<div class="spoilerContent">' . "\n"
    ;
    
    return $out;
}

function cleanUpLaTeX( $text )
{
    $claro_texRendererUrl = get_conf('claro_texRendererUrl');

    if ( !empty($claro_texRendererUrl) )
    {
        // new LaTeX images with class
        $text = preg_replace(
            '~<img src="(.*?)" border="0" align="absmiddle" class="latexFormula" alt="(.*?)" />~i', 
            '[tex]\2[/tex]', 
            $text );
        
        // old mimetex images without class
        $text = preg_replace_callback(
             '~<img(.*?)src="(.*?)mimetex(.*?)\?(.*?)"(.*?)/>~i',
             'deUrlizeLaTeX',
             $text );

    }
    else
    {
        $text = str_replace(
            '<embed TYPE="application/x-techexplorer" texdata="', 
            '[tex]',
            $text
        );

        $text = str_replace(
            '" width="100%" pluginspace="http://www.integretechpub.com/">','
            [/tex]',
            $text 
        );
    }
    
    return $text;
}

function deUrlizeLaTeX( $matches )
{
    if ( count($matches) < 5 )
    {
        return false;
    }

    return '[tex]'.rawurldecode($matches[4]).'[/tex]';
}




/**
 * Parse the user text to transform bb code style tex tags to
 * embedded tex plugin or tex generated image depending on
 * campus config
 *
 * @param string $text original user text
 * @return string : parsed user text
 */
function renderTex($text)
{
    $claro_texRendererUrl = get_conf('claro_texRendererUrl');

    if ( !empty($claro_texRendererUrl) )
    {
        $text = preg_replace_callback(  '/\[tex\](.+?)\[\/tex\]/i',
                                        'renderTexCallback',
                                        $text
                            );
    }
    else
    {
        $text = str_replace('[tex]',
        '<embed TYPE="application/x-techexplorer" texdata="',
        $text);

        $text = str_replace('[/tex]',
        '" width="100%" pluginspace="http://www.integretechpub.com/">',
        $text);
    }

    return $text;
}


function renderTexCallback( $matches )
{
    if(isset($matches[1]))
    {
        $claro_texRendererUrl = get_conf('claro_texRendererUrl');

        $text = '<img src="'.$claro_texRendererUrl.'?'.rawurlencode($matches[1]).'" border="0" align="absmiddle" class="latexFormula" alt="'.claro_htmlspecialchars($matches[1]).'" />';
        return $text;
    }
    else
    {
        return false;
    }
}


/**
 * Completes url contained in the text with "<a href ...".
 * However the function simply returns the submitted text without any
 * transformation if it already contains some "<a href:" or "<img src=".
 *
 * Actually this function is taken from the PHP BB 1.4 script
 * - Goes through the given string, and replaces xxxx://yyyy with an HTML <a> tag linking
 *  to that URL
 * - Goes through the given string, and replaces www.xxxx.yyyy[zzzz] with an HTML <a> tag linking
 *  to http://www.xxxx.yyyy[/zzzz]
 * - Goes through the given string, and replaces xxxx@yyyy with an HTML mailto: tag linking
 *      to that email address
 * - Only matches these 2 patterns either after a space, or at the beginning of a line
 *
 * Notes: the email one might get annoying - it's easy to make it more restrictive, though.. maybe
 * have it require something like xxxx@yyyy.zzzz or such. We'll see.
 *
 * @param  string $text text to be converted
 * @return string : text after conversion
 *
 * @author Rewritten by Nathan Codding - Feb 6, 2001.
 * @author completed by Hugues Peeters - July 22, 2002
 */
function make_clickable($text)
{

    // If the user has decided to deeply use html and manage himself hyperlink
    // cancel the make clickable() function and return the text untouched. HP

    if (preg_match ( "<(a|img)[[:space:]]*(href|src)[[:space:]]*=(.*)>", $text) )
    {
        return $text;
    }

    // pad it with a space so we can match things at the start of the 1st line.
    $ret = " " . $text;


    // matches an "xxxx://yyyy" URL at the start of a line, or after a space.
    // xxxx can only be alpha characters.
    // yyyy is anything up to the first space, newline, or comma.

    $ret = preg_replace("#([\n ])([a-z]+?)://([^, \n\r<]+)#i",
    "\\1<a href=\"\\2://\\3\" >\\2://\\3</a>",
    $ret);

    // matches a "www.xxxx.yyyy[/zzzz]" kinda lazy URL thing
    // Must contain at least 2 dots. xxxx contains either alphanum, or "-"
    // yyyy contains either alphanum, "-", or "."
    // zzzz is optional.. will contain everything up to the first space, newline, or comma.
    // This is slightly restrictive - it's not going to match stuff like "forums.foo.com"
    // This is to keep it from getting annoying and matching stuff that's not meant to be a link.

    $ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^, \n\r<]*)?)#i",
    "\\1<a href=\"http://www.\\2.\\3\\4\" >www.\\2.\\3\\4</a>",
    $ret);

    // matches an email@domain type address at the start of a line, or after a space.
    // Note: before the @ sign, the only valid characters are the alphanums and "-", "_", or ".".
    // After the @ sign, we accept anything up to the first space, linebreak, or comma.

    $ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^, \n\r<]+)#i",
    "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>",
    $ret);

    // Remove our padding..
    $ret = substr($ret, 1);

    return($ret);
}


/**
 * Deprecated functions
 * Some function still present to prevent local developpement
 *
 * They would be removed after 1.8
 *
 */

/**
 * Enhance a simple textarea with an inline html editor.
 *
 * @param string $name name attribute for <textarea> tag
 * @param string $content content to prefill the area
 * @param integer $rows count of rows for the displayed editor area
 * @param integer $cols count of columns for the displayed editor area
 * @param string $optAttrib    optional - additionnal tag attributes
 *                                       (wrap, class, ...)
 * @return string html output for standard textarea or Wysiwyg editor
 *
 * @deprecated would be removed after 1.8
 * @see claro_html_textarea_editor
 *
 */
function claro_disp_html_area($name, $content = '', $rows=20, $cols=80, $optAttrib='')
{
    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
    ? claro_html_debug_backtrace()
    : 'claro_html_debug_backtrace() not defined'
    )
    .'claro_disp_textarea_editor() is deprecated , use claro_html_textarea_editor()','error');

    // becomes a alias while the function call is not replaced by the new one
    return claro_html_textarea_editor($name,$content,$rows,$cols,$optAttrib);
}

/**
 * transform content in a html display
 * @param  - string $string string to htmlize
 * @return  - string htmlized
 */

function htmlize($phrase)
{
    // TODO use textile project here
    return claro_parse_user_text(claro_htmlspecialchars($phrase));
}

/**
 * convert a duration in seconds to a human readable duration
 * @author Sebastien Piraux <pir@cerdecam.be>
 * @param integer duration time in seconds to convert to a human readable duration
 */

function claro_disp_duration( $duration  )
{
    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
    ? claro_html_debug_backtrace()
    : 'claro_html_debug_backtrace() not defined'
    )
    .'claro_ disp _duration() is deprecated , use claro_ html _duration()','error');

    return claro_html_duration( $duration  );
}
function claro_html_duration( $duration  )
{
    if( $duration == 0 ) return '0 '.get_lang('SecondShort');

    $days = floor(($duration/86400));
    $duration = $duration % 86400;

    $hours = floor(($duration/3600));
    $duration = $duration % 3600;

    $minutes = floor(($duration/60));
    $duration = $duration % 60;
    // $duration is now equal to seconds

    $durationString = '';

    if( $days > 0 ) $durationString .= $days . ' ' . get_lang('PeriodDayShort') . ' ';
    if( $hours > 0 ) $durationString .= $hours . ' ' . get_lang('PeriodHourShort') . ' ';
    if( $minutes > 0 ) $durationString .= $minutes . ' ' . get_lang('MinuteShort') . ' ';
    if( $duration > 0 ) $durationString .= $duration . ' ' . get_lang('SecondShort');

    return $durationString;
}

/**
 * Return the breadcrumb to display in the header
 *
 * @global string  $nameTools
 * @global array   $interbredcrump
 * @global boolean $noPHP_SELF
 * @global boolean $noQUERY_STRING
 *
 * @return string html content
 */

function claro_html_breadcrumb()
{
    // dirty global to keep value (waiting a refactoring)
    global $nameTools, $interbredcrump, $noPHP_SELF, $noQUERY_STRING;
    /******************************************************************************
    BREADCRUMB LINE
    ******************************************************************************/
    $htmlBC = '';

    if( claro_is_in_a_course() || isset($nameTools) || ( isset($interbredcrump) && is_array($interbredcrump) ) )
    {
        $htmlBC .= '<div id="breadcrumbLine">' . "\n\n"
        .  '<hr />'
        . "\n"
        ;

        $breadcrumbUrlList = array();
        $breadcrumbNameList = array();

        $breadcrumbUrlList[]  = get_path('url') . '/index.php';
        $breadcrumbNameList[] = get_conf('siteName');

        if ( claro_is_in_a_course() )
        {
            $breadcrumbUrlList[]  = get_path('clarolineRepositoryWeb') . 'course/index.php?cid=' . claro_htmlspecialchars(claro_get_current_course_id());
            $breadcrumbNameList[] = claro_get_current_course_data('officialCode');
        }

        if ( claro_is_in_a_group() )
        {
            $breadcrumbUrlList[]  = get_module_url('CLGRP') . '/index.php?cidReq=' . claro_htmlspecialchars(claro_get_current_course_id());
            $breadcrumbNameList[] = get_lang('Groups');
            $breadcrumbUrlList[]  = get_module_url('CLGRP') . '/group_space.php?cidReq=' . claro_htmlspecialchars(claro_get_current_course_id()).'&gidReq=' . (int) claro_get_current_group_id();
            $breadcrumbNameList[] = claro_get_current_group_data('name');
        }

        if (isset($interbredcrump) && is_array($interbredcrump) )
        {
            while ( (list(,$bredcrumpStep) = each($interbredcrump)) )
            {
                $breadcrumbUrlList[] = $bredcrumpStep['url'];
                $breadcrumbNameList[] = $bredcrumpStep['name'];
            }
        }

        if (isset($nameTools) )
        {
            $breadcrumbNameList[] = $nameTools;

            if (isset($noPHP_SELF) && $noPHP_SELF)
            {
                $breadcrumbUrlList[] = null;
            }
            elseif ( isset($noQUERY_STRING) && $noQUERY_STRING)
            {
                $breadcrumbUrlList[] = $_SERVER['PHP_SELF'];
            }
            else
            {
                // set Query string to empty if not exists
                if (!isset($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING'] = '';
                $breadcrumbUrlList[] = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
            }
        }

        $htmlBC .= claro_html_breadcrumbtrail($breadcrumbNameList, $breadcrumbUrlList,
        ' &gt; ', get_icon_url('home'));


        if ( !claro_is_user_authenticated() )
        {
            $htmlBC .= "\n".'<div id="toolViewOption" style="padding-right:10px">'
            .'<a href="' . get_path('clarolineRepositoryWeb') . 'auth/login.php'
            .'?sourceUrl='.urlencode(base64_encode( (isset( $_SERVER['HTTPS']) && ($_SERVER['HTTPS']=='on'||$_SERVER['HTTPS']==1) ? 'https://' : 'http://'). $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])). '" target="_top">'
            .get_lang('Login')
            .'</a>'
            .'</div>'."\n";
        }
        elseif ( claro_is_in_a_course() && ! claro_is_course_member() && claro_get_current_course_data('registrationAllowed') && ! claro_is_platform_admin() )
        {
            $htmlBC .= '<div id="toolViewOption">'
            .    '<a href="' . get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=exReg&course='.claro_get_current_course_id().'">'
            .     '<img src="' . get_icon_url('enroll') . '" alt="" /> '
            .    '<b>' . get_lang('Enrolment') . '</b>'
            .    '</a>'
            .    '</div>' . "\n"
            ;
        }
        elseif ( claro_is_display_mode_available() )
        {
            $htmlBC .= "\n"
            .          '<div id="toolViewOption">' . "\n"
            ;

            if ( isset($_REQUEST['View mode']) )
            {
                $htmlBC .= claro_html_tool_view_option($_REQUEST['View mode']);
            }
            else
            {
                $htmlBC .= claro_html_tool_view_option();
            }

            if ( claro_is_platform_admin() && ! claro_is_course_member() )
            {
                $htmlBC .= ' | <a href="' . get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=exReg&course='.claro_get_current_course_id().'">';
                $htmlBC .= '<img src="' . get_icon_url('enroll') . '" alt="" /> ';
                $htmlBC .= '<b>' . get_lang('Enrolment') . '</b>';
                $htmlBC .= '</a>';
            }

            $htmlBC .= "\n".'</div>' ."\n";
        }


        $htmlBC .= '<div class="spacer"></div>' ."\n"
        .          '<hr />' ."\n"
        .          '</div>' . "\n"
        ;

    } // end if claro_is_in_a_course() isset($nameTools) && is_array($interbredcrump)
    else
    {
        // $htmlBC .= '<div style="height:1em"></div>';
    }
    return $htmlBC;
}

/**
 * Create a navigation tab bar
 *
 * @param array $section_list associative array of tabs tab id => tab label
 * @param string $section_selected_id selected tab id
 * @param array $url_params associative array of additionnal parameters
 *      name => value
 * @param string $section_request_var_name name of the HTTP GET variable
 *      to store the current tab id
 * @param string $baseUrl base url of the navigation tab bar
 * @return string html navigation tab bar
 */
function claro_html_tab_bar( $section_list,
                             $section_selected_id = null,
                             $url_params = array(),
                             $section_request_var_name = 'section',
                             $baseUrl = null )
{
    $menu = '';

    if ( !empty($section_list) )
    {
        $baseUrl = empty( $baseUrl )
            ? $_SERVER['PHP_SELF']
            : $baseUrl
            ;
            
        $baseUrl .= ( false !== strpos($baseUrl, '?' ) )
            ? '&amp;'
            : '?'
            ;
        
        
        $extra_url_params = '';
        
        if ( ! empty ( $url_params ) )
        {
            foreach ( $url_params as $name => $value )
            {
                $extra_url_params .= '&amp;'
                    . claro_htmlspecialchars($name)
                    . '=' . claro_htmlspecialchars($value)
                    ;
            }
        }
        
        $menu  = '<div>' . "\n";
        $menu .= '<ul id="navlist">' . "\n";

        foreach ( $section_list as $section_id => $section_label )
        {
            if ( empty( $section_selected_id ) )
            {
                $section_selected_id = $section_id;
            }
            
            $menu .=  '<li>'
                . '<a ' . ( $section_id == $section_selected_id ? 'class="current"' : '' )
                . ' href="' . claro_htmlspecialchars(Url::Contextualize( $baseUrl
                . claro_htmlspecialchars($section_request_var_name).'='
                . claro_htmlspecialchars($section_id) .$extra_url_params )) . '" '
                . 'id="'. claro_htmlspecialchars($section_id) .'">'
                . get_lang($section_label) . '</a>'
                . '</li>' . "\n"
                ;

        }
        
        $menu .= '</ul>' . "\n";
        $menu .= '</div>' . "\n" ;
    }
    
    return $menu;
}

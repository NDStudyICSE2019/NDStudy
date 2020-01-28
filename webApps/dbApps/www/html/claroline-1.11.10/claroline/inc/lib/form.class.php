<?php // $Id: form.class.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * Form class
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

class Form
{
    // vars
    private $_paramList = array();
    private $_elementList = array();

    private $_isDatePickerLoaded;

    // construct
    function __construct($name)
    {
        $this->setName($name);

        $this->_isDatePickerLoaded = false;
    }

    // define form
    //-- general set param method
    function setParam($paramName, $value)
    {
        $this->_paramList[$paramName] = trim($value);
    }

    //-- set params helper
    function setName($value)
    {
        $this->setParam('name', $value );
    }

    function setId($value)
    {
        $this->setParam('id', $value );
    }

    function setClass($value)
    {
        $this->setParam('class', $value );
    }

    function setAction($value)
    {
        $this->setParam('action', $value );
    }

    function setMethod($value)
    {
        $this->setParam('method', $value );
    }

    function setEnctype($value)
    {
        $this->setParam('enctype', $value );
    }

    // populate form
    function addElement($formElement)
    {
        $this->_elementList[] = $formElement;
    }

    function render()
    {
        // header
        $html = '<form ' . renderParams($this->_paramList) . '>' . "\n";

        // fields
        foreach( $this->_elementList as $formElement )
        {
            $html .= '<p>' . $formElement->render() . '</p>' . "\n";
        }

        // buttons
        $html .= '<input type="submit" value="Ok" />' . "\n";
        // footer
        $html .= '</form>' . "\n";

        return $html;
    }

}

class FormElement
{
    protected $name;
    protected $id;
    protected $label;
    protected $value;
    protected $required;
    protected $optionList;


    private function __construct($name)
    {
        $this->setName($name);

        // by default id is the same as the name
        $this->setId($name);
        $this->setLabel('');
        $this->setValue('');
        $this->setRequired(false);
        $this->setOptionList(array());
    }

    function render()
    {
        $html = $this->renderLabel() . '<br />'
        .     $this->renderElement();

        return $html;
    }

    function renderElement()
    {
        return '';
    }

    function renderLabel()
    {
        return '';
    }

    function setName($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getId()
    {
        return $this->id;
    }

    function setLabel($label)
    {
        $this->label = $label;
    }

    function getLabel()
    {
        return $this->label;
    }

    function setValue($value)
    {
        $this->value = $value;
    }

    function getValue()
    {
        return $this->value;
    }

    function setRequired($required = true)
    {
        $this->required = $required;
    }

    function isRequired()
    {
        return (bool) $this->required;
    }

    function setOptionList($optionList)
    {
        $this->optionList = $optionList;
    }

    function getOptionList()
    {
        return $this->optionList;
    }

}

class Input extends FormElement
{
    protected $type;

    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'text';
    }

    function renderElement()
    {
        $html = '<input type="'.$this->type.'"'
        .     ' id="form_'.$this->id.'"'
        .     ' name="'.$this->name.'"'
        .     ' value="'.claro_htmlspecialchars($this->value).'"'
        .     ' ' . renderParams($this->optionList)
        .     ' />';

        return $html;
    }

    function renderLabel()
    {
        $html = '<label for="form_'.$this->id.'">'.$this->label.'</label>';

        return $html;
    }
}

class InputText extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'text';
    }

    public static function Factory($name, $value = '', $label = '', $required = false, $attrList = array())
    {
        $element = new InputText($name);

        $element->setValue($value);
        $element->setLabel($label);
        $element->setRequired($required);
        $element->setOptionList($attrList);

        return $element;
    }
}

class InputHidden extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'hidden';
    }

    function render()
    {
        $html = $this->renderElement();

        return $html;
    }

    function renderLabel()
    {
        return '';
    }

    public static function Factory($name, $value = '', $attrList = array())
    {
        $element = new InputHidden($name);

        $element->setValue($value);
        $element->setOptionList($attrList);

        return $element;
    }

}

class InputPassword extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'password';
    }

    public static function Factory($name, $value = '', $label = '', $required = false, $attrList = array())
    {
        $element = new InputPassword($name);

        $element->setValue($value);
        $element->setLabel($label);
        $element->setRequired($required);
        $element->setOptionList($attrList);

        return $element;
    }
}

class InputFile extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'file';
    }

    public static function Factory($name, $label = '', $required = false, $attrList = array())
    {
        $element = new InputFile($name);

        $element->setLabel($label);
        $element->setRequired($required);
        $element->setOptionList($attrList);

        return $element;
    }
}

class InputCheckbox extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'checkbox';
    }


    // custom id is there to associate label with a unique id when several checkbox have same name (often...)
    public static function Factory($name, $value = '', $label = '', $customId = null, $required = false, $attrList = array())
    {
        $element = new InputCheckbox($name);

        $element->setValue($value);
        $element->setLabel($label);
        $element->setId($customId);
        $element->setRequired($required);
        $element->setOptionList($attrList);

        return $element;
    }

    function render()
    {
        $html = $this->renderElement() . '&nbsp;' . $this->renderLabel() . '<br />';

        return $html;
    }
}

class InputRadio extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'radio';
    }

    // custom id is there to associate label with a unique id when several checkbox have same name (often...)
    public static function Factory($name, $value = '', $label = '', $customId = null, $required = false, $attrList = array())
    {
        $element = new InputRadio($name);

        $element->setValue($value);
        $element->setLabel($label);
        $element->setId($customId);
        $element->setRequired($required);
        $element->setOptionList($attrList);

        return $element;
    }

    function render()
    {
        $html = $this->renderElement() . '&nbsp;' . $this->renderLabel() . '<br />';

        return $html;
    }
}


class Button extends Input
{
    function __construct($name)
    {
        parent::__construct($name);

        $this->type = 'button';
    }

    public static function Factory($name, $label = '', $attrList = array())
    {
        $element = new Button($name);

        $element->setLabel($label);
        $element->setOptionList($attrList);

        return $element;
    }

    function renderLabel()
    {
        return '';
    }

    function renderElement()
    {
        $html = '<input type="'.$this->type.'"'
        .     ' id="form_'.$this->id.'"'
        .     ' name="'.$this->name.'"'
        .     ' value="'.claro_htmlspecialchars($this->label).'"' // use label in value as there is no real value in a button
        .     ' ' . renderParams($this->optionList)
        .     ' />';

        return $html;
    }
}

class FieldSet extends FormElement
{
    private $_elementList;

    function __construct($name, $label)
    {
        parent::__construct($name);

        $this->setLabel($label);
    }

    function render()
    {
        // header
        $html = '<fieldset id="'.$this->id.'">' . "\n"
        .     '<legend>' . $this->label . '</legend>' . "\n";

        // fields
        foreach( $this->_elementList as $formElement )
        {
            $html .= '<p>' . $formElement->render() . '</p>' . "\n";
        }

        // footer
        $html .= '</fieldset>' . "\n";

        return $html;
    }

    // populate fieldset
    function addElement($formElement)
    {
        $this->_elementList[] = $formElement;
    }

    public static function Factory($name, $label)
    {
        $element = new FieldSet($name, $label);

        return $element;
    }

}

class TextArea extends FormElement
{
    protected $wysiwyg;
    protected $rows;
    protected $cols;

    function __construct($name)
    {
        parent::__construct($name);

        $this->useWysiwyg(false);
    }

    function useWysiwyg($use)
    {
        $this->wysiwyg = $use;
    }

    function setCols($cols)
    {
        $this->cols = (int) $cols;
    }

    function getCols()
    {
        return (int) $this->cols;
    }

    function setRows($rows)
    {
        $this->rows = (int) $rows;
    }

    function getRows()
    {
        return (int) $this->rows;
    }

    function renderElement()
    {
        if( $this->wysiwyg )
        {
            $claro_editor = get_conf('claro_editor', 'tiny_mce');

            // $claro_editor is the directory name of the editor
            $incPath = get_path('rootSys') . 'claroline/editor/' . $claro_editor;
            $editorPath = get_path('url') . '/claroline/editor/';
            $webPath = $editorPath . $claro_editor;

            if( file_exists($incPath . '/editor.class.php') )
            {
                // include editor class
                include_once $incPath . '/editor.class.php';

                // editor instance
                // TODO fix option list
                $editor = new editor($this->name, $this->value, $this->rows, $this->cols, renderParams($this->optionList), $webPath);

                $html = $editor->getAdvancedEditor();
            }
            else
            {
                // force display of textarea as it will not be possible to display it in wysiwyg mode
                $this->useWysiwyg(false);
            }

        }

        if( !$this->wysiwyg )
        {
            $html = '<textarea'
            .     ' id="form_'.$this->id.'"'
            .     ' name="'.$this->name.'"'
            .     ' ' . renderParams($this->optionList)
            .     ' >'
            .     claro_htmlspecialchars($this->value)
            .     "\n" . '</textarea>' . "\n";
        }
        return $html;
    }

    function renderLabel()
    {
        $html = '<label for="form_'.$this->id.'">'.$this->label.'</label>';

        return $html;
    }

    public static function Factory($name, $value, $label = '', $required = false, $wysiwyg = false, $cols  = 80, $rows = 20, $attrList = array())
    {
        $element = new TextArea($name);

        $element->setValue($value);
        $element->setLabel($label);
        $element->setRequired($required);
        $element->setOptionList($attrList);
        $element->useWysiwyg($wysiwyg);
        $element->setRows($rows);
        $element->setCols($cols);

        return $element;
    }
}


class SelectBox extends FormElement
{
    // in this class $value is an array
    protected $selectedValueList;

    function __construct($name)
    {
        parent::__construct($name);

        $this->value = array();
        $this->selectedValueList = array();
    }

    function setValue($value)
    {
        if( is_array($value) )
        {
            $this->value = $value;
        }
        else
        {
            $this->value[] = $value;
        }
    }

    function getValue()
    {
        return $this->value;
    }

    function setSelectedValueList($selected)
    {
        if( is_array($selected) )
        {
            $this->selectedValueList = $selected;
        }
        else
        {
            $this->selectedValueList[] = $selected;
        }
    }

    function getSelectedValueList()
    {
        return $his->selectedValueList;
    }

    function renderElement()
    {
        $html = '<select id="form_'.$this->id.'" name="'.$this->name.'" >' . "\n"
        .     $this->_buildOptionList() . "\n"
        .     '</select>' . "\n";

        return $html;
    }

    function renderLabel()
    {
        $html = '<label for="form_'.$this->id.'">'.$this->label.'</label>';

        return $html;
    }

    private function _buildOptionList()
    {
        $html = '';

        foreach( $this->value as $optionValue => $optionLabel )
        {
            // check if value must be selected
            if( ( in_array($optionValue, $this->selectedValueList) )
            )
            {
                $displaySelected = 'selected="selected"';
            }
            else
            {
                $displaySelected = '';
            }

            $html .= '<option value="'.$optionValue.'" '.$displaySelected.'>'
            .     claro_htmlspecialchars($optionLabel)
            .     '</option>' . "\n";
        }

        return $html;
    }

    public static function Factory($name, $valueList, $label = '', $required = false, $selected = array(), $attrList = array())
    {
        $element = new SelectBox($name);

        $element->setValue($valueList);
        $element->setLabel($label);
        $element->setRequired($required);
        $element->setOptionList($attrList);
        $element->setSelectedValueList($selected);

        return $element;
    }

}


class CheckBoxList
{
    public static function Factory($name, $checkboxList, $label, $required = false, $checked = null)
    {
        $fieldSet = FieldSet::Factory($name.'_list', $label);

        if( is_array($checkboxList) && !empty($checkboxList) )
        {
            $i = 0;
            foreach( $checkboxList as $value => $label )
            {
                $id = $name.++$i;
                $fieldSet->addElement( InputCheckbox::Factory($name, $value, $label, $id));
            }
        }

        return $fieldSet;
    }
}

class RadioList
{
    public static function Factory($name, $checkboxList, $label, $required = false, $checked = null)
    {
        $fieldSet = FieldSet::Factory($name.'_list', $label);

        if( is_array($checkboxList) && !empty($checkboxList) )
        {
            $i = 0;
            foreach( $checkboxList as $value => $label )
            {
                $id = $name.++$i;
                $fieldSet->addElement( InputRadio::Factory($name, $value, $label, $id));
            }
        }

        return $fieldSet;
    }
}


//-----------------------
function renderParams($paramList)
{
    $out = array();

    foreach( $paramList as $param => $value )
    {
        $out[] = $param . '="' . claro_htmlspecialchars($value) . '"';
    }

    return implode(' ', $out);
}

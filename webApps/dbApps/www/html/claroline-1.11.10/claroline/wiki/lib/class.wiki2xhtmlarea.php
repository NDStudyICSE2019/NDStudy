<?php // $Id: class.wiki2xhtmlarea.php 14093 2012-03-22 10:22:57Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14093 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 *
 * @package Wiki2xhtmlArea
 */
require_once dirname(__FILE__) . "/lib.javascript.php";

/**
 * Wiki2xhtml editor textarea
 */
class Wiki2xhtmlArea
{

    protected $content;
    protected $attributeList;

    /**
     * Constructor
     * @param string content of the area
     * @param string name name of the area
     * @param int cols number of cols
     * @param int rows number of rows
     * @param array extraAttributes extra html attributes for the area
     */
    public function __construct(
        $content = ''
        , $name = 'content'
        , $cols = 80
        , $rows = 30
        , $extraAttributes = null )
    {
        $this->setContent($content);

        $attributeList = array ();
        $attributeList['name'] = $name;
        $attributeList['id'] = $name;
        $attributeList['cols'] = $cols;
        $attributeList['rows'] = $rows;

        $this->attributeList = ( is_array($extraAttributes) ) ? array_merge($attributeList, $extraAttributes) : $attributeList
        ;
    }

    /**
     * Set area content
     * @param string content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get area content
     * @return string area content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get area wiki syntax toolbar
     * @return string toolbar javascript code
     */
    public function getToolbar()
    {
        $toolbar = '';


        $toolbar .= '<script type="text/javascript" src="'
            . document_web_path() . '/js/toolbar.js"></script>'
            . "\n"
        ;
        $toolbar .= "<script type=\"text/javascript\">if (document.getElementById) {
    var tb = new dcToolBar(document.getElementById('" . $this->attributeList['id'] . "'),
    'wiki','" . get_module_url('CLWIKI') . "/img/toolbar/');

    tb.btStrong('" . get_lang('Bold') . "');
    tb.btEm('" . get_lang('Italic') . "');
    tb.btIns('" . get_lang('Underline') . "');
    tb.btDel('" . get_lang('Strike') . "');
    tb.btQ('" . get_lang('Inline quote') . "');
    tb.btCode('" . get_lang('Code') . "');
    tb.addSpace(10);
    tb.btBr('" . get_lang('Line break') . "');
    tb.addSpace(10);
    tb.btBquote('" . get_lang('Blockquote') . "');
    tb.btPre('" . get_lang('Preformated text') . "');
    tb.btList('" . get_lang('Unordered list') . "','ul');
    tb.btList('" . get_lang('Ordered list') . "','ol');
    tb.addSpace(10);
    tb.btLink('" . get_lang('External link') . "','" . get_lang('URL?')
            . "','" . get_lang('Language') . "','" . $GLOBALS['iso639_1_code'] . "');
    tb.btImgLink('" . get_lang('External image') . "','" . get_lang('URL') . "');
    tb.draw('');
}
</script>\n";

        return $toolbar;
    }

    /**
     * paint (ie echo) area
     */
    public function paint()
    {
        echo $this->render();
    }
    
    public function render()
    {
        return $this->toHTML();
    }

    /**
     * get area html code for string inclusion
     * @return string area html code
     */
    function toHTML()
    {
        $wikiarea = '';

        $attr = '';

        foreach ($this->attributeList as $attribute => $value)
        {
            $attr .= ' ' . $attribute . '="' . $value . '"';
        }

        $wikiarea .= '<textarea' . $attr . '>' . $this->getContent() . '</textarea>' . "\n";

        $wikiarea .= $this->getToolbar();

        return $wikiarea;
    }

}

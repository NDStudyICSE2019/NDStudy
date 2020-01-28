<?php

class Documents_ResourceLinker extends ResourceLinker
{
    /**
     * Redefines renderLinkerBlock while ResourceLinker::renderLinkerBlock do not allow to pass 
     * bacend url as argument
     *
     * @return unknown
     */
    public static function renderLinkerBlock( $backendUrl = NULL )
    {
        parent::init();
        
        return '<div id="lnk_panel">' . "\n"
            . '<div id="lnk_ajax_loading"><img src="'.get_icon_url('loading').'" alt="" /></div>' . "\n"
            . '<div id="lnk_selected_resources"></div>' . "\n"
            . '<h4 id="lnk_location"></h4>' . "\n"
            . '<div id="lnk_back_link"></div>'
            . '<div id="lnk_resources"></div>' . "\n"
            . '<div id="lnk_hidden_fields"></div>' . "\n"
            . '</div>' . "\n\n"
            ;
    }
    
}

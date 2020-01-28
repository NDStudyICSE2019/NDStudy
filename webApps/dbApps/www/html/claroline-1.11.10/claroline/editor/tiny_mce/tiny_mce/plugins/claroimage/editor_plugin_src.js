/**
 * $Id: editor_plugin_src.js 677 2008-03-07 13:52:41Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
    tinymce.create('tinymce.plugins.ClaroImagePlugin', {
        init : function(ed, url) {
            // Register commands
            ed.addCommand('mceClaroImage', function() {
                // Internal image object like a flash placeholder
                if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
                    return;

                ed.windowManager.open({
                    file : url + '/image.php',
                    width : 640 + parseInt(ed.getLang('advimage.delta_width', 0)),
                    height : 560 + parseInt(ed.getLang('advimage.delta_height', 0)),
                    inline : 1
                }, {
                    plugin_url : url
                });
            });

            // Register buttons
            ed.addButton('claroimage', {
                title : 'advimage.image_desc',
                cmd : 'mceClaroImage',
                image : url + '/img/icon.png'
            });
        },

        getInfo : function() {
            return {
                longname : 'Claroline custom Advanced image plugin',
                author : 'Claroline team',
                authorurl : 'http://www.claroline.net',
                infourl : '',
                version : tinymce.majorVersion + "." + tinymce.minorVersion
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('claroimage', tinymce.plugins.ClaroImagePlugin);
})();
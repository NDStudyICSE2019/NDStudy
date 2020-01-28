<?php // $Id: index.php 13718 2011-10-20 09:24:38Z ffervaille $

require '../inc/claro_init_global.inc.php';

FromKernel::uses('utils/input.lib','utils/validator.lib');

$module = Claro_UserInput::getInstance()->get('module');
$block = Claro_UserInput::getInstance()->get('block');

if ( !empty($module)
    && $module != 'platform' 
    && file_exists( get_module_path($module).'/templates/help.tpl.php' ))
{
    $tpl = new ModuleTemplate( $module, 'help.tpl.php ' );
}
else
{
    $tpl = new CoreTemplate( 'help.tpl.php' );
}

if ( $moduleName = get_module_data($module, 'moduleName') )
{
    load_module_language($module);
    $tpl->assign('module', $moduleName);
}
else
{
    $tpl->assign('module', $module);
}

$tpl->assign('block',$block);

$claroline->setDisplayType(Claroline::POPUP);

$claroline->display->header->setTitle(get_lang('Claroline help'));
$claroline->display->body->appendContent($tpl->render());

echo $claroline->display->render();

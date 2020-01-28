<?php

// load Claroline kernel
require_once dirname(__FILE__) . '/../../../../../inc/claro_init_global.inc.php';

if( ! claro_is_allowed_to_edit() )
{
	claro_die(get_lang('Not allowed'));
}

require_once get_conf( 'includePath' ) . '/lib/core/linker.lib.php';
require_once dirname( __FILE__ ) . '/./lib/linker.lib.php';

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                            $cmd = null;

JavascriptLanguage::getInstance ()->addLangVar('Attach');
JavascriptLanguage::getInstance ()->addLangVar('Delete');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo get_lang('Documents Linker'); ?></title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<script type="text/javascript" src="<?php echo rtrim( get_path( 'rootWeb' ), '/' ); ?>/web/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo rtrim( get_path( 'rootWeb' ), '/' ); ?>/web/js/claroline.js"></script>
	<script type="text/javascript" src="<?php echo rtrim( get_path( 'rootWeb' ), '/' ); ?>/web/js/claroline.ui.js"></script>
    <script type="text/javascript" src="js/linker.js"></script>
    <script type="text/javascript">
        linkerFrontend.base_url = "<?php echo rtrim( get_path( 'url' ), '/' ); ?>/claroline/backends/linker.php";
        linkerFrontend.deleteIconUrl = "<?php echo get_icon_url('delete'); ?>";
        linkerFrontend.invisibleIconUrl = "<?php echo get_icon_url('invisible'); ?>";     
    </script>
    <?php echo JavascriptLanguage::getInstance ()->buildJavascript(); ?>
	<?php echo link_to_css( get_conf('claro_stylesheet') . '/main.css', 'screen, projection, tv' );?>
    <link rel="stylesheet" type="text/css" href="<?php echo rtrim( get_path( 'rootWeb' ), '/' ) ?>/web/css/classic/main.css" media="screen, projection, tv" />
</head>
<body>

<form onsubmit="DocumentsDialog.insert();return false;" action="#">
	<div>
		<fieldset>
			<legend><?php echo get_lang('Resources'); ?></legend>
      <?php echo Documents_ResourceLinker::renderLinkerBlock( rtrim( get_path( 'rootWeb' ), '/' ) . '/claroline/backends/linker.php'); ?>
      </fieldset>
		<fieldset>
			<legend><?php echo get_lang('Target'); ?></legend>
			<select name="target" id="target">
				<option value="_self">Open in the same window</option>
				<option value="_blank">Open in a new window</option>
			</select>
		</fieldset>
	</div>
	<div class="mceActionPanel">		
		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="{#close}" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>

</body>
</html>

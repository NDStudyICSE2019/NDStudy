<?php

// load Claroline kernel
require_once dirname(__FILE__) . '/../../../../../inc/claro_init_global.inc.php';

$acceptedCmdList = array(   'rqTex'
			 );

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                            $cmd = null;

if( $cmd == 'rqTex' )
{
	if(isset($_REQUEST['formula']) && $_REQUEST['formula'])
	{
		echo renderTex('[tex]' . $_REQUEST['formula'] . '[/tex]');
	}
	exit();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo get_lang('LaTeX Equation Editor'); ?></title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<script type="text/javascript" src="<?php echo get_path( 'rootWeb' ); ?>web/js/jquery.js"></script>
	<script type="text/javascript">
		var texRendererURL = "<?php echo get_conf('claro_texRendererUrl'); ?>";
	</script>
</head>
<body>

<form onsubmit="TexFormulaDialog.insert();return false;" action="#">
	<div>
		<fieldset>
			<legend><?php echo get_lang('Equation'); ?></legend>
			<div>
				<textarea id="formula" name="formula" style="width: 100%; height: 50px;"></textarea>			
			</div>
			<div>
				<input type="button" id="generatePreview" value="<?php echo get_lang('Preview'); ?>" onclick="TexFormulaDialog.preview();" />
			</div>
		</field>
	</div>
	<div>
		<fieldset>
			<legend><?php echo get_lang('Preview'); ?></legend>
			<div id="preview" style="clear: both; padding-top: 5px;"></div>
		</fieldset>
	</div>
	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="insert" name="insert" value="{#insert}" onclick="TexFormulaDialog.insert();" />
		</div>
	
		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>

</body>
</html>

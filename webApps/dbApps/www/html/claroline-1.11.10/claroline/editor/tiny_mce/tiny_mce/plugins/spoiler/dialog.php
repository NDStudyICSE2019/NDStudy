<?php

// load Claroline kernel
require_once dirname(__FILE__) . '/../../../../../inc/claro_init_global.inc.php';

if( ! claro_is_allowed_to_edit() )
{
				claro_die( get_lang('Not allowed') );
}

$acceptedCmdList = array(   'rqSpoiler'
			 );

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                            $cmd = null;

if( $cmd == 'rqSpoiler' )
{
	if(isset($_REQUEST['title']) && $_REQUEST['title'] && isset($_REQUEST['content']) && $_REQUEST['content'])
	{
		echo make_spoiler('<p>[spoiler /' . $_REQUEST['title'] . '/]</p>' . $_REQUEST['content'] . '<p>[/spoiler]</p>');
	}
	exit();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo get_lang('Spoiler Editor'); ?></title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<script type="text/javascript" src="<?php echo get_path( 'rootWeb' ); ?>web/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo get_path( 'rootWeb' ); ?>web/js/claroline.js"></script>
	<script type="text/javascript" src="<?php echo get_path( 'rootWeb' ); ?>web/js/claroline.ui.js"></script>
	<?php echo link_to_css( get_conf('claro_stylesheet') . '/main.css', 'screen, projection, tv' );?>
	<style>
	fieldset dl dt {
		float: left;
		width: 20%;
		padding: 1px;
	}
	    
	fieldset dl dd {
		float: left;
		padding: 1px;
		width: 70%;
		margin: 0;
	}
	    
	fieldset dt label {
		margin: 0;
		padding: 0;
	}
	    
	input.radio,
	input.checkbox {
		cursor: pointer;
		height: 1em;
		margin: 0;
		vertical-align: middle;
		width: 1em;
	}

	</style>
</head>
<body>

<form onsubmit="SpoilerDialog.insert();return false;" action="#">
	<div>
		<fieldset>
			<legend><?php echo get_lang('Spoiler'); ?></legend>
			<dl>
				<dt><?php echo get_lang('Title'); ?>&nbsp;:</dt>
				<dd><input id="title" name="title" type="text" style="width: 100%;" /></dd>
				<dt><?php echo get_lang('Content'); ?>&nbsp;:</dt>
				<dd><textarea id="content" name="content" style="width: 100%; height: 100px;"></textarea></dd>
			</dl>
			<div>
				<input type="button" id="generatePreview" value="<?php echo get_lang('Preview'); ?>" onclick="SpoilerDialog.preview();" />
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
			<input type="button" id="insert" name="insert" value="{#insert}" onclick="SpoilerDialog.insert();" />
		</div>
	
		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>

</body>
</html>

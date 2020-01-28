<!-- $Id: translation_index.tpl.php 12676 2010-10-20 14:59:33Z abourguignon $ -->

<h4><?php echo get_lang('Extract language variables')?></h4>
<ul>
<li><a href="lang/extract_var_from_lang_file.php"><?php echo get_lang('From language files')?></a></li>
<li><a href="lang/extract_var_from_script_file.php"><?php echo get_lang('From script files')?></a></li>
</ul>
<?php
if( $this->table_exists == TRUE ) :
?>
<h4><?php echo get_lang('Build language files')?></h4>
<ul>
<li><a href="lang/build_devel_lang_file.php"><?php echo get_lang('Complete language files')?></a></li>
<!-- <li><a href="lang/build_prod_lang_file.php"><?php echo get_lang('Production language files')?></a></li> -->
<li><a href="lang/build_missing_lang_file.php"><?php echo get_lang('Missing language files')?></a></li>
<li><a href="lang/build_empty_lang_file.php"><?php echo get_lang('Empty language file')?></a></li>
</ul>

<h4><?php echo get_lang('Find doubled variables')?></h4>
<ul>
<li><a href="lang/display_content_diff.php"><?php echo get_lang('Variables with same content and different name')?></a></li>
</ul>

<h4><?php echo get_lang('Translation Progression')?></h4>
<ul>
<li><a href="lang/progression_translation.php"><?php echo get_lang('Translation Progression')?></a></li>
</ul>

<h4><?php echo get_lang('Conversion')?></h4>
<ul>
<!-- <li><a href="lang/convert_lang_17_to_18.php"><?php echo get_lang('Conversion 1.7 to 1.8')?></a></li> -->
<li><a href="lang/compare_lang_18_to_19.php"><?php echo get_lang('Compare 1.8 to 1.9')?></a></li>
<li><a href="lang/convert_lang_18_to_19.php"><?php echo get_lang('Conversion 1.8 to 1.9')?></a></li>
</ul>

<?php

endif;

?>
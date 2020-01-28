<!-- $Id: help.tpl.php 13552 2011-09-07 12:55:36Z zefredz $ -->

<table width="100%" border="0" cellpadding="1" cellspacing="1">
<tr>
  <td align="left" valign="top">
    <h4><?php echo get_lang('%module% help',array('%module%' => ucfirst($this->module))); ?></h4>
  </td>
</tr>
<tr>
  <td>
    <?php echo get_block($this->block); ?> 
  </td>
</tr>
</table>

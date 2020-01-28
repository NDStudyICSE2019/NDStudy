<!-- $Id: advanced_user_search.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form action="admin_users.php" method="get">
<table border="0">
  <tr>
    <td align="right">
      <label for="lastName"><?php echo get_lang('Last name')?></label>
      : <br />
    </td>
    <td>
      <input type="text" name="lastName" id="lastName" value="<?php echo claro_htmlspecialchars($this->lastName); ?>" />
    </td>
  </tr>
  <tr>
    <td align="right">
      <label for="firstName"><?php echo get_lang('First name')?></label>
      : <br />
    </td>
    <td>
      <input type="text" name="firstName" id="firstName" value="<?php echo claro_htmlspecialchars($this->firstName) ?>"/>
    </td>
  </tr>
  <tr>
    <td align="right">
      <label for="userName"><?php echo get_lang('Username') ?></label>
      :  <br />
    </td>
    <td>
      <input type="text" name="userName" id="userName" value="<?php echo claro_htmlspecialchars($this->userName); ?>"/>
    </td>
  </tr>
  <tr>
    <td align="right">
      <label for="officialCode"><?php echo get_lang('Official code') ?></label>
      :  <br />
    </td>
    <td>
      <input type="text" name="officialCode" id="officialCode" value="<?php echo claro_htmlspecialchars($this->officialCode); ?>"/>
    </td>
  </tr>
  <tr>
    <td align="right">
      <label for="mail"><?php echo get_lang('Email') ?></label>
      : <br />
    </td>
    <td>
      <input type="text" name="mail" id="mail" value="<?php echo claro_htmlspecialchars($this->mail); ?>"/>
    </td>
  </tr>
  <tr>
    <td align="right">
      <label for="action"><?php echo get_lang('Action') ?></label> : <br />
    </td>
    <td>
      <?php
      echo claro_html_form_select( 'action'
                            , $this->action_list
                            , $this->action
                            , array('id'=>'action'))
                                     ; ?>
    </td>
  </tr>
  <tr>
    <td>
    </td>
    <td>
      <input type="submit" class="claroButton" value="<?php echo get_lang('Search user')?>"  />
    </td>
  </tr>
</table>
</form>
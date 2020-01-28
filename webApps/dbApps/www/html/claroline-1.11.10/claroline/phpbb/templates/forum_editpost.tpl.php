<!-- // $Id: forum_editpost.tpl.php 14428 2013-04-23 09:55:21Z ldumorti $ -->

<form id="#formPost" action="<?php echo claro_htmlspecialchars( $_SERVER['PHP_SELF'] )?>" method="post">
    <input type="hidden" name="forum" value="<?php echo $this->forumId ?>" />
    <input type="hidden" name="topic" value="<?php echo $this->topicId ?>" />
    <input type="hidden" name="post" value="<?php echo $this->postId ?>" />
    <input type="hidden" name="cmd" value="<?php echo $this->nextCommand ?>" />
    <input type="hidden" name="mode" value="<?php echo $this->editMode ?>" />
    <?php echo claro_form_relay_context() ?>
    <table border="0" width="100%">
    <?php if( 'add' == $this->editMode || 'edit' == $this->editMode && '' != trim( $this->subject ) ) :?>
        <tbody>
            <tr valign="top">
                <td width="10%" align="right"><label for="subject"><?php echo get_lang( 'Subject' ) ?></label> : </td>
                <td><input type="text" name="subject" id="subject" size="50" maxlength="100" value="<?php echo claro_htmlspecialchars( $this->subject ) ?>" /></td>
            </tr>
    <?php endif; ?>
            <tr valign="top">
                <td align="right"><br /><?php echo get_lang( 'Message body' ) ?> : </td>
                <td><?php echo $this->editor ?></td>
            </tr>
    <?php if( 'forbidden' !== $this->anonymityStatus ) :
            $checked = ( $this->anonymityStatus == 'default' ) ? ' checked=checked' : '';?>
            <tr valign="top">
                <td align="right"><label for="anonymous_post"><?php echo get_lang( 'Anonymous post' )?></label> : </td>
                <td><input id="anonymous_cb"<?php if (get_conf( 'confirm_not_anonymous', 'TRUE' ) == 'TRUE') : ?> class="confirmAnonymous"<?php endif; ?> type="checkbox" name="anonymous_post" value="1"<?php echo $checked ?>/> </td>
            </tr>
    <?php endif;?>
            <tr valign="top"><td>&nbsp;</td>
                <td><input class="confirm" type="submit" name="submit" value="<?php echo get_lang( 'Ok' )?>" />&nbsp;
                   <?php  
                      if ($this->editMode != "add") 
                      {
                        echo claro_html_button( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?topic=' . $this->topicId ) ), get_lang( 'Cancel' ) ); 
                      }
                      else
                      {    
                        echo claro_html_button( claro_htmlspecialchars( Url::Contextualize( get_module_url('CLFRM') . '/viewforum.php?forum='. $this->forumId   ) ), get_lang('Cancel') );
                      }
                      ?>
                </td>
            </tr>
        </tbody>
    </table>
</form>

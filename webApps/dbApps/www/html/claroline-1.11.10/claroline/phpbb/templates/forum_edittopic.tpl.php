<!-- // $Id: forum_edittopic.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form action="<?php echo claro_htmlspecialchars( $_SERVER['PHP_SELF'] )?>" method="post">
    <input type="hidden" name="claroFormId" value="<?php echo uniqid( '' ) ?>" />
    <input type="hidden" name="cmd" value="<?php echo $this->nextCommand ?>" />
    <input type="hidden" name="topic" value="<?php echo $this->topicId ?>" />
    <input type="hidden" name="forum" value="<?php echo $this->forumId ?>" />
    <?php echo claro_form_relay_context(); ?>
    <label for="title"><strong><?php echo get_lang( 'New topic title' ) ?> : </strong></label><br />
    <input type="text" name="title" id="title" value="<?php echo $this->topicTitle ?>" /><br /><br />
    <input type="submit" value="<?php echo get_lang( 'Ok' ) ?>" />&nbsp;
    <?php echo claro_html_button( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forumId ) ), get_lang( 'Cancel' ) )?>
</form>

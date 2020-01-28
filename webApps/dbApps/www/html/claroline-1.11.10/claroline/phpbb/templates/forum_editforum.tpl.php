<!-- // $Id: forum_editforum.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<strong><?php echo $this->header ?></strong>
<form action="<?php echo claro_htmlspecialchars( $_SERVER['PHP_SELF'] )?>" method="post">
    <input type="hidden" name="claroFormId" value="<?php echo uniqid( '' ) ?>" />
    <input type="hidden" name="cmd" value="<?php echo $this->nextCommand ?>" />
    <input type="hidden" name="forumId" value="<?php echo $this->forumId ?>" />
    <?php echo claro_form_relay_context(); ?>
    <label for="forumName"><?php echo get_lang( 'Name' ) ?> : </label><br />
    <input type="text" name="forumName" id="forumName" value="<?php echo $this->forumName ?>" /><br /><br />
    <label for="forumDesc"><?php echo get_lang( 'Description' ) ?> : </label><br />
    <textarea name="forumDesc" id="forumDesc" cols="50" rows="3"><?php echo $this->forumDesc ?></textarea><br /><br />
    <label for="catId"><?php echo get_lang( 'Category' ) ?> : </label><br />
    <select name="catId">
    <?php foreach( $this->categoryList as $category ) :
        $selected = $this->catId == $category['cat_id'] ? ' selected="selected"' : '' ?>
        <option value="<?php echo $category['cat_id'] ?>"<?php echo $selected ?>><?php echo $category['cat_title']?></option>
    <?php endforeach;?>
    </select><br /><br />
    <?php if( $this->anonymity_enabled ) :?>
        <label><?php echo get_lang( 'Anonymity' ) ?> : </label><br />
        <input type="radio" id="anonymity_forbidden" name="anonymity" value="forbidden" <?php echo ( 'forbidden' == $this->anonymity ? ' checked="checked"' : '' ) ?>/>
        <label for="anonymity_forbidden"><?php echo get_lang( 'forbidden' ) ?></label><br />
        <input type="radio" id="anonymity_allowed" name="anonymity" value="allowed" <?php echo ( 'allowed' == $this->anonymity ? ' checked="checked"' : '' ) ?>/>
        <label for="anonymity_allowed"><?php echo get_lang( 'allowed' ) ?></label><br />
        <input type="radio" id="anonymity_default" name="anonymity" value="default" <?php echo ( 'default' == $this->anonymity ? ' checked="checked"' : '' ) ?>/>
        <label for="anonymity_default"><?php echo get_lang( 'default' ) ?></label><br /><br />
    <?php endif;?>
    <input type="checkbox" id="forumPostUnallowed" name="forumPostUnallowed" <?php echo $this->is_postAllowed ? '' : ' checked="checked"'?>/>
    <label for="forumPostUnallowed"><?php  echo get_lang( 'Locked' ) ?> <small>(<?php echo get_lang( 'No new post allowed' )?>)</small></label><br /><br />
    <input type="submit" value="<?php echo get_lang( 'Ok' ) ?>" />&nbsp;
    <?php echo claro_html_button( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] ) ), get_lang( 'Cancel' ) )?>
</form>

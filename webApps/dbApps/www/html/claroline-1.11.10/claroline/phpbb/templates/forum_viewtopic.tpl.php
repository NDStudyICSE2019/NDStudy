<!-- // $Id: forum_viewtopic.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<h4 class="header">
<?php
// Allow user to be have notification for this topic or disable it
if ( claro_is_user_authenticated() ) : //anonymous user do not have this function
?>
<span style="float: right;" class="claroCmd">
  <?php if ( is_topic_notification_requested($this->topic_id, claro_get_current_user_id()) ) :  // display link NOT to be notified ?>
  <img src="<?php echo get_icon_url('mail_close'); ?>" alt="" style="vertical-align: text-bottom" />
  <?php echo get_lang('Notify by email when replies are posted'); ?>
  [<a href="<?php echo claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forum_id . '&amp;topic=' . $this->topic_id . '&amp;cmd=exdoNotNotify' ) ); ?>"><?php echo get_lang('Disable'); ?></a>]
  <?php else : //display link to be notified for this topic ?>
  <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forum_id . '&amp;topic=' . $this->topic_id . '&amp;cmd=exNotify' ) ); ?>">
  <img src="<?php echo get_icon_url('mail_close'); ?>" alt="" /><?php echo get_lang('Notify by email when replies are posted'); ?></a>
  <?php endif; ?>
</span>
<?php
endif; //end not anonymous user
?>
<?php echo $this->topic_subject; ?>
</h4>
<div id="postList">
<?php foreach( $this->postList as $thisPost ) : ?>
<div id="post<?php echo $thisPost['post_id']; ?>" class="threadPost">
  <div class="threadPostInfo">
    <?php
    if( 'anonymous' !== $thisPost['lastname']
     && user_get_picture_path( user_get_properties( $thisPost['poster_id'] ) )
     && file_exists( user_get_picture_path( user_get_properties( $thisPost['poster_id'] ) ) )
     ) :
    ?>
    <div class="threadPosterPicture">
        <img src="<?php echo user_get_picture_url( user_get_properties( $thisPost['poster_id'] ) ); ?>" alt=" " />
    </div>
    <?php
    endif;
    ?>
    <?php
    if( 'anonymous' === $thisPost['lastname'] ) :
        ?><span style="font-weight: bold;"><?php
        $userData = user_get_properties( $thisPost['poster_id'] );
        echo get_lang( 'Anonymous contribution' );?>
        </span>
        <?php
        if( claro_is_platform_admin() ) :?>
        <span style="font-weight: bold;" id="<?php $thisPost['post_id']?>" class="switch">
            <a href="#" class="show">(<?php echo get_lang( 'show' );?>)</a>
            <a href="#" class="hide" style="display:none;">(<?php echo get_lang( 'hide' );?>)</a>
        </span>
        <span style="font-weight: bold;display:none;"><?php echo $userData[ 'firstname' ] . '&nbsp;' . $userData[ 'lastname' ];?></span><?php
        endif;
    else :
        ?><span style="font-weight: bold;"><?php
        echo $thisPost[ 'firstname' ] . '&nbsp;' . $thisPost[ 'lastname' ];
        ?></span><?php
    endif;
    ?>
    <br />
    <small><?php echo claro_html_localised_date( get_locale( 'dateTimeFormatLong' ), datetime_to_timestamp( $thisPost['post_time'] ) ); ?></small>
  </div>
  <div class="threadPostContent">
    <?php $itemClass = claro_is_user_authenticated()
                       && $this->claro_notifier->is_a_notified_ressource( claro_get_current_course_id(), $this->claro_notifier->get_notification_date( claro_get_current_user_id() ), claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $this->forum_id . "-" . $this->topic_id . "-" . $thisPost['post_id'] )
                       ? 'item hot' : 'item';?>
    <span class="threadPostIcon <?php echo $itemClass ?>">
        <img src="<?php echo get_icon_url( 'post' ); ?>" alt="" />
    </span><br />
    <?php echo claro_parse_user_text( $thisPost[ 'post_text' ] ); ?>
    <?php if( $this->is_post_allowed ) :?>
        <p>
        <a class="claroCmd" href="<?php  echo claro_htmlspecialchars( Url::Contextualize( get_module_url('CLFRM') . '/viewtopic.php?topic=' . $thisPost['topic_id'] . '&amp;post=' . $thisPost['post_id'] . '&amp;cmd=rqPost&amp;mode=quote' ) ); ?>">
            <img src="<?php echo get_icon_url('post'); ?>" alt="<?php echo get_lang('Quote'); ?>" />
            <?php echo get_lang( 'Quote' ) ?>
          </a>
        <?php if( $this->is_allowedToEdit ) : ?>
        <span>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
          <a class="claroCmd" href="<?php  echo claro_htmlspecialchars( Url::Contextualize( get_module_url('CLFRM') . '/viewtopic.php?post=' . $thisPost['post_id'] . '&amp;cmd=rqPost&amp;mode=edit' ) ); ?>">
            <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Edit'); ?>" />
          </a>
          <?php if( !is_first_post( $this->topic_id, $thisPost['post_id'] ) ) :?>
          <a class="claroCmd" href="<?php echo claro_htmlspecialchars( Url::Contextualize( get_module_url('CLFRM') . '/viewtopic.php?post=' . $thisPost['post_id'] . '&amp;cmd=exDelete&amp;submit=submit' ) ); ?>" onclick="return confirmationDel('<?php echo get_lang('this item'); ?>');" >
            <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
          </a>
          <?php endif; ?>
        <?php endif; ?>
        </p>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

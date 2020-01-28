<!-- $Id: forum_export.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<h4 class="header">
<?php
// Allow user to be have notification for this topic or disable it
if ( claro_is_user_authenticated() ) : //anonymous user do not have this function
?>
<span style="float: right;" class="claroCmd">
  <?php if ( is_topic_notification_requested($this->topic_id, claro_get_current_user_id()) ) :  // display link NOT to be notified ?>
  <img src="<?php echo get_icon_url('mail_close'); ?>" alt="" style="vertical-align: text-bottom" />
  <?php echo get_lang('Notify by email when replies are posted'); ?>
  [<a href="<?php echo claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forum_id . '&amp;topic=' . $this->topic_id . '&amp;cmd=exdoNotNotify' ) ); ?>"><?php echo get_lang('Disable'); ?></a>
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
<?php foreach( $this->postList as $thisPost ) : ?>
<div id="post<?php echo $thisPost['post_id']; ?>" class="threadPost">
  <?php
  if( user_get_picture_path( user_get_properties( $thisPost['poster_id'] ) )
     && file_exists( user_get_picture_path( user_get_properties( $thisPost['poster_id'] ) ) )
     ) :
  ?>
    <div class="threadPosterPicture"><img src="<?php echo user_get_picture_url( user_get_properties( $thisPost['poster_id'] ) ); ?>" alt=" " /></div>
  <?php
  endif;
  ?>
  <div class="threadPostInfo">
    <span style="font-weight: bold;"><?php echo $thisPost[ 'firstname' ]; ?> <?php echo $thisPost[ 'lastname' ]; ?></span>
    <br />
    <small><?php echo claro_html_localised_date(get_locale('dateTimeFormatLong'), datetime_to_timestamp( $thisPost['post_time']) ); ?></small>
  </div>
  <div class="threadPostContent">
    <span class="threadPostIcon item"><img src="<?php echo get_icon_url( 'post' ); ?>" alt="" /></span><br />
    <?php echo claro_parse_user_text( $thisPost[ 'post_text' ] ); ?>
    <?php if( $this->is_allowedToEdit ) : ?>
    <p>
      <a href="<?php  echo claro_htmlspecialchars(Url::Contextualize( get_module_url('CLFRM') . '/editpost.php?post_id=' . $thisPost['post_id'] )); ?>">
        <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Edit'); ?>" />
      </a>
      <a href="<?php echo claro_htmlspecialchars(Url::Contextualize( get_module_url('CLFRM') . '/editpost.php?post_id=' . $thisPost['post_id'] . '&amp;delete=delete&amp;submit=submit')); ?>" onclick="return confirm_delete();" >
        <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
      </a>
    </p>
    <?php endif; ?>
  </div>
  <div class="spacer"></div>
</div>
<?php endforeach; ?>

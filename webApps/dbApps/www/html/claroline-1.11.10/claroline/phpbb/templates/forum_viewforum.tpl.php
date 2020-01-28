<!-- // $Id: forum_viewforum.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<table class="claroTable emphaseLine" width="100%">
    <tbody>
        <tr class="superHeader">
            <th colspan="<?php echo ( $this->is_allowedToEdit ) ? 9 : 6 ?>">
            <?php
            // Allow course managers to receive notification for all new contributions in this forum or disable it
            if ( claro_is_user_authenticated() && claro_is_course_member() ) : //anonymous user do not have this function
            ?>
            <span style="float: right;" class="claroCmd">
            <?php if( is_forum_notification_requested( $this->forumId, claro_get_current_user_id() ) ) :  // display link NOT to be notified ?>
                <img src="<?php echo get_icon_url( 'mail_close' ); ?>" alt="" style="vertical-align: text-bottom" />
                <?php echo get_lang( 'Notify by email when topics are created' ); ?>
                [<a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forumId . '&amp;cmd=exdoNotNotify' ) ); ?>"><?php echo get_lang( 'Disable' ); ?>]</a>
                <?php else : //display link to be notified for this topic ?>
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?forum=' . $this->forumId . '&amp;cmd=exNotify' ) ); ?>">
                <img src="<?php echo get_icon_url('mail_close'); ?>" alt="" />
                <?php echo get_lang( 'Notify by email when topics are created' ); ?></a>
              <?php endif; ?>
            </span>
            <?php endif; //notification bloc
            echo $this->forumName;
            ?>
            </th>
        </tr>
        <tr class="headerX" align="left">
            <th>&nbsp;<?php echo get_lang( 'Topic' ) ?> </th>
            <th width="9%"  align="center"><?php echo get_lang( 'Posts' )?></th>
            <th width="20%" align="center">&nbsp;<?php echo get_lang( 'Author' )?></th>
            <th width="8%"  align="center"><?php echo get_lang( 'Seen' )?></th>
            <th width="15%" align="center"><?php echo get_lang( 'Last message' )?></th>
            <?php if( $this->is_allowedToEdit ) : ?>
                <th><?php echo get_lang( 'Edit' )?></th>
                <th><?php echo get_lang( '(Un)Lock' )?></th>
                <th><?php echo get_lang( 'Delete' )?></th>
            <?php endif; ?>
        </tr>
        <?php if( count( $this->topicList ) == 0 ) : ?>
        <tr>
            <td colspan="<?php echo ( $this->is_allowedToEdit ) ? 9 : 6 ?>" align="center">
            <?php echo ( $this->forumSettings['forum_access'] == 2 ) ? get_lang( 'There are no topics for this forum. You can post one' ) : get_lang( 'There are no topics for this forum.' ) ?>
            </td>
        </tr>
        <?php else :
            foreach( $this->topicList as $thisTopic ) : ?>
            <tr>
            <?php $itemClass = claro_is_user_authenticated()
                           && $this->claro_notifier->is_a_notified_ressource( claro_get_current_course_id(), $this->claro_notifier->get_notification_date( claro_get_current_user_id() ), claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $this->forumId . "-" . $thisTopic['topic_id'], FALSE )
                           ? 'item hot' : 'item';
                  $itemLink = claro_htmlspecialchars( Url::Contextualize( get_module_url( 'CLFRM' ) . '/viewtopic.php?topic=' . $thisTopic['topic_id']
                            .  ( is_null( $this->forumSettings['idGroup'] )
                                ? '' : '&amp;gidReq =' . $this->forumSettings['idGroup'] ) ) );
            ?>
                <td>
                    <span class="<?php echo $itemClass ?>">
                        <img src="<?php echo get_icon_url( 'topic' ) ?>" alt="" />&nbsp;
                        <a href="<?php echo $itemLink ?>"><?php echo $thisTopic['topic_title'] ?></a>
                    </span>&nbsp;
                    <?php if( $this->forumSettings['forum_access'] == 0 || $thisTopic['topic_status'] == 1 ) : ?>
                    <img src="<?php echo get_icon_url( 'locked' ) ?>" alt="<?php get_lang( 'Locked' ) ?>" title="<?php echo get_lang( 'Locked' ) ?>" /> <small>(<?php echo get_lang( 'No new post allowed' ) ?>)</small>
                    <?php endif; ?>
                    <?php echo disp_mini_pager( $itemLink, 'start', $thisTopic['topic_replies'], get_conf( 'posts_per_page' ) );?>
                </td>
                <td align="center"><small><?php echo $thisTopic['topic_replies']?></small></td>
                <td align="center">
                    <small>
                    <?php echo 'anonymous' == $thisTopic['nom'] ? get_lang( 'Anonymous' ) : $thisTopic['prenom'] . '&nbsp;' . $thisTopic['nom'];?>
                    </small>
                </td>
                <td align="center"><small><?php echo $thisTopic['topic_views'] ?></small></td>
                <td align="center"><small><?php echo claro_html_localised_date( get_locale( 'dateTimeFormatShort' ), datetime_to_timestamp( $thisTopic['post_time'] ) ); ?></small></td>
                <?php if( $this->is_allowedToEdit ) : ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=rqEditTopic&amp;forum=' . $this->forumId . '&amp;topic=' . $thisTopic['topic_id'] ) ) ?>">
                    <img src="<?php echo get_icon_url( 'edit' ) ?>" alt="<?php echo get_lang( 'Edit' )?>" />
                    </a>
                </td>
                <td align="center">
                    <?php if( $this->forumSettings['forum_access'] == 0 ) : ?>
                    <img src="<?php echo get_icon_url( 'locked_disabled' ) ?>" alt="<?php echo get_lang( 'Forum locked' ) ?>" />
                    <?php elseif( $thisTopic['topic_status'] == 1 ) :?>
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exUnlock&amp;topic=' . $thisTopic['topic_id'] . '&amp;forum=' . $this->forumId ) ) ?>">
                        <img src="<?php echo get_icon_url( 'locked' ) ?>" alt="<?php echo get_lang( 'Unlock' ) ?>" title="<?php echo get_lang( 'Unlock' ) ?>" />
                    </a>
                    <?php else : ?>
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exLock&amp;topic=' . $thisTopic['topic_id'] . '&amp;forum=' . $this->forumId ) ) ?>">
                        <img src="<?php echo get_icon_url( 'unlock' ) ?>" alt="<?php echo get_lang( 'Lock' ) ?>" title="<?php echo get_lang( 'Lock' ) ?>" />
                    </a>
                    <?php endif;?>
                </td>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exDelTopic&amp;forum=' . $this->forumId . '&amp;topic=' . $thisTopic['topic_id'] ) ) ?>" onclick="return confirmationDel('<?php echo clean_str_for_javascript($thisTopic['topic_title']); ?>');">
                    <img src="<?php echo get_icon_url( 'delete' ) ?>" alt="<?php echo get_lang( 'Delete' )?>" />
                    </a>
                </td>
                <?php  endif; ?>
            </tr>
            <?php endforeach;
        endif; ?>
    </tbody>
</table>

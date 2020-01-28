<!-- // $Id: forum_index.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<table width="100%" class="claroTable emphaseLine">
<?php
$categoryIterator = 0;
$categoryCount = count( $this->categoryList );
if( !is_tool_available_in_current_course_groups( 'CLFRM' ) ) $categoryCount--;
foreach( $this->categoryList as $thisCategory ) :
    if ( !is_tool_available_in_current_course_groups( 'CLFRM' )
        && $thisCategory['cat_id'] == GROUP_FORUMS_CATEGORY ) :
        continue;
    endif;
    if( $thisCategory['forum_count'] == 0 && !$this->is_allowedToEdit ) : continue;
    endif;
    $categoryIterator++;?>
    <tr class="superHeader" align="left" valign="top">
        <th colspan="<?php echo ( $this->is_allowedToEdit ) ? 9 : 4 ?>" class="<?php echo $thisCategory['forum_count'] > 0 ? '' : 'invisible' ?>">
        <?php if( $this->is_allowedToEdit ) : ?>
            <div style="float:right">
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=rqEdCat&amp;catId=' . $thisCategory['cat_id'] ) ) ?>">
                    <img src="<?php echo get_icon_url( 'edit' ) ?>" alt="<?php echo get_lang( 'Edit' ) ?>" />
                </a>&nbsp;
                <?php if( $thisCategory['cat_id'] != GROUP_FORUMS_CATEGORY ) : ?>
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exDelCat&amp;catId=' . $thisCategory['cat_id'] ) ) ?>" onclick="return confirm_delete();" >
                    <img src="<?php echo get_icon_url( 'delete' ) ?>" alt="<?php echo get_lang( 'Delete' ) ?>" />
                </a>&nbsp;
                <?php endif; ?>
                <?php if( $categoryIterator > 1 ) : ?>
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exMvUpCat&amp;catId=' . $thisCategory['cat_id'] ) ) ?>">
                    <img src="<?php echo get_icon_url( 'move_up' ) ?>" alt="<?php echo get_lang( 'Move up' ) ?>" />
                </a>&nbsp;
                <?php endif; ?>
                <?php if( $categoryIterator < $categoryCount ) : ?>
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exMvDownCat&amp;catId=' . $thisCategory['cat_id'] ) ) ?>">
                    <img src="<?php echo get_icon_url( 'move_down' ) ?>" alt="<?php echo get_lang( 'Move down' ) ?>" />
                </a>&nbsp;
                <?php endif;?>
                <?php if( $thisCategory['cat_id'] == GROUP_FORUMS_CATEGORY ) : ?>
                <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( get_module_url( 'CLGRP' ) . '/group.php' ) ) ?>">
                    <img src="<?php echo get_icon_url( 'group' ) ?>" alt="<?php echo get_lang( 'Groups' ) ?>" />
                </a>&nbsp;
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php echo claro_htmlspecialchars( $thisCategory['cat_title'] ); ?>
        </th>
    </tr>
    <?php if( $thisCategory['forum_count'] == 0 ) : ?>
    <tr>
        <td colspan="9" align="center"><?php echo get_lang( 'No forum' ) ?></td>
    </tr>
    <?php else : ?>
    <tr class="headerX" align="center">
        <th align="left"><?php echo get_lang( 'Forum' ) ?></th>
        <th><?php echo get_lang( 'Topics' ) ?></th>
        <th><?php echo get_lang( 'Posts' ) ?></th>
        <th><?php echo get_lang( 'Last message' ) ?></th>
        <?php if( $this->is_allowedToEdit ) : ?>
        <th><?php echo get_lang( 'Edit' ) ?></th>
        <th><?php echo get_lang( 'Empty it' ) ?></th>
        <th><?php echo get_lang( 'Delete' ) ?></th>
        <th colspan="2"><?php echo get_lang( 'Move' ) ?></th>
        <?php endif; ?>
    </tr>
    <?php endif; ?>
    <?php $forumIterator = 0;
          $lockedString = ' <img src="' . get_icon_url( 'locked' ) . '" alt="' . get_lang( 'Locked' ) . '" title="' . get_lang( 'Locked' ) . '" /> <small>(' . get_lang( 'No new post allowed' ) . ')</small>';
    foreach( $this->forumList as $thisForum ) :
        if( $thisForum['cat_id'] != $thisCategory['cat_id'] ) : continue;
        endif;
        $forumIterator++;
        $displayName = $thisForum['forum_name'];
        //temporary fix for 1.9 releases : avoids change in database definition (using unused 'forum_type' field)
        //TODO : use a specific enum field (field name: anonymity) in bb_forum table
        switch( $thisForum['forum_type'] )
        {
            case 0 : $anonymity = 'forbidden'; break;
            case 1 : $anonymity = 'allowed'; break;
            case 2 : $anonymity = 'default'; break;
            default : $anonymity = 'forbidden'; break;
        }
        if( get_conf( 'clfrm_anonymity_enabled', true ) ) :
            if( 'allowed' == $anonymity )  : $displayName .= ' (' . get_lang( 'anonymity allowed' ) . ')';
            elseif( 'default' == $anonymity ) : $displayName .= ' (' . get_lang( 'anonymous forum' ) . ')';
            endif;
        endif;
        $itemClass = claro_is_user_authenticated()
                     && $this->claro_notifier->is_a_notified_forum( claro_get_current_course_id(), $this->claro_notifier->get_notification_date( claro_get_current_user_id() ), claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $thisForum['forum_id'] )
                     ? 'item hot' : 'item';
        ?>
        <tr align="left" valign="top">
            <td>
                <span class="<?php echo $itemClass ?>">
                    <img src="<?php echo get_icon_url( 'forum', 'CLFRM' ) ?>" alt="" />&nbsp;
                    <?php if( !is_null( $thisForum['group_id'] ) ) : ?>
                        <?php $accessMode = get_access_mode_to_group_forum( $thisForum );
                            if( 'private' == $accessMode ) : echo $displayName;
                            else :?>
                            <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( get_module_url( 'CLFRM' ) . '/viewforum.php?gidReq=' . $thisForum['group_id'] . '&amp;forum=' . $thisForum['forum_id'] ) ) ?>">
                            <?php echo $displayName ?>
                            </a>&nbsp;
                            <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( get_module_url( 'CLGRP' ) . '/group_space.php?gidReq=' . $thisForum['group_id'] ) ) ?>">
                            <img src="<?php echo get_icon_url( 'group' )?>" alt="<?php echo get_lang( 'Group area' )?>" />
                            </a>
                            <?php if( 'tutor' == $accessMode ) : ?>
                            &nbsp;<small>(<?php echo get_lang( 'my supervision' ) ?>)</small>
                            <?php elseif( 'member' == $accessMode ) : ?>
                            &nbsp;<small>(<?php echo get_lang( 'my group' ) ?>)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( get_module_url( 'CLFRM' ) . '/viewforum.php?forum=' . $thisForum['forum_id'] ) ) ?>">
                        <?php echo $displayName ?>
                        </a>
                    <?php endif; ?>
                    <?php if( $thisForum['forum_access'] == 0 ) : echo $lockedString; endif; ?>
                </span><br />
                <span class="comment"><?php  echo $thisForum['forum_desc'];?></span>
            </td>
            <td align="center"><small><?php echo $thisForum['forum_topics'] ?></small></td>
            <td align="center"><small><?php echo $thisForum['forum_posts'] ?></small></td>
            <td align="center"><small><?php echo $thisForum['post_time'] > 0 ? claro_html_localised_date( get_locale( 'dateTimeFormatShort' ), datetime_to_timestamp( $thisForum['post_time'] ) ) : get_lang( 'No post' ) ?></small></td>
            <?php if( $this->is_allowedToEdit ) : ?>
            <td align="center">
                <a href="<?php echo get_lang( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=rqEdForum&amp;forumId=' . $thisForum['forum_id'] ) ) ) ?>">
                <img src="<?php echo get_icon_url( 'edit' ) ?>" alt="<?php echo get_lang( 'Edit' ) ?>" />
                </a>
            </td>
            <td align="center">
                <a href="<?php echo get_lang( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exEmptyForum&amp;forumId=' . $thisForum['forum_id'] ) ) ) ?>" onclick="return confirm_empty('<?php echo clean_str_for_javascript( $thisForum['forum_name'] ) ?>');">
                <img src="<?php echo get_icon_url( 'sweep' ) ?>" alt="<?php echo get_lang( 'Empty' ) ?>" />
                </a>
            </td>
            <td align="center">
                <?php if ( is_null( $thisForum['group_id'] ) ) : ?>
                <a href="<?php echo get_lang( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exDelForum&amp;forumId=' . $thisForum['forum_id'] ) ) ) ?>" onclick="return confirm_delete('<?php echo clean_str_for_javascript( $thisForum['forum_name'] ) ?>');">
                <img src="<?php echo get_icon_url( 'delete' ) ?>" alt="<?php echo get_lang( 'Delete' ) ?>" />
                </a>
                <?php endif; ?>
            </td>
            <td align="center">
                <?php if( $forumIterator > 1 ) : ?>
                <a href="<?php echo get_lang( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exMvUpForum&amp;forumId=' . $thisForum['forum_id'] ) ) ) ?>">
                <img src="<?php echo get_icon_url( 'move_up' ) ?>" alt="<?php echo get_lang( 'Move up' ) ?>" />
                </a>
                <?php else : ?>&nbsp;
                <?php endif; ?>
            </td>
            <td align="center">
                <?php if( $forumIterator < $thisCategory['forum_count'] ) : ?>
                <a href="<?php echo get_lang( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exMvDownForum&amp;forumId=' . $thisForum['forum_id'] ) ) ) ?>">
                <img src="<?php echo get_icon_url( 'move_down' ) ?>" alt="<?php echo get_lang( 'Move down' ) ?>" />
                </a>
                <?php else : ?>&nbsp;
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
<?php endforeach; ?>
</table>

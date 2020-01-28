<!-- // $Id: list.tpl.php 612 2012-02-21 13:45:43Z jmeuriss $ -->
<table class="claroTable emphaseLine" border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
    <thead>
        <tr class="headerX">
            <?php $colspan = 1; ?>
            <th><?php echo get_lang ( 'Exercise title' ); ?></th>
            <?php if( $this->is_allowedToEdit ) : ?>
                <th><?php echo get_lang ( 'Modify' ); $colspan++; ?></th>
                <th><?php echo get_lang ( 'Delete' ); $colspan++; ?></th>
                <th><?php echo get_lang ( 'Visibility' ); $colspan++; ?></th>
                <th><?php echo get_lang ( 'Export' ); $colspan++; ?></th>
                <?php if( $this->is_allowedToTrack ) : ?>
                    <th><?php echo get_lang ( 'Statistics' ); $colspan++; ?></th>
                <?php endif; ?>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ( count( $this->exerciseList ) > 0 ) : ?>    
        <?php foreach ( $this->exerciseList as $thisExercise ) : ?>
        <?php
            $invisibleClass = ( $this->is_allowedToEdit && 'INVISIBLE' == $thisExercise['visibility'] ) ? ' class="invisible"' : '';
            $appendToStyle = ( claro_is_user_authenticated () && $this->notifier->is_a_notified_ressource ( claro_get_current_course_id (), $this->notifier->get_notification_date( claro_get_current_course_id() ), claro_get_current_user_id (), claro_get_current_group_id (), claro_get_current_tool_id (), $thisExercise[ 'id' ] ) )
                             ? ' hot'
                             : '';
        ?>
        <tr <?php echo $invisibleClass; ?>>
            <td>
                <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'exercise_submit.php?exId=' . $thisExercise[ 'id' ] ) ); ?>" class="item<?php echo $appendToStyle; ?>">
                    <img src="<?php echo get_icon_url( 'quiz' ) ; ?>" alt="" />
                    <?php echo $thisExercise['title']; ?>
                </a>
            </td>
            <?php if ( $this->is_allowedToEdit ) : ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'admin/edit_exercise.php?exId=' . $thisExercise[ 'id' ] ) ); ?>">
                        <img src="<?php echo get_icon_url( 'edit' ) ; ?>" alt="<?php echo get_lang( 'Modify' ); ?>" />
                    </a>
                </td>
                <?php $confirmString = ! is_null ( $thisExercise['module_id'] ) ? get_block ( 'blockUsedInSeveralPath' ) : get_lang ( 'Are you sure you want to delete this exercise ?' ); ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?exId=' . $thisExercise[ 'id' ] . '&amp;cmd=exDel' ) ) . '" onclick="javascript:if(!confirm(\'' . clean_str_for_javascript ( $confirmString ) . '\')) return false;'; ?>">
                        <img src="<?php echo get_icon_url( 'delete' ) ; ?>" alt="<?php echo get_lang( 'Delete' ); ?>" />
                    </a>
                </td>
                <td align="center">
                    <?php if ( 'VISIBLE' == $thisExercise['visibility'] ) : ?>
                        <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?exId=' . $thisExercise[ 'id' ] . '&amp;cmd=exMkInvis' ) ); ?>">
                            <img src="<?php echo get_icon_url( 'visible' ) ; ?>" alt="<?php echo get_lang( 'Make invisible' ); ?>" />
                        </a>
                    <?php else : ?>
                        <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?exId=' . $thisExercise[ 'id' ] . '&amp;cmd=exMkVis' ) ); ?>">
                            <img src="<?php echo get_icon_url( 'invisible' ) ; ?>" alt="<?php echo get_lang( 'Make visible' ); ?>" />
                        </a>
                    <?php endif; ?>
                </td>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?exId=' . $thisExercise[ 'id' ] . '&amp;cmd=rqExport' ) ); ?>">
                        <img src="<?php echo get_icon_url( 'export' ) ; ?>" alt="<?php echo get_lang( 'Export' ); ?>" />
                    </a>
                </td>
                <?php if( $this->is_allowedToTrack ) : ?>
                    <td align="center">
                        <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 'track_exercises.php?exId=' . $thisExercise[ 'id' ] . '&amp;src=ex' ) ); ?>">
                            <img src="<?php echo get_icon_url( 'statistics' ) ; ?>" alt="<?php echo get_lang( 'Statistics' ); ?>" />
                        </a>
                    </td>
                <?php endif; ?>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="<?php echo $colspan; ?>"><?php echo get_lang ( 'Empty' ); ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
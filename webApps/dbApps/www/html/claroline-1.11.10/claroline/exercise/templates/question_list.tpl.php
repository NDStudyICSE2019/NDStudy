<table class="claroTable emphaseLine" border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
    <thead>
        <tr><?php $colspan = 4; ?>
            <th><?php echo get_lang( 'Id' ); ?></th>
            <th><?php echo get_lang( 'Question' ); ?></th>
            <th><?php echo get_lang( 'Category' ); ?></th>
            <th><?php echo get_lang( 'Answer type' ); ?></th>
            <?php if( 'reuse' == $this->context ) : ?>
                <th><?php echo get_lang( 'Reuse' ); $colspan++; ?></th>
            <?php else : ?>
                <th><?php echo get_lang( 'Modify' ); $colspan++; ?></th>
                <th><?php echo get_lang( 'Delete' ); $colspan++; ?></th>
            <?php endif; ?>
            <?php if( 'pool' == $this->context ) : ?>
                <th><?php if( get_conf( 'enableExerciseExportQTI', false ) ) : echo get_lang( 'Export' ); $colspan++; endif; ?>
            <?php elseif( 'exercise' == $this->context ) : ?>
                <th colspan="2"><?php echo get_lang( 'Order' ); $colspan++; ?></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $this->questionList ) ) : ?>
        <tr>
            <td colspan="<?php echo $colspan; ?>"><?php echo get_lang( 'Empty' ); ?></td>
        </tr>
    <?php endif; ?>
    <?php $questionIterator = 0;
          foreach( $this->questionList as $question ) : $questionIterator++; ?>
        <tr>
            <td align="center"><?php echo $question['id']; ?></td>
            <td><?php echo $question['title']; ?></td>
            <td><?php echo getCategoryTitle( $question['id_category'] ); ?></td>
            <td><small><?php echo $this->localizedQuestionType[$question['type']]; ?></small></td>
            <?php if( 'reuse' == $this->context ) : ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( 'question_pool.php?exId=' . $this->exId . '&amp;cmd=rqUse&amp;quId=' . $question['id'] ) ); ?>">
                        <img src="<?php echo get_icon_url( 'select' ); ?>" alt="<?php echo get_lang( 'Reuse' ); ?>" />
                    </a>
                    &nbsp; <?php if ($this->exId && ( 'reuse' == $this->context )) { ?><input type="checkbox" name="<?php echo $question['id']; ?>" value="true" /> <?php } ?>
                </td>
            <?php else : ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( 'edit_question.php?exId=' . $this->exId . '&amp;quId=' . $question['id'] ) ); ?>">
                        <img src="<?php echo get_icon_url( 'edit' ); ?>" alt="<?php echo get_lang( 'Modify' ); ?>" />
                    </a>
                </td>
                <?php if( 'pool' == $this->context ) :
                          $confirmString = get_lang('Are you sure you want to completely delete this question ?');
                          $url = 'question_pool.php?exId=' . $this->exId . '&amp;cmd=delQu&amp;quId=' . $question['id']. '&amp;offset='. $this->offset;
                      else :
                          $confirmString = get_lang ( 'Are you sure you want to remove the question from the exercise ?' );
                          $url = 'edit_exercise.php?exId=' . $this->exId . '&amp;cmd=rmQu&amp;quId=' . $question['id'];
                      endif;
                ?>
                <td align="center">
                    <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize( $url ) ) . '" onclick="javascript:if(!confirm(\'' . clean_str_for_javascript( $confirmString ) . '\')) return false;'; ?>">
                        <img src="<?php echo get_icon_url( 'delete' ); ?>" alt="<?php echo get_lang( 'Delete' ); ?>" />
                    </a>
                </td>
                <?php if( 'pool' == $this->context ) : ?>
                    <td align="center">
                        <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( 'question_pool.php?exId=' . $this->exId . '&amp;cmd=exExport&amp;quId=' . $question['id'] ) ); ?>">
                            <img src="<?php echo get_icon_url( 'export' ); ?>" alt="<?php echo get_lang( 'Export' ); ?>" />
                        </a>
                    </td>
                <?php elseif( 'exercise' == $this->context ) : ?>
                    <td align="center">
                    <?php if( $questionIterator > 1 ) : ?>
                        <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( 'edit_exercise.php?exId=' . $this->exId . '&amp;cmd=mvUp&amp;quId=' . $question['id'] ) ); ?>">
                            <img src="<?php echo get_icon_url( 'move_up' ); ?>" alt="<?php echo get_lang( 'Move up' ); ?>" />
                        </a>
                    <?php else : ?>
                        &nbsp;
                    <?php endif; ?>
                    </td>
                    <td align="center">
                    <?php if( $questionIterator < count( $this->questionList ) ) : ?>
                        <a href="<?php echo claro_htmlspecialchars( Url::Contextualize( 'edit_exercise.php?exId=' . $this->exId . '&amp;cmd=mvDown&amp;quId=' . $question['id'] ) ); ?>">
                            <img src="<?php echo get_icon_url( 'move_down' ); ?>" alt="<?php echo get_lang( 'Move down' ); ?>" />
                        </a>
                    <?php else : ?>
                        &nbsp;
                    <?php endif; ?>
                    </td>
                <?php endif; ?>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
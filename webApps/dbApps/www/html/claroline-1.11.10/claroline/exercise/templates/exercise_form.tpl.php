<!-- // $Id: exercise_form.tpl.php 612 2012-02-21 13:45:43Z jmeuriss $ -->
<form method="post" action="./edit_exercise.php?exId=<?php echo $this->exId ? $this->exId : ''; ?>">
<?php echo $this->relayContext; ?>
    <input type="hidden" name="cmd" value="exEdit" />
    <input type="hidden" name="claroFormId" value="<?php echo uniqid(''); ?>" />
    <!-- general information -->
    <fieldset>
        <legend><?php echo get_lang( 'Basic information' ); ?></legend>
        <dl>
            <dt><label for="title"><?php echo get_lang( 'Title' ); ?>&nbsp;<span class="required">*</span></label></dt>
            <dd><input type="text" name="title" id="title" size="60" maxlength="200" value="<?php echo claro_htmlspecialchars( $this->data['title'] ); ?>" /></dd>
            <dt><label for="description"><?php echo get_lang( 'Description' ); ?>&nbsp;<span class="required">*</span></label></dt>
            <dd><div style="width:700px;"><?php echo claro_html_textarea_editor( 'description', $this->data['description'] ); ?></div></dd>
            <dt><?php echo get_lang( 'Exercise type' ); ?>&nbsp;<span class="required">*</span></dt>
            <dd>
                <input type="radio" name="displayType" id="displayTypeOne" value="ONEPAGE" class="radio" <?php echo ( $this->data['displayType'] == 'ONEPAGE' ) ? ' checked="checked"' : ''; ?> />
                <label for="displayTypeOne"><?php echo get_lang( 'On an unique page' ); ?></label>
                <input type="radio" name="displayType" id="displayTypeSeq" value="SEQUENTIAL" class="radio" <?php echo ( $this->data['displayType'] == 'SEQUENTIAL' ) ? ' checked="checked"' : ''; ?> />
                <label for="displayTypeSeq"><?php echo get_lang( 'One question per page (sequential)' ); ?></label>
            </dd>
            <?php if( $this->exId && $this->questionCount > 0 ) : 
                $questionDrawnOptions = array();
                for ( $i = 1; $i <= $this->questionCount; $i++ ) :
                    $questionDrawnOptions[$i] = $i;
                endfor;
            ?>
                <dt><label for="randomize"><?php echo get_lang('Random questions'); ?></label></dt>
                <dd>
                    <div>
                        <input type="checkbox" name="randomize" id="randomize" class="checkbox" 
                        <?php 
                            echo ( $this->data['randomize'] ? ' checked="checked"' : ' ' )
                            . '/>&nbsp;'
                            . get_lang('<label1>Yes</label1>, <label2>take</label2> %nb questions among %total',
                                array ( '<label1>' => '<label for="randomize">',
                                '</label1>' => '</label>',
                                '<label2>' => '<label for="questionDrawn">',
                                '</label2>' => '</label>',
                                '%nb' => claro_html_form_select('questionDrawn',
                                                                $questionDrawnOptions,
                                                                $this->data['questionDrawn'],
                                                                array('id' => 'questionDrawn') ) ,
                                '%total' =>  $this->questionCount ) );
                        ?>
                    </div>
                    <div>
                        <input type="checkbox" name="useSameShuffle" value="1" class="checkbox" <?php echo ( $this->data['useSameShuffle'] ? ' checked="checked"' : ' ' ); ?>/>&nbsp;<?php echo get_lang( 'Reuse the same shuffle' ); ?>
                    </div>
                </dd>          
            <?php endif; ?>
        </dl>
    </fieldset>
    <!-- advanced information -->
    <fieldset id="advancedInformation" class="collapsible collapsed">
        <legend><a href="#" class="doCollapse"><?php echo get_lang( 'Advanced' ) . ' (' . get_lang( 'Optional' ) . ')'; ?> </a></legend>
        <div class="collapsible-wrapper">
            <dl>
                <dt><?php echo get_lang( 'Start date' ); ?></dt>
                <dd><?php echo claro_html_date_form( 'startDay', 'startMonth', 'startYear', $this->data['startDate'], 'long' ) . ' - ' . claro_html_time_form( 'startHour', 'startMinute', $this->data['startDate'] ); ?>
                    <small><?php echo get_lang( '(d/m/y hh:mm)' ); ?></small>
                </dd>
                <dt><label for="useEndDate"><?php echo get_lang( 'End date' ); ?></label></dt>
                <dd>
                    <input type="checkbox" name="useEndDate" id="useEndDate" <?php echo ( $this->data['useEndDate'] ? ' checked="checked"' : '' ); ?>/>
                    <label for="useEndDate"><?php echo get_lang( 'Yes' ); ?></label>
                    <?php echo claro_html_date_form( 'endDay', 'endMonth', 'endYear', $this->data['endDate'], 'long' ) . ' - ' . claro_html_time_form( 'endHour', 'endMinute', $this->data['endDate'] ); ?>
                    <small><?php echo get_lang( '(d/m/y hh:mm)' ); ?></small>
                </dd>
                <dt><label for="useTimeLimit"><?php echo get_lang( 'Time limit' ); ?></label></dt>
                <dd>
                    <input type="checkbox" name="useTimeLimit" id="useTimeLimit" <?php echo ( $this->data['useTimeLimit'] ? ' checked="checked"' : '' ); ?>/>
                    <label for="useEndDate"><?php echo get_lang( 'Yes' ); ?></label>
                    <input type="text" name="timeLimitMin" id="timeLimitMin" size="3" maxlength="3" value="<?php echo $this->data['timeLimitMin']; ?>" /><?php echo get_lang( 'min.' );?>
                    <input type="text" name="timeLimitSec" id="timeLimitSec" size="2" maxlength="2" value="<?php echo $this->data['timeLimitSec']; ?>" /><?php echo get_lang( 'sec.' );?>
                </dd>
                <dt><label for="attempts"><?php echo get_lang( 'Attempts allowed' ); ?></label></dt>
                <dd>
                    <select name="attempts" id="attempts">
                        <option value="0" <?php echo ( $this->data['attempts'] < 1 ? ' selected="selected"' : '' ) . '>' . get_lang( 'unlimited'); ?></option>
                        <?php for( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo $i; ?>" <?php echo ( $this->data['attempts'] == $i ? ' selected="selected"' : '' ) . '>' . $i; ?></option>
                        <?php  endfor; ?>
                    </select>
                </dd>
                <dt><?php echo get_lang( 'Anonymous attempts' ); ?></dt>
                <dd>
                    <input type="radio" name="anonymousAttempts" id="anonymousAttemptsAllowed" value="ALLOWED"
                    <?php echo ( $this->data['anonymousAttempts'] == 'ALLOWED' ) ? ' checked="checked"' : ' '; ?> />
                    <label for="anonymousAttemptsAllowed"><?php echo get_lang( 'Allowed : do not record usernames in tracking, anonymous users can do the exercise.' ); ?></label>
                    <br />
                    <input type="radio" name="anonymousAttempts" id="anonymousAttemptsNotAllowed" value="NOTALLOWED" 
                    <?php echo ( $this->data['anonymousAttempts'] == 'NOTALLOWED' ? ' checked="checked"' : ' ' ); ?> />
                    <label for="anonymousAttemptsNotAllowed"><?php echo get_lang( 'Not allowed : record usernames in tracking, anonymous users cannot do the exercise.' ); ?></label>
                </dd>
                <dt><?php echo get_lang( 'Show answers' ); ?></dt>
                <dd>
                    <input type="radio" name="showAnswers" id="showAnswerAlways" value="ALWAYS" 
                    <?php echo ( $this->data['showAnswers'] == 'ALWAYS' ? ' checked="checked"' : ' '); ?> />
                    <label for="showAnswerAlways"><?php echo get_lang( 'Yes' ); ?></label>
                    <br />
                    <input type="radio" name="showAnswers" id="showAnswerLastTry" value="LASTTRY" 
                    <?php echo ( $this->data['showAnswers'] == 'LASTTRY' ? ' checked="checked"' : ' '); ?> />
                    <label for="showAnswerLastTry"><?php echo get_lang( 'After last allowed attempt' ); ?></label>
                    <br />
                    <input type="radio" name="showAnswers" id="showAnswerNever" value="NEVER" 
                    <?php echo ( $this->data['showAnswers'] == 'NEVER' ? ' checked="checked"' : ' '); ?> />
                    <label for="showAnswerNever"><?php echo get_lang( 'No' ); ?></label>
                </dd>
                <dt><?php echo get_lang( 'Quiz end message' ); ?></dt>
                <dd>
                    <div style="width:700px;"><?php echo claro_html_textarea_editor( 'quizEndMessage', $this->data['quizEndMessage'] ); ?></div>
                </dd>
            </dl>
        </div>
    </fieldset>
    <div style="padding-top: 5px;">
        <small><?php echo get_lang( '<span class="required">*</span> denotes required field' ); ?></small>
    </div>
    <div style="text-align: center;">
        <input type="submit" name="" id="" value="<?php echo get_lang( 'Ok' ); ?>" />&nbsp;&nbsp;
        <?php echo claro_html_button( Url::Contextualize( '../exercise.php' ), get_lang( 'Cancel' ) ); ?>
    </div>
</form>
<form method="post" action="./edit_question.php?quId=<?php echo ( $this->question->getId() != -1 ? $this->question->getId() : '' ) . '&amp;exId=' . $this->exId; ?>" enctype="multipart/form-data">
    <?php echo $this->relayContext; ?>
    <input type="hidden" name="cmd" value="exEdit" />
    <input type="hidden" name="claroFormId" value="<?php echo uniqid(''); ?>" />
    <fieldset>
        <legend><?php echo get_lang( 'Question details' ); ?></legend>
    <dl>
        <?php if( $this->askDuplicate ) : ?>
        <dt>&nbsp;</dt>
        <dd><?php echo html_ask_duplicate(); ?></dd>
        <?php endif; ?>
        <dt><label for="title"><?php echo get_lang( 'Title' ); ?>&nbsp;<span class="required">*</span></label></dt>
        <dd><input type="text" name="title" id="title" size="60" maxlength="200" value="<?php echo claro_htmlspecialchars( $this->data['title'] ); ?>" /></dd>
        <dt><label for="description"><?php echo get_lang( 'Description' ); ?>&nbsp;<span class="required">*</span></label></dt>
        <dd><div style="width:500px;"><?php echo claro_html_textarea_editor( 'description', $this->data['description'] ); ?></div></dd>
        <dt><label for="category"><?php echo get_lang( 'Category' ); ?></label></dt>
        <dd>
            <?php if( !empty( $this->categoryList ) ) : ?>
            <select name="categoryId">
                <option value="0">&nbsp;</option>
                <?php foreach( $this->categoryList as $category ) : ?>
                <option value="<?php echo $category['id']; ?>" <?php echo ( $category['id'] == $this->data['categoryId'] ? ' selected="selected"' : '' ); ?>>
                    <?php echo $category['title']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php else : 
            echo get_lang( 'You can sort your question by categories. To create categories, follow this <a href="%url">link</a>.',
                           array( '%url' => claro_htmlspecialchars( Url::Contextualize( './question_category.php' ) ) ) );
            endif; ?>
        </dd>
        <?php if( !empty( $this->data['attachment' ] ) ) : ?>
        <dt><?php echo get_lang( 'Current file' ); ?></dt>    
        <dd>
            <a href="<?php echo $this->question->getQuestionDirWeb() . $this->data['attachment']; ?>" target="_blank"><?php echo $this->data['attachment'] ; ?></a><br />
            <input type="checkbox" name="delAttachment" id="delAttachment" />
            <label for="delAttachment"><?php echo get_lang( 'Delete attached file' ); ?></label>
        </dd>
        <?php else : ?>
        <dt><label for="attachment"><?php echo get_lang( 'Attached file' ); ?></label></dt>
        <dd><input type="file" name="attachment" id="attachment" size="30" /></dd>
        <?php endif; ?>
        <dt><?php echo get_lang( 'Answer type' ); ?></dt>
        <?php if( -1 == $this->question->getId() ) : ?>
        <dd>
            <input type="radio" name="type" id="MCUA" value="MCUA"<?php echo ( $this->data['type'] == 'MCUA' ? ' checked="checked"' : '' ); ?>/>
            <label for="MCUA"><?php echo get_lang( 'Multiple choice (Unique answer)' ); ?></label><br/>
            <input type="radio" name="type" id="MCMA" value="MCMA"<?php echo ( $this->data['type'] == 'MCMA' ? ' checked="checked"' : '' ); ?>/>
            <label for="MCMA"><?php echo get_lang( 'Multiple choice (Multiple answers)' ); ?></label><br/>
            <input type="radio" name="type" id="TF" value="TF"<?php echo ( $this->data['type'] == 'TF' ? ' checked="checked"' : '' ); ?>/>
            <label for="TF"><?php echo get_lang( 'True/False' ); ?></label><br/>
            <input type="radio" name="type" id="FIB" value="FIB"<?php echo ( $this->data['type'] == 'FIB' ? ' checked="checked"' : '' ); ?>/>
            <label for="FIB"><?php echo get_lang( 'Fill in blanks' ); ?></label><br/>
            <input type="radio" name="type" id="MATCHING" value="MATCHING"<?php echo ( $this->data['type'] == 'MATCHING' ? ' checked="checked"' : '' ); ?>/>
            <label for="MATCHING"><?php echo get_lang( 'Matching' ); ?></label>
        </dd>
        <?php else : ?>
        <dd><?php if( isset( $this->questionType[$this->data['type']] ) ) : echo $this->questionType[$this->data['type']]; endif; ?></dd>
        <?php endif; ?>        
    </dl>
    </fieldset>
    <div style="padding-top: 5px;">
        <small><?php echo get_lang( '<span class="required">*</span> denotes required field' ); ?></small>
    </div>
    <div style="text-align: center;">
        <input type="submit" name="" id="" value="<?php echo get_lang( 'Ok' ); ?>" />&nbsp;&nbsp;
        <?php if( !is_null( $this->exId ) ) : 
                  echo claro_html_button( Url::Contextualize( './edit_exercise.php?exId=' . $this->exId ), get_lang( 'Cancel' ) ); 
              else :
                  echo claro_html_button( Url::Contextualize( './question_pool.php' ), get_lang( 'Cancel' ) );     
              endif;
        ?>
    </div>
</form>
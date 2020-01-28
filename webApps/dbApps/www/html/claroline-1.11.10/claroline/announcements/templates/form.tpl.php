<!-- $Id: form.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form method="post" action="<?php echo claro_htmlspecialchars($this->formAction); ?>">
    <fieldset>
        <legend><?php echo get_lang('Basic settings'); ?></legend>
        
        <?php echo $this->relayContext ?>
        <input type="hidden" name="cmd" value="<?php echo $this->cmd; ?>" />
        <input type="hidden" name="claroFormId" value="<?php echo uniqid(''); ?>" />
        
        <?php if (!empty($this->announcement['id'])) : ?>
        <input type="hidden" name="id" value="<?php echo $this->announcement['id']; ?>" />
        <?php endif; ?>
        
        <dl>
            <dt><label for="title"><?php echo get_lang('Title'); ?></label></dt>
            <dd>
                <input type="text" id="title" name="title"
                value = "<?php if (isset($this->announcement['title'])) : ?><?php echo claro_htmlspecialchars($this->announcement['title']); ?><?php endif; ?>"
                size="80" />
            </dd>
            
            <dt><label for="newContent"><?php echo get_lang('Content'); ?></label></dt>
            <dd>
                <?php echo claro_html_textarea_editor('newContent', (!empty($this->announcement) ? $this->announcement['content'] : ''), 12, 67); ?>
            </dd>
            
            <dt></dt>
            <dd>
                <input type="checkbox" value="1" name="emailOption" id="emailOption" />
                <label for="emailOption"><?php echo get_lang('Send this announcement to registered students'); ?></label>
            </dd>
        </dl>
    </fieldset>
    
    <fieldset id="advancedInformation" class="collapsible collapsed">
        <legend><a href="#" class="doCollapse"><?php echo get_lang('Visibility options'); ?></a></legend>
        
        <div class="collapsible-wrapper">
            <dl>
                <dt>
                    <input name="visibility" id="visible" value="1" type="radio"'<?php if (!isset($this->announcement['visibility']) || $this->announcement['visibility'] == 'SHOW') : ?> checked="checked"<?php endif; ?> />
                    <label for="visible">
                        <img src="<?php echo get_icon_url('visible'); ?>" alt="<?php echo get_lang('Visible'); ?>" />
                        <?php echo get_lang('Visible'); ?>
                    </label>
                </dt>
                <dt>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input name="enable_visible_from" id="enable_visible_from" type="checkbox"<?php if (isset($this->announcement['visibleFrom'])) : ?> checked="checked"<?php endif; ?> />
                    <label for="enable_visible_from">
                        <?php echo get_lang('Visible from'); ?> (<?php echo get_lang('included'); ?>)
                    </label>
                </dt>
                <dd>
                    <?php echo claro_html_date_form('visible_from_day', 'visible_from_month', 'visible_from_year',
                                ((isset($this->announcement['visibleFrom']) ? strtotime($this->announcement['visibleFrom']) : (strtotime('Now')))), 'long' ); ?>
                </dd>
                <dt>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input name="enable_visible_until" id="enable_visible_until" type="checkbox"<?php if (isset($this->announcement['visibleUntil'])) : ?> checked="checked"<?php endif; ?> />
                    <label for="enable_visible_until">
                        <?php echo get_lang('Visible until'); ?> (<?php echo get_lang('included'); ?>)
                    </label>
                </dt>
                <dd>
                    <?php echo claro_html_date_form('visible_until_day', 'visible_until_month', 'visible_until_year',
                                ((isset($this->announcement['visibleUntil']) ? strtotime($this->announcement['visibleUntil']) : (strtotime('Now +1 day')))), 'long' ); ?>
                </dd>
                <dt>
                    <input name="visibility" id="invisible" value="0" type="radio"<?php if (isset($this->announcement['visibility']) && $this->announcement['visibility'] == 'HIDE') : ?> checked="checked"<?php endif; ?> />
                    <label for="invisible">
                        <img src="<?php echo get_icon_url('invisible'); ?>" alt="<?php echo get_lang('Invisible'); ?>" />
                        <?php echo get_lang('Invisible'); ?>
                    </label>
                </dt>
            </dl>
        </div>
    </fieldset>
    
    <fieldset>
        <legend><?php echo get_lang('Attached resources'); ?></legend>
        
        <?php echo ResourceLinker::renderLinkerBlock(); ?>
    </fieldset>
    
    <input type="submit" class="claroButton" name="submitEvent" value="<?php echo get_lang('Ok'); ?>" />
    <?php echo claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'])), get_lang('Cancel')); ?>
</form>

<hr />
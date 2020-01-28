<!-- $Id: form.tpl.php 14476 2013-06-18 14:14:54Z zefredz $ -->

<form method="post" action="<?php echo claro_htmlspecialchars($this->formAction); ?>">
    <fieldset>
        <legend><?php echo get_lang('Basic settings'); ?></legend>
        
        <?php echo $this->relayContext ?>
        <input type="hidden" name="cmd" value="<?php echo $this->cmd; ?>" />
        <input type="hidden" name="claroFormId" value="<?php echo uniqid(''); ?>" />
        
        <?php if ($this->intro->getId()) : ?>
        <input type="hidden" name="id" value="<?php echo $this->intro->getId(); ?>" />
        <input type="hidden" name="rank" value="<?php echo $this->intro->getRank(); ?>" />
        <input type="hidden" name="visibility" value="<?php echo $this->intro->getVisibility(); ?>" />
        <?php endif; ?>
        
        <dl>
            <dt><label for="content"><?php echo get_lang('Content'); ?></label></dt>
            <dd>
                <?php echo claro_html_textarea_editor('content', ($this->intro->getContent() ? $this->intro->getContent() : ''), 12, 67); ?>
            </dd>
        </dl>
    </fieldset>
    
    <fieldset>
        <legend><?php echo get_lang('Attached resources'); ?></legend>
        
        <?php echo ResourceLinker::renderLinkerBlock(); ?>
    </fieldset>
    
    <input type="submit" class="claroButton" name="submitEvent" value="<?php echo get_lang('Ok'); ?>" />
    <?php echo claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'])), get_lang('Cancel')); ?>
</form>

<hr />
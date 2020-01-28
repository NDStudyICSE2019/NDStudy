<!-- $Id: item.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php if ($this->intro->getVisibility() == 'SHOW' || claro_is_allowed_to_edit()) : ?>
<div class="item<?php if ($this->intro->getVisibility() != 'SHOW') : ?> hidden<?php endif; ?>">
    <div class="content">
        <p><?php echo claro_parse_user_text($this->intro->getContent()); ?></p>
        
        <?php echo ResourceLinker::renderLinkList($this->rsLocator); ?>
    </div>
    
    <?php if (claro_is_allowed_to_edit()) : ?>
    <div class="manageTools">
        <a
            href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqEd&amp;id='.$this->intro->getId())); ?>"
            title="<?php echo get_lang('Edit this item'); ?>">
            <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Edit'); ?>" />
        </a>
        
        <a
            href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=exDel&amp;id=' . $this->intro->getId())); ?>"
            title="<?php echo get_lang('Delete this item'); ?>"
            onclick="return CLTI.confirmation('headline <?php echo $this->intro->getId(); ?>')">
            <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
        </a>
        
        <a
            href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd='.(($this->intro->getVisibility() == 'SHOW')?('mkInvisible'):('mkVisible')).'&amp;id='.$this->intro->getId())); ?>"
            title="<?php echo (($this->intro->getVisibility() == 'SHOW')?(get_lang('Hide this item')):(get_lang('Show this item'))); ?>">
            <img src="<?php echo (($this->intro->getVisibility() == 'SHOW')?(get_icon_url('visible')):(get_icon_url('invisible'))); ?>"
                alt="<?php echo get_lang('Swap visibility'); ?>" />
        </a>
        
        <a
            href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=exMvUp&amp;id='.$this->intro->getId())); ?>" title="<?php echo get_lang('Move this item up'); ?>">
            <img src="<?php echo get_icon_url('move_up'); ?>" alt="<?php echo get_lang('Move up'); ?>" />
        </a>
        
        <a
            href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=exMvDown&amp;id='.$this->intro->getId())); ?>"
            title="<?php echo get_lang('Move this item down'); ?>">
            <img src="<?php echo get_icon_url('move_down'); ?>" alt="<?php echo get_lang('Move down'); ?>" />
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
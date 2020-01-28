<!-- $Id: list.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php if (count($this->descriptionList) > 0) : ?>

<?php foreach ($this->descriptionList as $description) : ?>
<div class="item<?php if (!$description['visible']) : ?> hidden<?php endif; ?>">
    <h1<?php if ($description['hot']) : ?> class="hot"<?php endif; ?> id="item<?php echo $description['id']; ?>">
        <img src="<?php echo get_icon_url('icon'); ?>" alt="" />
        <?php echo claro_htmlspecialchars($description['title']); ?>
    </h1>
    <div class="content">
        <?php echo claro_parse_user_text($description['content']); ?>
    </div>
    
    <?php if (claro_is_allowed_to_edit()) : ?>
    <div class="manageTools">
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=rqEdit&amp;descId=' . (int) $description['id'])); ?>">
            <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php get_lang('Modify'); ?>" />
        </a>
        
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exDelete&amp;descId=' . (int) $description['id'])); ?>"
         onclick="javascript:if(!confirm('<?php echo clean_str_for_javascript(get_lang('Are you sure to delete "%title" ?', array('%title' => $description['title']))); ?>')) return false;">
            <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
        </a>
        
        <?php if ($description['visible']) : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkInvis&amp;descId=' . (int) $description['id'])); ?>">
            <img src="<?php echo get_icon_url('visible'); ?>" alt="<?php echo get_lang('Invisible'); ?>" />
        </a>
        <?php else : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkVis&amp;descId=' . (int) $description['id'])); ?>">
            <img src="<?php echo get_icon_url('invisible'); ?>" alt="<?php echo get_lang('Visible'); ?>" />
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php else : ?>
<p><?php echo get_lang("This course is currently not described"); ?></p>
<?php endif; ?>
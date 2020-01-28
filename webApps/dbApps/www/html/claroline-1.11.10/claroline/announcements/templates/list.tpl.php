<!-- $Id: list.tpl.php 14338 2012-12-03 14:44:41Z zefredz $ -->

<?php if (count($this->announcementList) > 0) : ?>

<?php foreach ($this->announcementList as $announcement) : ?>

<div class="item<?php if (!$announcement['visible']) : ?> hidden<?php endif; ?>">
    <h1<?php if ($announcement['hot']) : ?> class="hot"<?php endif; ?> id="item<?php echo $announcement['id']; ?>">
        <img src="<?php echo get_icon_url('announcement'); ?>" alt="<?php echo get_lang('Announcement'); ?>" />
        <?php echo get_lang('Published on'); ?>:
        <?php echo claro_html_localised_date( get_locale('dateFormatLong'), strtotime($announcement['time'])); ?>
    </h1>
    
    <div class="content">
        <?php if (!empty($announcement['title'])) : ?><h2><?php echo claro_htmlspecialchars($announcement['title']); ?></h2><?php endif; ?>
        <?php if (!empty($announcement['content'])) : ?><?php echo claro_parse_user_text($announcement['content']); ?><?php endif; ?>
    </div>
    
    <?php if (!empty($announcement['currentLocator'])) : ?>
    <?php echo ResourceLinker::renderLinkList($announcement['currentLocator']); ?>
    
    <?php endif; ?>
    
    <?php if (claro_is_allowed_to_edit()) : ?>
    <div class="manageTools">
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqEdit&id=' . $announcement['id'])); ?>">
            <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Modify'); ?>" />
        </a>
        
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exDelete&id=' . $announcement['id'])); ?>"
         onclick="return CLANN.confirmationDel('<?php echo clean_str_for_javascript($announcement['title']); ?>')">
            <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
        </a>
        
        <?php if ($announcement['visible']) : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkHide&id=' . $announcement['id'])); ?>">
            <img src="<?php echo get_icon_url('visible'); ?>" alt="<?php echo get_lang('Make invisible'); ?>" />
        </a>
        <?php else : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkShow&id=' . $announcement['id'])); ?>">
            <img src="<?php echo get_icon_url('invisible'); ?>" alt="<?php echo get_lang('Make visible'); ?>" />
        </a>
        <?php endif; ?>
        
        <?php if ( $announcement['rank'] !== $this->maxRank ): ?>
        
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exMvUp&id=' . $announcement['id'])); ?>">
            <img src="<?php echo get_icon_url('move_up'); ?>" alt="<?php echo get_lang('Move up'); ?>" />
        </a>
        
        <?php endif; ?>
        
        <?php if ( $announcement['rank'] !== $this->minRank ): ?>
        
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exMvDown&id=' . $announcement['id'])); ?>">
            <img src="<?php echo get_icon_url('move_down'); ?>" alt="<?php echo get_lang('Move down'); ?>" />
        </a>
        
        <?php endif; ?>
        
    </div>
    <?php endif; ?>
</div>

<?php endforeach; ?>

<?php else : ?>
<p><?php echo get_lang('No announcement'); ?></p>
<?php endif; ?>
<!-- $Id: list.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php if (count($this->eventList) > 0) : ?>
<?php $month = ''; $now = ''; ?>

<?php foreach ($this->eventList as $event) : ?>

<?php
// Display a title for months
if ($month != date('mY', strtotime($event['day']))) :
?>
<?php $month = date('mY', strtotime($event['day'])); ?>
<h2>
    <?php echo ucfirst(claro_html_localised_date('%B %Y', strtotime($event['day']))); ?>
</h2>
<?php endif; ?>

<?php
// Display a title to situate the current time in the event list
if (
      (
        ((strtotime($event['day'] . ' ' . $event['hour'] ) > time())
            &&  'ASC' == $this->orderDirection
        )
        ||
        ((strtotime($event['day'] . ' ' . $event['hour'] ) < time())
            &&  'DESC' == $this->orderDirection
        )
      )
      && empty($now)
   ) :
?>
<?php
$now = ucfirst(claro_html_localised_date( get_locale('dateFormatLong')))
     . ' ' . ucfirst(strftime( get_locale('timeNoSecFormat')));
?>

<h3 id="today" class="highlight">
    <?php echo $now; ?>
    &mdash;
    <?php echo get_lang('Now'); ?>
</h3>
<?php endif; ?>

<div class="item<?php if (!$event['visible']) : ?> hidden<?php endif; ?>">
    <h1<?php if ($event['hot']) : ?> class="hot"<?php endif; ?> id="item<?php echo $event['id']; ?>">
        <img src="<?php echo get_icon_url('agenda'); ?>" alt="<?php echo get_lang('Calendar'); ?>" />
        <?php echo ucfirst(claro_html_localised_date( get_locale('dateFormatLong'), strtotime($event['day']))); ?>
        <?php echo ucfirst( strftime( get_locale('timeNoSecFormat'), strtotime($event['hour']))); ?>
        <?php if (!empty($event['lasting'])) : ?> | <?php echo get_lang('Lasting'); ?>: <?php echo $event['lasting']; ?><?php endif; ?>
        <?php if (!empty($event['location'])) : ?> | <?php echo get_lang('Location'); ?>: <?php echo $event['location']; ?><?php endif; ?>
        <?php if (!empty($event['speakers'])) : ?> | <?php echo get_lang('Speakers'); ?>: <?php echo $event['speakers']; ?><?php endif; ?>
    </h1>
    
    <div class="content">
        <?php if (!empty($event['title'])) : ?><h2><?php echo claro_htmlspecialchars($event['title']); ?></h2><?php endif; ?>
        <?php if (!empty($event['content'])) : ?><?php echo claro_parse_user_text($event['content']); ?><?php endif; ?>
    </div>
    
    <?php echo ResourceLinker::renderLinkList($event['currentLocator']); ?>
    
    <?php if ( claro_is_allowed_to_edit() ) : ?>
    <div class="manageTools">
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqEdit&amp;id=' . $event['id'])); ?>">
            <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Modify'); ?>" />
        </a>
        
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exDelete&amp;id=' . $event['id'])); ?>"
         onclick="javascript:if(!confirm('<?php echo clean_str_for_javascript(get_lang('Are you sure to delete "%title" ?', array('%title' => $event['title']))); ?>')) return false;">
            <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete'); ?>" />
        </a>
        
        <?php if ($event['visible']) : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkHide&amp;id=' . $event['id'])); ?>">
            <img src="<?php echo get_icon_url('visible'); ?>" alt="<?php echo get_lang('Make invisible'); ?>" />
        </a>
        <?php else : ?>
        <a href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=mkShow&amp;id=' . $event['id'])); ?>">
            <img src="<?php echo get_icon_url('invisible'); ?>" alt="<?php echo get_lang('Make visible'); ?>" />
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php endforeach; ?>

<?php else : ?>
<p><?php echo get_lang('No event in the agenda'); ?></p>
<?php endif; ?>
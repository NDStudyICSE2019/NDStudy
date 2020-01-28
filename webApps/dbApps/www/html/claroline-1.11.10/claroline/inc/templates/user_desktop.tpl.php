<!-- $Id: user_desktop.tpl.php 14225 2012-07-30 06:38:39Z zefredz $ -->

<div id="rightSidebar">
    <?php echo $this->userProfileBox->render(); ?>
    
    <?php include_textzone('textzone_right.inc.html'); ?>
</div>

<div id="leftContent">
    <?php echo claro_html_tool_title(get_lang(get_lang('My desktop'))); ?>
    
    <div id="dekstopLeftSidebar">
        <?php echo $this->mycourselist; ?>
    </div>
    
    <div id="desktopRightContent">
        <?php echo $this->dialogBox->render(); ?>
        
        <div class="portlet collapsible<?php echo get_conf('userDesktopMessageCollapsedByDefault', true) ? '  collapsed' : ''; ?>">
            <h1>
                <?php echo get_lang('Presentation'); ?>
                <span class="separator">|</span>
                <a href="#" class="doCollapse"><?php echo 
                    get_conf('userDesktopMessageCollapsedByDefault', true) 
                        ? get_lang('Show/Hide') 
                        : get_lang('Hide/Show'); ?></a>
            </h1>
            <div class="content collapsible-wrapper">
                <?php include_textzone('textzone_top.authenticated.inc.html'); ?>
            </div>
        </div>

        <?php echo $this->outPortlet; ?>
    </div>
</div>
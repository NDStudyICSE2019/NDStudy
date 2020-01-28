<!-- $Id: admin_panel.tpl.php 13234 2011-05-26 16:58:52Z abourguignon $ -->

<?php echo claro_html_tool_title(get_lang('Administration')); ?>

<?php echo $this->dialogBox->render(); ?>

<ul class="adminPanel">
    <li>
        <h2><?php echo '<img src="' . get_icon_url('user') . '" alt="" />&nbsp;'.get_lang('Users'); ?></h2>
        <?php echo claro_html_list($this->menu['AdminUser'], array('class' => 'adminUser')); ?>
    </li>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('course') . '" alt="" />&nbsp;'.get_lang('Courses'); ?></h2>
        <?php echo claro_html_list($this->menu['AdminCourse'], array('class' => 'adminCourse')); ?>
    </li>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('settings') . '" alt="" />&nbsp;'.get_lang('Platform\' configuration'); ?></h2>
        <?php echo claro_html_list($this->menu['AdminPlatform'], array('class' => 'adminPlatform')); ?>
    </li>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('exe') . '" alt="" />&nbsp;' . get_lang('Tools'); ?></h2>
        <?php echo claro_html_list($this->menu['AdminTechnical'], array('class' => 'adminTechnical')); ?>
    </li>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('claroline') . '" alt="" />&nbsp;Claroline.net'; ?></h2>
        <?php echo claro_html_list($this->menu['AdminClaroline'], array('class' => 'adminClaroline')); ?>
    </li>
    <?php if (!empty($this->menu['ExtraTools'])) : ?>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('exe') . '" alt="" />&nbsp;' . get_lang('Administration modules'); ?></h2>
        <?php echo claro_html_list($this->menu['ExtraTools'], array('class' => 'adminExtraTools')); ?>
    </li>
    <?php endif; ?>
    <li>
        <h2><?php echo '<img src="' . get_icon_url('mail_close') . '" alt="" />&nbsp;'.get_lang('Communication'); ?></h2>
        <?php echo claro_html_list($this->menu['Communication'], array('class' => 'adminCommunication')); ?>
    </li>
</ul>
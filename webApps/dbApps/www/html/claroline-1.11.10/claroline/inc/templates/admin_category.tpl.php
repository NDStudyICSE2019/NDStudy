<!-- $Id: admin_category.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php echo $this->title; ?>

<?php echo $this->dialogBox->render(); ?>

<?php if (get_conf('categories_order_by', 'rank') != 'rank') : ?>
<p>
    <?php echo get_block('blockCategoriesOrderInfo'); ?>
</p>
<?php endif; ?>

<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">
<thead>
  <tr class="headerX">
    <th><?php echo get_lang('Category label'); ?></th>
    <th><?php echo get_lang('Dedicated course'); ?></th>
    <th><?php echo get_lang('Courses'); ?></th>
    <th><?php echo get_lang('Visibility'); ?></th>
    <th><?php echo get_lang('Edit'); ?></th>
    <th><?php echo get_lang('Delete'); ?></th>
    <th colspan="2"><?php echo get_lang('Order'); ?></th>
  </tr>
</thead>
<tbody>
  <?php if (count($this->categories) == 0) : ?>
  <tr>
    <td colspan="7">
        <?php echo get_lang('There are no cateogries right now.  Use the link above to add some.'); ?>
    </td>
  </tr>
  <?php else : ?>
  <?php foreach ($this->categories as $elmt) : ?>
  <tr>
    <td><?php echo str_repeat('&nbsp;', 4*$elmt['level']) . $elmt['name'] . ' (' . $elmt['code'] . ')'; ?></td>
    <td><?php echo (!is_null($elmt['dedicatedCourse']) ? ($elmt['dedicatedCourse'] . ' (' . $elmt['dedicatedCourseCode'] . ')') : ('')); ?></td>
    <td align="center"><?php echo $elmt['nbCourses']; ?></td>
    <td align="center">
       <a href="<?php echo claro_htmlspecialchars(URL::Contextualize('?cmd=exVisibility&amp;categoryId=' . $elmt['id'])); ?>">
       <img src="<?php echo get_icon_url($elmt['visible']?'visible':'invisible'); ?>" alt="<?php echo get_lang('Change visibility'); ?>" />
       </a>
    </td>
    <td align="center">
       <a href="<?php echo claro_htmlspecialchars(URL::Contextualize('?cmd=rqEdit&amp;categoryId=' . $elmt['id'])); ?>">
       <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Edit category'); ?>" />
       </a>
    </td>
    <td align="center">
       <a href="<?php echo claro_htmlspecialchars(URL::Contextualize('?cmd=exDelete&amp;categoryId=' . $elmt['id'])); ?>"
        onclick="return ADMIN.confirmationDel('<?php echo clean_str_for_javascript($elmt['name']); ?>');">
       <img src="<?php echo get_icon_url('delete'); ?>" alt="<?php echo get_lang('Delete category'); ?>" />
       </a>
    </td>
    <?php if (get_conf('categories_order_by') == 'rank') : ?>
    <td align="center">
        <a href="<?php echo claro_htmlspecialchars(URL::Contextualize('?cmd=exMoveUp&amp;categoryId=' . $elmt['id'])); ?>">
            <img src="<?php echo get_icon_url('move_up'); ?>" alt="<?php echo get_lang('Move up category'); ?>" />
        </a>
    </td>
    <td align="center">
        <a href="<?php echo claro_htmlspecialchars(URL::Contextualize('?cmd=exMoveDown&amp;categoryId=' . $elmt['id'])); ?>">
            <img src="<?php echo get_icon_url('move_down'); ?>" alt="<?php echo get_lang('Move down category'); ?>" />
        </a>
    </td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
  <?php endif; ?>
</tbody>
</table>
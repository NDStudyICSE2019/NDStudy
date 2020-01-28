<!-- $Id: admin_files_stats.tpl.php 13374 2011-07-28 09:56:00Z abourguignon $ -->

<?php echo claro_html_tool_title(get_lang('Files statistics')); ?>

<?php echo $this->dialogBox->render(); ?>

<table style="margin: 5px 0 10px 0; padding: 0;">
  <tr>
    <td>
        <form method="post" action="<?php echo $this->formAction; ?>">
            <input type="hidden" name="cmd" id="cmd" value="run" />
            <input type="hidden" name="viewAs" id="viewAs" value="html" />
            <input type="submit" name="changeProperties" value="<?php echo get_lang('Get HTML statistics'); ?>" />
        </form>
    </td>
    <td>
        <form method="post" action="<?php echo $this->formAction; ?>">
            <input type="hidden" name="cmd" id="cmd" value="run" />
            <input type="hidden" name="viewAs" id="viewAs" value="csv" />
            <input type="submit" name="changeProperties" value="<?php echo get_lang('Get CSV statistics'); ?>" />
        </form>
    </td>
  </tr>
</table>

<?php if (!empty($this->stats)) : ?>
<table class="claroTable emphaseLineemphaseLine">
<thead>
  <tr>
    <th><?php echo get_lang('Course code'); ?></th>
    <th><?php echo get_lang('Course title'); ?></th>
    <th><?php echo get_lang('Lecturer(s)'); ?></th>
    <th><?php echo get_lang('Category'); ?></th>
    <?php
    foreach ($this->allExtensions as $ext) :
    ?>
       <th colspan="2"><?php echo get_lang($ext); ?></th>
    <?php
    endforeach;
    ?>
  </tr>
  <tr>
    <th> </th>
    <th> </th>
    <th> </th>
    <th> </th>

    <?php
    foreach ($this->allExtensions as $ext) :
    ?>
       <th><?php echo get_lang('Nb'); ?></th>
       <th><?php echo get_lang('Size'); ?></th>
    <?php
    endforeach;
    ?>
  </tr>
</thead>
<tbody>
  <?php
  foreach ($this->stats as $courseCode => $courseInfos) :
  ?>
     <tr>
        <td style="font-weight: bold;"><?php echo $courseCode; ?></td>
        <td><?php echo $courseInfos['courseTitle']; ?></td>
        <td><?php echo $courseInfos['courseTitulars']; ?></td>
        <td>
        <?php
        foreach ($courseInfos['courseCategory'] as $cat)
            echo $cat . '<BR>';
        ?>
        </td>
        <?php
        foreach ($courseInfos['courseStats'] as $courseStats) :
        ?>
            <td><?php echo $courseStats['count']; ?></td>
            <td><?php echo format_bytes($courseStats['size']); ?></td>
        <?php
        endforeach;
        ?>
    </tr>
  <?php
  endforeach;
  ?>
</tbody>
</table>
<?php endif; ?>
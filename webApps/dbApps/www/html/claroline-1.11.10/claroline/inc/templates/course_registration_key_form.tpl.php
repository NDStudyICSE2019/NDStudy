<!-- $Id: course_registration_key_form.tpl.php 13880 2011-12-09 15:54:43Z abourguignon $ -->

<p class="notice">
    <?php echo get_lang('If you do not have the key, please contact the course manager'); ?>
</p>

<?php echo get_locked_course_by_key_explanation($this->courseCode); ?>

<form action="<?php echo $this->formAction; ?>" method="POST">
  <fieldset>
    <input type="hidden" name="cmd" value="exReg" />
    <input type="hidden" name="course" value="<?php echo $this->courseCode; ?>" />
    
    <dl>
        <dt><?php echo get_lang('Enrolment key'); ?></dt>
    
        <dd>
            <input type="text" name="registrationKey" />
        </dd>
    </dl>
  </fieldset>
    
  <p>
      <input type="submit" value="<?php echo get_lang('Ok'); ?>" />&nbsp;
      <?php echo claro_html_button($_SERVER['PHP_SELF'].'?cmd=rqReg', get_lang('Cancel')); ?>
  </p>
</form>
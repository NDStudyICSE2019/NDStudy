<!-- $Id: select_course_form.tpl.php 13476 2011-08-26 10:03:22Z abourguignon $ -->

<table align="center">
  <tr>
    <td>
        <?php if (isset($courseList) && count($courseList) > 0) : ?>
            <?php echo claro_html_tool_title(get_lang('Choose a course to access this page.')); ?>
            <form class="claroLoginForm" action ="<?php echo $this->formAction; ?>" method="post">
                <fieldset>
                    <?php echo $this->sourceUrlFormField; ?>
                    <?php echo $this->cidRequiredFormField; ?>
                    <?php echo $this->sourceCidFormField; ?>
                    <?php echo $this->sourceGidFormField; ?>
                    
                    <label for="selectCourse"><?php echo get_lang('Course'); ?></label><br />
                    <select name="cidReq" id="selectCourse">
                        <?php foreach ($this->courseList as $course) : ?>
                        <option value="<?php echo $course['value']; ?>">
                            <?php echo $course['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select><br />
                    <br />
                    <input type="submit" value="<?php echo get_lang('Ok'); ?>" />&nbsp;
                    <?php echo claro_html_button(get_path('url') . '/index.php', get_lang('Cancel')); ?>
                </fieldset>
            </form>
        <?php else : ?>
            <p align="center">
                <?php echo get_lang('If you wish to enrol on this course'); ?>:
                <a href="<?php echo get_path('clarolineRepositoryWeb'); ?>auth/courses.php?cmd=rqReg">
                    <?php echo get_lang('Enrolment'); ?>
                </a>
            </p>
        <?php endif; ?>
    </td>
  </tr>
</table>
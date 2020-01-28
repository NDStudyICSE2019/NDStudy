<!-- $Id: user_course_list.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php if (!empty($this->courseList)) : ?>
<dl class="courseList">
    <?php foreach ($this->courseList as $course) : ?>
    <dt>
        <img
            class="access qtip"
            src="<?php echo get_course_access_icon($course->access); ?>"
            alt="<?php echo claro_htmlspecialchars(get_course_access_mode_caption($course->access)); ?>" />
        
        <a href="<?php echo claro_htmlspecialchars(get_path('url')
            .'/claroline/course/index.php?cid='.$course->sysCode); ?>">
            <?php echo claro_htmlspecialchars($course->officialCode); ?>
            &ndash;
            <?php echo claro_htmlspecialchars($course->name); ?>
        </a>
        
        <span class=role>
        <?php if ($this->cupList->getCoursePrivileges($course->courseId)->isCourseManager()) : ?>
        [Manager]
        <?php elseif ($this->cupList->getCoursePrivileges($course->courseId)->isCourseTutor()) : ?>
        [Tutor]
        <?php elseif ($this->cupList->getCoursePrivileges($course->courseId)->isCourseMember()) : ?>
        [Member]
        <?php elseif ($this->cupList->getCoursePrivileges($course->courseId)->isEnrolmentPending()) : ?>
        [Pending]
        
        <?php endif; ?>
        </span>
    </dt>
    <dd>
        <?php if (isset($course->email) && claro_is_user_authenticated()) : ?>
        <a href="mailto:<?php echo $course->email; ?>">
            <?php echo claro_htmlspecialchars($course->titular); ?>
        </a>
        
        <?php else : ?>
        <?php echo claro_htmlspecialchars($course->titular); ?>
        
        <?php endif; ?>
        
        -
        
        <?php echo get_course_locale_lang($course->language); ?>
    </dd>
    <?php endforeach; ?>
</dl>

<?php else : ?>
<p>
    <?php echo get_lang('No courses here.'); ?>
</p>

<?php endif; ?>

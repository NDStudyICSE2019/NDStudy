<!-- $Id: course_list.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<?php

/**
 *
 * *************************
 * This is work in progress.
 * *************************
 *
 * When finished, this template will be used in several
 * platform's places, like:
 *
 * - Category browser course list
 * - My course list (home page and user's desktop)
 * - My course list desactivated (home page and user's desktop)
 * - Courses search results
 * - Enrol to a course
 * - Remove course enrolment
 *
 *
 * Note 1: the main purpose is to manage the display of course lists
 *      in one and only one place and thereby have a consistent presentation
 *      of course lists.
 *
 * Note 2: this template purpose is NOT to take in charge the
 *      old "Categories organization".  It only manage flat course list.
 *
 * Note 3: main function regarding this template will be written in
 *      "claroline/inc/lib/courselist.lib.php".
 *
 * @todo work in progress
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 *
 */

?>

<?php if (!empty($this->courseList)) : ?>
<dl class="courseList">
    <?php foreach ($this->courseList as $course) : ?>
    <dt<?php if (isset($course['hot']) && $course['hot']) : ?> class="hot"<?php endif; ?>>
        <img
            class="access qtip"
            src="<?php echo get_course_access_icon($course['access']); ?>"
            alt="<?php echo claro_htmlspecialchars(get_course_access_mode_caption($course['access'])); ?>" />
        
        <a href="<?php echo claro_htmlspecialchars(get_path('url')
            .'/claroline/course/index.php?cid='.$course['sysCode']); ?>"
            <?php if (!empty($course['hot']) && $course['hot']) : ?> class="hot"<?php endif; ?>>
            <?php echo claro_htmlspecialchars($course['officialCode']); ?>
            &ndash;
            <?php echo claro_htmlspecialchars($course['title']); ?>
        </a>
        
        <?php if (claro_is_user_authenticated() && isset($course['enroled']) && $course['enroled']) : ?>
        
        &nbsp;&nbsp;
        
        <?php if (isset($course['isCourseManager']) && $course['isCourseManager']) : ?>
        <img class="role qtip"
            src="<?php echo get_icon_url('manager'); ?>"
            alt="<?php echo get_lang('You are manager of this course'); ?>" />
        <?php else : ?>
        <img class="role qtip"
            src="<?php echo get_icon_url('user'); ?>"
            alt="<?php echo get_lang('You are enrolled to this course'); ?>" />
        
        <?php endif; ?>
        
        <?php elseif (claro_is_user_authenticated() && isset($course['enroled']) && !$course['enroled']) : ?>
        
        <?php
        /**
        &nbsp;&nbsp;
        
        <?php if (1) : ?>
        <a href="#enroll">
            <img class="action qtip"
                src="<?php echo get_icon_url('enroll_allowed'); ?>"
                alt="<?php echo get_lang('You are allowed to enrol yourself to this course'); ?>" />
        </a>
        <?php else : ?>
        <img class="action qtip"
            src="<?php echo get_icon_url('enroll_forbidden'); ?>"
            alt="<?php echo get_lang('You are forbidden to enrol yourself to this course'); ?>" />
        
        <?php endif; ?>
        **/
        ?>
        
        <?php endif; ?>
    </dt>
    <dd>
        <?php if (isset($course['email']) && claro_is_user_authenticated()) : ?>
        <a href="mailto:<?php echo $course['email']; ?>">
            <?php echo claro_htmlspecialchars($course['titular']); ?>
        </a>
        
        <?php else : ?>
        <?php echo claro_htmlspecialchars($course['titular']); ?>
        
        <?php endif; ?>
        
        -
        
        <?php echo get_course_locale_lang($course['language']); ?>
    </dd>
    <?php endforeach; ?>
</dl>

<?php else : ?>
<p>
    <?php echo get_lang('No courses here.'); ?>
</p>

<?php endif; ?>
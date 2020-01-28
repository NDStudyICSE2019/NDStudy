<!-- $Id: mycourses.tpl.php 13271 2011-06-28 01:08:31Z abourguignon $ -->

<?php if (!empty( $this->userCourseList)) : ?>
<h1><?php echo get_lang('My course list'); ?></h1>
<?php echo $this->userCourseList; // Comes from render_user_course_list(); ?>

<?php elseif (empty($this->userCourseListDesactivated)) : ?>
<?php echo get_lang('You are not enrolled to any course on this platform or all your courses are deactivated'); ?>

<?php else : ?>
<?php echo get_lang( 'All your courses are deactivated (see list below)' ); ?>

<?php endif; ?>


<?php if (!empty($this->userCourseListDesactivated)) : ?>
<h1><?php echo get_lang('Deactivated course list'); ?></h1>
<?php echo $this->userCourseListDesactivated; // Comes from render_user_course_list_desactivated(); ?>

<?php endif; ?>
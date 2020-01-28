<!-- $Id: course_tree_node.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<dt<?php if (!empty($this->notifiedCourseList) 
    && $this->notifiedCourseList->isCourseNotified($this->node->getCourse()->courseId)) : 
    ?> class="hot"<?php endif; ?>>
    
    <!-- Access icon -->
    <img
        class="access qtip"
        src="<?php echo get_course_access_icon(
            $this->node->getCourse()->access ); ?>"
        alt="<?php echo claro_htmlspecialchars(
            get_course_access_mode_caption(
                $this->node->getCourse()->access ) ); ?>" />
    
    <?php if ( claro_is_platform_admin() && $this->viewOptions->classEnrolmentMode() ): ?>
    
        <a href="<?php 
            $urlObj = Url::buildUrl(
                $this->viewOptions->getEnrollLinkUrl()->toUrl(), 
                array('cidReq' => $this->node->getCourse()->courseId, 'cidReset' => 'true' ), 
                null);

            echo $urlObj->toUrl();
            ?>">
            <img class="enrolment" src="<?php echo get_icon_url('enroll'); ?>" alt="<?php echo get_lang('Enroll class'); ?>" />
        </a>
    
    <?php elseif (!empty($this->courseUserPrivilegesList)) : ?>
        
        <!-- Enrolment/unenrolment icon -->
        <?php
            /*
             * Display this link if: 
             * - The user isn't course member already
             * - The config says so
             */
            if (!$this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseMember() &&
            $this->viewOptions->haveToDisplayEnrollLink()) : ?>
            
            <a href="<?php 
                $urlObj = Url::buildUrl(
                    $this->viewOptions->getEnrollLinkUrl()->toUrl(), 
                    array('course' => $this->node->getCourse()->courseId), 
                    null);
                    
                echo $urlObj->toUrl();
                ?>">
                <img class="enrolment" src="<?php echo get_icon_url('enroll'); ?>" alt="<?php echo get_lang('Enroll'); ?>" />
            </a>
            
        <?php
            /*
             * Display this link if: 
             * - The user is course member already
             * - The user isn't course manager
             * - The config says so
             */
            elseif ($this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseMember() 
            && !$this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseManager()
            && $this->viewOptions->haveToDisplayUnenrollLink()) : ?>
            
            <a href="<?php 
                $urlObj = Url::buildUrl(
                    $this->viewOptions->getUnenrollLinkUrl()->toUrl(), 
                    array('course' => $this->node->getCourse()->courseId), 
                    null);
                    
                echo $urlObj->toUrl();
                ?>"
               onclick="javascript:if(!confirm('<?php echo clean_str_for_javascript(get_lang('Are you sure you want to remove this course from your list ?')); ?>')) return false;">
                <img class="enrolment" src="<?php echo get_icon_url('unenroll'); ?>" alt="<?php echo get_lang('Enroll'); ?>" />
            </a>
            
        <?php endif; ?>
        
        
        <!-- Role icon -->
        <?php if ( $this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseManager() ) : ?>
            
            <img class="role qtip" src="<?php echo get_icon_url('manager'); ?>" alt="<?php echo get_lang('You are manager of this course'); ?>" />
            
        <?php elseif ( $this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseTutor() ) : ?>
            
            <span class="role">[Tutor]</span>
            
        <?php elseif ( $this->courseUserPrivilegesList->getCoursePrivileges(
            $this->node->getCourse()->courseId)->isCourseMember() ) : ?>
            
            <?php if ( $this->courseUserPrivilegesList->getCoursePrivileges(
                $this->node->getCourse()->courseId)->isEnrolmentPending() ) : ?>
            <span class="role">[Pending]</span>
            
            <?php else : ?>
            <img class="role qtip" src="<?php echo get_icon_url('user'); ?>" alt="<?php echo get_lang('You are user of this course'); ?>" />
            
            <?php endif; ?>
            
        <?php endif; ?>
        
    <?php else : ?>
        
    <?php endif; ?>
    
    <a <?php if (!empty($this->notifiedCourseList) 
        && $this->notifiedCourseList->isCourseNotified($this->node->getCourse()->courseId)) : 
        ?>class="hot"<?php endif; ?>
        href="<?php echo claro_htmlspecialchars(
            claro_get_course_homepage_url($this->node->getCourse()->sysCode)); ?>">
        <?php echo claro_htmlspecialchars( $this->node->getCourse()->officialCode) ; ?>
        &ndash;
        <?php echo claro_htmlspecialchars( $this->node->getCourse()->name ); ?>
    </a>
</dt>
<dd>
    <span>
    <?php if ( isset($this->node->getCourse()->email)
        && claro_is_user_authenticated() ) : ?>
    
    <a href="mailto:<?php echo $this->node->getCourse()->email; ?>">
        <?php echo claro_htmlspecialchars($this->node->getCourse()->titular); ?>
    </a>
    
    <?php else : ?>
    
    <?php echo claro_htmlspecialchars($this->node->getCourse()->titular); ?>
    
    <?php endif; ?>
    
    -
    
    <?php echo get_course_locale_lang($this->node->getCourse()->language); ?>
    
    <?php if ($this->node->hasChildren()) : ?>
    
    <dl>
        
    <?php foreach ($this->node->getChildren() as $childNode) : ?>
        
        <?php if ($childNode->getCourse()->isActivated()) : ?>
        <?php
            $childNodeView = new CourseTreeNodeView(
                $childNode, 
                $this->courseUserPrivilegesList,
                $this->notifiedCourseList,
                $this->viewOptions);
            
            echo $childNodeView->render();
        ?>
        
        <?php else : ?>
        <?php
            $childNodeView = new CourseTreeNodeDeactivatedView(
                $childNode, 
                $this->courseUserPrivilegesList,
                $this->notifiedCourseList,
                $this->viewOptions);
            
            echo $childNodeView->render();
        ?>
        
        <?php endif; ?>
        
    <?php endforeach; ?>
        
    </dl>
    
    <?php endif; ?>
    </span>
</dd>

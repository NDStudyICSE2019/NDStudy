<!-- $Id: course_index.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

    <div class="coursePortletList">
        <?php
            echo $this->dialogBox->render();
        ?>
        
        <?php if ( claro_is_allowed_to_edit() && !empty($this->activablePortlets) ) : ?>
        <ul class="commandList">
            <?php foreach($this->activablePortlets as $portlet) : ?>
            <li>
                <a style="background-image: url(<?php echo get_icon_url('add'); ?>); background-repeat: no-repeat; background-position: left center; padding-left: 20px;"
                    href="<?php echo claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?portletCmd=exAdd&portletLabel='.$portlet['label'])).'&courseId='.ClaroCourse::getIdFromCode(claro_get_current_course_id()); ?>">
                    <?php echo get_lang('Add a new portlet'); ?>: <?php echo get_lang($portlet['name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <?php
            if ( count( $this->portletIterator ) > 0)
            {
                foreach ($this->portletIterator as $portlet)
                {
                    if ($portlet->getVisible() || !$portlet->getVisible() && claro_is_allowed_to_edit())
                    {
                        echo $portlet->render();
                    }
                }
            }
            elseif ( count( $this->portletIterator ) == 0 && claro_is_allowed_to_edit())
            {
                echo get_block('blockIntroCourse');
            }
        ?>
    </div>

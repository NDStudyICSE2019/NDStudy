<!-- $Id: user_profilebox.tpl.php 14448 2013-05-15 08:47:35Z zefredz $ -->

<div id="userProfileBox">
    <h3 class="blockHeader">
        <span class="userName">
            <?php if ($this->condensedMode && $this->userData['user_id'] == claro_get_current_user_id()) : ?>
                <a href="<?php echo get_path('clarolineRepositoryWeb'); ?>desktop/index.php">
                    <?php echo $this->userFullName; ?>
                </a>
            <?php else : ?>
                <?php echo $this->userFullName; ?>
            
            <?php endif; ?>
        </span>
    </h3>
    <div id="userProfile">
        <?php if ( get_conf('allow_profile_picture') ) : ?>
        <div id="userPicture">
            <img class="userPicture" src="<?php echo $this->pictureUrl; ?>" alt="<?php echo get_lang('User picture'); ?>" />
        </div>
        
        <?php endif; ?>
        
        <div id="userDetails">
            <p>
                <span><?php echo get_lang('Email'); ?></span>
                <?php echo (!empty($this->userData['email']) ? claro_htmlspecialchars($this->userData['email']) : '-' ); ?>
            </p>
            
            <?php
            if (!$this->condensedMode) :
            ?>
                <p>
                    <span><?php echo get_lang('Phone'); ?></span>
                    <?php echo (!empty($this->userData['phone']) ? claro_htmlspecialchars($this->userData['phone']) : '-' ); ?>
                </p>
                <p>
                    <span><?php echo get_lang('Administrative code'); ?></span>
                    <?php echo (!empty($this->userData['officialCode']) ? claro_htmlspecialchars($this->userData['officialCode']) : '-' ); ?>
                </p>
                
                <?php if (get_conf('is_trackingEnabled')) : ?>
                <p>
                    <a class="claroCmd" href="<?php echo Url::Contextualize(get_path('clarolineRepositoryWeb')
                    .'tracking/userReport.php?userId='.claro_get_current_user_id()); ?>">
                    <img src="<?php echo get_icon_url('statistics'); ?>" alt="<?php echo get_lang('Statistics'); ?>" />
                    <?php echo get_lang('View my statistics'); ?>
                    </a>
                </p>
                
                <?php endif; ?>
            
            <?php endif; ?>
            
            <?php if( $this->userId == claro_get_current_user_id() || claro_is_platform_admin () ): ?>
            <p>
                
                <?php if( $this->userId == claro_get_current_user_id() ): ?>
                
                <a class="claroCmd" href="<?php  echo get_path('clarolineRepositoryWeb'); ?>auth/profile.php">
                <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('Manage my account'); ?>" />
                <?php echo get_lang('Manage my account'); ?>
                </a>
                
                <?php else: ?>
                
                <a class="claroCmd" href="<?php  echo get_path('clarolineRepositoryWeb'); ?>admin/admin_profile.php?uidToEdit=<?php echo $this->userId; ?>">
                <img src="<?php echo get_icon_url('edit'); ?>" alt="<?php echo get_lang('User settings'); ?>" />
                <?php echo get_lang('User settings'); ?>
                </a>
                
                <?php endif; ?>
                
            </p>
            <?php endif; ?>
            
        </div>
    </div>
    
    <?php if (!$this->condensedMode) : ?>
    <div id="userProfileBoxDock"><?php echo $this->dock->render(); ?></div>
    
    <?php endif; ?>
</div>

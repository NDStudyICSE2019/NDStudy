<!-- $Id: banner.tpl.php 13151 2011-05-11 12:09:47Z abourguignon $ -->

<!-- claroPage -->
<div id="claroPage">

<!-- Banner -->
<div id="topBanner">

<!-- Platform Banner -->
<div id="platformBanner">
    <div id="campusBannerLeft">
        <span id="siteName">
            <a href="<?php echo get_path('url'); ?>/index.php" target="_top">
            <?php if (get_conf('siteLogo') != '') : ?>
            <img src="<?php echo get_conf('siteLogo'); ?>" alt="<?php echo get_conf('siteName'); ?>" />
            <?php else : ?>
            <?php echo get_conf('siteName'); ?>
            <?php endif; ?>
            </a>
        </span>
        
        <?php include_dock('campusBannerLeft'); ?>
    </div>
    <div id="campusBannerRight">
        <span id="institution">
            <?php if (get_conf('institution_url') != '') : ?>
            <a href="<?php echo get_conf('institution_url'); ?>" target="_top">
            <?php endif; ?>
            
            <?php if (get_conf('institutionLogo') != '') : ?>
            <img src="<?php echo get_conf('institutionLogo'); ?>" alt="<?php echo get_conf('institution_name'); ?>" />
            <?php else : ?>
            <?php echo get_conf('institution_name'); ?>
            <?php endif; ?>
            
            <?php if (get_conf('institution_url') != '') : ?>
            </a>
            <?php endif; ?>
        </span>
        
        <?php include_dock('campusBannerRight'); ?>
    </div>
    <div class="spacer"></div>
</div>
<!-- End of Platform Banner -->

<?php if ( $this->userBanner && property_exists($this, 'user') ): ?>
<!-- User Banner -->
<div id="userBanner">
    <div id="userBannerLeft">
        <ul class="menu">
            <?php foreach($this->userToolListLeft as $menuItem) : ?>
            <li><span><?php echo $menuItem; ?></span></li>
            <?php endforeach; ?>
            <?php include_dock('userBannerLeft', true); ?>
        </ul>
        
    </div>
    
    <div id="userBannerRight">
        <ul class="menu">
            <li class="userName">
                <?php
                echo get_lang('%firstName %lastName', array(
                    '%firstName' => $this->user['firstName'],
                    '%lastName' => $this->user['lastName']));
                ?>
            </li>
            <?php foreach($this->userToolListRight as $menuItem) : ?>
            <li><span><?php echo $menuItem; ?></span></li>
            <?php endforeach; ?>
            <?php include_dock('userBannerRight', true); ?>
        </ul>
        
    </div>
    
    <div class="spacer"></div>
</div>
<?php else : ?>
<div id="userBanner">
    <div id="userBannerRight">
        <ul class="menu">
            <li><span><?php echo $this->viewmode->render(); ?></span></li>
        </ul>
    </div>
    
    <div class="spacer"></div>
</div>
<!-- End of User Banner -->
<?php endif; ?>

<?php if ( $this->breadcrumbLine ): ?>
<!-- BreadcrumbLine  -->
<div id="breadcrumbLine">
    <hr />
    <div class="breadcrumbTrails">
        <?php echo $this->breadcrumbs->render(); ?>
    </div>
    
    <div id="toolViewOption">
        <?php if (claro_is_user_authenticated()) : ?>
        <?php echo $this->viewmode->render(); ?>
        <?php endif; ?>
    </div>
    
    <div class="spacer"></div>
    <hr />
</div>
<!-- End of BreadcrumbLine  -->
<?php endif; ?>

</div>
<!-- End of topBanner -->
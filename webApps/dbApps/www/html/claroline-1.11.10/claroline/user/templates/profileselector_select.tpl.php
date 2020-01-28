<!-- $Id: profileselector_select.tpl.php 13976 2012-01-31 12:38:22Z zefredz $ -->
<select name="profileId">

    <?php foreach ( $this->profileList->getProfileList() as $profile ): ?>
        
        <?php 
        
        if ( $this->ignoreNonMemberProfiles 
            && ( $profile->name == 'Guest' || $profile->name == 'Anonymous' ) ):
            continue;
        endif; 
        
        ?>

        <option 
            value="<?php echo $profile->id; ?>"<?php echo $profile->name == 'User' ? ' selected="selected"' : ''; ?>>
            <?php echo get_lang($profile->name); ?>
        </option>

    <?php endforeach; ?>

</select>

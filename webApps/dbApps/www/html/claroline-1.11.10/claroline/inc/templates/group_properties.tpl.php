<form method="post" action="group.php">

    <?php echo claro_form_relay_context(); ?>

    <table border="0" width="100%" cellspacing="0" cellpadding="4">
        <tr>
            <td valign="top">
                <b><?php echo get_lang("Registration"); ?></b>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <span class="item">
                    <input
                        type="checkbox"
                        name="self_registration"
                        id="self_registration"
                        value="1"
                    <?php if ($this->registrationAllowedInGroup) : ?>
                        checked="checked"
                    <?php endif; ?>
                     />
                    <label for="self_registration" >
                    <?php echo get_lang("Students are allowed to self-register in groups"); ?>
                    </label>
                </span>
            </td>
        </tr>

        <tr>
            <td valign="top">
                <span class="item">
                    <input
                        type="checkbox"
                        name="self_unregistration"
                        id="self_unregistration"
                        value="1"
                    <?php if ($this->unregistrationAllowedInGroup) : ?>
                        checked="checked"
                    <?php endif; ?>
                     />
                    <label for="self_unregistration" >
                    <?php echo get_lang("Students are allowed to unregister from their group(s)"); ?>
                    </label>
                </span>
            </td>
        </tr>
        
        <tr>
            <td valign="top">
                <b><?php echo get_lang("Tutors"); ?></b>
            </td>
        </tr>
        
        <tr>
            <td valign="top">
                <span class="item">
                    <input
                        type="checkbox"
                        name="tutor_registration"
                        id="tutor_registration"
                        value="1"
                    <?php if ($this->tutorRegistrationAllowedInGroup) : ?>
                        checked="checked"
                    <?php endif; ?>
                     />
                    <label for="tutor_registration" >
                    <?php echo get_lang("Tutors are allowed to register/unregister themselves in/from supervised groups"); ?>
                    </label>
                </span>
            </td>
        </tr>

    <?php if ( get_conf('multiGroupAllowed') ): ?>
        <?php

        if ( is_null( $this->nbGroupPerUser ) )
        {
            $nbGroupsPerUserShow = "ALL";
        }
        else
        {
            $nbGroupsPerUserShow = $this->nbGroupPerUser;
        }

        $selector_nb_groups = '<select name="limitNbGroupPerUser" >'."\n";

        for ( $i = 1; $i <= 10; $i++ )
        {
            $selector_nb_groups .=  '<option value="'.$i.'"'
            . ( $nbGroupsPerUserShow == $i ? ' selected="selected" ' : '')
            . '>' . $i. '</option>' ;
        }

        $selector_nb_groups .= '<option value="ALL" '
        . ($nbGroupsPerUserShow == "ALL" ? ' selected="selected" ' : '')
        .'>ALL</option>'. "\n"
        .'</select>'
        ;

        ?>

        <tr>
            <td valign="top">
                <b><?php echo get_lang("Limit"); ?></b>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <span class="item">
                <?php echo get_lang(
                    'A user can be a member of maximum %nb groups',
                    array ( '%nb' => $selector_nb_groups ) );
                ?>
                </span>
            </td>
        </tr>

    <?php endif; ?>

        <tr>
            <td>
                <b><?php echo get_lang("Access"); ?></b>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <span class="item">
                    <input
                        type="radio"
                        name="private"
                        id="private_1"
                        value="1"
                    <?php if ( $this->groupPrivate ) : ?>
                        checked="checked"
                    <?php endif; ?>
                     />
                    <label for="private_1"><?php echo get_lang("Private"); ?></label>
                    <input
                        type="radio"
                        name="private"
                        id="private_0"
                        value="0"
                    <?php if ( ! $this->groupPrivate ): ?>
                        checked="checked"
                    <?php endif; ?>
                     />
                    <label for="private_0"><?php echo get_lang("Public"); ?></label>
                </span>
            </td>
        </tr>

        <tr>
            <td valign="top">
                <b><?php echo get_lang("Tools"); ?></b>
            </td>
        </tr>

    <?php foreach ( $this->groupToolList as $groupTool ): ?>

        <?php
            if( !array_key_exists( $groupTool['label'], $this->tools ) ):
                continue;
            endif;
        ?>

        <tr>
            <td valign="top">
                <span class="item">
                    <input
                        type="checkbox"
                        name="<?php echo $groupTool['label']; ?>"
                        id="<?php echo $groupTool['label']; ?>"
                        value="1"
                <?php if (
                    isset( $this->tools[$groupTool['label']] )
                    && $this->tools[$groupTool['label']]): ?>
                        checked="checked"
                <?php endif; ?>
                     />
                    <label for="<?php echo $groupTool['label']; ?>">
                        <?php echo get_lang( claro_get_module_name ( $groupTool['label'] ) ); ?>
                    </label>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>

        <tr>
            <td valign="top">
                <input type="submit" name="properties" value="<?php echo get_lang("Ok"); ?>" />
                <?php echo claro_html_button(claro_htmlspecialchars( $_SERVER['HTTP_REFERER'] ), get_lang("Cancel")); ?>
            </td>
        </tr>
    </table>
</form>

<?php if ( !property_exists ( $this, 'displayTitle' ) || $this->displyaTitle == true ): ?>
<h3><?php echo get_lang('My groups'); ?></h3>
<?php endif; ?>
<?php if ( count( $this->myGroupList ) ): ?>
<table class="claroTable emphaseLine">
    <thead>
        <tr>
            <th><?php echo get_lang('Groups'); ?></th>
            <th><?php echo get_lang('Tutor'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $this->myGroupList as $myGroup ) : ?>
        <tr>
            <td>
                <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( 
                    get_module_url ( 'CLGRP' ) ) . '/group_space.php?gidReset=true&gidReq=' . $myGroup->id ); ?>">
                <?php echo claro_htmlspecialchars ( $myGroup->name ); ?>
                </a>
            </td>
            <td>
                <?php if ( $myGroup->hasTutor() ): ?>
                
                    <?php if ( $myGroup->tutorId != claro_get_current_user_id() ): ?>
                        
                        <?php $tutor = $myGroup->getTutor(); ?>

                        <a href="<?php echo claro_htmlspecialchars ( Url::Contextualize ( get_module_url( 'CLUSR' ) . '/userInfo.php?uInfo=' . $tutor->userId ) ); ?>">
                        <?php

                            echo get_lang( '%firstName %lastName', array( '%firstName' => $tutor->firstName, '%lastName' => $tutor-> lastName ) );
                        ?>
                        </a>

                        <?php if ( current_user_is_allowed_to_send_message_to_user( $tutor->id ) ): ?>
                        - 
                        <a 
                            href="<?php echo claro_htmlspecialchars(Url::Contextualize(
                                '../messaging/sendmessage.php?cmd=rqMessageToUser&amp;userId=' 
                                . (int)$tutor->id )); ?>">
                            <?php echo get_lang('Send a message'); ?>
                        </a>

                        <?php endif; // allowed to send message ?>
                        
                    <?php else : ?>
                        
                        <?php echo get_lang('my supervision'); ?>
                        
                    <?php endif; // tutor == current user?>
                    
                <?php else: ?>
                    
                    &nbsp;-&nbsp;
                    
                <?php endif; // has tutor ?>
                    
            </td>
        </tr>
        <?php if ( $myGroup->description ): ?>
        <tr>
            <td colspan="2">
                <div class="comment">
                    <?php echo claro_htmlspecialchars( $myGroup->description ); ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

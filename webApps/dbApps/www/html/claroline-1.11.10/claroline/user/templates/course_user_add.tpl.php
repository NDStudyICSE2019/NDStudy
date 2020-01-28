<!-- $Id: course_user_add.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" >
    <input type="hidden" name="cidReset" value="true" />
    <input type="hidden" name="cidReq" value="<?php echo claro_get_current_course_id() ?>" />
    <input type="hidden" id="cmd" name="cmd" value="registration" />
    <input type="hidden" id="claroFormId" name="claroFormId" value="<?php echo uniqid(''); ?>" />
    <input id="confirmUserCreate" name="confirmUserCreate" value="0" type="hidden" />

    <?php if (claro_is_user_authenticated() && isset($_SESSION['csrf_token'])): ?>
        <input id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" type="hidden" />
    <?php endif; ?>

    <fieldset>

        <legend><?php echo get_lang('Fill in the user information'); ?>&nbsp;:</legend>
        
        <fieldset>
            <legend>
                <?php echo get_lang('Personal informations'); ?>
            </legend>

            <dl>
                <dt>
                    <label for="lastname"><?php echo get_lang('Last name'); ?></label>
                    &nbsp;<span class="required">*</span>&nbsp;:
                </dt>
                <dd>
                    <input size="40" id="lastname" name="lastname" value="" type="text">
                </dd>

                <dt>
                    <label for="firstname"><?php echo get_lang('First name'); ?></label>
                    &nbsp;<span class="required">*</span>&nbsp;:
                </dt>

                <dd>
                    <input type="text" size="40" id="firstname" name="firstname" value="" />
                </dd>

                <?php if ( get_conf('ask_for_official_code') || ! get_conf( 'userOfficialCodeCanBeEmpty') ): ?>
                    <dt class="moreOptions">
                        <label for="officialCode"><?php echo get_lang('Administrative code'); ?></label>
                        <?php if (! get_conf( 'userOfficialCodeCanBeEmpty') ): ?>
                            &nbsp;<span class="required">*</span>
                        <?php endif; ?>
                        &nbsp;:
                    </dt>
                    <dd class="moreOptions">
                        <input type="text" size="40" id="officialCode" name="officialCode" value="" />
                    </dd>
                <?php endif; ?>
            </dl>
        </fieldset>
        
        <fieldset>
            <legend>
                <?php echo get_lang('User account'); ?>
            </legend>

            <dl>
                <dt class="moreOptions">
                    <label for="username"><?php echo get_lang('Username'); ?></label>
                    &nbsp;<span class="required">*</span>&nbsp;:
                </dt>
                <dd class="moreOptions">
                    <input type="text" size="40" id="username" name="username" value="" />
                </dd>

                <dt>
                    <label for="password"><?php echo get_lang('Password'); ?></label>
                    &nbsp;<span class="required">*</span>&nbsp;:
                </dt>
                <dd>
                    <input size="40" id="password" name="password" autocomplete="off" type="password" >
                </dd>

                <dt>
                    <label for="password_conf"><?php echo get_lang('Password'); ?> 
                    <small>(<?php echo get_lang('Confirmation'); ?>)</small>
                    &nbsp;<span class="required">*</span>&nbsp;:
                </label>
                </dt>
                <dd>
                    <input size="40" id="password_conf" name="password_conf" type="password">
                </dd>
            </dl>
        </fieldset>
        
        <fieldset>
            <legend>
                <?php echo get_lang('Contact information'); ?>
            </legend>

            <dl>
            
                <dt class="moreOptions">
                    <label for="email">
                    <?php echo get_lang('Email'); ?></label>
                    <?php if ( ! get_conf('userMailCanBeEmpty') ): ?>
                        &nbsp;<span class="required">*</span>
                    <?php endif; ?>
                    &nbsp;:
                </dt>
                <dd class="moreOptions">
                    <input type="text" size="40" id="email" name="email" value="" />
                </dd>
                <dt>&nbsp;</dt>
                <dd><small><?php echo get_lang('If an email address is provided, the user will receive an email confirmation with his (or her) login and password'); ?></small></dd>

                <dt>
                    <label for="phone"><?php echo get_lang('Phone'); ?></label>&nbsp;:
                </dt>
                <dd>
                    <input size="40" id="phone" name="phone" value="" type="text">
                </dd>

                </dd>
            </dl>
        </fieldset>
        
        <fieldset>
            <legend>
                <?php echo get_lang('Course permissions'); ?>
            </legend>

            <dl>
            
                <dt>
                    <?php echo get_lang('Profile'); ?>&nbsp;:
                </dt>
                <dd>
                    <?php echo $this->profileSelector->render(); ?>
                </dd>

                <dt>
                    <?php echo get_lang('Group Tutor'); ?>&nbsp;:
                </dt>
                <dd>
                    <input name="courseTutor" value="1" id="tutorYes" type="radio">
                    <label for="tutorYes"><?php echo get_lang('Yes'); ?></label>
                    <br />
                    <input name="courseTutor" value="0" id="tutorNo" checked="checked" type="radio">
                    <label for="tutorNo"><?php echo get_lang('No'); ?></label>
                </dd>
            </dl>
        </fieldset>

            <dl>
                <dt>
                    &nbsp;
                </dt>
                <dd>
                    <input name="applyChange" id="applyChange" value="<?php echo get_lang('Create'); ?>" type="submit">
                    &nbsp;
                    <a href="<?php echo claro_htmlspecialchars(Url::Contextualize(get_module_url('CLUSR'))); ?>">
                    <input type="button" value="<?php echo get_lang('Cancel'); ?>" />
                    </a>
                </dd>

                <dt>
                    &nbsp;
                </dt>
                <dd>
                    <small><?php echo get_lang('<span class="required">*</span> denotes required field'); ?></small>
                </dd>
            </dl>
        
    </fieldset>
</form>

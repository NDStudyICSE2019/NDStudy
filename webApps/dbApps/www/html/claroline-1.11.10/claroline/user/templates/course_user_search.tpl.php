<!-- $Id: course_user_search.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" >
    <?php echo claro_form_relay_context(); ?>
    <input type="hidden" id="cmd" name="cmd" value="applySearch" />
    <fieldset>
        <legend><?php echo get_lang('Fill in one or more search criteria and press \'Search\''); ?></legend>
        <dl>
            <dt>
                <label for="lastname"><?php echo get_lang('Last name'); ?></label>&nbsp;:
            </dt>
            <dd>
                <input type="text" size="40" id="lastname" name="lastname" value="" />
            </dd>
            
            <dt>
                <label for="firstname"><?php echo get_lang('First name'); ?></label>&nbsp;:
            </dt>
            <dd>
                <input type="text" size="40" id="firstname" name="firstname" value="" />
            </dd>

            <?php if (get_conf('ask_for_official_code')): ?>
                <dt class="moreOptions">
                <label for="officialCode"><?php echo get_lang('Administrative code'); ?></label>&nbsp;:
                </dt>
                <dd class="moreOptions">
                    <input type="text" size="40" id="officialCode" name="officialCode" value="" />
                </dd>
            <?php endif; ?>

            <dt class="moreOptions">
                <label for="username"><?php echo get_lang('Username'); ?></label>&nbsp;:
            </dt>
            <dd class="moreOptions">
                <input type="text" size="40" id="username" name="username" value="" />
            </dd>

            <dt class="moreOptions">
                <label for="email"><?php echo get_lang('Email'); ?></label>&nbsp;:
            </dt>
            <dd class="moreOptions">
                <input type="text" size="40" id="email" name="email" value="" />
            </dd>
            <dt>
                &nbsp;
            </dt>
            <dd>
                <input type="submit" name="applySearch" id="applySearch" value="<?php echo get_lang("Search"); ?>" />
                &nbsp;
                <a href="<?php echo claro_htmlspecialchars(Url::Contextualize(get_module_url('CLUSR'))); ?>">
                <input type="button" value="<?php echo get_lang('Cancel'); ?>" />
                </a>
            </dd>
        </dl>
    </fieldset>
</form>

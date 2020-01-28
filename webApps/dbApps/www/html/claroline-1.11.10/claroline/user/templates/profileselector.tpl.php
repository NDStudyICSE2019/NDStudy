<!-- $Id: profileselector.tpl.php 13968 2012-01-31 09:37:01Z zefredz $ -->
<form method="post" action="<?php echo $this->baseUrl; ?>" enctype="multipart/form-data">
    <?php echo claro_form_relay_context() ?>
    <input type="hidden" id="cmd" name="cmd" value="<?php echo $this->command; ?>" />
    <input type="hidden" name="claroFormId" value="<?php echo uniqid(''); ?>" />
    
    <?php if (claro_is_user_authenticated()) : ?>
        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
    <?php endif; ?>
    
    <?php if (!empty($this->userId)) : ?>
        <input type="hidden" name="userId" value="<?php echo $this->userId; ?>" />
    <?php endif; ?>
    
    <?php include dirname(__FILE__) . '/profileselector_select.tpl.php'; ?>
    
    <input type="submit" name="submit" value="<?php echo get_lang('Enroll'); ?>" />
</form>

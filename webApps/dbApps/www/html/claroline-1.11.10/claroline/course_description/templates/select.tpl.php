<!-- $Id: select.tpl.php 13153 2011-05-11 15:44:00Z abourguignon $ -->

<form method="post" action="<?php echo $this->formAction; ?>">
    <fieldset>
        <?php echo $this->relayContext ?>
        <input type="hidden" name="cmd" value="rqEdit" />
        
        <select name="category">
            <?php echo $this->optionsList; ?>
            <option value="-1"><?php echo get_lang("Other"); ?></option>
        </select>
        
        <input type="submit" name="add" value="<?php echo get_lang('Add'); ?>" />
    </fieldset>
</form>
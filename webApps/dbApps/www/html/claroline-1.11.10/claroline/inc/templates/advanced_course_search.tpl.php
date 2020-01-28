<!-- $Id: advanced_course_search.tpl.php 14314 2012-11-07 09:09:19Z zefredz $ -->

<form action="admin_courses.php" method="get">
<fieldset>

<dl>

<dt><label for="code"><?php echo get_lang('Administrative code'); ?></label></dt>
<dd><input type="text" size="40" name="code" id="code" value="<?php echo claro_htmlspecialchars($this->code); ?>"/></dd>

<dt><label for="intitule"><?php echo get_lang('Course title')?></label></dt>
<dd><input type="text" size="40" name="intitule"  id="intitule" value="<?php echo claro_htmlspecialchars($this->intitule); ?>"/></dd>

<dt><label for="searchLang"><?php echo get_lang('Language')?></label></dt>
<dd>
    <?php
    echo claro_html_form_select(
        'searchLang',
        $this->language_list,
        '',
        array('id'=>'searchLang')
    );
    ?>
</dd>

<dt><?php echo get_lang('Course access') ?></dt>
<dd>
    <table>
        <tr>
            <td width="150px">
                <input type="radio" name="access" value="public"  id="access_public"  <?php if ($this->access=="public") echo 'checked="checked"';?>  />
                <label for="access_public"><?php echo get_lang('Public') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="access" value="platform" id="access_platform" <?php if ($this->access=="platform") echo 'checked="checked"';?> />
                <label for="access_platform"><?php echo get_lang('Platform') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="access" value="private" id="access_private" <?php if ($this->access=="private") echo 'checked="checked"';?> />
                <label for="access_private"><?php echo get_lang('Private') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="access" value="all"        id="access_all"     <?php if ($this->access=="all") echo 'checked="checked"';?> />
                <label for="access_all"><?php echo get_lang('All') ?></label>
            </td>
        </tr>
    </table>
</dd>

<dt><?php echo get_lang('Enrolment') ?></dt>
<dd>
    <table>
        <tr>
            <td width="150px">
                <input type="radio" name="subscription" value="allowed" id="subscription_allowed" <?php if ($this->subscription=="allowed") echo 'checked="checked"';?> />
                <label for="subscription_allowed"><?php echo get_lang('Allowed') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="subscription" value="key"  id="subscription_key" <?php if ($this->subscription=="key") echo 'checked="checked"';?> />
                <label for="subscription_key"><?php echo get_lang('Allowed with enrolment key') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="subscription" value="denied"  id="subscription_denied" <?php if ($this->subscription=="denied") echo 'checked="checked"';?> />
                <label for="subscription_denied"><?php echo get_lang('Denied') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="subscription" value="all"  id="subscription_all" <?php if ($this->subscription=="all") echo 'checked="checked"';?> />
                <label for="subscription_all"><?php echo get_lang('All') ?></label>
            </td>
        </tr>
    </table>
</dd>

<dt><?php echo get_lang('Visibility') ?></dt>
<dd>
    <table>
        <tr>
            <td width="150px">
                <input type="radio" name="visibility" value="visible" id="visibility_show" <?php if ($this->visibility=="visible") echo 'checked="checked"';?> />
                <label for="visibility_show"><?php echo get_lang('Show') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="visibility" value="invisible"  id="visibility_hidden" <?php if ($this->visibility=="invisible") echo 'checked="checked"';?> />
                <label for="visibility_hidden"><?php echo get_lang('Hidden') ?></label>
            </td>
            <td width="150px">
                <input type="radio" name="visibility" value="all"  id="visibility_all" <?php if ($this->visibility == "all") echo 'checked="checked"';?> />
                <label for="visibility_all"><?php echo get_lang('All') ?></label>
            </td>
        </tr>
    </table>
</dd>

</dl>

<input type="submit" class="claroButton" value="<?php echo get_lang('Search course')?>"  />

</fieldset>
</form>
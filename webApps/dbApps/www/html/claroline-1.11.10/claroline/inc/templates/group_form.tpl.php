<!-- $Id: group_form.tpl.php 12963 2011-03-14 11:17:01Z abourguignon $ -->

<form  class="msform" name="groupedit" method="post" action="<?php echo $this->formAction; ?>">
    <?php echo $this->relayContext; ?>
    <fieldset>
        
        <dl>
            
            <!-- Group name -->
            <dt>
                <label for="name"><?php echo get_lang("Group name"); ?></label>
                <span class="required">*</span>
            </dt>
            <dd>
                <input type="text" name="name" id="name" size="40" value="<?php echo $this->groupName; ?>" />
                <a href="group_space.php?gidReq=<?php echo $this->groupId; ?>">
                    <img src="<?php echo get_icon_url('group'); ?>" alt="" />
                    &nbsp;<?php echo get_lang("Area for this group"); ?>
                </a>
            </dd>
            
            <!-- Group description -->
            <dt>
                <label for="description"><?php echo get_lang("Description"); ?></label>
            </dt>
            <dd>
                <textarea name="description" id="description" rows="4 "cols="70" ><?php echo $this->groupDescription; ?></textarea>
            </dd>
            
            <!-- Group tutor -->
            <dt>
                <label for="tutor"><?php echo get_lang("Group Tutor"); ?></label>
            </dt>
            <dd>
                <?php echo claro_html_form_select('tutor', $this->tutorList, $this->groupTutorId, array('id'=>'tutor')); ?>
                &nbsp;&nbsp;
                (<a href="../user/user.php?gidReset=true"><?php echo get_lang("View user list"); ?></a>)
            </dd>
            
            <!-- Maximum number of seats -->
            <dt>
                <label for="maxMember"><?php echo get_lang("Seats"); ?></label>
            </dt>
            <dd>
                <label for="maxMember"><?php echo get_lang("Max."); ?></label>
                <input type="text" name="maxMember" id="maxMember" size="2" value="<?php echo $this->groupUserLimit; ?>" />
            </dd>
        
            <!-- Group members -->
            <dt>
                <label for="ingroup"><?php echo get_lang("Group members"); ?></label>
            </dt>
            <dd>
                <table class="multiselect">
                  <tr>
                    <td>
                        <label for="mslist1">
                            <?php echo get_lang("Users in group"); ?>
                        </label>
                        <br />
                        <select multiple="multiple" name="ingroup[]" id="mslist1" size="10">
                            <?php echo $this->usersInGroupListHtml; ?>
                        </select>
                    </td>
                        <td class="arrows">
                        <a href="#" class="msadd"><img src="<?php echo get_icon_url('go_right'); ?>" /></a>
                        <br /><br />
                        <a href="#" class="msremove"><img src="<?php echo get_icon_url('go_left'); ?>" /></a>
                    </td>
                    
                    <td>
                        <label for="mslist2">
                            <?php echo (get_conf('multiGroupAllowed') ? (get_lang("Users not in this group")) : (get_lang("Unassigned students"))); ?>
                        </label>
                        <br />
                        <select multiple="multiple" name="nogroup[]" id="mslist2" size="10">
                            <?php echo $this->userNotInGroupListHtml; ?>
                        </select>
                    </td>
                  </tr>
                </table>
            </dd>
            <dt>
                <input value="Ok" name="modify" type="submit" />
            </dt>
            
        </dl>
        
    </fieldset>
</form>

<p class="notice">
    <?php echo get_lang('<span class="required">*</span> denotes required field'); ?>
</p>
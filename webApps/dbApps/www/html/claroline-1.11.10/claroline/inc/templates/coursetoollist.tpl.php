<!-- $Id: coursetoollist.tpl.php 13569 2011-09-09 12:42:41Z zefredz $ -->

    <div id="courseToolListBlock">
    <?php
        if (is_array($this->toolLinkList)):
            echo claro_html_list($this->toolLinkList);
        endif;
    ?>

    <?php if (claro_is_user_authenticated() && !empty($this->otherToolsList)) : ?>
    
    <br />
    
    <ul>
        <?php foreach ($this->otherToolsList as $otherTool) : ?>
        
        <li><?php echo $otherTool; ?></li>
        
        <?php endforeach; ?>
    </ul>
    
    <?php endif; ?>

    <br />

    <?php
        if ( claro_is_course_manager() || claro_is_platform_admin() ) :
            echo claro_html_list($this->courseManageToolLinkList,  array('id'=>'courseManageToolList'));
        endif;
    ?>
    </div>
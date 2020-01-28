<!-- $Id: course_tree_node_anonymous.tpl.php 13931 2012-01-12 13:28:22Z abourguignon $ -->

<dt>
    <?php echo get_lang('Orphan session courses (without source course)'); ?>
</dt>

<dd>
    
    <?php if ( $this->node->hasChildren() ) : ?>
    
    <dl>
        
    <?php foreach ( $this->node->getChildren() as $childNode ) : ?>
        
        <?php
            $childNodeView = new CourseTreeNodeView(
                $childNode, 
                $this->courseUserPrivilegesList,
                $this->notifiedCourseList,
                $this->viewOptions);
            
            echo $childNodeView->render();
        ?>
    
    <?php endforeach; ?>
        
    </dl>
    
    <?php endif; ?>
    
</dd>

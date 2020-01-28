<!-- $Id: categorybrowser.tpl.php 13920 2012-01-06 18:31:59Z abourguignon $ -->

<!-- CURRENT CATEGORY (default: root category) -->
<?php if ($this->categoryBrowser->categoryId > 0) : ?>
<h3 id="categoryContent"><?php echo $this->currentCategory->name; ?></h3>

<p>
    <a class="backLink" href="<?php echo Url::buildUrl(
        $this->navigationUrl,
        array('categoryId' => $this->currentCategory->idParent),
        null)->toUrl(); ?>">
        <?php echo get_lang('Back to parent category'); ?>
    </a>
</p>

<?php else : ?>
<h3><?php echo get_lang('Root category'); ?></h3>

<?php endif; ?>



<!-- SUB CATEGORIES (with link to go deeper when possible) -->
<?php if ( count($this->categoryList) - 1 >= 0 ) : ?>

<h4><?php echo get_lang('Sub categories'); ?></h4>

<ul>
<?php foreach( $this->categoryList as $category ) : ?>

    <?php if (claroCategory::countAllCourses($category['id']) + claroCategory::countAllSubCategories($category['id']) > 0) : ?>
    <li>
        <a href="<?php echo Url::buildUrl(
            $this->navigationUrl,
            array('categoryId' => $category['id']),
            null)->toUrl(); ?>">
            <?php echo $category['name']; ?>
        </a>
    </li>
    
    <?php else : ?>
    <li><?php echo $category['name']; ?></li>
    
    <?php endif; ?>
    
<?php endforeach; ?>
</ul>

<?php endif; ?>



<!-- COURSES (belonging to the current category) -->
<h4><?php echo get_lang( 'Courses in this category' ); ?></h4>

<?php echo $this->courseTreeView->render(); ?>


<?php if ($this->categoryBrowser->categoryId > 0) : ?>
<p>
    <a class="backLink" href="<?php echo Url::buildUrl(
        $this->navigationUrl,
        array('categoryId' => $this->currentCategory->idParent),
        null)->toUrl(); ?>">
        <?php echo get_lang('Back to parent category'); ?>
    </a>
</p>
<?php endif; ?>

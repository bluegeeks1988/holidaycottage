<?php get_header()?>
    <?php
        $id = 1156932;
        $content = get_field('page_sections', $id)[0];

        if (isset($content['section']['image']['url'])) {
            $image_url = $content['section']['image'];
        }

        $content['image'] = $image_url;
    ?>

    <?php 
        get_html('sections/section.hero.php', [
            'content' => $content,
            'classes' => ['style-1']
        ]); 
    ?>

    <?php 
        //get_html('components/aside.modal.php', [
            //'content' => $content
       // ]); 
    ?>
    
    <?php get_html('sections/section.properties-archive.php'); ?>

    <?php 
        include_once('flexible-content.php');
    ?>
<?php get_footer(); ?>
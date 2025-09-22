<?php get_header('', ['classes' => ['style-3']]); ?>

    <?php
      $id = get_the_ID();

        $content = [
            'id' => $id,
            'heading' => get_field('sykes_property_name', $id),
            'description' => get_field('sykes_short_description', $id),
            'long_description' => get_field('sykes_long_description', $id),
			'accommodation_details' => get_field('sykes_accommodation_details', $id),
			'amenities_description' => get_field('sykes_amenities_description', $id),
            'region' => get_field('sykes_property_region', $id),
            'rating' => highest_rating($id),
            'property_type' => get_field('sykes_property_type', $id),
			'sleeps' => get_field('sykes_capacity', $id),
            'bathrooms' => get_field('sykes_property_bathrooms', $id),
            'bedrooms' => get_field('sykes_property_bedrooms', $id),
            'price' => get_field('sykes_property_lowest_price', $id),
            'available' => get_field('available', $id),
            'images' => get_image_urls_array(get_field('sykes_property_images_resized', $id)),
            'link' => get_field('sykes_property_link', $id),
            'awin_link' => get_field('awin_property_link', $id),
            'lat' => floatval(get_field('sykes_property_latitude', $id)),
            'lng' => floatval(get_field('sykes_property_longitude', $id))
        ];
    ?>

    <?php 
        get_html('components/aside.modal.php', [
            'content' => $content
        ]); 
    ?>

    <?php 
        get_html('sections/section.property-single.php', [
            'content' => $content
        ]); 
    ?>

    <?php 
        // Get current property region
        $current_region = get_field('sykes_property_region', $id);
        
        // Query 10 random properties in the same region
        $args = [
            'post_type' => 'properties', // change if your post type name differs
            'posts_per_page' => 10,
            'orderby' => 'rand',
            'post__not_in' => [$id], // exclude current property
            'meta_query' => [
                [
                    'key' => 'sykes_property_region',
                    'value' => $current_region,
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        // Extract IDs
        $related_property_ids = wp_list_pluck($query->posts, 'ID');
        
        // Add to content array
        $content['heading'] = 'Suggested Cottages';
        $content['description'] = '';
        $content['cottages'] = $related_property_ids;
        
        get_html('sections/section.cottages.php', [
            'content' => $content
        ]); 
    ?>

    <?php
      $id = get_the_ID();

      $newsletter = get_field('newsletter', 'option'); 
    ?>

    <?php get_html('sections/section.newsletter.php', [
        'content' => $newsletter,
        'classes' => ['style-1']
    ]); ?>

<?php get_footer(); ?>
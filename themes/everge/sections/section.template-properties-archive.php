<?php
    $latitude = $content['latitude']; 
    $longitude = $content['longitude']; 
    $distance = 20; 
    
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $ppp = get_option('posts_per_page'); 

    $query = get_properties_by_location($latitude, $longitude, $distance, $ppp, $paged);

    $total = $query->found_posts;
    $max = ceil($total / $ppp);
?>
<section class="properties-archive pt-3">
    <div class="overlay"></div>
	
    <div class="container w-1800">
        <section>
            <div class="main">
                <?php if(isset($content['description']) && $content['description']): ?>
                    <article class="accordion information mb-4 mt-3">
                        <div class="heading flex items-center justify-space-between">
                            <?php if($content['title']): ?>
                                <div class="label-12-14 pb-3">
                                    Read About <?php echo $content['title']; ?>
                                </div>
                            <?php endif; ?>
                            <span class="label ml-4 py-2 px-3 label-11-12 radius-10 background-white mb-3 border-28332C">Read More</span>
                        </div>
                        <div class="content">
                            <?php if($content['description']): ?>
                                <div class="description paragraph-14-18 information pt-3">
                                    <?php echo $content['description']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endif; ?>

                <div class="heading-20-26 py-3 colour">
                    <?php if (get_the_title()): ?>
                        Your search returned <span class="weight-600"><?php echo number_format($total); ?></span> cottages in <?php echo $content['title']; ?>
                    <?php endif; ?>
                </div>

                <?php if (have_posts()) : ?>
                    <div class="grid">
                        <?php 
                        $locations = [];
                        while ($query->have_posts()) : $query->the_post();
                            $id = get_the_ID();
                            $content = [
                                'id' => $id,
                                'heading' => get_field('sykes_property_name', $id),
                                'description' => get_field('sykes_short_description', $id),
                                'region' => get_field('sykes_property_region', $id),
                                'rating' => highest_rating($id),
                                'bathrooms' => get_field('sykes_property_bathrooms', $id),
                                'bedrooms' => get_field('sykes_property_bedrooms', $id),
								'sleeps' => get_field('sykes_capacity', $id),
                                'price' => get_field('sykes_property_lowest_price', $id),
                                'images' => get_image_urls_array(get_field('sykes_property_images_resized', $id)),
                                'link' => set_affiliate_link(get_field('sykes_property_link', $id), false),
                                'lat' => floatval(get_field('sykes_property_latitude', $id)),
                                'lng' => floatval(get_field('sykes_property_longitude', $id))
                            ];

                            // Capture location data
                            $locations[] = [
                                'id' => $id,
                                'lat' => $content['lat'],
                                'lng' => $content['lng'],
                                'price' => $content['price'],
                                'title' => $content['heading']
                            ];
                            ?>

                            <div class="column">
                                <?php get_html('components/article.card.php', [
                                    'content' => $content,
                                    'classes' => ['radius-10']
                                ]); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php
                    get_html('components/div.pagination.php', [
                        'classes' => ['mt-4', 'mb-5']
                    ]);
                    ?>

                <?php else: ?>
                    <p>No properties found</p>
                <?php endif; ?>
            </div>

            <aside>
                <div id="map"></div>
                <div id="property-information">
                    <span class="close-map p-3 label-11-12 weight-500 radius-10 background-white flex-inline justify-center items-center">
                        <span class="mr-1"><?php echo get_svg('close'); ?></span>
                        Close
                    </span>
                    <?php get_html('components/article.card-placeholder.php', [
                        'classes' => ['radius-10']
                    ]);
                    ?>
                </div>
            </aside>
        </section>
    </div>
    <?php if ($query->have_posts()) : ?>
        <aside class="pagination">
            <?php
                get_html('components/nav.pagination-manual.php', [
                    'paged' => $paged,
                    'max' => $max,
                ]); 
            ?>
        </div>
    <?php endif; ?>
</section>


<script>
    var locations = <?php echo json_encode($locations); ?>;
</script>
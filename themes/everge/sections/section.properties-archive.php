<section class="properties-archive pt-3">
    <div class="overlay"></div>
	
    <div class="container w-1800">
        <?php
            global $wp_query;
            $post_count = $wp_query->found_posts;

            $destination = isset($_GET['destination']) ? sanitize_text_field($_GET['destination']) : '';
            $latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : 0.0;
            $longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : 0.0;
        ?>

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
                    <?php if($destination): ?>
                        Found <?php echo number_format($post_count); ?> places in <?php echo esc_html($destination); ?>
                    <?php elseif(is_tax('regions')): ?>
                        Found <?php echo number_format($post_count); ?> <?php single_cat_title(); ?>
                    <?php elseif(is_tax('towns')): ?>
                        Found <?php echo number_format($post_count); ?> cottages in <?php single_cat_title(); ?>
                    <?php else: ?>
                        Found <?php echo number_format($post_count); ?> cottages in UK
                    <?php endif; ?>
                </div>

                <?php if (have_posts()) : ?>
                    <div class="grid">
                        <?php 
                        $locations = [];
                        while (have_posts()) : the_post();
                            $id = get_the_ID();
                            $content = [
                                'id' => $id,
                                'heading' => get_field('sykes_property_name', $id),
                                'description' => get_field('sykes_short_description', $id),
                                'region' => get_field('sykes_property_region', $id),
                                'region' => get_field('sykes_property_region', $id),
                                'area' => get_field('sykes_property_area', $id),
                                'rating' => highest_rating($id),
                                'bathrooms' => get_field('sykes_property_bathrooms', $id),
                                'bedrooms' => get_field('sykes_property_bedrooms', $id),
								'sleeps' => get_field('sykes_capacity', $id),
                                'price' => get_field('sykes_property_lowest_price', $id),
                                'images' => get_image_urls_array(get_field('sykes_property_images_resized', $id)),
                                'link' => set_affiliate_link(get_field('sykes_property_link', $id), false),
                                'property_link' => get_permalink($id),
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
</section>

<script>
    var locations = <?php echo json_encode($locations); ?>;
</script>

<section class="property-single">
    <div class="container w-1615">
        <div class="back flex items-center mt-4">
            <a class="flex-inline items-center justify-center weight-500" href="javascript:void(0);" onclick="window.history.back();">
                <span class="flex justify-center items-center mr-2 radius-500">
                    <?php echo get_svg('arrow-left'); ?>
                </span>
                Go back to search results
            </a>
        </div>

        <?php if($content['heading']): ?>
            <h2 class="heading-32-77 pt-4"><?php echo $content['heading']; ?></h2>
        <?php endif; ?>

        <div class="main my-4">
            <div class="slider">
                <div class="images radius-5 mb-20">
                    <?php if(isset($content['images']) && $content['images']): ?>
                        <div class="slider">
                            <div class="main-carousel">
                                <?php foreach($content['images'] as $image): ?>
                                    <div class="carousel-cell">
                                        <figure class="image">
                                            <img src="<?php echo $image; ?>" alt="<?php echo $content['heading']; ?>">
                                        </figure>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-center flex-column">
                    <div id="favouriteButton" data-id="<?php echo $content['id']; ?>" class="icon white favourite radius-500 flex-inline items-center justify-center mt-3">
                        <?php echo get_svg('favourite'); ?>
                    </div>
                    <span class="heading-18-23 mt-2">
                        <a href="/favourites/">Add to favourites</a>
                    </span>
					
				<h3 class="label-12-14 mt-5 mb-3 line medium">Search More Cottages</h3>
                <?php
                    get_html('components/div.homepage-search.php', [
                        'classes' => [
                            'mb-4', 
                            'mt-2', 
                            'radius-15', 
                        ]
                    ]);
                ?>
                </div>
            </div>
            <div class="content">
                <?php if($content['region']): ?>
                    <div class="flex paragraph-14-16 items-center weight-500">
                        <?php echo get_svg('location'); ?>
                        <span class="ml-2"><?php echo $content['region']; ?></span>
                    </div>
                <?php endif; ?>

                <div class="meta mt-3 flex-wrap">
                    <?php if($content['bedrooms'] || $content['bathrooms'] || $content['sleeps'] || $content['property_type']): ?>
                        <ul class="flex-inline flex-wrap label-11-12">
						
							<?php if($content['sleeps']): ?>
                                <li class="background-white 
                                        radius-10 
                                        p-2 
                                        m-1
                                        min-h-45
                                        flex-inline 
                                        items-center">

                                    <?php echo get_svg('bed'); ?> <span class="medium ml-2 nowrap"><?php echo $content['sleeps']; ?> Guests</span>
                                </li>
                            <?php endif; ?>
                            <?php if($content['bedrooms']): ?>
                                <li class="background-white 
                                        radius-10 
                                        px-3 
                                        m-1 
                                        min-h-45
                                        flex-inline 
                                        items-center">

                                    <?php echo get_svg('bed'); ?> <span class="medium ml-2 nowrap"><?php echo $content['bedrooms']; ?> Bedrooms</span>
                                </li>
                            <?php endif; ?>

                            <?php if($content['bathrooms']): ?>
                                <li class="background-white 
                                        radius-10 
                                        p-2 
                                        m-1
                                        min-h-45
                                        flex-inline 
                                        items-center">
                                    <?php echo get_svg('bath'); ?> <span class="medium ml-2 nowrap"><?php echo $content['bathrooms']; ?> Bathrooms</span>
                                </li>
                            <?php endif; ?>

                            <?php if($content['property_type']): ?>
                                <li class="background-white 
                                        radius-10 
                                        p-2 
                                        m-1 
                                        min-h-45
                                        flex-inline 
                                        items-center">
                                    <?php echo get_svg('home'); ?> <span class="medium ml-2 nowrap"><?php echo $content['property_type']; ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if(isset($content['rating']) && $content['rating']): ?>
                        <li class="background-white 
                                    radius-10 
                                    p-2 d
                                    min-h-45
                                    flex-inline 
                                    items-center
                                    label-11-12">
                                <?php echo $content['rating']['label']; ?>
                                <span class="flex-inline mx-2 rating"><?php echo get_svg('star'); ?></span>
                                <?php echo $content['rating']['rating']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if($content['description']): ?>
                    <div class="paragraph-14-18 mt-4">
                        <?php echo wp_trim_words( $content['description'], 500, '...' ); ?>
						<div style="clear:both;">&nbsp;</div>
						<a href="#accommodation-details" id="more-info" class="button light-green radius-10">Read More</a>
                    </div>
                <?php endif; ?>

                <div class="flex mt-4 justify-end items-end">
                    <?php if($content['price']): ?>
                        <div>
                            <span class="paragraph-14-18 opacity-75">3 nights from</span>
                            <span class="heading-20-32 weight-600">£<?php echo number_format( $content['price'], 0, '.', ',' ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book mt-2 flex justify-end items-end">
                    <?php if($content['link']): ?>
                        <a onclick="gtag_report_conversion('<?php echo set_affiliate_link(get_field('sykes_property_link', $id), false); ?>');" href="<?php echo set_affiliate_link(get_field('sykes_property_link', $id), false); ?>" id="book-now" class="button light-green radius-10">Book Now</a>
                    <?php else: ?>
                        <a onclick="gtag_report_conversion('<?php echo $content['awin_link']; ?>');" href="<?php echo $content['awin_link']; ?>" id="book-now" class="button light-green radius-10">Book Now</a>
                    <?php endif; ?>
                </div>
                
                <?php $terms = wp_get_post_terms($content['id'], 'property_features'); ?>
                <?php if (!empty($terms) && !is_wp_error($terms)): ?>
                    <h3 class="label-12-14 line mt-5 medium">Features</h3>
                    <div class="features">
                        <div class="columns mt-4">
                            <?php foreach ($terms as $term): ?>
                                <div class="column flex flex-column items-center mx-2" data-slug="<?php echo $term->slug; ?>">
                                    <?php 
                                        $icon = get_field('icon', $term); 
                                    ?>
                                    <?php if(isset($icon) && $icon): ?>
                                        <div class="icon faded-green radius-500 flex-inline items-center justify-center">
                                            <img src="<?php echo $icon['url']; ?>" alt="<?php echo $icon['alt']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <span class="flex-inline label-10 mt-2">
                                        <?php echo $term->name; ?>
                                    </span>
                                    <input type="checkbox" name="filter[]" value="<?php echo $term->slug; ?>"  style="display: none;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif;?>
				
				<!-- Tabs for Accommodation Details and Amenities -->
				<h3 id="accommodation-details" class="label-12-14 line mt-5 medium">Accommodation Details</h3>
				<?php if($content['accommodation_details']): ?>
				<div class="columns mt-4">
				<div class="paragraph-14-18 accommodation-details">
						<?php echo wp_kses_post($content['accommodation_details']); ?>
				</div>
				</div>
				<?php endif; ?>

                <h3 class="label-12-14 line mt-5 medium ">More Information</h3>
                <?php if($content['long_description']): ?>
                    <div class="paragraph-14-18 mt-4">
                        <?php echo $content['long_description']; ?>
                    </div>
                <?php endif; ?>

                <?php if($content['lat'] || $content['lng']): ?>
                    <h3 class="label-12-14 mt-5 line medium">Location</h3>

                    <div id="single-map" class="mt-4" data-lat="<?php echo $content['lat']; ?>" data-lng="<?php echo $content['lng']; ?>"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Optional styling (if not handled globally) -->
<style>
    .tab-button {
        padding: 8px 16px;
        border: none;
        background: #f2f2f2;
        cursor: pointer;
        margin-right: 10px;
        border-radius: 8px;
        font-weight: 500;
    }

    .tab-button.active {
        background-color: #a7dfc3;
        color: #000;
    }

    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
	.accommodation-details p::before {
    content: "✔";
    color: #28a745; /* Green tick */
    margin-right: 10px;
    font-weight: bold;
}
</style>
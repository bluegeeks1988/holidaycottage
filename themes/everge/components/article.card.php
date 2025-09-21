<article class="card <?php if ( isset($classes) ) echo implode( ' ', $classes ); ?>">
    <?php if(isset($content['images']) && $content['images']): ?>
        <div class="slider">
            <div class="main-carousel">
            <?php
                $count = 0;
                foreach ($content['images'] as $image):
                    if ($count >= 3) break;
                ?>
                    <div class="carousel-cell">
                        <figure class="image">
                            <img src="<?php echo $image; ?>" alt="<?php echo $content['heading']; ?>">
                        </figure>
                    </div>
                <?php
                    $count++;
                endforeach;
                ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="content">
        <div>
            <div class="meta 
                        flex 
                        items-center 
                        justify-space-between 
                        label-14">

                <?php if($content['region']): ?>
                    <div class="flex label-12-14 items-center weight-500">
                        <?php echo get_svg('location'); ?>
                        <span class="ml-2"><?php echo $content['region']; ?>, <?php echo $content['area']; ?></span>
                    </div>
                <?php endif; ?>

                <?php if(isset($content['rating']) && $content['rating']): ?>
                    <div class="flex items-center">
                        <?php echo get_svg('star'); ?>
                        <span class="ml-1"><?php echo $content['rating']['rating']; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($content['heading']): ?>
                <h2 class="mt-4 heading-20-27"><?php echo $content['heading']; ?></h2>
            <?php endif; ?>

            <?php if($content['description']): ?>
                <div class="mt-3 paragraph-14-16">
                    <?php echo wp_trim_words( $content['description'], 8, '...' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php if($content['bedrooms'] || $content['bathrooms'] || $content['sleeps']): ?>
                <ul class="mt-4 flex-inline flex-wrap paragraph-14-16 weight-500">
				    <?php if($content['sleeps']): ?>
                        <li class="bordered-dark 
                                   radius-10 
                                   px-3 
                                   m-1 
                                   min-h-45
                                   flex-inline 
                                   items-center">

                            <?php echo get_svg('bed'); ?> <span class="medium ml-2 nowrap"><?php echo $content['bedrooms']; ?> Guests</span>
                        </li>
                    <?php endif; ?>
                    <?php if($content['bedrooms']): ?>
                        <li class="bordered-dark 
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
                        <li class="bordered-dark 
                                   radius-10 
                                   p-2 
                                   m-1 
                                   min-h-45
                                   flex-inline 
                                   items-center">
                            <?php echo get_svg('bath'); ?> <span class="medium ml-2 nowrap"><?php echo $content['bathrooms']; ?> Bathrooms</span>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>


            <?php if($content['price']): ?>
                <div class="pt-3 flex justify-end items-center">
                    <span class="paragraph-14-16 mr-2">3 nights from</span>
                    <span class="heading-20-24 heading-20-27">£<?php echo number_format( $content['price'], 0, '.', ',' ); ?></span>
                </div>
            <?php endif; ?>

            <div class="mt-3 flex justify-space-between items-center">
                <?php if($content['property_link']): ?>
                    <a href="<?php echo $content['property_link']; ?>" alt="<?php echo $content['heading']; ?>" class="button light-green flex items-center justify-center radius-10" target="_blank" id="book-now"><span class="mr-2"><?php echo get_svg('calendar'); ?></span>View Details</a>
                <?php else: ?>
                    <a href="<?php echo $content['awin_link']; ?>" id="book-now" class="button light-green flex items-center justify-center radius-10">View Details</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</article>
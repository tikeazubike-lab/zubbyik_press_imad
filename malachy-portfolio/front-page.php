<?php
/**
 * Front Page Template
 *
 * @package Malachy_Portfolio
 */

get_header();

get_template_part( 'template-parts/section-hero' );
get_template_part( 'template-parts/section-about' );
get_template_part( 'template-parts/section-offers' );
get_template_part( 'template-parts/section-skills' );
get_template_part( 'template-parts/section-projects' );
get_template_part( 'template-parts/section-experience' );
get_template_part( 'template-parts/section-blog-preview' );
get_template_part( 'template-parts/section-contact' );

get_footer();

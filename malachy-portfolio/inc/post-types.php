<?php
/**
 * Custom Post Types & Taxonomies
 *
 * @package Malachy_Portfolio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'malachy_register_post_types' );
add_action( 'init', 'malachy_register_taxonomies' );

/**
 * Register Custom Post Types.
 */
function malachy_register_post_types() {
	$types = array(
		'project' => array(
			'labels' => array(
				'name'          => __( 'Projects', 'malachy-portfolio' ),
				'singular_name' => __( 'Project', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Project', 'malachy-portfolio' ),
				'edit_item'     => __( 'Edit Project', 'malachy-portfolio' ),
				'not_found'     => __( 'No projects found.', 'malachy-portfolio' ),
			),
			'menu_icon'       => 'dashicons-portfolio',
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
			'rewrite'         => array( 'slug' => 'projects' ),
		),
		'experience' => array(
			'labels' => array(
				'name'          => __( 'Experience', 'malachy-portfolio' ),
				'singular_name' => __( 'Experience Entry', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Entry', 'malachy-portfolio' ),
				'edit_item'     => __( 'Edit Entry', 'malachy-portfolio' ),
				'not_found'     => __( 'No experience entries found.', 'malachy-portfolio' ),
			),
			'menu_icon'       => 'dashicons-clock',
			'supports'        => array( 'title', 'excerpt', 'custom-fields', 'page-attributes' ),
			'rewrite'         => array( 'slug' => 'experience' ),
		),
		'skill' => array(
			'labels' => array(
				'name'          => __( 'Skills', 'malachy-portfolio' ),
				'singular_name' => __( 'Skill', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Skill', 'malachy-portfolio' ),
				'edit_item'     => __( 'Edit Skill', 'malachy-portfolio' ),
				'not_found'     => __( 'No skills found.', 'malachy-portfolio' ),
			),
			'menu_icon'       => 'dashicons-lightbulb',
			'supports'        => array( 'title', 'excerpt', 'custom-fields', 'page-attributes' ),
			'rewrite'         => array( 'slug' => 'skills' ),
		),
		'testimonial' => array(
			'labels' => array(
				'name'          => __( 'Testimonials', 'malachy-portfolio' ),
				'singular_name' => __( 'Testimonial', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Testimonial', 'malachy-portfolio' ),
				'edit_item'     => __( 'Edit Testimonial', 'malachy-portfolio' ),
			),
			'menu_icon'       => 'dashicons-testimonial',
			'public'          => true,
			'has_archive'     => false,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ),
			'rewrite'         => array( 'slug' => 'testimonials' ),
			'show_in_menu'    => true,
		),
		'publication' => array(
			'labels' => array(
				'name'          => __( 'Publications', 'malachy-portfolio' ),
				'singular_name' => __( 'Publication', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Publication', 'malachy-portfolio' ),
				'edit_item'     => __( 'Edit Publication', 'malachy-portfolio' ),
			),
			'menu_icon'       => 'dashicons-media-document',
			'public'          => true,
			'has_archive'     => false,
			'supports'        => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes' ),
			'rewrite'         => array( 'slug' => 'publications' ),
			'show_in_menu'    => true,
		),
	);

	$defaults = array(
		'public'            => true,
		'has_archive'       => true,
		'publicly_queryable' => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_admin_bar' => true,
		'can_export'        => true,
		'delete_with_user'  => false,
		'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
	);

	foreach ( $types as $slug => $args ) {
		$register_args = wp_parse_args( $args, $defaults );
		$rewrite       = isset( $register_args['rewrite'] ) ? $register_args['rewrite'] : array( 'slug' => $slug );
		$register_args['rewrite'] = $rewrite;

		register_post_type( $slug, $register_args );
	}
}

/**
 * Register Custom Taxonomies.
 */
function malachy_register_taxonomies() {
	// Project Categories (hierarchical — like categories)
	register_taxonomy(
		'project_category',
		'project',
		array(
			'labels' => array(
				'name'          => __( 'Project Categories', 'malachy-portfolio' ),
				'singular_name' => __( 'Project Category', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Category', 'malachy-portfolio' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'project-category' ),
		)
	);

	// Technology Stack (non-hierarchical — like tags)
	register_taxonomy(
		'technology_stack',
		array( 'project', 'experience' ),
		array(
			'labels' => array(
				'name'          => __( 'Technology Stack', 'malachy-portfolio' ),
				'singular_name' => __( 'Technology', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Technology', 'malachy-portfolio' ),
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'technology' ),
		)
	);

	// Skill Categories (hierarchical)
	register_taxonomy(
		'skill_category',
		'skill',
		array(
			'labels' => array(
				'name'          => __( 'Skill Categories', 'malachy-portfolio' ),
				'singular_name' => __( 'Skill Category', 'malachy-portfolio' ),
				'add_new_item'  => __( 'Add New Category', 'malachy-portfolio' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'skill-category' ),
		)
	);
}

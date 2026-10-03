<?php
/**
 * Project-section reseed preflight and verifier — READ ONLY.
 *
 * Complements `wp malachy migrate_projects` (malachy-portfolio/inc/data-seeder.php),
 * which mutates the `project` CPT with no preview and no acceptance check. This
 * script never writes: it reports what that migration WOULD do, and afterwards
 * whether the result is correct.
 *
 * It reads the canonical item list by reflecting into Malachy_Seeder itself, so
 * it can never drift from what the seeder will actually write. Nothing is
 * duplicated here.
 *
 * Usage (from the WordPress root, or anywhere with an explicit --path):
 *
 *   wp eval-file <repo>/docs/ops/project-reseed-preflight.php preflight
 *   wp eval-file <repo>/docs/ops/project-reseed-preflight.php verify
 *
 * Exit status: preflight always exits 0. verify exits 1 on FAIL so it can gate
 * a scripted deploy.
 *
 * Compatibility: PHP 7.0+ (no arrow functions, no spread, no nullsafe, no
 * trailing commas in calls) — production is shared hosting whose PHP minor
 * version is not nailed down in the docs.
 *
 * @package Malachy_Portfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$mode = isset( $args[0] ) ? strtolower( trim( (string) $args[0] ) ) : 'preflight';

if ( ! in_array( $mode, array( 'preflight', 'verify', 'render' ), true ) ) {
	echo "Unknown mode: {$mode}. Use 'preflight', 'verify' or 'render'.\n";
	exit( 1 );
}

// ---------------------------------------------------------------------------
// Canonical item list — reflected out of the seeder, the single source of truth.
// ---------------------------------------------------------------------------

if ( ! class_exists( 'Malachy_Seeder' ) ) {
	echo "FAIL: Malachy_Seeder is not loaded — is the malachy-portfolio theme active?\n";
	exit( 1 );
}

$seeder     = new Malachy_Seeder();
$reflection = new ReflectionMethod( 'Malachy_Seeder', 'get_project_items' );
$reflection->setAccessible( true );
$items = $reflection->invoke( $seeder );

if ( ! is_array( $items ) || empty( $items ) ) {
	echo "FAIL: could not read the canonical project list from Malachy_Seeder.\n";
	exit( 1 );
}

$theme_dir = defined( 'MALACHY_THEME_DIR' ) ? MALACHY_THEME_DIR : get_template_directory();
$theme_uri = defined( 'MALACHY_THEME_URI' ) ? MALACHY_THEME_URI : get_template_directory_uri();

/**
 * Find the post the seeder would match for one canonical item.
 *
 * Mirrors Malachy_Seeder::seed_projects(): first hit by canonical title, then
 * by legacy title, status-agnostic.
 *
 * @param array $item Canonical item.
 * @return int Post ID, or 0 when the seeder would insert a new post.
 */
function malachy_preflight_match( $item ) {
	$needles = array();
	if ( isset( $item['title'] ) ) {
		$needles[] = $item['title'];
	}
	if ( ! empty( $item['legacy_title'] ) ) {
		$needles[] = $item['legacy_title'];
	}

	foreach ( $needles as $needle ) {
		$hits = get_posts(
			array(
				'post_type'      => 'project',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'title'          => $needle,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $hits ) ) {
			return (int) $hits[0];
		}
	}

	return 0;
}

/**
 * All project posts currently in the database, oldest first.
 *
 * @return array List of post objects.
 */
function malachy_preflight_all_projects() {
	return get_posts(
		array(
			'post_type'      => 'project',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
}

// ---------------------------------------------------------------------------
// preflight — what would migrate_projects do?
// ---------------------------------------------------------------------------

if ( 'preflight' === $mode ) {
	echo "\n=== PREFLIGHT: project reseed (read-only, nothing is written) ===\n";
	echo 'Site: ' . home_url() . "\n";
	echo 'Theme dir: ' . $theme_dir . "\n";
	echo 'Canonical items: ' . count( $items ) . "\n\n";

	$to_update  = 0;
	$to_create  = 0;
	$missing    = 0;
	$matched    = array();

	foreach ( $items as $item ) {
		$id    = malachy_preflight_match( $item );
		$image = isset( $item['image'] ) ? $item['image'] : '';
		$w800  = $theme_dir . '/assets/images/' . $image . '-800.webp';
		$w1400 = $theme_dir . '/assets/images/' . $image . '-1400.webp';

		printf( "  %-34s order=%s\n", $item['title'], $item['menu_order'] );
		printf( "      legacy title : %s\n", empty( $item['legacy_title'] ) ? '(none)' : $item['legacy_title'] );
		printf( "      _project_image -> %s%s\n", $image, file_exists( $theme_dir . '/assets/images/' . $image . '-1400.webp' ) ? '' : '   [MISSING ON DISK]' );

		if ( ! file_exists( $w800 ) || ! file_exists( $w1400 ) ) {
			++$missing;
			printf( "      files: -800.webp=%s  -1400.webp=%s\n", file_exists( $w800 ) ? 'ok' : 'MISSING', file_exists( $w1400 ) ? 'ok' : 'MISSING' );
		}

		if ( $id ) {
			$matched[] = $id;
			++$to_update;
			$current_image = (string) get_post_meta( $id, '_project_image', true );
			$thumb         = (int) get_post_thumbnail_id( $id );
			printf( "      ACTION: UPDATE id=%d (matched existing post)\n", $id );
			printf( "        _project_image now: %s%s\n", '' === $current_image ? '(empty -> falls back to featured image)' : $current_image, '' === $current_image ? '' : ' (already correct)' );
			printf( "        featured image now: %s\n", $thumb ? 'id=' . $thumb : '(none)' );
			printf( "        resulting <img>: %s\n", $theme_uri . '/assets/images/' . $image . '-800.webp' );
		} else {
			++$to_create;
			printf( "      ACTION: CREATE new post (no title/legacy-title match)\n" );
			printf( "        resulting <img>: %s\n", $theme_uri . '/assets/images/' . $image . '-800.webp' );
		}
		echo "\n";
	}

	// Anything in the CPT that the seeder will not touch stays on the page.
	$orphans = array();
	foreach ( malachy_preflight_all_projects() as $post ) {
		if ( ! in_array( (int) $post->ID, $matched, true ) ) {
			$orphans[] = $post;
		}
	}

	echo "--- summary ---\n";
	printf( "  would update : %d\n", $to_update );
	printf( "  would create : %d\n", $to_create );
	printf( "  posts after  : %d\n", $to_update + $to_create + count( $orphans ) );
	printf( "  theme images missing on disk: %d\n", $missing );

	if ( $orphans ) {
		echo "\n  ! UNMATCHED posts that will REMAIN on the page as extra cards:\n";
		foreach ( $orphans as $post ) {
			printf( "      id=%d  %s  (status=%s)\n", $post->ID, $post->post_title, $post->post_status );
		}
	} else {
		echo "\n  no unmatched posts — after the migration the CPT holds exactly the canonical set.\n";
	}

	echo "\nNothing was written. To apply:  wp malachy migrate_projects\n\n";
	exit( 0 );
}

// ---------------------------------------------------------------------------
// render — acceptance check on the actual template output, no browser needed.
// ---------------------------------------------------------------------------

if ( 'render' === $mode ) {
	echo "\n=== RENDER: what the project section actually outputs ===\n";
	echo 'Site: ' . home_url() . "\n\n";

	ob_start();
	get_template_part( 'template-parts/section-projects' );
	$html = ob_get_clean();

	$cards = substr_count( $html, 'jc-card-img' );
	printf( "cards rendered (jc-card-img): %d\n", $cards );

	preg_match_all( '/<img[^>]*src="([^"]+)"/', $html, $matches );
	$srcs = isset( $matches[1] ) ? array_values( array_unique( $matches[1] ) ) : array();

	$uploads = 0;
	$assets  = 0;
	foreach ( $srcs as $src ) {
		if ( false !== strpos( $src, '/wp-content/uploads/' ) ) {
			++$uploads;
		} elseif ( false !== strpos( $src, '/assets/images/' ) ) {
			++$assets;
		}
	}

	echo "\nimage sources in this section:\n";
	foreach ( $srcs as $src ) {
		$kind = 'other';
		if ( false !== strpos( $src, '/wp-content/uploads/' ) ) {
			$kind = 'UPLOADS (legacy fallback)';
		} elseif ( false !== strpos( $src, '/assets/images/' ) ) {
			$kind = 'theme asset';
		}
		printf( "  [%-22s] %s\n", $kind, $src );
	}

	echo "\n--- result ---\n";
	printf( "  theme-asset images : %d\n", $assets );
	printf( "  uploads/ images    : %d\n", $uploads );

	if ( 0 === $uploads && $cards > 0 ) {
		printf( "PASS: %d cards, no uploads/ fallbacks — the section is on the new showcase assets.\n\n", $cards );
		exit( 0 );
	}

	printf( "FAIL: %d card(s) still resolving to uploads/ images.\n\n", $uploads );
	exit( 1 );
}

// ---------------------------------------------------------------------------
// verify — acceptance check on the result.
// ---------------------------------------------------------------------------

echo "\n=== VERIFY: project section after the reseed ===\n";
echo 'Site: ' . home_url() . "\n\n";

$posts  = malachy_preflight_all_projects();
$total  = count( $posts );
$expect = count( $items );
$fails  = array();

printf( "canonical items: %d\n", $expect );
printf( "posts in CPT   : %d\n\n", $total );

if ( $total !== $expect ) {
	$fails[] = sprintf( 'CPT holds %d posts, expected %d', $total, $expect );
}

$no_image_meta = 0;
$legacy_upload = 0;

foreach ( $posts as $post ) {
	$image = (string) get_post_meta( $post->ID, '_project_image', true );
	$thumb = (int) get_post_thumbnail_id( $post->ID );
	$src   = '';
	$from  = '';

	if ( '' !== $image ) {
		$src  = $theme_uri . '/assets/images/' . $image . '-800.webp';
		$from = 'theme asset';
		if ( ! file_exists( $theme_dir . '/assets/images/' . $image . '-800.webp' ) ) {
			$fails[] = sprintf( 'id=%d references %s but the file is missing on disk', $post->ID, $image . '-800.webp' );
		}
	} elseif ( $thumb ) {
		$src  = (string) wp_get_attachment_url( $thumb );
		$from = 'featured image (uploads)';
		++$legacy_upload;
	} else {
		$src  = '(none)';
		$from = 'NO IMAGE';
	}

	if ( '' === $image ) {
		++$no_image_meta;
	}

	printf( "  id=%-5d order=%-2d %-34s\n", $post->ID, $post->menu_order, $post->post_title );
	printf( "           _project_image: %s\n", '' === $image ? '(EMPTY)' : $image );
	printf( "           renders       : %s   [%s]\n\n", $src, $from );

	if ( 'featured image (uploads)' === $from ) {
		$fails[] = sprintf( 'id=%d renders an uploads/ image, not a theme asset', $post->ID );
	}
}

if ( $no_image_meta ) {
	$fails[] = sprintf( '%d post(s) lack _project_image meta', $no_image_meta );
}
if ( $legacy_upload ) {
	$fails[] = sprintf( '%d post(s) still fall back to uploads/ images', $legacy_upload );
}

echo "--- result ---\n";
if ( empty( $fails ) ) {
	printf( "PASS: %d/%d posts, every card renders a theme asset image.\n", $total, $expect );
	echo "      Now confirm the rendered page: no 'uploads/2026' image URLs in section #work.\n\n";
	exit( 0 );
}

echo "FAIL:\n";
foreach ( $fails as $fail ) {
	echo '  - ' . $fail . "\n";
}
echo "\n";
exit( 1 );

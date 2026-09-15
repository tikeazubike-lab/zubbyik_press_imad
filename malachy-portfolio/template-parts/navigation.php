<?php
/**
 * Template Part: Navigation
 *
 * Reference: polished-portfolio header — wordmark, nav links, CTA, mobile menu.
 *
 * @package Malachy_Portfolio
 */

$nav_links = array(
	array( 'href' => '/#work', 'label' => __( 'Work', 'malachy-portfolio' ), 'section' => 'work' ),
	array( 'href' => '/#experience', 'label' => __( 'Experience', 'malachy-portfolio' ), 'section' => 'experience' ),
	array( 'href' => '/#contact', 'label' => __( 'Contact', 'malachy-portfolio' ), 'section' => 'contact' ),
	array( 'href' => '/blog', 'label' => __( 'Blog', 'malachy-portfolio' ), 'route' => true ),
);
?>
<header id="site-header" class="site-header">
	<a class="wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>#top" aria-label="<?php esc_attr_e( 'Malachy Egbuna home', 'malachy-portfolio' ); ?>">
		<span>ME</span><span class="wordmark-dot">.</span>
	</a>

	<nav id="site-nav" class="site-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'malachy-portfolio' ); ?>">
		<?php foreach ( $nav_links as $link ) : ?>
			<?php if ( isset( $link['route'] ) && $link['route'] ) : ?>
				<a href="<?php echo esc_url( home_url( $link['href'] ) ); ?>" data-section-link="<?php echo esc_attr( $link['section'] ?? '' ); ?>">
					<?php echo esc_html( $link['label'] ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_attr( $link['href'] ); ?>" data-section-link="<?php echo esc_attr( $link['section'] ); ?>">
					<?php echo esc_html( $link['label'] ); ?>
				</a>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>

	<div class="nav-actions">
		<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="header-cta" data-section-link="contact">
			<?php esc_html_e( "Let's talk", 'malachy-portfolio' ); ?>
			<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
		</a>

		<button id="theme-toggle" class="nav-icon-btn" aria-label="<?php esc_attr_e( 'Toggle theme', 'malachy-portfolio' ); ?>">
			<svg id="theme-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
			<svg id="theme-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
		</button>

		<button id="mobile-menu-toggle" class="menu-toggle" aria-label="<?php esc_attr_e( 'Toggle menu', 'malachy-portfolio' ); ?>" aria-expanded="false" aria-controls="site-nav">
			<svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
			</svg>
			<svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
				<path d="M18 6 6 18"/><path d="m6 6 12 12"/>
			</svg>
		</button>
	</div>
</header>

<?php
/**
 * Template Part: Navigation
 *
 * @package Malachy_Portfolio
 */

// Define nav links matching the React NAV_LINKS constant
$nav_links = array(
	array( 'href' => '/', 'label' => __( 'Home', 'malachy-portfolio' ), 'section' => 'home' ),
	array( 'href' => '/#about', 'label' => __( 'About', 'malachy-portfolio' ), 'section' => 'about' ),
	array( 'href' => '/#skills', 'label' => __( 'Skills', 'malachy-portfolio' ), 'section' => 'skills' ),
	array( 'href' => '/#projects', 'label' => __( 'Projects', 'malachy-portfolio' ), 'section' => 'projects' ),
	array( 'href' => '/#experience', 'label' => __( 'Experience', 'malachy-portfolio' ), 'section' => 'experience' ),
	array( 'href' => '/blog', 'label' => __( 'Blog', 'malachy-portfolio' ), 'route' => true ),
	array( 'href' => '/#contact', 'label' => __( 'Contact', 'malachy-portfolio' ), 'section' => 'contact' ),
);
?>
<header id="site-header" class="nav-header">
	<nav class="nav-inner container-x" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'malachy-portfolio' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo">
			<span class="nav-logo-dot"></span>
			IMaD <span class="nav-logo-muted">Consulting</span>
		</a>

		<div class="nav-desktop-links">
			<?php foreach ( $nav_links as $link ) : ?>
				<?php if ( isset( $link['route'] ) && $link['route'] ) : ?>
					<a href="<?php echo esc_url( home_url( $link['href'] ) ); ?>" class="nav-desktop-link">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php elseif ( isset( $link['section'] ) ) : ?>
					<a href="<?php echo esc_attr( $link['href'] ); ?>" class="nav-desktop-link" data-section-link="<?php echo esc_attr( $link['section'] ); ?>">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_attr( $link['href'] ); ?>" class="nav-desktop-link">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="nav-actions">
			<button id="theme-toggle" class="nav-icon-btn" aria-label="Toggle theme">
				<svg id="theme-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
				<svg id="theme-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
			</button>
			<button id="mobile-menu-toggle" class="nav-icon-btn nav-mobile-toggle" aria-label="Toggle menu">
				<svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
				</svg>
				<svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
					<path d="M18 6 6 18"/><path d="m6 6 12 12"/>
				</svg>
			</button>
		</div>
	</nav>

	<div id="mobile-menu" class="nav-mobile-menu" style="display:none">
		<div class="nav-mobile-inner container-x">
			<?php foreach ( $nav_links as $link ) : ?>
				<?php if ( isset( $link['route'] ) && $link['route'] ) : ?>
					<a href="<?php echo esc_url( home_url( $link['href'] ) ); ?>" class="nav-mobile-link">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php elseif ( isset( $link['section'] ) ) : ?>
					<a href="<?php echo esc_attr( $link['href'] ); ?>" class="nav-mobile-link" data-section-link="<?php echo esc_attr( $link['section'] ); ?>">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_attr( $link['href'] ); ?>" class="nav-mobile-link">
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</header>

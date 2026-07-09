<?php
/**
 * Template Part: Hero Section
 *
 * Source: React Hero.tsx — full-screen hero with portrait, text, marquee.
 *
 * @package Malachy_Portfolio
 */

$portrait_url  = get_theme_mod( 'malachy_portrait', MALACHY_THEME_URI . '/assets/images/malachy-portrait.webp' );
$hero_subtitle = get_theme_mod( 'malachy_hero_subtitle', __( 'Portfolio · 2026', 'malachy-portfolio' ) );

$badges = array( 'Docker', 'Linux', 'Python', 'Git', 'Playwright', 'WordPress', 'Prompt Eng.', 'Context Eng.', 'Agentic Coding' );
?>
<section id="home" class="hero-section" aria-label="<?php esc_attr_e( 'Hero introduction', 'malachy-portfolio' ); ?>">
	<div aria-hidden="true" class="grain-bg hero-section" style="position:absolute;inset:0;"></div>

	<div aria-hidden="true" class="hero-portrait-wrap">
		<img src="<?php echo esc_url( $portrait_url ); ?>"
			srcset="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-400w.webp' ); ?> 400w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-600w.webp' ); ?> 600w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/malachy-portrait-1024w.webp' ); ?> 1024w"
			sizes="(max-width: 640px) 50vw, (max-width: 1024px) 40vw, 30vw"
			class="hero-portrait" alt="Malachy Egbuna — portrait photo" draggable="false" width="600" height="800" fetchpriority="high" />
	</div>

	<div aria-hidden="true" class="hero-portrait-bg"></div>

	<div aria-hidden="true" class="hero-light-left"></div>
	<div aria-hidden="true" class="hero-veil"></div>
	<div aria-hidden="true" class="hero-floor"></div>
	<div aria-hidden="true" class="hero-floor-line"></div>

	<div class="hero-content container-x">
		<div class="hero-text-col">
			<p class="hero-eyebrow">
				<span class="hero-eyebrow-line"></span>
				<?php echo esc_html( $hero_subtitle ); ?>
			</p>
			<h1 class="hero-title">
				<span class="block" style="display:block"><?php esc_html_e( "Hi, I'm", 'malachy-portfolio' ); ?></span>
				<span class="block hero-title-primary"><?php esc_html_e( 'Malachy.', 'malachy-portfolio' ); ?></span>
				<span class="block hero-title-sub"><?php esc_html_e( 'QA Engineer,', 'malachy-portfolio' ); ?></span>
				<span class="block hero-title-sub"><?php esc_html_e( 'System Admin &', 'malachy-portfolio' ); ?></span>
				<span class="block hero-title-sub"><?php esc_html_e( 'IT Support Specialist.', 'malachy-portfolio' ); ?></span>
			</h1>
			<p class="hero-lede">
				<?php esc_html_e( "I engineer confidence into software and infrastructure — from automated test suites to resilient servers, with a fondness for LLM-driven, spec-first workflows.", 'malachy-portfolio' ); ?>
			</p>
			<div class="hero-cta-row">
				<a href="#projects" class="hero-cta-primary">
					<?php esc_html_e( 'View My Projects', 'malachy-portfolio' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
				</a>
				<a href="<?php echo esc_url( get_theme_mod( 'malachy_resume_url', '#' ) ); ?>" class="hero-cta-outline">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
					<?php esc_html_e( 'Download CV', 'malachy-portfolio' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="hero-marquee" aria-hidden="true">
		<div class="hero-marquee-track-wrap">
			<div class="hero-marquee-track" id="hero-marquee-track">
				<?php foreach ( array_merge( $badges, $badges ) as $i => $badge ) : ?>
					<span class="hero-marquee-badge">
						<span class="hero-marquee-dot"></span>
						<?php echo esc_html( $badge ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="hero-scroll-hint"><span><?php esc_html_e( 'Scroll', 'malachy-portfolio' ); ?></span></div>
	</div>
</section>

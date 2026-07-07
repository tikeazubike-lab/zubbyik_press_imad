<?php
/**
 * Template Part: About Section
 *
 * Source: React About.tsx
 *
 * @package Malachy_Portfolio
 */
?>
<section id="about" class="about-section" aria-label="<?php esc_attr_e( 'About', 'malachy-portfolio' ); ?>">
	<div aria-hidden="true" class="about-illustration">
		<img src="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/about-illustration.webp' ); ?>"
			srcset="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/about-illustration-400w.webp' ); ?> 400w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/about-illustration-768w.webp' ); ?> 768w,
			        <?php echo esc_url( MALACHY_THEME_URI . '/assets/images/about-illustration-1024w.webp' ); ?> 1024w"
			sizes="(max-width: 768px) 50vw, 40vw"
			alt="" width="1024" height="1024" loading="lazy" />
	</div>
	<div aria-hidden="true" class="about-veil"></div>

	<div class="about-inner container-x">
		<div class="about-content">
			<p class="section-eyebrow about-reveal">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'About', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="about-heading about-reveal">
				<?php esc_html_e( 'Engineering quality,', 'malachy-portfolio' ); ?>
				<span class="about-heading-accent"><?php esc_html_e( 'from code to server rack.', 'malachy-portfolio' ); ?></span>
			</h2>
			<div class="about-text about-reveal">
				<p>
					<?php esc_html_e( "I'm Malachy — a QA engineer and systems specialist who treats software the way an architect treats a building. Every deploy, every migration, every test suite is a chance to make things feel effortless for the humans that come after.", 'malachy-portfolio' ); ?>
				</p>
				<p>
					<?php esc_html_e( "I lean on Playwright, Docker, and Linux daily, and I've been deeply exploring LLM-driven, spec-first coding — where the conversation between engineer and model becomes the source of truth.", 'malachy-portfolio' ); ?>
				</p>
			</div>
			<div class="about-stats about-reveal">
				<div>
					<div class="about-stat-num">8+</div>
					<div class="about-stat-label"><?php esc_html_e( 'Years in QA', 'malachy-portfolio' ); ?></div>
				</div>
				<div>
					<div class="about-stat-num">40+</div>
					<div class="about-stat-label"><?php esc_html_e( 'Projects shipped', 'malachy-portfolio' ); ?></div>
				</div>
				<div>
					<div class="about-stat-num">99.9%</div>
					<div class="about-stat-label"><?php esc_html_e( 'Uptime targeted', 'malachy-portfolio' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</section>

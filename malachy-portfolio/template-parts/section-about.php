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
			alt="Illustration of developer working at multi-monitor setup" width="1024" height="1024" loading="lazy" />
	</div>
	<div aria-hidden="true" class="about-veil"></div>

	<div class="about-inner container-x">
		<div class="about-content">
			<p class="section-eyebrow about-reveal">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'About', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="about-heading about-reveal">
				<?php esc_html_e( 'Software and servers,', 'malachy-portfolio' ); ?>
				<span class="about-heading-accent"><?php esc_html_e( 'sorted.', 'malachy-portfolio' ); ?></span>
			</h2>
			<div class="about-text about-reveal">
				<p>
					<?php esc_html_e( "I'm Malachy. Most of what I do comes down to one thing: making sure things work before someone else finds out they don't.", 'malachy-portfolio' ); ?>
				</p>
				<p>
					<?php esc_html_e( "Some days that means writing test suites that catch a bug before it reaches production. Other days it's keeping a Linux server patched, monitored, and boring — in a good way. I've spent enough time on both sides of that line to know they're really the same job: reduce the number of surprises.", 'malachy-portfolio' ); ?>
				</p>
				<p>
					<?php esc_html_e( "Day to day, I work in Playwright, Docker, and Linux. More recently, I've been building with LLMs as part of the actual workflow — writing the spec first, then working with an AI model to build against it, and reviewing what comes out the same way I'd review a pull request from anyone else.", 'malachy-portfolio' ); ?>
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

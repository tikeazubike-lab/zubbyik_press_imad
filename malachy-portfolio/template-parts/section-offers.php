<?php
/**
 * Template Part: Offers Section
 *
 * Displays 9 service offers across 3 categories with CTAs.
 *
 * @package Malachy_Portfolio
 */

// Offer data: id, title, description, price, slug, page_path, highlight, show_started.
$offer_groups = array(
	'Email Deliverability & Security' => array(
		array(
			'id'           => 1,
			'title'        => 'Fix Business Emails Going to Spam',
			'description'  => 'Diagnose and fix the SPF, DKIM, and DMARC issues that push your emails to junk folders.',
			'price'        => 'from £15',
			'slug'         => 'fix-spam',
			'page_path'    => '/offers/fix-business-emails-going-to-spam/',
			'highlight'    => true,
			'show_started' => true,
		),
		array(
			'id'           => 5,
			'title'        => 'Audit and Report on Your Business Email Security and Deliverability',
			'description'  => 'A clear, actionable report on your email authentication, DNS health, and spam risk.',
			'price'        => 'from £15',
			'slug'         => 'email-audit',
			'page_path'    => '/offers/audit-and-report-on-your-business-email-security-and-deliverability/',
			'highlight'    => true,
			'show_started' => true,
		),
		array(
			'id'           => 6,
			'title'        => 'Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud',
			'description'  => 'Harden your domain records and registrar settings against impersonation and takeover.',
			'price'        => 'from £35',
			'slug'         => 'domain-security',
			'page_path'    => '/offers/lock-down-your-domain-and-dns-against-spoofing-hijacking-and-email-fraud/',
			'highlight'    => false,
			'show_started' => false,
		),
		array(
			'id'           => 7,
			'title'        => 'Provide Ongoing Email & DNS Health Monitoring for Your Business',
			'description'  => 'Monthly monitoring that catches email and DNS problems before they affect your customers.',
			'price'        => 'from £20/month',
			'slug'         => 'email-monitoring',
			'page_path'    => '/offers/provide-ongoing-email-and-dns-health-monitoring-for-your-business/',
			'highlight'    => false,
			'show_started' => false,
		),
	),
	'Microsoft 365, Google Workspace & Migrations' => array(
		array(
			'id'           => 2,
			'title'        => 'Set Up Microsoft 365 Business Email With Your Custom Domain',
			'description'  => 'Get professional business email running on your domain with correct DNS and authentication.',
			'price'        => 'from £30',
			'slug'         => 'm365-setup',
			'page_path'    => '/offers/set-up-microsoft-365-business-email-with-your-custom-domain/',
			'highlight'    => true,
			'show_started' => true,
		),
		array(
			'id'           => 3,
			'title'        => 'Migrate Business Email to Microsoft 365, Google Workspace',
			'description'  => 'Move mailboxes, calendars, and contacts without downtime or lost messages.',
			'price'        => 'from £50',
			'slug'         => 'migrate-email',
			'page_path'    => '/offers/migrate-business-email-to-microsoft-365-google-workspace/',
			'highlight'    => true,
			'show_started' => true,
		),
		array(
			'id'           => 4,
			'title'        => 'Migrate Your Website and Business Email to a New Host',
			'description'  => 'Relocate your site and email together with minimal disruption and proper DNS handover.',
			'price'        => 'from £50',
			'slug'         => 'migrate-website-email',
			'page_path'    => '/offers/migrate-your-website-and-business-email-to-a-new-host/',
			'highlight'    => true,
			'show_started' => true,
		),
	),
	'AI & WordPress Modernization' => array(
		array(
			'id'           => 9,
			'title'        => 'Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration',
			'description'  => 'A full rebuild on a modern, maintainable stack with AI chatbot integration built in.',
			'price'        => 'from £220',
			'slug'         => 'wp-rebuild',
			'page_path'    => '/offers/migrate-and-rebuild-a-wordpress-site-onto-a-modern-stack-for-ai-chatbot-integration/',
			'highlight'    => true,
			'show_started' => false,
			'show_discovery' => true,
		),
		array(
			'id'           => 8,
			'title'        => 'Assess Your WordPress Site for AI Chatbot Integration',
			'description'  => 'A structured review of whether your site is ready for an AI chatbot and what it would take.',
			'price'        => 'from £30',
			'slug'         => 'wp-chatbot-assessment',
			'page_path'    => '/offers/assess-your-wordpress-site-for-ai-chatbot-integration/',
			'highlight'    => false,
			'show_started' => true,
		),
	),
);

$booking_url = defined( 'MALACHY_BOOKING_URL' ) ? MALACHY_BOOKING_URL : 'https://cal.com/malachy-egbuna';
?>
<section id="offers" class="offers-section" aria-label="<?php esc_attr_e( 'Services and offers', 'malachy-portfolio' ); ?>">
	<div class="container-x">
		<div class="max-w-2xl">
			<p class="section-eyebrow offers-head">
				<span class="hero-eyebrow-line"></span>
				<?php esc_html_e( 'What I do', 'malachy-portfolio' ); ?>
			</p>
			<h2 class="offers-heading offers-head">
				<?php esc_html_e( 'Fixed-price help for common email, migration, and WordPress problems.', 'malachy-portfolio' ); ?>
			</h2>
		</div>

		<div class="offers-groups" id="offers-groups">
			<?php foreach ( $offer_groups as $group_title => $offers ) : ?>
				<div class="offers-group">
					<h3 class="offers-group-title"><?php echo esc_html( $group_title ); ?></h3>
					<div class="offers-grid">
						<?php
						$highlight_used = false;
						foreach ( $offers as $offer ) :
							$is_highlight = ! $highlight_used && ! empty( $offer['highlight'] );
							if ( $is_highlight ) {
								$highlight_used = true;
							}
						?>
							<article class="offer-card<?php echo $is_highlight ? ' highlight' : ''; ?>">
								<?php if ( $is_highlight ) : ?>
									<span class="badge-highlight"><?php esc_html_e( 'Most Requested', 'malachy-portfolio' ); ?></span>
								<?php endif; ?>
								<div class="offer-card-meta">
									<h4 class="offer-card-title"><?php echo esc_html( $offer['title'] ); ?></h4>
									<p class="offer-card-price"><?php echo esc_html( $offer['price'] ); ?></p>
								</div>
								<p class="offer-card-desc"><?php echo esc_html( $offer['description'] ); ?></p>
								<div class="offer-card-actions">
									<?php if ( ! empty( $offer['show_started'] ) ) : ?>
										<a href="<?php echo esc_url( home_url( '/?service=' . $offer['slug'] . '#contact' ) ); ?>" class="offer-cta-primary">
											<?php esc_html_e( 'Get started', 'malachy-portfolio' ); ?>
										</a>
									<?php endif; ?>
									<?php if ( ! empty( $offer['show_discovery'] ) ) : ?>
										<a href="<?php echo esc_url( $booking_url ); ?>" class="offer-cta-discovery" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'Book a discovery call', 'malachy-portfolio' ); ?>
										</a>
									<?php endif; ?>
									<a href="<?php echo esc_url( home_url( $offer['page_path'] ) ); ?>" class="offer-cta-secondary">
										<?php esc_html_e( 'Learn more', 'malachy-portfolio' ); ?>
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

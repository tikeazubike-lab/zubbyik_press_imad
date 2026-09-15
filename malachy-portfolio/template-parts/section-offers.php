<?php
/**
 * Template Part: Offers Section
 *
 * 2-column grid with oversized highlight card and GSAP stagger reveal.
 *
 * @package Malachy_Portfolio
 */

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
			'icon'         => 'spam',
		),
		array(
			'id'           => 5,
			'title'        => 'Audit and Report on Your Business Email Security and Deliverability',
			'description'  => 'A clear, actionable report on your email authentication, DNS health, and spam risk.',
			'price'        => 'from £15',
			'slug'         => 'email-audit',
			'page_path'    => '/offers/audit-and-report-on-your-business-email-security-and-deliverability/',
			'highlight'    => false,
			'show_started' => true,
			'icon'         => 'audit',
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
			'icon'         => 'shield',
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
			'icon'         => 'monitor',
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
			'icon'         => 'mail',
		),
		array(
			'id'           => 3,
			'title'        => 'Migrate Business Email to Microsoft 365, Google Workspace',
			'description'  => 'Move mailboxes, calendars, and contacts without downtime or lost messages.',
			'price'        => 'from £50',
			'slug'         => 'migrate-email',
			'page_path'    => '/offers/migrate-business-email-to-microsoft-365-google-workspace/',
			'highlight'    => false,
			'show_started' => true,
			'icon'         => 'migrate',
		),
		array(
			'id'           => 4,
			'title'        => 'Migrate Your Website and Business Email to a New Host',
			'description'  => 'Relocate your site and email together with minimal disruption and proper DNS handover.',
			'price'        => 'from £50',
			'slug'         => 'migrate-website-email',
			'page_path'    => '/offers/migrate-your-website-and-business-email-to-a-new-host/',
			'highlight'    => false,
			'show_started' => true,
			'icon'         => 'server',
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
			'icon'         => 'wordpress',
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
			'icon'         => 'ai',
		),
	),
);

$booking_url = defined( 'MALACHY_BOOKING_URL' ) ? MALACHY_BOOKING_URL : 'https://cal.com/malachy-egbuna';

function malachy_offer_icon( $icon ) {
	$icons = array(
		'spam'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/><path d="M12 13l-2 2"/><path d="M12 13l2 2"/></svg>',
		'audit'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
		'shield'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
		'monitor'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M6 10l3 3 3-3"/><path d="M12 7v3"/></svg>',
		'mail'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
		'migrate'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
		'server'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 6h.01M6 18h.01"/></svg>',
		'wordpress'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M6 11c1 4 3 8 6 8s5-4 6-8"/></svg>',
		'ai'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><circle cx="12" cy="12" r="4"/></svg>',
	);
	echo isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['mail'];
}
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
								<div class="offer-card-icon">
									<?php malachy_offer_icon( $offer['icon'] ); ?>
								</div>
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

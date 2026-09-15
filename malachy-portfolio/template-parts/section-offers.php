<?php
/**
 * Template Part: Offers Section (Services)
 *
 * Reference: polished-portfolio Services section.
 * First highlighted offer becomes the featured service;
 * the next offers become a numbered service list.
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
			'id'             => 9,
			'title'          => 'Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration',
			'description'    => 'A full rebuild on a modern, maintainable stack with AI chatbot integration built in.',
			'price'          => 'from £220',
			'slug'           => 'wp-rebuild',
			'page_path'      => '/offers/migrate-and-rebuild-a-wordpress-site-onto-a-modern-stack-for-ai-chatbot-integration/',
			'highlight'      => true,
			'show_started'   => false,
			'show_discovery' => true,
			'icon'           => 'wordpress',
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

// Flatten offers and identify the featured + numbered list.
$all_offers   = array();
$featured     = null;
foreach ( $offer_groups as $group_title => $offers ) {
	foreach ( $offers as $offer ) {
		$all_offers[] = $offer;
		if ( null === $featured && ! empty( $offer['highlight'] ) ) {
			$featured = $offer;
		}
	}
}

// Fallback featured if none highlighted.
if ( ! $featured && ! empty( $all_offers ) ) {
	$featured = $all_offers[0];
}

$numbered = array();
$index    = 0;
foreach ( $all_offers as $offer ) {
	if ( $featured && $offer['id'] === $featured['id'] ) {
		continue;
	}
	if ( count( $numbered ) >= 3 ) {
		break;
	}
	$numbered[] = $offer;
}

function malachy_featured_cta( $offer, $booking_url ) {
	if ( ! empty( $offer['show_started'] ) ) {
		return home_url( '/?service=' . $offer['slug'] . '#contact' );
	}
	if ( ! empty( $offer['show_discovery'] ) ) {
		return $booking_url;
	}
	return home_url( $offer['page_path'] );
}
?>
<section id="offers" class="services section-wrap" aria-labelledby="services-title">
	<div class="section-label reveal"><span>03</span><span><?php esc_html_e( 'How I can help', 'malachy-portfolio' ); ?></span></div>
	<div class="services-content">
		<?php if ( $featured ) : ?>
			<div class="featured-service reveal">
				<div class="service-index"><?php esc_html_e( 'Featured service', 'malachy-portfolio' ); ?> <span>01</span></div>
				<h2 id="services-title"><?php echo esc_html( $featured['title'] ); ?>.</h2>
				<p><?php echo esc_html( $featured['description'] ); ?></p>
				<a href="<?php echo esc_url( malachy_featured_cta( $featured, $booking_url ) ); ?>" class="btn-primary" <?php echo ! empty( $featured['show_discovery'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php esc_html_e( "Tell me what's stuck", 'malachy-portfolio' ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
			</div>
		<?php endif; ?>

		<div class="service-list">
			<?php foreach ( $numbered as $i => $offer ) : ?>
				<a href="<?php echo esc_url( home_url( $offer['page_path'] ) ); ?>" class="service-item reveal">
					<span class="service-number"><?php echo esc_html( sprintf( '%02d', $i + 2 ) ); ?></span>
					<div>
						<h3><?php echo esc_html( $offer['title'] ); ?></h3>
						<p><?php echo esc_html( $offer['description'] ); ?></p>
					</div>
					<svg class="service-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

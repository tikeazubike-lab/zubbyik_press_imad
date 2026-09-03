<?php
/**
 * Template Name: Lead Magnet
 *
 * Low-friction landing page for lead magnets.
 * Opt-in form posts directly to Listmonk.
 *
 * @package Malachy_Portfolio
 */

// Phase 1 endpoint: Listmonk public subscription API.
// Phase 1 → Phase 2 swap point: replace this URL with the n8n webhook URL when n8n is back online.
if ( ! defined( 'MALACHY_LEAD_ENDPOINT' ) ) {
	define( 'MALACHY_LEAD_ENDPOINT', 'https://mail.imadconsulting.co.uk/api/public/subscription' );
}

get_header();

$headline     = get_post_meta( get_the_ID(), '_lead_magnet_headline', true ) ?: get_the_title();
$bullets    = get_post_meta( get_the_ID(), '_lead_magnet_bullets', true );
$list_uuid    = get_post_meta( get_the_ID(), '_lead_magnet_list_uuid', true );
$download_url = get_post_meta( get_the_ID(), '_lead_magnet_download_url', true );
$tripwire_slug = get_post_meta( get_the_ID(), '_lead_magnet_tripwire_slug', true ) ?: 'fix-spam';

if ( empty( $bullets ) ) {
	$bullets = array(
		'A practical checklist you can use immediately',
		'Common mistakes that waste time and money',
		'What to do first if you are not sure where to start',
	);
} elseif ( is_string( $bullets ) ) {
	$bullets = array_filter( array_map( 'trim', explode( "\n", $bullets ) ) );
}

?>
<main class="lead-magnet-page">
	<div class="lead-magnet-inner container-x">
		<h1 class="lead-magnet-headline"><?php echo esc_html( $headline ); ?></h1>

		<?php if ( ! empty( $bullets ) ) : ?>
			<ul class="lead-magnet-bullets">
				<?php foreach ( $bullets as $bullet ) : ?>
					<li><?php echo esc_html( $bullet ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<form id="lead-magnet-form" class="lead-magnet-form" method="post" action="<?php echo esc_url( MALACHY_LEAD_ENDPOINT ); ?>">
			<?php if ( ! empty( $list_uuid ) ) : ?>
				<input type="hidden" name="l" value="<?php echo esc_attr( $list_uuid ); ?>" />
			<?php endif; ?>

			<!-- Honeypot -->
			<div style="position:absolute;left:-9999px" aria-hidden="true">
				<input type="text" name="malachy_hp" tabindex="-1" autocomplete="off" />
			</div>

			<div class="lead-magnet-field">
				<label for="lead-email" class="lead-magnet-label"><?php esc_html_e( 'Email', 'malachy-portfolio' ); ?></label>
				<input type="email" id="lead-email" name="email" placeholder="<?php esc_attr_e( 'your@email.com', 'malachy-portfolio' ); ?>" required autocomplete="email" />
			</div>

			<div class="lead-magnet-field">
				<label for="lead-name" class="lead-magnet-label"><?php esc_html_e( 'Name (optional)', 'malachy-portfolio' ); ?></label>
				<input type="text" id="lead-name" name="name" placeholder="<?php esc_attr_e( 'Your name', 'malachy-portfolio' ); ?>" autocomplete="name" />
			</div>

			<button type="submit" class="lead-magnet-submit" id="lead-magnet-submit">
				<?php esc_html_e( 'Get the guide', 'malachy-portfolio' ); ?>
			</button>

			<div id="lead-magnet-status" class="lead-magnet-status" style="display:none"></div>
		</form>

		<div class="lead-magnet-tripwire">
			<p><?php esc_html_e( 'Rather skip the download and just get this fixed?', 'malachy-portfolio' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/?service=' . $tripwire_slug . '#contact' ) ); ?>" class="lead-magnet-cta">
				<?php esc_html_e( 'Get started', 'malachy-portfolio' ); ?>
			</a>
		</div>
	</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('lead-magnet-form');
  const status = document.getElementById('lead-magnet-status');
  const submitBtn = document.getElementById('lead-magnet-submit');

  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Subscribing...';
    }
    if (status) {
      status.style.display = 'none';
      status.textContent = '';
    }

    // Honeypot check
    const hp = form.querySelector('input[name="malachy_hp"]');
    if (hp && hp.value) {
      window.location.href = <?php echo wp_json_encode( esc_url( home_url( '/thank-you/' ) ) ); ?>;
      return;
    }

    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
      });

      if (response.ok) {
        const redirectUrl = <?php
        echo wp_json_encode(
          esc_url(
            home_url( '/thank-you/' ) . ( ! empty( $download_url ) ? '?download=' . urlencode( $download_url ) : '' )
          )
        );
        ?>;
        window.location.href = redirectUrl;
      } else {
        const text = await response.text();
        if (status) {
          status.style.display = 'block';
          status.textContent = 'Something went wrong. Please try again or contact me directly.';
          status.classList.add('lead-magnet-status-error');
        }
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Get the guide';
        }
      }
    } catch (err) {
      if (status) {
        status.style.display = 'block';
        status.textContent = 'Network error. Please check your connection and try again.';
        status.classList.add('lead-magnet-status-error');
      }
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Get the guide';
      }
    }
  });
});
</script>

<?php
get_footer();

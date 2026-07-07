<?php
/**
 * Template Part: Contact Section
 *
 * Source: React Contact.tsx — form with animated background orbs.
 *
 * @package Malachy_Portfolio
 */
?>
<section id="contact" class="contact-section" aria-label="<?php esc_attr_e( 'Contact', 'malachy-portfolio' ); ?>">
	<div aria-hidden="true" class="contact-orb-a" id="contact-orb-a"></div>
	<div aria-hidden="true" class="contact-orb-b" id="contact-orb-b"></div>

	<div class="contact-inner">
		<div class="contact-heading-wrap">
			<h2 class="contact-heading">
				<?php esc_html_e( 'Let\'s', 'malachy-portfolio' ); ?>
				<span class="contact-heading-accent"><?php esc_html_e( 'talk.', 'malachy-portfolio' ); ?></span>
			</h2>
			<p class="contact-lede">
				<?php esc_html_e( 'Whether it\'s a test strategy, a server migration, or a quiet pairing session on LLM workflows — I\'d be glad to hear from you.', 'malachy-portfolio' ); ?>
			</p>
		</div>

		<form id="contact-form" class="contact-form" method="post">
			<?php wp_nonce_field( 'malachy_contact_nonce', 'malachy_nonce' ); ?>
			<input type="hidden" name="action" value="malachy_send_contact" />

			<!-- Honeypot -->
			<div style="position:absolute;left:-9999px" aria-hidden="true">
				<input type="text" name="malachy_hp" tabindex="-1" autocomplete="off" />
			</div>

			<div class="full-width">
				<label for="malachy_name" class="contact-form-label"><?php esc_html_e( 'Name', 'malachy-portfolio' ); ?></label>
				<input type="text" id="malachy_name" name="malachy_name" placeholder="<?php esc_attr_e( 'Your name', 'malachy-portfolio' ); ?>" required autocomplete="name" />
			</div>
			<div class="full-width">
				<label for="malachy_email" class="contact-form-label"><?php esc_html_e( 'Email', 'malachy-portfolio' ); ?></label>
				<input type="email" id="malachy_email" name="malachy_email" placeholder="<?php esc_attr_e( 'your@email.com', 'malachy-portfolio' ); ?>" required autocomplete="email" />
			</div>
			<div class="full-width">
				<label for="malachy_message" class="contact-form-label"><?php esc_html_e( 'Message', 'malachy-portfolio' ); ?></label>
				<textarea id="malachy_message" name="malachy_message" rows="5" placeholder="<?php esc_attr_e( 'Tell me about your project...', 'malachy-portfolio' ); ?>" required></textarea>
			</div>
			<button type="submit" class="contact-form-submit" id="contact-submit">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
				<?php esc_html_e( 'Send Message', 'malachy-portfolio' ); ?>
			</button>
			<div id="contact-status" class="full-width" style="text-align:center;font-size:0.875rem;min-height:1.5rem;margin-top:0.5rem"></div>
		</form>

		<div class="contact-social">
			<a href="https://github.com/malachy" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
			</a>
			<a href="https://www.linkedin.com/in/malachy-egbuna" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
			</a>
			<a href="mailto:malachy.egbuna@imadconsulting.co.uk" aria-label="Email">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
			</a>
			<span class="contact-social-dot"></span>
			<span><?php esc_html_e( 'Based in Beswick, Manchester', 'malachy-portfolio' ); ?></span>
		</div>
	</div>
</section>

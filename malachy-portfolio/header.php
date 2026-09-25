<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
// Google Search Console verification — only emitted once Malachy pastes the
// token into Settings → Malachy Portfolio (never ship a placeholder, HO-049).
$malachy_gsc = (string) get_option( 'malachy_gsc_token', '' );
if ( '' !== $malachy_gsc ) {
	echo '<meta name="google-site-verification" content="' . esc_attr( $malachy_gsc ) . '">' . "\n";
}
// Curated description — deliberately NOT blogdescription: production's tagline
// is the stale "Portfolio · 2026" and staging's is empty (HO-047's fallback
// only ever fired on staging). Same text on every page until per-page
// descriptions arrive with the SEO plugin (Phase 1, blocked on Malachy).
?>
<meta name="description" content="<?php echo esc_attr( 'IMAD Consulting helps businesses fix email deliverability, migrate to Microsoft 365, secure their domain, and modernize WordPress with AI chatbot integration. QA engineering, web development, and IT support.' ); ?>">
<?php wp_head(); ?>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" />
<link rel="icon" type="image/x-icon" href="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/favicon.ico' ); ?>" sizes="any">
<link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/site-icon.png' ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( MALACHY_THEME_URI . '/assets/images/apple-touch-icon.png' ); ?>">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Malachy Egbuna",
  "jobTitle": "QA Engineer, Web Development & IT Support Specialist",
  "url": "<?php echo esc_url( home_url( '/' ) ); ?>"
}
</script>
<script>
/* Light mode is the default. Only switch to dark if the user explicitly chose it. */
(function(){try{var t=localStorage.getItem('theme');if(t==='dark'){document.documentElement.classList.add('dark');}}catch(e){}})();
</script>
</head>
<body <?php body_class( 'antialiased' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#top"><?php esc_html_e( 'Skip to content', 'malachy-portfolio' ); ?></a>
<div class="portfolio-shell">
<?php get_template_part( 'template-parts/navigation' ); ?>
<main id="top" role="main">

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo esc_attr(get_bloginfo('description') ?: 'Malachy — QA Engineer, SysAdmin & IT Support Specialist. Portfolio and blog.'); ?>">
<?php wp_head(); ?>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" />
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Malachy Egbuna",
  "jobTitle": "QA Engineer, SysAdmin & IT Support Specialist",
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

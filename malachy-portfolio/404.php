<?php
/**
 * 404 Template
 *
 * @package Malachy_Portfolio
 */

get_header();
?>
<div class="flex min-h-screen items-center justify-center bg-background px-4">
	<div class="max-w-md text-center">
		<h1 class="text-7xl font-bold text-foreground">404</h1>
		<h2 class="mt-4 text-xl font-semibold text-foreground">Page not found</h2>
		<p class="mt-2 text-sm text-muted-foreground">The page you're looking for doesn't exist or has been moved.</p>
		<div class="mt-6">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">
				Go home
			</a>
		</div>
	</div>
</div>
<?php
get_footer();

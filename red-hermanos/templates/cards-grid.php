<?php
/**
 * Template: cards_grid (the primary, fully-polished format).
 *
 * Expected in scope: $articles (array), $args (array), $heading, $accent.
 * All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

echo RH_Renderer::container_open( $args, 'cards_grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<?php if ( '' !== $heading ) : ?>
		<h2 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>
	<div class="rh-cards-grid">
		<?php
		foreach ( $articles as $article ) {
			include RH_PLUGIN_DIR . 'templates/partials/card.php';
		}
		?>
	</div>
</section>

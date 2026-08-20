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
		<h3 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<div class="rh-cards-grid">
		<?php
		$rendered = 0;
		foreach ( $articles as $article ) {
			// CAMBIO 1B: skip articles without a usable image (safety net).
			if ( RH_Renderer::skip_no_image( $article, $args ) ) {
				continue;
			}
			if ( $rendered >= (int) $args['count'] ) {
				break;
			}
			++$rendered;
			include RH_PLUGIN_DIR . 'templates/partials/card.php';
		}
		?>
	</div>
<?php echo RH_Renderer::container_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

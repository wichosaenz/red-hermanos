<?php
/**
 * Template: in_post (single insertable card / short stack). Functional-but-
 * minimal for v1.0; reuses the card partial with a compact wrapper.
 * // TODO v1.1: dedicated "recommended read" styling variants.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

echo RH_Renderer::container_open( $args, 'in_post' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<?php if ( '' !== $heading ) : ?>
		<h3 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<div class="rh-in-post">
		<?php
		foreach ( $articles as $article ) {
			include RH_PLUGIN_DIR . 'templates/partials/card.php';
		}
		?>
	</div>
<?php echo RH_Renderer::container_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

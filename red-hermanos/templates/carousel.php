<?php
/**
 * Template: carousel (interactive). Functional-but-minimal for v1.0.
 * Reuses the card partial as slides. // TODO v1.1: dots, autoplay, a11y roles.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

echo RH_Renderer::container_open( $args, 'carousel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<?php if ( '' !== $heading ) : ?>
		<h3 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<div class="rh-carousel" data-rh-carousel>
		<button type="button" class="rh-carousel__nav rh-carousel__nav--prev" aria-label="<?php esc_attr_e( 'Previous', 'red-hermanos' ); ?>">&#8249;</button>
		<div class="rh-carousel__viewport">
			<div class="rh-carousel__track">
				<?php
				$rendered = 0;
				foreach ( $articles as $article ) {
					if ( RH_Renderer::skip_no_image( $article, $args ) ) {
						continue;
					}
					if ( $rendered >= (int) $args['count'] ) {
						break;
					}
					++$rendered;
					echo '<div class="rh-carousel__slide">';
					include RH_PLUGIN_DIR . 'templates/partials/card.php';
					echo '</div>';
				}
				?>
			</div>
		</div>
		<button type="button" class="rh-carousel__nav rh-carousel__nav--next" aria-label="<?php esc_attr_e( 'Next', 'red-hermanos' ); ?>">&#8250;</button>
	</div>
<?php echo RH_Renderer::container_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

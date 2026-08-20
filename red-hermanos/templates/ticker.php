<?php
/**
 * Template: ticker (horizontal, CNN-style). Functional-but-minimal for v1.0.
 * // TODO v1.1: pause-on-hover controls, seamless clone loop polish.
 *
 * Link rules: dofollow, no target="_blank", no rel (R-LINK-1/2). The <a> wraps
 * the title text only.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

echo RH_Renderer::container_open( $args, 'ticker' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<div class="rh-ticker" data-rh-ticker>
		<?php if ( '' !== $heading ) : ?>
			<span class="rh-ticker__label"><?php echo esc_html( $heading ); ?></span>
		<?php endif; ?>
		<div class="rh-ticker__track">
			<?php
			$rendered = 0;
			foreach ( $articles as $article ) :
				if ( RH_Renderer::skip_no_image( $article, $args ) ) {
					continue;
				}
				if ( $rendered >= (int) $args['count'] ) {
					break;
				}
				++$rendered;
				?>
				<span class="rh-ticker__item">
					<?php if ( ! empty( $article->site_name ) ) : ?>
						<span class="rh-ticker__site"><?php echo esc_html( $article->site_name ); ?></span>
					<?php endif; ?>
					<a class="rh-ticker__title" href="<?php echo esc_url( $article->post_url ); ?>"><?php echo esc_html( $article->post_title ); ?></a>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
<?php echo RH_Renderer::container_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

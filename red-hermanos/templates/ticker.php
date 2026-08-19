<?php
/**
 * Template: ticker (horizontal, CNN-style). Functional-but-minimal for v1.0.
 * // TODO v1.1: pause-on-hover controls, seamless clone loop polish.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

$rh_target = ! empty( $args['open_new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';

echo RH_Renderer::container_open( $args, 'ticker' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<div class="rh-ticker" data-rh-ticker>
		<?php if ( '' !== $heading ) : ?>
			<span class="rh-ticker__label"><?php echo esc_html( $heading ); ?></span>
		<?php endif; ?>
		<div class="rh-ticker__track">
			<?php foreach ( $articles as $article ) : ?>
				<a class="rh-ticker__item" href="<?php echo esc_url( $article->post_url ); ?>"<?php echo $rh_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php if ( ! empty( $article->site_name ) ) : ?>
						<span class="rh-ticker__site"><?php echo esc_html( $article->site_name ); ?></span>
					<?php endif; ?>
					<span class="rh-ticker__title"><?php echo esc_html( $article->post_title ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
/**
 * Template: marquee (vertical scroll). Functional-but-minimal for v1.0.
 * // TODO v1.1: CSS-driven continuous vertical scroll + pause on hover.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

$rh_target = ! empty( $args['open_new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';

echo RH_Renderer::container_open( $args, 'marquee' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<?php if ( '' !== $heading ) : ?>
		<h2 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>
	<div class="rh-marquee" data-rh-marquee>
		<ul class="rh-marquee__list">
			<?php foreach ( $articles as $article ) : ?>
				<li class="rh-marquee__item">
					<a href="<?php echo esc_url( $article->post_url ); ?>"<?php echo $rh_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<span class="rh-marquee__title"><?php echo esc_html( $article->post_title ); ?></span>
						<?php if ( ! empty( $article->site_name ) ) : ?>
							<span class="rh-marquee__site"><?php echo esc_html( $article->site_name ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

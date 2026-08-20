<?php
/**
 * Template: marquee (vertical scroll). Functional-but-minimal for v1.0.
 * // TODO v1.1: CSS-driven continuous vertical scroll + pause on hover.
 *
 * Link rules: dofollow, no target="_blank", no rel (R-LINK-1/2). The <a> wraps
 * the title text only.
 *
 * Expected in scope: $articles, $args, $heading, $accent. All output escaped.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

echo RH_Renderer::container_open( $args, 'marquee' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
	<?php if ( '' !== $heading ) : ?>
		<h3 class="rh-related-posts__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<div class="rh-marquee" data-rh-marquee>
		<ul class="rh-marquee__list">
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
				<li class="rh-marquee__item">
					<a class="rh-marquee__title" href="<?php echo esc_url( $article->post_url ); ?>"><?php echo esc_html( $article->post_title ); ?></a>
					<?php if ( ! empty( $article->site_name ) ) : ?>
						<span class="rh-marquee__site"><?php echo esc_html( $article->site_name ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php echo RH_Renderer::container_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

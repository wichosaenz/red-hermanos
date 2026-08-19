<?php
/**
 * Reusable card partial. Used by cards-grid, in-post-card and (loosely) other
 * formats. Contains the image-resolution logic.
 *
 * ALL OUTPUT IS ESCAPED HERE. The plugin never emits raw HTML from the
 * request; it builds its own markup from already-sanitized fields.
 *
 * Expected in scope:
 *   $article (object)  One DB row.
 *   $args    (array)   Parsed renderer args.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

// Resolve the image decision (image | text | skip).
$rh_thumb = RH_Renderer::resolve_thumb( $article, $args );

// 'skip' means: no image available and mode is hide_card -> omit entirely.
if ( 'skip' === $rh_thumb['mode'] ) {
	return;
}

$rh_target = ! empty( $args['open_new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
$rh_title  = isset( $article->post_title ) ? $article->post_title : '';
$rh_url    = isset( $article->post_url ) ? $article->post_url : '';
$rh_excerpt = isset( $article->post_excerpt ) ? $article->post_excerpt : '';
$rh_site   = isset( $article->site_name ) ? $article->site_name : '';
$rh_topic  = isset( $article->topic_tag ) ? $article->topic_tag : '';

$rh_card_class = 'rh-card';
if ( 'image' !== $rh_thumb['mode'] ) {
	$rh_card_class .= ' rh-card--noimg';
}
?>
<article class="<?php echo esc_attr( $rh_card_class ); ?>">
	<a class="rh-card__link" href="<?php echo esc_url( $rh_url ); ?>"<?php echo $rh_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( 'image' === $rh_thumb['mode'] ) : ?>
			<span class="rh-card__thumb">
				<img
					src="<?php echo esc_url( $rh_thumb['src'] ); ?>"
					alt="<?php echo esc_attr( $rh_title ); ?>"
					loading="lazy"
					width="320" height="183"
					data-rh-fallback="<?php echo esc_url( $args['fallback_image'] ); ?>"
					data-rh-mode="<?php echo esc_attr( $args['broken_image_mode'] ); ?>" />
			</span>
		<?php endif; ?>
		<span class="rh-card__body">
			<?php if ( '' !== $rh_topic ) : ?>
				<span class="rh-card__topic"><?php echo esc_html( $rh_topic ); ?></span>
			<?php endif; ?>
			<span class="rh-card__title"><?php echo esc_html( $rh_title ); ?></span>
			<?php if ( '' !== $rh_excerpt ) : ?>
				<span class="rh-card__excerpt"><?php echo esc_html( $rh_excerpt ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $rh_site ) : ?>
				<span class="rh-card__site"><?php echo esc_html( $rh_site ); ?></span>
			<?php endif; ?>
		</span>
	</a>
</article>

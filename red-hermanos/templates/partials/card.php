<?php
/**
 * Reusable card partial. Used by cards-grid, in-post-card and carousel.
 *
 * ALL OUTPUT IS ESCAPED HERE. The plugin never emits raw HTML from the
 * request; it builds its own markup from already-sanitized fields.
 *
 * Link rules (INVIOLABLE — see R-LINK in the brief):
 *   R-LINK-1/2: no target="_blank", no rel="nofollow|noopener|sponsored|ugc".
 *   R-LINK-3:   absolute URLs (post_url is stored absolute).
 *   R-LINK-4:   anchor text = article title inside <h4><a>…</a></h4>.
 *   R-LINK-6:   the <a> wraps ONLY the title, not the whole card.
 *   R-LINK-7:   the whole card is made clickable via JS (rh-cards.js) using the
 *               data-rh-url attribute; crawlers only ever see the single <a>.
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

$rh_title    = isset( $article->post_title ) ? $article->post_title : '';
$rh_url      = isset( $article->post_url ) ? $article->post_url : '';
$rh_excerpt  = isset( $article->post_excerpt ) ? $article->post_excerpt : '';
$rh_site     = isset( $article->site_name ) ? $article->site_name : '';
$rh_topic    = isset( $article->topic_tag ) ? $article->topic_tag : '';
$rh_favicon  = isset( $article->site_icon_url ) ? $article->site_icon_url : '';

$rh_card_class = 'rh-card';
if ( 'image' !== $rh_thumb['mode'] ) {
	$rh_card_class .= ' rh-card--noimg';
}
?>
<article class="<?php echo esc_attr( $rh_card_class ); ?>" data-rh-url="<?php echo esc_url( $rh_url ); ?>">
	<?php if ( 'image' === $rh_thumb['mode'] ) : ?>
		<span class="rh-card__thumb">
			<img
				src="<?php echo esc_url( $rh_thumb['src'] ); ?>"
				alt="<?php echo esc_attr( $rh_title ); ?>"
				loading="lazy"
				decoding="async"
				width="350" height="200"
				data-rh-fallback="<?php echo esc_url( $args['fallback_image'] ); ?>"
				data-rh-mode="<?php echo esc_attr( $args['broken_image_mode'] ); ?>" />
		</span>
	<?php endif; ?>
	<div class="rh-card__body">
		<?php if ( '' !== $rh_topic ) : ?>
			<span class="rh-card__topic"><?php echo esc_html( $rh_topic ); ?></span>
		<?php endif; ?>

		<?php // R-LINK-4/6: dofollow backlink; the <a> wraps only the title. ?>
		<h4 class="rh-card__title">
			<a class="rh-card__link" href="<?php echo esc_url( $rh_url ); ?>"><?php echo esc_html( $rh_title ); ?></a>
		</h4>

		<?php if ( '' !== $rh_site ) : ?>
			<div class="rh-card-source">
				<?php if ( '' !== $rh_favicon ) : ?>
					<img class="rh-card-favicon"
						src="<?php echo esc_url( $rh_favicon ); ?>"
						alt=""
						width="16"
						height="16"
						loading="lazy"
						decoding="async"
						onerror="this.style.display='none'" />
				<?php endif; ?>
				<span class="rh-card-site-name"><?php echo esc_html( $rh_site ); ?></span>
			</div>
		<?php endif; ?>

		<?php // CAMBIO 1: only show a real excerpt (guards against junk). ?>
		<?php if ( ! empty( $rh_excerpt ) && strlen( $rh_excerpt ) > 10 ) : ?>
			<p class="rh-card-excerpt"><?php echo esc_html( $rh_excerpt ); ?></p>
		<?php endif; ?>
	</div>
</article>

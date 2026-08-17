<?php get_header(); ?>
<?php dahim_breadcrumbs(); ?>

  <?php while ( have_posts() ) : the_post(); ?>

  <section class="article-hero" id="main-content">
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="article-hero-photo"><?php the_post_thumbnail( 'large', array( 'loading' => false ) ); ?></div>
    <?php endif; ?>
    <div class="wrap">
      <div class="article-meta">
        <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">ALL INSIGHTS</a>
        <span class="dot"></span>
        <span><?php echo esc_html( get_the_date() ); ?></span>
        <span class="dot"></span>
        <span><?php echo esc_html( dahim_reading_time() ); ?></span>
      </div>
      <h1><?php the_title(); ?></h1>
    </div>
  </section>

  <section class="article-body">
    <div class="wrap">
      <?php the_content(); ?>

      <div class="article-share">
        <span>Share on Socials</span>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn">in</a>
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" target="_blank" rel="noopener" aria-label="Share on X">X</a>
        <a href="https://wa.me/?text=<?php echo urlencode( get_the_title() . ' — ' . get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp">wa</a>
      </div>
    </div>
  </section>

  <?php
  $cat_ids = wp_get_post_categories( get_the_ID() );
  $related = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 2,
    'category__in'   => $cat_ids,
    'post__not_in'   => array( get_the_ID() ),
    'orderby'        => 'date',
    'order'          => 'DESC',
  ) );
  if ( $related->have_posts() ) :
  ?>
  <section class="related-insights">
    <div class="wrap">
      <div class="section-head">
        <h2>Discover More Insights</h2>
      </div>
      <div class="related-grid">
        <?php while ( $related->have_posts() ) : $related->the_post(); ?>
          <div class="related-card">
            <span class="insight-meta"><?php echo esc_html( dahim_primary_category() ); ?> · <?php echo esc_html( get_the_date() ); ?></span>
            <a href="<?php the_permalink(); ?>" class="insight-title"><?php the_title(); ?></a>
            <p class="insight-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
            <a href="<?php the_permalink(); ?>" class="text-link">Read More →</a>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="cta-band">
    <div class="wrap">
      <h2>Need a reliable logistics partner in Nigeria?</h2>
      <div class="btn-group">
        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary">Request a Quote</a>
        <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" class="btn btn-whatsapp">Chat on WhatsApp</a>
      </div>
    </div>
  </section>

  <?php endwhile; ?>

<?php get_footer(); ?>

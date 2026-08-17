<?php
/**
 * Renders the Insights card grid for the current WP loop.
 * Expects the main query to already be positioned (have_posts()).
 */
if ( have_posts() ) : ?>
  <div class="insights-grid">
    <?php while ( have_posts() ) : the_post(); ?>
      <article class="insight-card">
        <a href="<?php the_permalink(); ?>" class="insight-photo">
          <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?>
            <img loading="lazy" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/freight.webp' ); ?>" alt="<?php the_title_attribute(); ?>">
          <?php endif; ?>
        </a>
        <div class="insight-body">
          <span class="insight-meta"><?php echo esc_html( get_the_date() ); ?></span>
          <a href="<?php the_permalink(); ?>" class="insight-title"><?php the_title(); ?></a>
          <p class="insight-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
          <a href="<?php the_permalink(); ?>" class="insight-readmore">Read More →</a>
        </div>
      </article>
    <?php endwhile; ?>
  </div>

  <?php
  global $wp_query;
  $per_page    = (int) $wp_query->get( 'posts_per_page' );
  if ( $per_page <= 0 ) $per_page = (int) get_option( 'posts_per_page' );
  $total_posts = (int) $wp_query->found_posts;
  $current_pg  = max( 1, get_query_var( 'paged' ) ?: get_query_var( 'page' ) ?: 1 );
  $range_start = $total_posts ? ( ( $current_pg - 1 ) * $per_page ) + 1 : 0;
  $range_end   = min( $total_posts, $current_pg * $per_page );
  ?>

  <div class="insights-pagination">
    <p class="insights-pagination-count">
      Showing <?php echo esc_html( $range_start ); ?>–<?php echo esc_html( $range_end ); ?> of <?php echo esc_html( $total_posts ); ?> All Insights
    </p>
    <?php
    echo paginate_links( array(
      'total'     => $wp_query->max_num_pages,
      'current'   => $current_pg,
      'prev_text' => 'Previous',
      'next_text' => 'Next',
      'type'      => 'list',
      'mid_size'  => 2,
      'end_size'  => 1,
    ) );
    ?>
  </div>

<?php else : ?>
  <p style="color:var(--steel);font-size:15px;">No articles published yet — check back soon.</p>
<?php endif; ?>

<?php get_header(); ?>
<?php dahim_breadcrumbs(); ?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">Insights</div>
      <h1><?php single_cat_title(); ?></h1>
      <p class="lead"><?php $cat_desc = wp_strip_all_tags( category_description() ); echo esc_html( $cat_desc ? $cat_desc : 'Articles filed under this topic.' ); ?></p>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <?php
      $categories = get_categories( array( 'hide_empty' => true ) );
      ?>
      <div class="insight-filters-row">
        <?php if ( ! empty( $categories ) ) : ?>
          <div class="insight-filters">
            <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">All Insights</a>
            <?php foreach ( $categories as $cat ) : ?>
              <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="<?php echo ( is_category( $cat->term_id ) ) ? 'active' : ''; ?>"><?php echo esc_html( $cat->name ); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="insight-search" id="dahim-insight-search">
          <?php get_search_form(); ?>
        </div>
        <button type="button" class="search-toggle insight-search-toggle" id="dahim-insight-search-toggle" aria-label="Toggle search" aria-expanded="false" aria-controls="dahim-insight-search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
      </div>

      <?php get_template_part( 'template-parts/insights-grid' ); ?>
    </div>
  </section>

  <section class="cta-band">
    <div class="wrap">
      <h2>Need a reliable logistics partner in Nigeria?</h2>
      <div class="btn-group">
        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary">Request a Quote</a>
        <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" class="btn btn-whatsapp">Chat on WhatsApp</a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>

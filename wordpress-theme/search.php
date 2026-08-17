<?php
/**
 * search.php — results for the on-site search box (searchform.php).
 */
get_header();
dahim_breadcrumbs();
global $wp_query;
$dahim_result_count = $wp_query->found_posts;
?>

  <section class="page-header" id="main-content">
    <div class="wrap">
      <div class="eyebrow">Search</div>
      <h1>Results for &ldquo;<?php echo esc_html( get_search_query() ); ?>&rdquo;</h1>
      <p class="lead">
        <?php if ( $dahim_result_count > 0 ) : ?>
          <?php echo esc_html( $dahim_result_count ); ?> result<?php echo $dahim_result_count === 1 ? '' : 's'; ?> found.
        <?php else : ?>
          No results found. Try a different search term, or browse Insights below.
        <?php endif; ?>
      </p>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div style="max-width:480px;margin-bottom:40px;">
        <?php get_search_form(); ?>
      </div>

      <?php if ( $dahim_result_count > 0 ) : ?>
        <?php get_template_part( 'template-parts/insights-grid' ); ?>
      <?php else : ?>
        <div class="section-head">
          <h2>Not finding it?</h2>
          <p>Here are a few places to start instead.</p>
        </div>
        <div class="desks-grid" style="grid-template-columns:1fr 1fr;">
          <div class="desk-card">
            <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
            <h3>Our Services</h3>
            <p>Freight, customs, and haulage.</p>
            <a class="desk-link" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">View services →</a>
          </div>
          <div class="desk-card">
            <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 7.65l8.42 8.42 8.42-8.42a5.4 5.4 0 0 0 0-7.65z"/></svg></div>
            <h3>Track a Shipment</h3>
            <p>Look up your cargo's status.</p>
            <a class="desk-link" href="<?php echo esc_url( home_url( '/track/' ) ); ?>">Track now →</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php get_footer(); ?>

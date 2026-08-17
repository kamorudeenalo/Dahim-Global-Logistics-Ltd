<?php get_header(); ?>
<?php dahim_breadcrumbs(); ?>

  <?php while ( have_posts() ) : the_post(); ?>
  <section class="page-header" id="main-content">
    <div class="page-header-photo"><?php the_post_thumbnail( 'large', array( 'loading' => false ) ); ?></div>
    <div class="wrap">
      <div class="eyebrow">Service</div>
      <h1><?php the_title(); ?></h1>
    </div>
  </section>

  <section class="section">
    <div class="wrap" style="max-width:780px;">
      <div class="entry-content"><?php the_content(); ?></div>
      <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary" style="margin-top:20px;">Request a Quote</a>
    </div>
  </section>
  <?php endwhile; ?>

<?php get_footer(); ?>

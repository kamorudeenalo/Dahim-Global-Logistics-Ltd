<?php get_header(); ?>
<?php dahim_breadcrumbs(); ?>

  <section class="page-header" id="main-content">
    <div class="wrap">
      <div class="eyebrow">Page</div>
      <h1><?php the_title(); ?></h1>
    </div>
  </section>

  <section class="section">
    <div class="wrap" style="max-width:820px;">
      <?php while ( have_posts() ) : the_post(); ?>
        <div class="entry-content"><?php the_content(); ?></div>
      <?php endwhile; ?>
    </div>
  </section>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Full Width (Blank)
 *
 * Keeps the site header/footer/nav, but gives the content area full width
 * with no page-header banner and no max-width constraint — the right choice
 * when building a page with Elementor (or any fully custom layout), since it
 * won't fight with Elementor's own width/section controls.
 */
get_header();
?>

<main style="width:100%;" id="main-content">
  <?php while ( have_posts() ) : the_post(); ?>
    <?php the_content(); ?>
  <?php endwhile; ?>
</main>

<?php get_footer(); ?>

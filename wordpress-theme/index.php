<?php get_header(); ?>

  <section class="section" id="main-content">
    <div class="wrap" style="max-width:820px;">
      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <article <?php post_class(); ?> style="margin-bottom:48px;">
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="entry-content"><?php the_excerpt(); ?></div>
        </article>
      <?php endwhile; else : ?>
        <p>Nothing found.</p>
      <?php endif; ?>
    </div>
  </section>

<?php get_footer(); ?>

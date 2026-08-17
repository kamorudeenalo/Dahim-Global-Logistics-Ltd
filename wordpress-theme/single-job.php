<?php get_header(); ?>
<?php dahim_breadcrumbs(); ?>

  <?php while ( have_posts() ) : the_post();
    $location = get_post_meta( get_the_ID(), '_dahim_job_location', true );
    $type     = get_post_meta( get_the_ID(), '_dahim_job_type', true );
    $deadline = get_post_meta( get_the_ID(), '_dahim_job_deadline', true );
    $status   = get_post_meta( get_the_ID(), '_dahim_job_status', true ) ?: 'open';
    $apply_url = add_query_arg( array( 'department' => rawurlencode( 'Careers' ), 'role' => rawurlencode( get_the_title() ) ), home_url( '/contact/' ) ) . '#form';
  ?>
  <section class="page-header" id="main-content">
    <div class="wrap">
      <div class="eyebrow">Careers<?php echo ( $status === 'closed' ) ? ' — Closed' : ''; ?></div>
      <h1><?php the_title(); ?></h1>
      <p class="lead">
        <?php
        $meta_bits = array_filter( array( $location, $type, $deadline ? 'Apply by ' . date_i18n( 'j M Y', strtotime( $deadline ) ) : '' ) );
        echo esc_html( implode( ' · ', $meta_bits ) );
        ?>
      </p>
    </div>
  </section>

  <section class="section">
    <div class="wrap" style="max-width:780px;">
      <div class="entry-content"><?php the_content(); ?></div>
      <?php if ( $status === 'open' ) : ?>
        <a href="<?php echo esc_url( $apply_url ); ?>" class="btn btn-primary" style="margin-top:20px;">Apply Now</a>
      <?php else : ?>
        <p style="margin-top:20px;color:var(--steel);font-family:'IBM Plex Mono',monospace;font-size:13px;">This role is no longer accepting applications.</p>
      <?php endif; ?>
    </div>
  </section>
  <?php endwhile; ?>

<?php get_footer(); ?>

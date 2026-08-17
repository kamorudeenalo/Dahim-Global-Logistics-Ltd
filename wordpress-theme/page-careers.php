<?php
/**
 * Template Name: Careers
 */
get_header();
dahim_breadcrumbs();

$dahim_open_jobs = get_posts( array(
	'post_type'      => 'job',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	'meta_query'     => array(
		'relation' => 'OR',
		array( 'key' => '_dahim_job_status', 'value' => 'open' ),
		array( 'key' => '_dahim_job_status', 'compare' => 'NOT EXISTS' ),
	),
) );
?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">Careers</div>
      <h1><?php the_title(); ?></h1>
      <p class="lead">Open roles across operations, customs, and dispatch — join a team moving cargo through Nigerian ports.</p>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <?php if ( $dahim_open_jobs ) : ?>
        <div class="jobs-list">
          <?php foreach ( $dahim_open_jobs as $job ) :
            $location = get_post_meta( $job->ID, '_dahim_job_location', true );
            $type     = get_post_meta( $job->ID, '_dahim_job_type', true );
            $deadline = get_post_meta( $job->ID, '_dahim_job_deadline', true );
          ?>
            <a href="<?php echo esc_url( get_permalink( $job ) ); ?>" class="job-card">
              <div class="job-card-main">
                <h3><?php echo esc_html( $job->post_title ); ?></h3>
                <div class="job-card-meta">
                  <?php if ( $location ) : ?><span><?php echo esc_html( $location ); ?></span><?php endif; ?>
                  <?php if ( $type ) : ?><span><?php echo esc_html( $type ); ?></span><?php endif; ?>
                  <?php if ( $deadline ) : ?><span>Apply by <?php echo esc_html( date_i18n( 'j M Y', strtotime( $deadline ) ) ); ?></span><?php endif; ?>
                </div>
              </div>
              <span class="job-card-arrow">→</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="section-head section-head--center">
          <h2>No open roles right now</h2>
          <p>Check back soon, or send us your CV anyway — we're always glad to hear from good people.</p>
        </div>
        <div style="text-align:center;">
          <a href="<?php echo esc_url( add_query_arg( 'department', rawurlencode( 'Careers' ), home_url( '/contact/' ) ) . '#form' ); ?>" class="btn btn-primary">Send Your CV</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php get_footer(); ?>

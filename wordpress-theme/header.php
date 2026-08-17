<!DOCTYPE html>
<html lang="<?php bloginfo( 'language' ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ( ! has_site_icon() ) : // Fallback favicon until an admin sets one at Settings → General → Site Icon ?>
<link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-favicon.webp' ); ?>">
<?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <a class="skip-link" href="#main-content">Skip to content</a>

  <?php
  $dahim_credentials = array(
    array( 'CAC', 'Registered Company' ),
    array( 'NSC', 'Licensed Freight Forwarder' ),
    array( 'NCS', 'Approved Customs Agent' ),
    array( 'NIMASA', 'Compliant Operations' ),
  );
  ?>
  <div class="utility-bar">
    <div class="credentials-carousel auto-carousel" id="credentials-carousel" data-auto-carousel data-speed="0.5">
      <div class="credentials-track" data-carousel-track>
        <?php
        // Rendered twice back-to-back so the auto-scroll can loop seamlessly.
        for ( $pass = 0; $pass < 2; $pass++ ) {
          foreach ( $dahim_credentials as $cred ) {
            echo '<span class="cred-item"><b>' . esc_html( $cred[0] ) . '</b> ' . esc_html( $cred[1] ) . '</span>';
          }
        }
        ?>
      </div>
    </div>
  </div>

  <header class="site-header">
    <div class="wrap">
      <?php if ( has_custom_logo() ) : ?>
        <div class="logo"><?php the_custom_logo(); ?></div>
      <?php else : ?>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
          <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-logo.webp' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
        </a>
      <?php endif; ?>
      <nav class="main-nav" id="dahim-main-nav">
        <?php
        if ( has_nav_menu( 'primary' ) ) {
          wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '<ul>%3$s</ul>',
          ) );
        } else {
          dahim_fallback_menu();
        }
        ?>
      </nav>
      <div class="header-right">
        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="header-cta">Get a Quote</a>
        <button class="menu-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="dahim-main-nav">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

<?php
/**
 * Template Name: Our Services
 */
get_header();
dahim_breadcrumbs();
?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">Our Services</div>
      <h1>Logistics services built for the Nigerian supply chain.</h1>
      <p class="lead">From port to warehouse, we manage freight forwarding, clearing, and last-mile delivery under one roof.</p>
    </div>
  </section>

  <section class="section" id="what-we-do">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>What We Do</h2>
        <p>Core services covering the full journey of your cargo. Manage these under <strong>Services</strong> in the WordPress admin menu.</p>
      </div>
      <div class="services-grid">
        <?php
        $services = new WP_Query( array( 'post_type' => 'service', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
        if ( $services->have_posts() ) :
          $n = 0;
          while ( $services->have_posts() ) : $services->the_post(); $n++;
        ?>
          <div class="service-card">
            <div class="service-photo"><?php the_post_thumbnail( 'medium' ); ?></div>
            <div class="service-body">
              <h3><?php the_title(); ?></h3>
              <p><?php the_content(); ?></p>
              <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="link">Request a Quote →</a>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata();
        else :
          // Fallback — shown until Services are added in wp-admin (Services → Add New)
          $fallback = array(
            array( 'title' => 'Freight Forwarding (Land, Air & Sea)', 'img' => 'freight.webp', 'text' => 'Dahim Logistics provides reliable freight forwarding in Nigeria, coordinating international cargo transportation via sea, air, and land. We manage smooth communication between shipping lines, customs authorities, and inland transport providers to ensure the safe, timely, and efficient movement of goods.' ),
            array( 'title' => 'Customs Documentation & Clearance', 'img' => 'dahim-home-about.webp', 'text' => 'We handle tariff classification, port documentation, and duty processing in full compliance with Nigeria Customs Service regulations, helping shipments clear the port accurately and without unnecessary delay.' ),
            array( 'title' => 'Haulage & Inland Transportation', 'img' => 'home-haulage.webp', 'text' => 'We provide reliable haulage and inland transportation services in Nigeria, moving containers and cargo from ports to warehouses, factories, and distribution centres nationwide.' ),
            array( 'title' => 'Import & Export Logistics', 'img' => 'freight.webp', 'text' => 'Dahim Logistics delivers efficient import and export logistics services in Nigeria, managing shipments from origin to final delivery with accurate coordination and documentation.' ),
            array( 'title' => 'Procurement & General Supply', 'img' => 'warehousing.webp', 'text' => 'We provide procurement and general supply services in Nigeria, helping companies source industrial materials, equipment, and goods through trusted supplier networks.' ),
            array( 'title' => 'Oil & Gas Logistics Support', 'img' => 'warehousing.webp', 'text' => 'Dahim Logistics offers specialized oil and gas logistics support in Nigeria, including equipment transportation, procurement, and supply chain coordination for energy sector projects.' ),
          );
          foreach ( $fallback as $i => $s ) : ?>
          <div class="service-card">
            <div class="service-photo"><img loading="lazy" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/' . $s['img'] ); ?>" alt="<?php echo esc_attr( $s['title'] ); ?>"></div>
            <div class="service-body">
              <h3><?php echo esc_html( $s['title'] ); ?></h3>
              <p><?php echo esc_html( $s['text'] ); ?></p>
              <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="link">Request a Quote →</a>
            </div>
          </div>
        <?php endforeach;
        endif;
        ?>
      </div>
    </div>
  </section>

  <section class="lanes">
    <div class="wrap section" style="padding-top:88px;">
      <div class="section-head section-head--center">
        <h2>Key routes we move on</h2>
        <p>Common port-to-destination corridors for our clients.</p>
      </div>
      <div class="lane-list">
        <?php
        $lanes = new WP_Query( array( 'post_type' => 'trade_lane', 'posts_per_page' => 3, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
        if ( $lanes->have_posts() ) :
          $n = 0;
          while ( $lanes->have_posts() ) : $lanes->the_post(); $n++;
          $origin = get_post_meta( get_the_ID(), '_dahim_lane_origin', true );
          $dest   = get_post_meta( get_the_ID(), '_dahim_lane_destination', true );
          $mode   = get_post_meta( get_the_ID(), '_dahim_lane_mode', true );
          $time   = get_post_meta( get_the_ID(), '_dahim_lane_transit', true );
        ?>
          <div class="lane-row">
            <span class="lane-idx"><?php echo str_pad( $n, 2, '0', STR_PAD_LEFT ); ?></span>
            <span class="lane-route"><?php echo esc_html( $origin ); ?> → <?php echo esc_html( $dest ); ?></span>
            <span class="lane-mode"><?php echo esc_html( $mode ); ?></span>
            <span class="lane-time"><?php echo esc_html( $time ); ?></span>
            <a href="<?php echo esc_url( home_url( '/track/' ) ); ?>" class="lane-cta">Track →</a>
          </div>
        <?php endwhile; wp_reset_postdata();
        else :
          $fallback_lanes = array(
            array( 'China', 'Apapa Port, Lagos', 'Ocean FCL/LCL', '28–35 days' ),
            array( 'Apapa Port', 'Kano', 'Inland Haulage', '2–3 days' ),
            array( 'UAE', 'Onne Port', 'Ocean Freight', '18–22 days' ),
          );
          foreach ( $fallback_lanes as $i => $l ) : ?>
          <div class="lane-row">
            <span class="lane-idx"><?php echo str_pad( $i + 1, 2, '0', STR_PAD_LEFT ); ?></span>
            <span class="lane-route"><?php echo esc_html( $l[0] ); ?> → <?php echo esc_html( $l[1] ); ?></span>
            <span class="lane-mode"><?php echo esc_html( $l[2] ); ?></span>
            <span class="lane-time"><?php echo esc_html( $l[3] ); ?></span>
            <a href="<?php echo esc_url( home_url( '/track/' ) ); ?>" class="lane-cta">Track →</a>
          </div>
        <?php endforeach;
        endif;
        ?>
      </div>
    </div>
  </section>

  <section class="section" id="industries">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Industries We Service</h2>
        <p>Logistics support tailored to sector-specific cargo and compliance needs.</p>
      </div>
      <div class="industries">
        <?php
        // Rarely-changing list — edit directly here if a sector needs to be added or removed.
        $industries = array( 'Import & Export Businesses', 'Oil & Gas', 'Manufacturing', 'Retail & Distribution', 'Construction & Industrial Projects', 'Maritime & Shipping Operations' );
        foreach ( $industries as $ind ) {
          echo '<div class="industry-card"><h3>' . esc_html( $ind ) . '</h3></div>';
        }
        ?>
      </div>
    </div>
  </section>

  <div class="credentials">
    <div class="credentials-carousel auto-carousel" data-auto-carousel data-speed="0.5">
      <div class="credentials-track" data-carousel-track>
        <?php
        $dahim_service_credentials = array(
          array( 'CAC', 'Registered Company' ),
          array( 'NSC', 'Licensed Freight Forwarder' ),
          array( 'NCS', 'Approved Customs Agent' ),
          array( 'NIMASA', 'Compliant Operations' ),
        );
        // Rendered twice back-to-back so the auto-scroll can loop seamlessly.
        for ( $pass = 0; $pass < 2; $pass++ ) {
          foreach ( $dahim_service_credentials as $cred ) {
            echo '<span class="credential-band-item"><b>' . esc_html( $cred[0] ) . '</b> ' . esc_html( $cred[1] ) . '</span>';
          }
        }
        ?>
      </div>
    </div>
  </div>

  <section class="cta-band">
    <div class="wrap">
      <h2>Ready to move your next shipment?</h2>
      <div class="btn-group">
        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary">Request a Quote</a>
        <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" class="btn btn-whatsapp">Chat on WhatsApp</a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>

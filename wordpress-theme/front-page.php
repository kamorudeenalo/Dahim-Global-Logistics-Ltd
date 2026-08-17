<?php get_header(); ?>

  <section class="hero" id="main-content">
    <div class="wrap">
      <div class="hero-main">
        <h1><?php
          $heading_lines = explode( "\n", get_theme_mod( 'dahim_hero_heading', "Your cargo,\ncleared and\nmoving on time." ) );
          $last = count( $heading_lines ) - 1;
          foreach ( $heading_lines as $i => $line ) {
            $line = esc_html( trim( $line ) );
            echo ( $i === $last ) ? "<em>{$line}</em>" : "{$line}<br>";
          }
        ?></h1>
        <p class="lead"><?php echo esc_html( get_theme_mod( 'dahim_hero_lead', 'Dahim Global Logistics Ltd handles freight forwarding, port haulage, and customs clearance for businesses importing and exporting through Nigeria — with one team managing your shipment from the port to your door.' ) ); ?></p>
        <div class="hero-actions">
          <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn-primary">Request a Quote</a>
          <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" class="btn btn-whatsapp">Chat on WhatsApp</a>
        </div>
      </div>

      <div class="hero-photo">
        <img src="<?php echo esc_url( get_theme_mod( 'dahim_hero_image', get_template_directory_uri() . '/assets/images/dahim-hero.webp' ) ); ?>" alt="Cargo plane, cargo ship, and freight trucks representing Dahim Global Logistics multimodal freight services">
        <div class="manifest-tag">
          <span class="status">● On Schedule — DGL-08842-LOS</span>
          <span class="route">Apapa Port, Lagos → Kano, Nigeria</span>
        </div>
      </div>
    </div>

    <div class="hero-strip">
      <div class="wrap">
        <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
          <div class="stat">
            <b><?php echo esc_html( dahim_stat( $i, 'num' ) ); ?></b>
            <span><?php echo esc_html( dahim_stat( $i, 'label' ) ); ?></span>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <section class="section" id="about">
    <div class="wrap">
      <div class="about-grid">
        <div>
          <div class="eyebrow" style="margin-bottom:18px;">About Dahim Global Logistics</div>
          <h2 style="font-size:clamp(24px,3vw,34px);margin-bottom:20px;">Built on port experience, run with discipline.</h2>
          <p><?php echo esc_html( get_theme_mod( 'dahim_overview', 'DAHIM Global Logistics Limited (DGL) is a trusted logistics and freight management company delivering reliable, efficient, and cost-effective supply chain solutions across Nigeria and West Africa.' ) ); ?></p>
          <p>We manage the full chain of moving cargo through Nigerian ports — freight forwarding, customs documentation, haulage, and inland delivery — so our clients can focus on their business instead of chasing shipments.</p>
          <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-primary" style="margin-top:14px;">Read Our Story</a>
        </div>
        <div class="about-photo about-photo--compact">
          <img loading="lazy" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-home-about.webp' ); ?>" alt="Dahim Global Logistics team members reviewing documents at the company office">
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="services" style="padding-top:0;">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Dahim Logistic Services</h2>
        <p>From port to warehouse, we manage freight forwarding, clearing, and last-mile delivery under one roof.</p>
      </div>

      <div class="services-carousel-wrap">
        <div class="services-carousel auto-carousel" id="services-carousel" data-auto-carousel data-speed="0.6">
          <div class="services-carousel-track" data-carousel-track>
            <?php
            $all_services = new WP_Query( array( 'post_type' => 'service', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
            $service_cards = array();
            if ( $all_services->have_posts() ) :
              while ( $all_services->have_posts() ) : $all_services->the_post();
                $service_cards[] = array(
                  'title'     => get_the_title(),
                  'text'      => wp_trim_words( get_the_excerpt(), 20 ),
                  'thumb'     => get_the_post_thumbnail( get_the_ID(), 'medium' ),
                );
              endwhile; wp_reset_postdata();
            else :
              // Fallback content — shown until Services are added in wp-admin
              $fallback_defs = array(
                array( 'title' => 'Freight Forwarding', 'text' => 'Land, air, and sea cargo coordination, liaising with shipping lines and inland carriers on your behalf.', 'img' => 'freight.webp' ),
                array( 'title' => 'Customs Documentation & Clearance', 'text' => 'Tariff classification, port documentation, and duty processing in full compliance with Nigeria Customs Service.', 'img' => 'dahim-home-about.webp' ),
                array( 'title' => 'Haulage & Inland Transport', 'text' => 'Container haulage from Apapa, Tin Can, and Onne ports to warehouses and distribution points nationwide.', 'img' => 'home-haulage.webp' ),
                array( 'title' => 'Warehousing & Distribution', 'text' => 'Short and long-term storage with cross-docking and nationwide distribution support.', 'img' => 'warehousing.webp' ),
                array( 'title' => 'Procurement & General Supply', 'text' => 'Sourcing and supply of industrial materials and equipment through trusted supplier networks.', 'img' => 'warehousing.webp' ),
                array( 'title' => 'Import & Export Logistics', 'text' => 'Managing shipments from origin to final delivery with accurate coordination and documentation.', 'img' => 'freight.webp' ),
              );
              foreach ( $fallback_defs as $f ) {
                $service_cards[] = array(
                  'title' => $f['title'],
                  'text'  => $f['text'],
                  'thumb' => '<img loading="lazy" src="' . esc_url( get_template_directory_uri() . '/assets/images/' . $f['img'] ) . '" alt="' . esc_attr( $f['title'] ) . '">',
                );
              }
            endif;

            // The auto-carousel engine scrolls a continuously-repeating track,
            // so the card set is rendered twice back-to-back for a seamless loop.
            for ( $loop = 0; $loop < 2; $loop++ ) :
              foreach ( $service_cards as $s ) : ?>
                <div class="service-card" <?php echo $loop ? 'aria-hidden="true" tabindex="-1"' : ''; ?>>
                  <div class="service-photo"><?php echo $s['thumb']; ?></div>
                  <div class="service-body">
                    <h3><?php echo esc_html( $s['title'] ); ?></h3>
                    <p><?php echo esc_html( $s['text'] ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/services/#what-we-do' ) ); ?>" class="link"<?php echo $loop ? ' tabindex="-1"' : ''; ?>>Explore →</a>
                  </div>
                </div>
              <?php endforeach;
            endfor; ?>
          </div>
        </div>

        <div class="slider-nav-row">
          <button type="button" class="slider-nav" data-carousel-prev aria-label="Previous services">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button type="button" class="slider-nav" data-carousel-next aria-label="Next services">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Our Logistics Process</h2>
        <p>Simple &amp; efficient logistics process</p>
      </div>
      <div class="process-grid">
        <div class="process-card">
          <div class="step-ribbon">Step 1</div>
          <div class="process-body">
            <h3>Shipment Booking &amp; Planning</h3>
            <p>We assess clients' cargo details and determine the most efficient logistics strategy.</p>
          </div>
        </div>
        <div class="process-card">
          <div class="step-ribbon">Step 2</div>
          <div class="process-body">
            <h3>Documentation &amp; Processing</h3>
            <p>Our team prepares all customs and regulatory documentation required for shipment clearance.</p>
          </div>
        </div>
        <div class="process-card">
          <div class="step-ribbon">Step 3</div>
          <div class="process-body">
            <h3>Cargo Handling &amp; Transport</h3>
            <p>Cargo is safely handled, cleared, and transported to its final destination by professionals.</p>
          </div>
        </div>
        <div class="process-card">
          <div class="step-ribbon">Step 4</div>
          <div class="process-body">
            <h3>Delivery &amp; Client Support</h3>
            <p>We ensure timely delivery while maintaining communication throughout the process.</p>
          </div>
        </div>
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
        $lanes = new WP_Query( array( 'post_type' => 'trade_lane', 'posts_per_page' => 4, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
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
            array( 'Tin Can Island', 'Port Harcourt', 'Road Haulage', '1–2 days' ),
            array( 'Lagos', 'Accra, Ghana', 'Cross-Border Road', '3–4 days' ),
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

  <section class="section" id="faq">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Frequently asked questions</h2>
        <p>Common questions from clients shipping into and out of Nigeria.</p>
      </div>
      <div class="faq-list">
        <?php
        $faqs = new WP_Query( array(
          'post_type'      => 'faq',
          'posts_per_page' => 6,
          'orderby'        => 'menu_order',
          'order'          => 'ASC',
          'meta_query'     => array(
            'relation' => 'OR',
            array( 'key' => '_dahim_faq_group', 'value' => 'general' ),
            array( 'key' => '_dahim_faq_group', 'compare' => 'NOT EXISTS' ), // FAQs created before "Shows On" existed default to General
          ),
        ) );
        if ( $faqs->have_posts() ) :
          $faq_n = 0;
          while ( $faqs->have_posts() ) : $faqs->the_post(); $faq_n++; ?>
          <details class="faq-item" name="dahim-faq"<?php echo ( $faq_n === 1 ) ? ' open' : ''; ?>>
            <summary><?php the_title(); ?></summary>
            <p><?php the_content(); ?></p>
          </details>
        <?php endwhile; wp_reset_postdata();
        else :
          $fallback_faqs = array(
            array( 'What documents do I need for customs clearance in Nigeria?', "Typical requirements include a Bill of Lading or Airway Bill, commercial invoice, packing list, Form M, and SONCAP or NAFDAC certificates where applicable. Our team reviews your documentation before your cargo arrives so clearance isn't delayed at the port." ),
            array( 'How long does clearing take at Apapa or Tin Can port?', 'Clearing times vary with cargo type and documentation readiness, but with complete paperwork submitted in advance, most shipments clear within a few working days of vessel arrival.' ),
            array( 'Do you handle both import and export shipments?', 'Yes. We manage both inbound and outbound freight, including customs documentation, port coordination, and inland transportation on either side of the shipment.' ),
            array( "Can I track my shipment while it's in transit?", 'Yes. Use the Track a Shipment page with your waybill or container number, or reach our ops desk on WhatsApp for a direct status update.' ),
            array( 'Which locations in Nigeria do you deliver to?', 'We move cargo nationwide from Lagos ports to destinations including Kano, Port Harcourt, Abuja, and other major commercial centres, as well as select cross-border routes into West Africa.' ),
          );
          foreach ( $fallback_faqs as $fi => $f ) : ?>
          <details class="faq-item" name="dahim-faq"<?php echo ( $fi === 0 ) ? ' open' : ''; ?>>
            <summary><?php echo esc_html( $f[0] ); ?></summary>
            <p><?php echo esc_html( $f[1] ); ?></p>
          </details>
        <?php endforeach;
        endif;
        ?>
      </div>
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

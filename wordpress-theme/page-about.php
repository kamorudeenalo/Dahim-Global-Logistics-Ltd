<?php
/**
 * Template Name: About Dahim
 */
get_header();
dahim_breadcrumbs();
?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">About Dahim</div>
      <h1>Trusted logistics and freight management across Nigeria &amp; West Africa.</h1>
      <p class="lead">Dahim Global Logistics Limited (DGL) delivers reliable, efficient, and cost-effective supply chain solutions for businesses importing and exporting through Nigeria.</p>
    </div>
  </section>

  <!-- Company Overview -->
  <section class="section">
    <div class="wrap">
      <div class="about-grid about-grid--top">
        <div>
          <h2 style="font-size:clamp(22px,2.6vw,32px);margin-bottom:20px;">Company Overview</h2>
          <p><?php echo esc_html( get_theme_mod( 'dahim_overview', 'DAHIM Global Logistics Limited (DGL) is a trusted logistics and freight management company dedicated to delivering reliable, efficient, and cost-effective supply chain solutions across Nigeria and West Africa.' ) ); ?></p>
          <p>We specialize in freight forwarding, customs documentation and clearance, haulage and inland transportation, import and export logistics, and procurement services. Our operations are designed to ensure seamless cargo movement while maintaining full compliance with maritime and port regulations.</p>
          <p>With a strong focus on professionalism, efficiency, and client satisfaction, we help businesses move goods safely and efficiently across local and international trade routes.</p>
          <p><?php echo esc_html( get_theme_mod( 'dahim_experience', 'DAHIM Global Logistics Limited was founded by Ajide Ibrahim Eniobanfe, a seasoned logistics professional with over 14 years of hands-on experience in clearing and forwarding operations.' ) ); ?></p>
          <p>Before establishing the company, he gained extensive industry experience working with Waxtee Nigeria Limited, where he developed deep expertise in port operations, cargo handling, customs documentation, and freight coordination.</p>
          <p>His academic background further strengthens the company's expertise. He holds a Master's Degree in Maritime Administration and Management, providing advanced knowledge in maritime regulations, port operations, shipping management, and global logistics systems.</p>
        </div>
        <div class="about-photo">
          <img loading="lazy" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-home-about.webp' ); ?>" alt="Dahim Global Logistics staff reviewing shipment documentation at the company office">
        </div>
      </div>
    </div>
  </section>

  <!-- What We Stand For -->
  <section class="section" style="padding-top:0;">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>What We Stand For</h2>
        <p>Our mission and vision statements</p>
      </div>
      <div class="mv-section">
        <div class="mv-block">
          <div class="mv-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <h3>Our Mission</h3>
          <p><?php echo esc_html( get_theme_mod( 'dahim_mission', 'Our mission is to deliver reliable, transparent, and efficient logistics solutions while maintaining the highest standards of professionalism, maritime compliance, and operational excellence in every shipment we handle.' ) ); ?></p>
        </div>
        <div class="mv-block">
          <div class="mv-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <h3>Our Vision</h3>
          <p><?php echo esc_html( get_theme_mod( 'dahim_vision', 'To become a leading logistics and maritime solutions provider recognized for reliability, innovation, and excellence in delivering seamless global shipping and supply chain solutions.' ) ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- Our Core Values -->
  <section class="section" style="padding-top:0;">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Our Core Values</h2>
        <p>We use R.I.P.E. to guide every decision we make.</p>
      </div>
      <div class="values-grid">
        <div class="value-card">
          <div class="value-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
          </div>
          <h3>Reliability</h3>
          <p>We are committed to delivering cargo safely and on schedule while maintaining consistent communication with our clients.</p>
        </div>
        <div class="value-card">
          <div class="value-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="3" x2="12" y2="21"/><line x1="5" y1="7" x2="19" y2="7"/><path d="M5 7l-3 6a3 3 0 006 0z"/><path d="M19 7l-3 6a3 3 0 006 0z"/><line x1="7" y1="21" x2="17" y2="21"/></svg>
          </div>
          <h3>Integrity</h3>
          <p>We operate with transparency, honesty, and strong professional ethics in every logistics operation we manage.</p>
        </div>
        <div class="value-card">
          <div class="value-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
          </div>
          <h3>Professionalism</h3>
          <p>Our team operates with industry knowledge, discipline, and commitment to excellence.</p>
        </div>
        <div class="value-card">
          <div class="value-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 11 14 9 22 21 10 13 10 13 2"/></svg>
          </div>
          <h3>Efficiency</h3>
          <p>Our logistics processes are structured to minimize delays and ensure smooth cargo movement.</p>
        </div>
      </div>
    </div>
  </section>

  <div class="credentials">
    <div class="credentials-carousel auto-carousel" data-auto-carousel data-speed="0.5">
      <div class="credentials-track" data-carousel-track>
        <?php
        $dahim_about_credentials = array(
          array( 'CAC', 'Registered Company' ),
          array( 'NSC', 'Licensed Freight Forwarder' ),
          array( 'NCS', 'Approved Customs Agent' ),
          array( 'NIMASA', 'Compliant Operations' ),
        );
        // Rendered twice back-to-back so the auto-scroll can loop seamlessly.
        for ( $pass = 0; $pass < 2; $pass++ ) {
          foreach ( $dahim_about_credentials as $cred ) {
            echo '<span class="credential-band-item"><b>' . esc_html( $cred[0] ) . '</b> ' . esc_html( $cred[1] ) . '</span>';
          }
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Why Choose Dahim -->
  <section class="section">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Why Choose Dahim Logistics</h2>
        <p>Reasons clients trust us with cargo moving through Nigerian ports.</p>
      </div>
      <div class="reasons-grid">
        <div class="reason-card"><span class="reason-badge">1</span><h3>Over 14 Years of Logistics Industry Experience</h3></div>
        <div class="reason-card"><span class="reason-badge">2</span><h3>Strong Knowledge of Maritime &amp; Port Operations</h3></div>
        <div class="reason-card"><span class="reason-badge">3</span><h3>Reliable Customs Documentation &amp; Clearance Support</h3></div>
        <div class="reason-card"><span class="reason-badge">4</span><h3>Commitment to Efficient, Transparent Operations</h3></div>
        <div class="reason-card"><span class="reason-badge">5</span><h3>Professional Cargo Handling &amp; Transportation</h3></div>
        <div class="reason-card"><span class="reason-badge">6</span><h3>24/7 Ops Desk Support</h3></div>
      </div>
    </div>
  </section>

  <!-- Our Team (pulled from the Team Members custom post type) -->
  <section class="section">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Our Team</h2>
        <p>The people behind every shipment we manage.</p>
      </div>
      <div class="team-grid">
        <?php
        $team = new WP_Query( array(
          'post_type'      => 'team_member',
          'post_status'    => 'publish',
          'posts_per_page' => -1,
          'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
          'no_found_rows'  => true,
        ) );

        if ( $team->have_posts() ) :
          while ( $team->have_posts() ) :
            $team->the_post();
            $role = get_post_meta( get_the_ID(), '_dahim_role', true );
            $name = get_the_title();
            $bio  = get_the_content();
        ?>
          <article class="team-card">
            <div class="team-photo">
              <?php
              if ( has_post_thumbnail() ) {
                echo get_the_post_thumbnail(
                  get_the_ID(),
                  'medium_large',
                  array(
                    'loading' => 'lazy',
                    'alt'     => $name,
                  )
                );
              } else {
                echo '<div class="team-photo-placeholder" aria-hidden="true"></div>';
              }
              ?>
            </div>
            <div class="team-body">
              <?php if ( $role ) : ?>
                <span class="role"><?php echo esc_html( $role ); ?></span>
              <?php endif; ?>
              <h3><?php echo esc_html( $name ); ?></h3>
              <?php if ( trim( wp_strip_all_tags( $bio ) ) ) : ?>
                <div class="team-bio"><?php echo wp_kses_post( wpautop( $bio ) ); ?></div>
              <?php endif; ?>
            </div>
          </article>
        <?php
          endwhile;
          wp_reset_postdata();
        endif;
        ?>
      </div>
    </div>
  </section>

  <!-- Our Commitment -->
  <section class="section" style="padding-top:0;">
    <div class="wrap" style="max-width:820px;text-align:center;">
      <h2 style="margin-bottom:22px;">Our Commitment</h2>
      <p style="color:var(--steel);font-size:15px;line-height:1.75;margin-bottom:16px;">At DAHIM Global Logistics Limited, our goal is to provide dependable logistics services that help businesses operate smoothly in an increasingly complex global trade environment.</p>
      <p style="color:var(--steel);font-size:15px;line-height:1.75;">We work closely with our clients to understand their logistics needs and deliver solutions that ensure timely cargo movement, operational efficiency, and long-term partnership.</p>
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

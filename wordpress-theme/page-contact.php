<?php
/**
 * Template Name: Contact Us
 */
get_header();

dahim_breadcrumbs();

$dahim_preselected_department = isset( $_GET['department'] ) ? sanitize_text_field( $_GET['department'] ) : '';
?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">Contact Us</div>
      <h1>Let's talk.</h1>
      <p class="lead">Reach our Lagos ops desk directly, or send us a message and we'll respond within one business day.</p>
    </div>
  </section>

  <section class="section" style="padding-bottom:0;">
    <div class="wrap">
      <div class="section-head section-head--center">
        <div class="eyebrow">Get In Touch</div>
        <h2>Reach the right desk</h2>
        <p>One inbox slows everyone down — pick the team built for what you need, and skip the wait.</p>
      </div>
      <div class="desks-grid">
        <?php foreach ( dahim_get_all_departments() as $dept ) :
          $card_url = $dept['external_url']
            ? ( strpos( $dept['external_url'], 'http' ) === 0 ? $dept['external_url'] : home_url( $dept['external_url'] ) )
            : add_query_arg( 'department', rawurlencode( $dept['title'] ), get_permalink() ) . '#form';
        ?>
          <div class="desk-card">
            <div class="icon"><?php echo $dept['icon_svg']; ?></div>
            <h3><?php echo esc_html( $dept['title'] ); ?></h3>
            <p><?php echo esc_html( $dept['description'] ); ?></p>
            <a class="desk-link" href="<?php echo esc_url( $card_url ); ?>"><?php echo esc_html( $dept['link_text'] ); ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">

      <?php if ( isset( $_GET['dahim_contact'] ) ) : ?>
        <?php if ( $_GET['dahim_contact'] === 'success' ) : ?>
          <div style="border:2px solid var(--ink);border-radius:var(--radius);background:var(--paper-dim);padding:18px 22px;margin-bottom:36px;font-family:'IBM Plex Mono',monospace;font-size:13.5px;">
            ✓ Thanks — your request has been sent. Our ops desk will respond within one business day.
          </div>
        <?php else : ?>
          <div style="border:2px solid #B3261E;border-radius:var(--radius);background:#FBEAE9;padding:18px 22px;margin-bottom:36px;font-family:'IBM Plex Mono',monospace;font-size:13.5px;color:#B3261E;">
            ✕ Something went wrong sending your message. Please try again or reach us directly on WhatsApp.
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="contact-grid">
        <div>
          <div class="contact-cards">
            <div class="contact-card">
              <h3>Call Us</h3>
              <p>
                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', get_option( 'dahim_phone', '+2348031234567' ) ) ); ?>"><?php echo esc_html( get_option( 'dahim_phone', '+234 803 123 4567' ) ); ?></a><br>
                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', get_option( 'dahim_phone2', '+2348021234567' ) ) ); ?>"><?php echo esc_html( get_option( 'dahim_phone2', '+234 802 123 4567' ) ); ?></a>
              </p>
            </div>
            <div class="contact-card">
              <h3>WhatsApp</h3>
              <p><a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>">Chat with our ops desk</a><br>Fastest response, 24/7</p>
            </div>
            <div class="contact-card">
              <h3>Email</h3>
              <p>
                <a href="mailto:<?php echo esc_attr( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?>"><?php echo esc_html( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?></a><br>
                <a href="mailto:<?php echo esc_attr( get_option( 'dahim_email_ops', 'ops@dahimlogistics.com' ) ); ?>"><?php echo esc_html( get_option( 'dahim_email_ops', 'ops@dahimlogistics.com' ) ); ?></a>
              </p>
            </div>
            <div class="contact-card">
              <h3>Visit Us</h3>
              <p><?php echo nl2br( esc_html( get_option( 'dahim_address', "Plot 14, Wharf Road, Apapa,\nLagos State, Nigeria" ) ) ); ?></p>
            </div>
          </div>
          <?php $dahim_map_address = preg_replace( '/\s*\n\s*/', ', ', trim( get_option( 'dahim_address', "Plot 14, Wharf Road, Apapa,\nLagos State, Nigeria" ) ) ); ?>
          <div class="map-photo">
            <iframe
              src="https://www.google.com/maps?q=<?php echo rawurlencode( $dahim_map_address ); ?>&output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="<?php echo esc_attr( get_bloginfo( 'name' ) . ' — office location' ); ?>"
              allowfullscreen>
            </iframe>
          </div>
        </div>

        <div id="form">
          <div class="eyebrow" id="form-eyebrow" style="margin-bottom:16px;">Get In Touch</div>
          <h2 id="form-heading" style="font-size:24px;margin-bottom:20px;">How can we help?</h2>
          <script type="application/json" id="dahim-department-data">
            <?php
            $dahim_dept_js = array();
            foreach ( dahim_get_all_departments() as $dept ) {
              if ( ! empty( $dept['external_url'] ) ) continue; // routing-only, never selectable in the form
              $dahim_dept_js[ $dept['title'] ] = array(
                'eyebrow'     => $dept['eyebrow'],
                'heading'     => $dept['heading'],
                'showCompany' => $dept['show_company'],
                'showService' => $dept['show_service'],
                'showRoleCv'  => $dept['show_role_cv'],
                'label'       => $dept['message_label'],
                'placeholder' => $dept['message_placeholder'],
                'submitLabel' => $dept['submit_label'],
              );
            }
            echo wp_json_encode( $dahim_dept_js );
            ?>
          </script>
          <form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="dahim_contact_submit">
            <?php wp_nonce_field( 'dahim_contact_submit', 'dahim_contact_nonce' ); ?>
            <input type="hidden" name="dahim_form_ts" value="<?php echo esc_attr( time() ); ?>">
            <input type="hidden" name="dahim_submission_token" value="<?php echo esc_attr( wp_generate_password( 24, false ) ); ?>">
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
              <label for="dahim_hp">Leave this field empty</label>
              <input type="text" id="dahim_hp" name="dahim_hp" value="" tabindex="-1" autocomplete="off">
            </div>
            <div>
              <label for="department">Department</label>
              <select id="department" name="department">
                <?php foreach ( dahim_inquiry_departments() as $dept ) : ?>
                  <option value="<?php echo esc_attr( $dept ); ?>"<?php selected( $dahim_preselected_department, $dept ); ?>><?php echo esc_html( $dept ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="contact-form-row">
              <div>
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Your name" required>
              </div>
              <div id="company-field">
                <label for="company">Company</label>
                <input type="text" id="company" name="company" placeholder="Company name">
              </div>
            </div>
            <div class="contact-form-row">
              <div>
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+234 800 000 0000">
              </div>
              <div>
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@company.com" required>
              </div>
            </div>
            <div id="service-field">
              <label for="service">Service Needed</label>
              <select id="service" name="service">
                <option>Freight Forwarding</option>
                <option>Customs Documentation &amp; Clearance</option>
                <option>Haulage &amp; Inland Transportation</option>
                <option>Import &amp; Export Logistics</option>
                <option>Procurement &amp; General Supply</option>
                <option>Oil &amp; Gas Logistics Support</option>
              </select>
            </div>
            <div class="contact-form-row" id="role-cv-row">
              <div>
                <label for="role">Role Applying For</label>
                <input type="text" id="role" name="role" placeholder="e.g. Customs Documentation Officer" value="<?php echo esc_attr( isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : '' ); ?>">
              </div>
              <div>
                <label for="cv_link">CV / Portfolio Link</label>
                <input type="url" id="cv_link" name="cv_link" placeholder="Link to your CV or portfolio">
              </div>
            </div>
            <div>
              <label for="message" id="message-label">Cargo Details</label>
              <textarea id="message" name="message" placeholder="Tell us about your shipment — origin, destination, cargo type, and estimated volume"></textarea>
            </div>
            <button type="submit" class="btn btn-dark" id="form-submit">Send Request</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <div class="credentials">
    <div class="credentials-carousel auto-carousel" data-auto-carousel data-speed="0.5">
      <div class="credentials-track" data-carousel-track>
        <?php
        $dahim_contact_credentials = array(
          array( 'CAC', 'Registered Company' ),
          array( 'NSC', 'Licensed Freight Forwarder' ),
          array( 'NCS', 'Approved Customs Agent' ),
          array( 'NIMASA', 'Compliant Operations' ),
        );
        // Rendered twice back-to-back so the auto-scroll can loop seamlessly.
        for ( $pass = 0; $pass < 2; $pass++ ) {
          foreach ( $dahim_contact_credentials as $cred ) {
            echo '<span class="credential-band-item"><b>' . esc_html( $cred[0] ) . '</b> ' . esc_html( $cred[1] ) . '</span>';
          }
        }
        ?>
      </div>
    </div>
  </div>

<?php get_footer(); ?>

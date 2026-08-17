  <footer class="site-footer">
    <div class="wrap">
      <div class="footer-grid">
        <div class="footer-about">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
            <img loading="lazy" src="<?php echo esc_url( get_theme_mod( 'dahim_footer_logo', get_template_directory_uri() . '/assets/images/dahim-footer-logo.webp' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
          </a>
          <p>Freight forwarding, customs clearing, and haulage services for businesses moving cargo through Nigeria.</p>
        </div>
        <div>
          <h4>Company</h4>
          <ul>
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
            <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Dahim</a></li>
            <li><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">Our Services</a></li>
            <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact Us</a></li>
            <li><a href="<?php echo esc_url( home_url( '/track/' ) ); ?>">Track a Shipment</a></li>
          </ul>
        </div>
        <div>
          <h4>Services</h4>
          <ul>
            <?php
            $footer_services = new WP_Query( array( 'post_type' => 'service', 'posts_per_page' => 4, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
            if ( $footer_services->have_posts() ) :
              while ( $footer_services->have_posts() ) : $footer_services->the_post();
                echo '<li><a href="' . esc_url( home_url( '/services/#what-we-do' ) ) . '">' . esc_html( get_the_title() ) . '</a></li>';
              endwhile;
              wp_reset_postdata();
            else :
              ?>
              <li><a href="<?php echo esc_url( home_url( '/services/#what-we-do' ) ); ?>">Freight Forwarding</a></li>
              <li><a href="<?php echo esc_url( home_url( '/services/#what-we-do' ) ); ?>">Customs Clearing</a></li>
              <li><a href="<?php echo esc_url( home_url( '/services/#what-we-do' ) ); ?>">Haulage &amp; Transport</a></li>
            <?php endif; ?>
            <li><a href="<?php echo esc_url( home_url( '/services/#industries' ) ); ?>">Industries We Service</a></li>
          </ul>
        </div>
        <div>
          <h4>Get In Touch</h4>
          <ul>
            <li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', get_option( 'dahim_phone', '+2348031234567' ) ) ); ?>"><?php echo esc_html( get_option( 'dahim_phone', '+234 803 123 4567' ) ); ?></a></li>
            <li><a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>">WhatsApp Chat</a></li>
            <li><a href="mailto:<?php echo esc_attr( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?>"><?php echo esc_html( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?></a></li>
            <li><?php echo nl2br( esc_html( get_option( 'dahim_address', "Plot 14, Wharf Road, Apapa,\nLagos State, Nigeria" ) ) ); ?></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</span>
        <span><a href="<?php echo esc_url( dahim_privacy_url() ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( dahim_terms_url() ); ?>">Terms of Service</a> · dahimlogistics.com</span>
      </div>
    </div>
  </footer>

  <div class="chat-widget">
    <div class="chat-popup" id="dahim-chat-popup" aria-hidden="true">
      <div class="chat-popup-header">
        <span><?php bloginfo( 'name' ); ?> — Human Agent</span>
        <button type="button" class="chat-popup-close" id="dahim-chat-close" aria-label="Close chat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="chat-popup-body" id="dahim-chat-body" data-chat-body
        data-msg-1="<?php echo esc_attr( 'Hello 👋, welcome to ' . get_bloginfo( 'name' ) . '!' ); ?>"
        data-msg-2="Need help with shipping, freight, or logistics services? Our team is ready to assist.">
      </div>
      <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" target="_blank" rel="noopener" class="chat-popup-cta">
        Chat with Logistics Support
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </a>
    </div>

    <button type="button" class="back-to-top" id="dahim-back-to-top" aria-label="Back to top">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
    </button>

    <button type="button" class="chat-fab" id="dahim-chat-fab" aria-label="Open chat" aria-expanded="false" aria-controls="dahim-chat-popup">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 0 1 4 4v3a4 4 0 0 1-4 4H8l-4 3v-3H6a4 4 0 0 1-4-4v-3a4 4 0 0 1 4-4h2"/><path d="M8 3h9a4 4 0 0 1 4 4v3"/></svg>
      <span class="chat-fab-badge" id="dahim-chat-badge">1</span>
    </button>
  </div>

  <div class="cookie-notice" id="dahim-cookie-notice">
    <p>We use cookies for basic site functionality, and analytics if enabled. See our <a href="<?php echo esc_url( dahim_privacy_url() ); ?>">Privacy Policy</a> for details.</p>
    <button type="button" class="btn btn-dark" id="dahim-cookie-accept">Accept</button>
  </div>

<?php wp_footer(); ?>
</body>
</html>

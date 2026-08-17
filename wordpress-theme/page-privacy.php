<?php
/**
 * Template Name: Privacy Policy
 */
get_header();
dahim_breadcrumbs();
?>

  <section class="page-header" id="main-content">
    <div class="wrap">
      <div class="eyebrow">Legal</div>
      <h1><?php the_title(); ?></h1>
      <p class="lead">Last updated: <?php echo esc_html( date_i18n( 'j F Y' ) ); ?></p>
    </div>
  </section>

  <section class="section">
    <div class="wrap" style="max-width:760px;">
      <div class="entry-content" style="color:var(--graphite);font-size:15.5px;line-height:1.75;">

        <p style="background:var(--paper-dim);border-radius:var(--radius);padding:16px 20px;font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:var(--steel);margin-bottom:32px;">
          This page is a starting draft covering the data <?php bloginfo( 'name' ); ?> actually collects through this website. It has not been reviewed by a lawyer — please have it checked against Nigeria's Data Protection Act before publishing.
        </p>

        <p>
          <?php bloginfo( 'name' ); ?> ("we", "us", "our") respects your privacy. This policy explains what
          personal information we collect through this website, why we collect it, and how it's used.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Information We Collect</h2>
        <p>We collect information you provide directly to us, specifically:</p>
        <ul style="margin:0 0 18px 22px;">
          <li><strong>Contact and quote request forms</strong> — your name, company, email address, phone number, and details about your shipment or enquiry.</li>
          <li><strong>Shipment tracking</strong> — when a shipment is booked with us, we record the shipment owner's name, email, phone number, and cargo/route details in order to provide tracking updates.</li>
        </ul>
        <p>We do not collect payment card details through this website, and we do not run any e-commerce checkout.</p>

        <h2 style="font-size:20px;margin:32px 0 12px;">How We Use Your Information</h2>
        <ul style="margin:0 0 18px 22px;">
          <li>To respond to quote requests and enquiries submitted through our contact form</li>
          <li>To provide shipment status updates by email</li>
          <li>To improve our services and website</li>
        </ul>

        <h2 style="font-size:20px;margin:32px 0 12px;">WhatsApp</h2>
        <p>
          Our website links to WhatsApp for direct chat with our ops desk. Conversations conducted there are
          subject to WhatsApp's own privacy policy, which is operated by Meta, not <?php bloginfo( 'name' ); ?>.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Analytics</h2>
        <p>
          We may use Google Analytics to understand how visitors use this site. Google Analytics uses cookies
          to collect anonymous usage data such as pages visited and time spent on the site. You can opt out of
          Google Analytics tracking through your browser settings.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Data Sharing</h2>
        <p>
          We do not sell your personal information. We may share it with service providers who help us operate
          our business (such as email delivery services) solely for that purpose, or where required by law.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Your Rights</h2>
        <p>
          You may request access to, correction of, or deletion of your personal information held by us at any
          time by contacting us using the details below.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Contact Us</h2>
        <p>
          Questions about this policy can be sent to
          <a href="mailto:<?php echo esc_attr( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?>"><?php echo esc_html( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?></a>.
        </p>

      </div>
    </div>
  </section>

<?php get_footer(); ?>

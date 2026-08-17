<?php
/**
 * Template Name: Terms of Service
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
          This page is a starting draft. It has not been reviewed by a lawyer — please have actual freight/logistics service terms (liability limits, claims process, etc.) drafted or reviewed by counsel before publishing.
        </p>

        <p>
          These Terms of Service govern your use of the <?php bloginfo( 'name' ); ?> website. By using this
          site, you agree to these terms.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Website Use</h2>
        <p>
          This website is provided to give information about our logistics services and to let visitors submit
          quote requests and track shipments. Content on this site is for general information purposes and does
          not constitute a binding quote or contract of carriage until confirmed separately in writing.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Quote Requests</h2>
        <p>
          Submitting a form on this website is a request for information and does not create a binding agreement.
          Any shipment, pricing, or service agreement is only finalized once confirmed directly with our team.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Shipment Tracking</h2>
        <p>
          Tracking information displayed on this site reflects the latest status recorded by our operations team
          and is provided for convenience. For time-sensitive or disputed shipment matters, please contact our
          ops desk directly.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Limitation of Liability</h2>
        <p>
          <?php bloginfo( 'name' ); ?> is not liable for any indirect, incidental, or consequential damages
          arising from use of this website. Liability relating to actual freight, cargo, or logistics services
          is governed separately by our service agreements and applicable carriage terms, not by this website.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Changes to These Terms</h2>
        <p>
          We may update these terms from time to time. Continued use of the website after changes are posted
          constitutes acceptance of the revised terms.
        </p>

        <h2 style="font-size:20px;margin:32px 0 12px;">Contact Us</h2>
        <p>
          Questions about these terms can be sent to
          <a href="mailto:<?php echo esc_attr( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?>"><?php echo esc_html( get_option( 'dahim_email', 'info@dahimlogistics.com' ) ); ?></a>.
        </p>

      </div>
    </div>
  </section>

<?php get_footer(); ?>

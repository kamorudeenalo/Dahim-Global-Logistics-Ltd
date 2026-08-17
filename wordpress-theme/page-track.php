<?php
/**
 * Template Name: Track a Shipment
 */
get_header();

dahim_breadcrumbs();

$tracking_input = isset( $_GET['tracking'] ) ? sanitize_text_field( $_GET['tracking'] ) : '';
$result         = $tracking_input ? dahim_find_shipment( $tracking_input ) : null;
// $result === null  -> no search performed yet
// $result === false -> searched, not found
// $result === array -> found, contains full shipment record
$stage = ( is_array( $result ) ) ? $result['stage'] : 2; // default demo state when nothing searched

$stage_steps = array(
  1 => array( 'Booked', 'Shipment confirmed and documentation prepared.' ),
  2 => array( 'Cleared at Port / Picked Up', 'Customs clearance completed / cargo picked up from sender.' ),
  3 => array( 'In Transit', 'Cargo moving via inland haulage to destination.' ),
  4 => array( 'Out for Delivery', 'Out with our team for final delivery.' ),
  5 => array( 'Delivered', 'Shipment delivered and signed for at destination.' ),
);

?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">Track a Shipment</div>
      <h1>Where's your cargo right now?</h1>
      <p class="lead">Enter your waybill, container, or booking number below to see the latest status of your shipment.</p>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div class="track-panel">
        <div class="eyebrow">Shipment Tracker</div>
        <h2>Track your cargo</h2>
        <p>Your tracking number is on your booking confirmation or shipping documents. Contact our ops desk on WhatsApp if you can't find it.</p>
        <form class="track-form-lg" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
          <input type="text" name="tracking" value="<?php echo esc_attr( $tracking_input ); ?>" placeholder="e.g. DGL-7K4P-9XQR">
          <button type="submit">Track Shipment</button>
        </form>

        <?php if ( $result === false ) : ?>
          <p style="margin-top:18px;color:var(--amber);font-family:'IBM Plex Mono',monospace;font-size:13px;">No shipment found for "<?php echo esc_html( $tracking_input ); ?>". Double-check the number, or reach out on WhatsApp and we'll look it up directly.</p>
        <?php elseif ( is_array( $result ) ) : ?>
          <p style="margin-top:18px;color:var(--amber);font-family:'IBM Plex Mono',monospace;font-size:13px;"><?php echo esc_html( $result['origin'] ); ?> → <?php echo esc_html( $result['destination'] ); ?></p>
          <button type="button" class="btn btn-dark" id="dahim-open-shipment-modal" style="margin-top:14px;">View Full Shipment Details</button>
        <?php endif; ?>
      </div>

      <div class="track-steps">
        <?php
        foreach ( $stage_steps as $num => $s ) :
          $done = $num <= $stage;
        ?>
          <div class="track-step<?php echo $done ? ' done' : ''; ?>">
            <div class="dot"></div>
            <h4><?php echo str_pad( $num, 2, '0', STR_PAD_LEFT ); ?> · <?php echo esc_html( $s[0] ); ?></h4>
            <p><?php echo esc_html( $s[1] ); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ( is_array( $result ) ) : ?>
  <div class="ship-modal-overlay" id="dahim-shipment-modal" data-autopen="true" aria-hidden="true">
    <div class="ship-modal" role="dialog" aria-modal="true" aria-labelledby="dahim-shipment-modal-title">

      <div class="ship-modal-toolbar">
        <button type="button" class="ship-modal-action" id="dahim-print-shipment">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print
        </button>
        <button type="button" class="ship-modal-action" id="dahim-download-shipment">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PDF
        </button>
        <button type="button" class="ship-modal-action ship-modal-close" id="dahim-close-shipment-modal" aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="ship-doc" id="dahim-shipment-doc">
        <div class="ship-doc-header">
          <img loading="lazy" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-logo.webp' ); ?>" alt="Dahim Global Logistics" class="ship-doc-logo">
          <div class="ship-doc-header-right">
            <div class="ship-doc-title" id="dahim-shipment-modal-title">Shipment Details</div>
            <div class="ship-doc-tracking" data-field="tracking_number"><?php echo esc_html( $result['tracking_number'] ); ?></div>
          </div>
        </div>

        <div class="ship-doc-status">
          <span class="ship-status-badge" data-field="stage_label"><?php echo esc_html( $result['stage_label'] ); ?></span>
          <span class="ship-doc-generated">Generated <?php echo esc_html( date_i18n( 'j F Y, g:i a' ) ); ?></span>
        </div>

        <div class="ship-doc-grid">
          <div class="ship-doc-col">
            <h4>Shipment Owner</h4>
            <?php
            dahim_detail_row( 'Name', 'owner_name', $result['owner_name'] );
            dahim_detail_row( 'Email', 'owner_email', $result['owner_email'] );
            dahim_detail_row( 'Phone', 'owner_phone', $result['owner_phone'] );
            ?>
          </div>
          <div class="ship-doc-col">
            <h4>Consignee</h4>
            <?php
            dahim_detail_row( 'Name', 'consignee_name', $result['consignee_name'] );
            dahim_detail_row( 'Phone', 'consignee_phone', $result['consignee_phone'] );
            ?>
          </div>
        </div>

        <div class="ship-doc-grid">
          <div class="ship-doc-col">
            <h4>Route</h4>
            <?php
            dahim_detail_row( 'Origin', 'origin', $result['origin'] );
            dahim_detail_row( 'Destination', 'destination', $result['destination'] );
            dahim_detail_row( 'Current Location', 'current_location', $result['current_location'] );
            ?>
          </div>
          <div class="ship-doc-col">
            <h4>Service</h4>
            <?php
            dahim_detail_row( 'Service Type', 'service_type', $result['service_type'] );
            dahim_detail_row( 'Carrier', 'carrier', $result['carrier'] );
            dahim_detail_row( 'Date Booked', 'date_booked', $result['date_booked'] );
            dahim_detail_row( 'Estimated Delivery', 'estimated_delivery', $result['estimated_delivery'] );
            ?>
          </div>
        </div>

        <div class="ship-doc-col ship-doc-col--full">
          <h4>Cargo Details</h4>
          <?php
          dahim_detail_row( 'Description', 'package_description', $result['package_description'] );
          dahim_detail_row( 'Weight', 'weight', $result['weight'] );
          dahim_detail_row( 'Pieces', 'pieces', $result['pieces'] );
          dahim_detail_row( 'Dimensions', 'dimensions', $result['dimensions'] );
          dahim_detail_row( 'Declared Value', 'declared_value', $result['declared_value'] );
          dahim_detail_row( 'Special Instructions', 'special_instructions', $result['special_instructions'] );
          ?>
        </div>

        <div class="ship-doc-col ship-doc-col--full">
          <h4>Timeline</h4>
          <ol class="ship-doc-timeline">
            <?php foreach ( $stage_steps as $num => $s ) :
              $done = $num <= $stage; ?>
              <li class="<?php echo $done ? 'done' : ''; ?>"><?php echo esc_html( $s[0] ); ?></li>
            <?php endforeach; ?>
          </ol>
        </div>

        <div class="ship-doc-footer">
          <?php echo esc_html( get_bloginfo( 'name' ) ); ?> — <?php echo esc_html( get_bloginfo( 'url' ) ); ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <section class="section" style="padding-top:0;">
    <div class="wrap">
      <div class="section-head">
        <h2>Can't find your tracking number?</h2>
        <p>Our ops desk can look up your shipment directly.</p>
      </div>
      <div class="cta-band cta-band--boxed">
        <div class="wrap">
          <h2>Reach us on WhatsApp with your name and company, and we'll locate your shipment.</h2>
          <a href="<?php echo esc_url( dahim_whatsapp_link() ); ?>" class="btn btn-dark">Chat on WhatsApp</a>
        </div>
      </div>
    </div>
  </section>

  <?php
  $track_faqs = new WP_Query( array(
    'post_type'      => 'faq',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'meta_key'       => '_dahim_faq_group',
    'meta_value'     => 'tracking',
  ) );
  if ( $track_faqs->have_posts() ) :
  ?>
  <section class="section" id="track-faq">
    <div class="wrap">
      <div class="section-head section-head--center">
        <h2>Frequently Asked Questions</h2>
        <p>Common questions about tracking a shipment with us.</p>
      </div>
      <div class="faq-list">
        <?php
          $track_faq_n = 0;
          while ( $track_faqs->have_posts() ) : $track_faqs->the_post(); $track_faq_n++; ?>
          <details class="faq-item" name="dahim-track-faq"<?php echo ( $track_faq_n === 1 ) ? ' open' : ''; ?>>
            <summary><?php the_title(); ?></summary>
            <p><?php the_content(); ?></p>
          </details>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

<?php get_footer(); ?>

<?php get_header(); ?>

  <section class="page-header" id="main-content">
    <div class="page-header-photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/dahim-hero.webp' ); ?>" alt="Dahim Global Logistics multimodal freight — cargo plane, ship, and trucks"></div>
    <div class="wrap">
      <div class="eyebrow">404 — Not Found</div>
      <h1>This shipment took a wrong turn.</h1>
      <p class="lead">The page you're looking for doesn't exist, may have moved, or the link might be out of date.</p>
    </div>
  </section>

  <section class="section">
    <div class="wrap" style="max-width:640px;">
      <div style="margin-bottom:40px;">
        <?php get_search_form(); ?>
      </div>

      <div class="section-head" style="margin-bottom:24px;">
        <h2>Where would you like to go?</h2>
      </div>
      <div class="desks-grid" style="grid-template-columns:1fr 1fr;">
        <div class="desk-card">
          <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
          <h3>Home</h3>
          <p>Back to the homepage.</p>
          <a class="desk-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">Go home →</a>
        </div>
        <div class="desk-card">
          <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 7.65l8.42 8.42 8.42-8.42a5.4 5.4 0 0 0 0-7.65z"/></svg></div>
          <h3>Track a Shipment</h3>
          <p>Look up your cargo's status.</p>
          <a class="desk-link" href="<?php echo esc_url( home_url( '/track/' ) ); ?>">Track now →</a>
        </div>
        <div class="desk-card">
          <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
          <h3>Our Services</h3>
          <p>Freight, customs, and haulage.</p>
          <a class="desk-link" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">View services →</a>
        </div>
        <div class="desk-card">
          <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></div>
          <h3>Contact Us</h3>
          <p>Reach our ops desk directly.</p>
          <a class="desk-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch →</a>
        </div>
      </div>
    </div>
  </section>

<?php get_footer(); ?>

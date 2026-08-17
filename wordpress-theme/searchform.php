<form role="search" method="get" class="site-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <label for="dahim-search-field" class="screen-reader-text">Search</label>
  <input type="search" id="dahim-search-field" class="search-field" placeholder="Search Insights…" value="<?php echo get_search_query(); ?>" name="s">
  <button type="submit" class="search-submit" aria-label="Submit search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </button>
</form>

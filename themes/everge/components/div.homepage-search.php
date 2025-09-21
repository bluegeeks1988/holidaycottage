<?php
    $search_fields = prepopulate_search_fields();

    $terms = get_terms(array(
        'taxonomy' => 'property_features',
        'hide_empty' => false,
    ));

    $terms = show_terms($terms);

$range_display = '';
if (!empty($search_fields['checkin']) && !empty($search_fields['checkout'])) {
    $range_display = date('d/m/Y', strtotime($search_fields['checkin'])) . ' to ' . date('d/m/Y', strtotime($search_fields['checkout']));
}

// Get current values from query string, fallback defaults
$current_adults   = isset($_GET['adults'])   ? intval($_GET['adults'])   : 2;
$current_children = isset($_GET['children']) ? intval($_GET['children']) : 0;
$current_infants  = isset($_GET['infants'])  ? intval($_GET['infants'])  : 0;
?>
<!-- Mobile Search Button -->
<?php if ( !is_home() && !is_category() && !is_tag() ) : ?>
<!-- Desktop / Tablet Search Form -->
<div class="homepage2-search-container">
    <div class="homepage2-search-wrapper">
        <form action="/properties/" method="GET" class="homepage2-search-form">
            <!-- Destination -->
            <div class="homepage2-search-field">
                <label for="destination" class="homepage2-label">Search Destination</label>
                <input type="text" class="search-destination" id="destination" name="destination" placeholder="Search Destination" required value="<?php echo esc_attr($search_fields['destination']); ?>">
            </div>

           <!-- Date Range -->
<div class="homepage2-search-field">
  <label for="daterange_display" class="homepage2-label">Dates</label>
  <input type="text" id="daterange_display" name="daterange_display" readonly placeholder="Select Dates" value="">
  <input type="hidden" id="checkin_hidden" name="checkin" value="">
  <input type="hidden" id="checkout_hidden" name="checkout" value="">
</div>

<!-- Hidden inputs (used by JS + form submission) -->
<input type="hidden" id="adults_input" name="adults" value="<?php echo esc_attr($current_adults); ?>">
<input type="hidden" id="children_input" name="children" value="<?php echo esc_attr($current_children); ?>">
<input type="hidden" id="infants_input" name="infants" value="<?php echo esc_attr($current_infants); ?>">
<input type="hidden" id="guests_input" name="guests" value="<?php echo esc_attr($total_guests); ?>">

<!-- Trigger button -->
<div class="homepage2-search-field guest-picker-wrapper">
  <label class="homepage2-label">Guests</label>
  <div id="guests_trigger" class="guest-picker-toggle">
    <span id="guest_summary">
      <?php echo $total_guests; ?> guest<?php echo $total_guests !== 1 ? 's' : ''; ?>
      <?php if ($current_infants > 0): ?>, 
        <?php echo $current_infants; ?> infant<?php echo $current_infants !== 1 ? 's' : ''; ?>
      <?php endif; ?>
    </span>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
      <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2"/>
    </svg>
  </div>
</div>


<!-- Popup Modal -->
<div id="guest_picker_modal" class="guest-picker-modal hidden">
  <div class="guest-picker-box">
    
    <!-- Adults -->
    <div class="guest-picker-row">
      <div>
        <strong>Adults</strong><br><small>18 and over</small>
      </div>
      <div class="guest-picker-controls">
        <button type="button" class="decrease" data-target="adults">−</button>
        <span id="adults_count"><?php echo esc_html($current_adults); ?></span>
        <button type="button" class="increase" data-target="adults">+</button>
      </div>
    </div>

    <!-- Children -->
    <div class="guest-picker-row">
      <div>
        <strong>Children</strong><br><small>2 to 17</small>
      </div>
      <div class="guest-picker-controls">
        <button type="button" class="decrease" data-target="children">−</button>
        <span id="children_count"><?php echo esc_html($current_children); ?></span>
        <button type="button" class="increase" data-target="children">+</button>
      </div>
    </div>

    <!-- Infants -->
    <div class="guest-picker-row">
      <div>
        <strong>Infants</strong><br><small>Under 2</small>
      </div>
      <div class="guest-picker-controls">
        <button type="button" class="decrease" data-target="infants">−</button>
        <span id="infants_count"><?php echo esc_html($current_infants); ?></span>
        <button type="button" class="increase" data-target="infants">+</button>
      </div>
    </div>

    <!-- Actions -->
    <div class="guest-picker-actions">
      <button type="button" id="clear_guest_picker" class="btn-clear">Clear</button>
      <button type="button" id="apply_guest_picker" class="btn-apply">Apply</button>
    </div>
  </div>
</div>




            <!-- Features -->
            <?php if (isset($terms) && $terms): ?>
                <div class="homepage2-search-field">
                    <label for="filter" class="homepage2-label">Features</label>
                    <select id="filter" name="filter">
                        <option value="">Select Feature</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo esc_attr($term->slug); ?>">
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Pets Toggle -->
            <div class="column">
    <div class="field">
        <div class="homepage2-toggle-wrapper homepage2-toggle-field">
            <label class="homepage2-toggle-switch">
                <span class="homepage2-toggle-text">No Pets</span>
				<input type="checkbox" id="pet-toggle" name="pets" <?php checked(true, $search_fields['pets'] ?? true); ?>>
                <span class="homepage2-slider"></span>
                <span class="homepage2-toggle-text">Pets Allowed</span>
            </label>
        </div>
    </div>
</div>

            <!-- Hidden Inputs -->
            <input type="hidden" id="combined-filter" name="filter" value="">
            <input type="hidden" class="search-latitude" id="latitude" name="latitude" value="<?php echo esc_attr($search_fields['latitude']); ?>">
			<input type="hidden" class="search-longitude" id="longitude" name="longitude" value="<?php echo esc_attr($search_fields['longitude']); ?>">

            <!-- Submit -->
            <div class="homepage2-search-field">
                <button type="submit" class="button light-green flex w-100 items-center justify-center radius-10 homepage2-button">
                    <span class="mr-1 flex-inline items-center justify-center"><?php get_svg('search'); ?></span> Search
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  if (typeof flatpickr !== 'undefined') {
    flatpickr("#daterange_display", {
      mode: "range",
      dateFormat: "d/m/Y",
      minDate: "today",
      onChange: function(selectedDates, dateStr) {
        if (selectedDates.length === 2) {
          const [start, end] = selectedDates;
          document.getElementById('checkin_hidden').value = start.toISOString().split('T')[0];
          document.getElementById('checkout_hidden').value = end.toISOString().split('T')[0];
        }
      }
    });
  }
});

</script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  const trigger = document.getElementById('guests_trigger');
  const modal = document.getElementById('guest_picker_modal');
  const applyBtn = document.getElementById('apply_guest_picker');
  const clearBtn = document.getElementById('clear_guest_picker');

  // Counts initialized from hidden inputs (set by PHP)
  const counts = {
    adults: parseInt(document.getElementById('adults_input').value) || 2,
    children: parseInt(document.getElementById('children_input').value) || 0,
    infants: parseInt(document.getElementById('infants_input').value) || 0,
  };

  // Update summary + hidden inputs
  const updateSummary = () => {
    const totalGuests = counts.adults + counts.children;
    let summary = `${totalGuests} guest${totalGuests !== 1 ? 's' : ''}`;
    if (counts.infants > 0) {
      summary += `, ${counts.infants} infant${counts.infants !== 1 ? 's' : ''}`;
    }
    document.getElementById('guest_summary').innerText = summary;

    // Update hidden inputs
    document.getElementById('adults_input').value = counts.adults;
    document.getElementById('children_input').value = counts.children;
    document.getElementById('infants_input').value = counts.infants;
    document.getElementById('guests_input').value = totalGuests;
  };

  // Update UI (numbers in popup)
  const updateUI = () => {
    document.getElementById('adults_count').innerText = counts.adults;
    document.getElementById('children_count').innerText = counts.children;
    document.getElementById('infants_count').innerText = counts.infants;
  };

  // Open modal
  trigger.addEventListener('click', () => {
    modal.classList.remove('hidden');
  });

  // Close modal when clicking outside box
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });

  // Apply button closes modal
  applyBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    updateSummary();
  });

  // Clear button resets values
  clearBtn.addEventListener('click', () => {
    counts.adults = 2;
    counts.children = 0;
    counts.infants = 0;
    updateUI();
    updateSummary();
  });

  // Handle plus/minus clicks
  document.querySelectorAll('.increase, .decrease').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.target;
      if (!counts.hasOwnProperty(target)) return;

      if (btn.classList.contains('increase')) {
        counts[target]++;
      } else {
        if (target === 'adults') {
          counts[target] = Math.max(1, counts[target] - 1); // keep at least 1 adult
        } else {
          counts[target] = Math.max(0, counts[target] - 1);
        }
      }
      updateUI();
    });
  });

  // Initialize on page load
  updateUI();
  updateSummary();
});
</script>


<div class="wrap">
    <h1>Sykes Plugin Settings</h1>
    <form method="post" action="">
        <input type="hidden" name="sykes_form_submitted" value="1" />
        <label>
            Save IDs Interval (in minutes):
            <input type="number" name="save_ids_interval" value="<?php echo esc_attr(get_option('save_ids_interval', 60)); ?>" min="1" />
        </label><br>
        <label>
            Save Property Information Interval (in minutes):
            <input type="number" name="save_property_information_interval" value="<?php echo esc_attr(get_option('save_property_information_interval', 60)); ?>" min="1" />
        </label><br>

        <input type="submit" value="Save Settings and Schedule Cron Jobs" class="button button-primary" />
    </form>
</div>

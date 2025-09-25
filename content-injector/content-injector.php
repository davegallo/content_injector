<?php
/**
 * Plugin Name: Content Injector
 * Description: Adds a dashboard to inject shortcodes and ads into various locations in all posts, plus an exit pop-up.
 * Version: 1.7
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define the number of slots as a constant for easy updates
define('CI_TOTAL_SLOTS', 4);

// 1. Create the Admin Menu Item
add_action('admin_menu', 'ci_add_admin_menu');
function ci_add_admin_menu() {
    add_options_page('Content Injector Settings', 'Content Injector', 'manage_options', 'content-injector', 'ci_settings_page_html');
}

// 2. Register Settings using the Settings API
add_action('admin_init', 'ci_settings_init');
function ci_settings_init() {
    register_setting('content_injector_group', 'ci_settings', ['sanitize_callback' => 'ci_sanitize_settings']);

    add_settings_section('ci_popup_section', 'Exit-Intent Pop-up Ad', null, 'content-injector-popup');
    add_settings_field("ci_popup", "Pop-up Settings", 'ci_render_popup_field', 'content-injector-popup', 'ci_popup_section');

    add_settings_section('ci_shortcode_section', 'Manage Your Shortcode Slots', null, 'content-injector');
    for ($i = 1; $i <= CI_TOTAL_SLOTS; $i++) {
        add_settings_field("ci_slot_{$i}", "Shortcode Slot {$i}", 'ci_render_shortcode_slot_field', 'content-injector', 'ci_shortcode_section', ['slot_number' => $i]);
    }

    add_settings_section('ci_ad_section', 'Manage Your Ad Slots', null, 'content-injector-ads');
    for ($i = 1; $i <= CI_TOTAL_SLOTS; $i++) {
        add_settings_field("ci_ad_slot_{$i}", "Ad Slot {$i}", 'ci_render_ad_slot_field', 'content-injector-ads', 'ci_ad_section', ['slot_number' => $i]);
    }
}

// 3a. Render the HTML for the POP-UP slot
function ci_render_popup_field() {
    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }
    $enabled = isset($options["popup_enabled"]) ? $options["popup_enabled"] : 0;
    $desktop_url = isset($options["popup_desktop_image_url"]) ? $options["popup_desktop_image_url"] : '';
    $mobile_url = isset($options["popup_mobile_image_url"]) ? $options["popup_mobile_image_url"] : '';
    $dest_url = isset($options["popup_dest_url"]) ? $options["popup_dest_url"] : '';

    echo '<div><label><input type="checkbox" name="ci_settings[popup_enabled]" value="1" ' . checked(1, $enabled, false) . '> <strong>Enable Exit Pop-up</strong></label></div>';
    echo '<p class="description">This pop-up will appear when a user tries to exit your site on any blog post.</p>';
    echo '<div style="margin-top: 10px;"><label for="popup_desktop_image_url">Desktop Image URL (700x500px):</label><br>';
    echo '<input type="text" id="popup_desktop_image_url" name="ci_settings[popup_desktop_image_url]" value="' . esc_attr($desktop_url) . '" class="regular-text ci-image-url-field" placeholder="https://example.com/desktop-ad.jpg">';
    echo '<button type="button" class="button ci-upload-btn">Upload Image</button></div>';
    echo '<div style="margin-top: 10px;"><label for="popup_mobile_image_url">Mobile Image URL (360x660px):</label><br>';
    echo '<input type="text" id="popup_mobile_image_url" name="ci_settings[popup_mobile_image_url]" value="' . esc_attr($mobile_url) . '" class="regular-text ci-image-url-field" placeholder="https://example.com/mobile-ad.jpg">';
    echo '<button type="button" class="button ci-upload-btn">Upload Image</button></div>';
    echo '<div style="margin-top: 10px;"><label for="popup_dest_url">Destination URL (for both images):</label><br>';
    echo '<input type="url" id="popup_dest_url" name="ci_settings[popup_dest_url]" value="' . esc_attr($dest_url) . '" class="regular-text" placeholder="https://sponsor.com/product"></div>';
}

// 3b. Render the HTML for each SHORTCODE slot
function ci_render_shortcode_slot_field($args) {
    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }
    $slot_number = $args['slot_number'];
    $enabled = isset($options["slot_{$slot_number}_enabled"]) ? $options["slot_{$slot_number}_enabled"] : 0;
    $shortcode = isset($options["slot_{$slot_number}_shortcode"]) ? $options["slot_{$slot_number}_shortcode"] : '';
    $location = isset($options["slot_{$slot_number}_location"]) ? $options["slot_{$slot_number}_location"] : 'disabled';
    $selected_cats = isset($options["slot_{$slot_number}_categories"]) ? $options["slot_{$slot_number}_categories"] : [];

    ci_render_location_dropdown($location, "ci_settings[slot_{$slot_number}_location]");
    echo '<div><label><input type="checkbox" name="ci_settings[slot_' . $slot_number . '_enabled]" value="1" ' . checked(1, $enabled, false) . '> Enable this shortcode slot</label></div>';
    echo '<div style="margin-top: 10px;"><label>Shortcode:</label><br><input type="text" name="ci_settings[slot_' . $slot_number . '_shortcode]" value="' . esc_attr($shortcode) . '" class="regular-text" placeholder="[your_shortcode_here]"></div>';
    ci_render_category_checkboxes($selected_cats, "ci_settings[slot_{$slot_number}_categories]");
}

// 3c. Render the HTML for each AD slot
function ci_render_ad_slot_field($args) {
    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }
    $slot_number = $args['slot_number'];
    $enabled = isset($options["ad_slot_{$slot_number}_enabled"]) ? $options["ad_slot_{$slot_number}_enabled"] : 0;
    $image_url = isset($options["ad_slot_{$slot_number}_image_url"]) ? $options["ad_slot_{$slot_number}_image_url"] : '';
    $dest_url = isset($options["ad_slot_{$slot_number}_dest_url"]) ? $options["ad_slot_{$slot_number}_dest_url"] : '';
    $location = isset($options["ad_slot_{$slot_number}_location"]) ? $options["ad_slot_{$slot_number}_location"] : 'disabled';
    $selected_cats = isset($options["ad_slot_{$slot_number}_categories"]) ? $options["ad_slot_{$slot_number}_categories"] : [];

    ci_render_location_dropdown($location, "ci_settings[ad_slot_{$slot_number}_location]");
    echo '<div><label><input type="checkbox" name="ci_settings[ad_slot_' . $slot_number . '_enabled]" value="1" ' . checked(1, $enabled, false) . '> Enable this ad slot</label></div>';
    echo '<div style="margin-top: 10px;"><label>Image URL:</label><br>';
    echo '<input type="text" name="ci_settings[ad_slot_' . $slot_number . '_image_url]" value="' . esc_attr($image_url) . '" class="regular-text ci-image-url-field" placeholder="https://example.com/ad.jpg">';
    echo ' <button type="button" class="button ci-upload-btn">Upload Image</button></div>';
    echo '<div style="margin-top: 10px;"><label>Destination URL:</label><br><input type="url" name="ci_settings[ad_slot_' . $slot_number . '_dest_url]" value="' . esc_attr($dest_url) . '" class="regular-text" placeholder="https://sponsor.com/product"></div>';
    ci_render_category_checkboxes($selected_cats, "ci_settings[ad_slot_{$slot_number}_categories]");
}

// 3d. Reusable function for the location dropdown
function ci_render_location_dropdown($current_location, $name) {
    $locations = ci_get_valid_locations();

    echo '<div style="margin-top: 10px; margin-bottom: 20px;">';
    echo '<label>Location:</label><br>';
    echo '<select name="' . esc_attr($name) . '">';
    foreach ($locations as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($current_location, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div>';
}

// 3e. Reusable function for category checkboxes
function ci_render_category_checkboxes($selected_cats, $name_prefix) {
    $categories = get_categories(['hide_empty' => 0]);
    $selected_cats = is_array($selected_cats) ? $selected_cats : [];

    echo '<div style="margin-top: 10px; max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px;">';
    echo '<strong>Show on Categories:</strong><br>';
    echo '<p class="description">If no categories are selected, it will show on all posts.</p>';

    foreach ($categories as $category) {
        $field_name = esc_attr($name_prefix) . '[' . esc_attr($category->term_id) . ']';
        $checked = in_array($category->term_id, $selected_cats);
        echo '<label style="display: block; margin-bottom: 5px;">';
        echo '<input type="checkbox" name="' . $field_name . '" value="' . esc_attr($category->term_id) . '" ' . checked($checked, true, false) . '> ';
        echo esc_html($category->name);
        echo '</label>';
    }
    echo '</div>';
}

// NEW. Sanitize and validate all settings.
function ci_sanitize_settings($input) {
    $new_input = [];
    $locations = ci_get_valid_locations(); // Get the list of valid locations

    // Sanitize Pop-up settings
    $new_input['popup_enabled'] = isset($input['popup_enabled']) ? 1 : 0;
    $new_input['popup_desktop_image_url'] = isset($input['popup_desktop_image_url']) ? esc_url_raw($input['popup_desktop_image_url']) : '';
    $new_input['popup_mobile_image_url'] = isset($input['popup_mobile_image_url']) ? esc_url_raw($input['popup_mobile_image_url']) : '';
    $new_input['popup_dest_url'] = isset($input['popup_dest_url']) ? esc_url_raw($input['popup_dest_url']) : '';

    // Sanitize Shortcode and Ad slots
    for ($i = 1; $i <= CI_TOTAL_SLOTS; $i++) {
        // Shortcode slots
        $new_input["slot_{$i}_enabled"] = isset($input["slot_{$i}_enabled"]) ? 1 : 0;
        if (isset($input["slot_{$i}_location"]) && array_key_exists($input["slot_{$i}_location"], $locations)) {
            $new_input["slot_{$i}_location"] = $input["slot_{$i}_location"];
        } else {
            $new_input["slot_{$i}_location"] = 'disabled';
        }
        // For shortcodes, we allow the raw shortcode format [shortcode attr="value"], but we should still sanitize it.
        // A simple sanitize_text_field is a good starting point. For more complex shortcodes, this might need adjustment.
        $new_input["slot_{$i}_shortcode"] = isset($input["slot_{$i}_shortcode"]) ? sanitize_text_field($input["slot_{$i}_shortcode"]) : '';

        // Sanitize category selections for shortcode slots
        if (!empty($input["slot_{$i}_categories"]) && is_array($input["slot_{$i}_categories"])) {
            $new_input["slot_{$i}_categories"] = array_map('absint', array_keys($input["slot_{$i}_categories"]));
        } else {
            $new_input["slot_{$i}_categories"] = [];
        }

        // Ad slots
        $new_input["ad_slot_{$i}_enabled"] = isset($input["ad_slot_{$i}_enabled"]) ? 1 : 0;
        if (isset($input["ad_slot_{$i}_location"]) && array_key_exists($input["ad_slot_{$i}_location"], $locations)) {
            $new_input["ad_slot_{$i}_location"] = $input["ad_slot_{$i}_location"];
        } else {
            $new_input["ad_slot_{$i}_location"] = 'disabled';
        }
        $new_input["ad_slot_{$i}_image_url"] = isset($input["ad_slot_{$i}_image_url"]) ? esc_url_raw($input["ad_slot_{$i}_image_url"]) : '';
        $new_input["ad_slot_{$i}_dest_url"] = isset($input["ad_slot_{$i}_dest_url"]) ? esc_url_raw($input["ad_slot_{$i}_dest_url"]) : '';

        // Sanitize category selections for ad slots
        if (!empty($input["ad_slot_{$i}_categories"]) && is_array($input["ad_slot_{$i}_categories"])) {
            $new_input["ad_slot_{$i}_categories"] = array_map('absint', array_keys($input["ad_slot_{$i}_categories"]));
        } else {
            $new_input["ad_slot_{$i}_categories"] = [];
        }
    }

    return $new_input;
}

// Helper function to get valid locations
function ci_get_valid_locations() {
    return [
        'disabled' => 'Disabled', 'before_content' => 'Top of Post (Before Content)', 'after_content' => 'Bottom of Post (After Content)',
        'after_p_1' => 'Below 1st Paragraph', 'after_p_2' => 'Below 2nd Paragraph', 'after_p_3' => 'Below 3rd Paragraph',
        'after_h2_1' => 'Below 1st H2 Heading', 'after_h2_2' => 'Below 2nd H2 Heading', 'after_h2_3' => 'Below 3rd H2 Heading',
        'after_h3_1' => 'Below 1st H3 Heading', 'after_h3_2' => 'Below 2nd H3 Heading', 'after_h3_3' => 'Below 3rd H3 Heading',
        'after_h2_from_bottom_2' => 'Below 2nd H2 From Bottom', 'after_h2_from_bottom_3' => 'Below 3rd H2 From Bottom',
        'middle_content' => 'Middle of Post (Approx.)', 'before_last_p' => 'Before Last Paragraph',
    ];
}

// Enqueue admin scripts, including the media uploader
add_action('admin_enqueue_scripts', 'ci_admin_enqueue_scripts');
function ci_admin_enqueue_scripts($hook) {
    // Only load on our plugin's settings page
    if ('settings_page_content-injector' !== $hook) {
        return;
    }
    // Enqueue the media uploader script
    wp_enqueue_media();
    wp_enqueue_script(
        'ci-admin-media-uploader',
        plugin_dir_url(__FILE__) . 'assets/admin-media-uploader.js',
        ['jquery'],
        '1.0.0',
        true
    );
}


// 4. Render the main settings page container
function ci_settings_page_html() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('content_injector_group');
            do_settings_sections('content-injector-popup');
            echo '<hr>';
            do_settings_sections('content-injector');
            echo '<hr>';
            do_settings_sections('content-injector-ads');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// 5. Hook into `the_content` to inject shortcodes and ads
add_filter('the_content', 'ci_inject_content', 100);
function ci_inject_content($content) {
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }

    $injections = [];

    for ($i = 1; $i <= CI_TOTAL_SLOTS; $i++) {
        $selected_cats = isset($options["slot_{$i}_categories"]) ? $options["slot_{$i}_categories"] : [];
        $is_category_match = empty($selected_cats) || has_category($selected_cats);

        if ($is_category_match && !empty($options["slot_{$i}_enabled"]) && !empty($options["slot_{$i}_shortcode"]) && $options["slot_{$i}_location"] !== 'disabled') {
            $injections[] = ['html' => do_shortcode($options["slot_{$i}_shortcode"]), 'location' => $options["slot_{$i}_location"]];
        }
    }

    for ($i = 1; $i <= CI_TOTAL_SLOTS; $i++) {
        $selected_cats = isset($options["ad_slot_{$i}_categories"]) ? $options["ad_slot_{$i}_categories"] : [];
        $is_category_match = empty($selected_cats) || has_category($selected_cats);

        if ($is_category_match && !empty($options["ad_slot_{$i}_enabled"]) && !empty($options["ad_slot_{$i}_image_url"]) && !empty($options["ad_slot_{$i}_dest_url"]) && $options["ad_slot_{$i}_location"] !== 'disabled') {
            $image_url = esc_url($options["ad_slot_{$i}_image_url"]);
            $dest_url = esc_url($options["ad_slot_{$i}_dest_url"]);
            $ad_html = '<div class="ci-ad-wrapper" style="margin: 20px 0; text-align: center;"><a href="' . $dest_url . '" target="_blank" rel="nofollow sponsored"><img src="' . $image_url . '" alt="Advertisement" style="max-width:100%; height:auto; border:0;" /></a></div>';
            $injections[] = ['html' => $ad_html, 'location' => $options["ad_slot_{$i}_location"]];
        }
    }

    if (empty($injections)) return $content;

    foreach ($injections as $injection) {
        $content = ci_apply_injection($content, $injection['html'], $injection['location']);
    }
    return $content;
}

// --- FIX: Re-included the missing helper function ---
// 6. Helper function to apply the injection logic
function ci_apply_injection($content, $html_to_inject, $location) {
    switch ($location) {
        case 'before_content': return $html_to_inject . $content;
        case 'after_content': return $content . $html_to_inject;
        case 'middle_content':
            $paragraphs = explode('</p>', $content);
            $midpoint = floor(count($paragraphs) / 2);
            if ($midpoint > 0) $paragraphs[$midpoint] .= $html_to_inject;
            return implode('</p>', $paragraphs);
        case 'before_last_p':
            $paragraphs = explode('</p>', $content);
            $last_p_index = count($paragraphs) - 2;
            if ($last_p_index >= 0) $paragraphs[$last_p_index] = $paragraphs[$last_p_index] . $html_to_inject;
            return implode('</p>', $paragraphs);
        case 'after_h2_from_bottom_2':
        case 'after_h2_from_bottom_3':
            $n_from_bottom = ($location === 'after_h2_from_bottom_2') ? 2 : 3;
            $parts = preg_split('/(<\/h2>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            $h2_count = floor(count($parts) / 2);
            if ($h2_count >= $n_from_bottom) {
                $target_h2_from_top = $h2_count - $n_from_bottom + 1;
                $target_array_index = ($target_h2_from_top * 2) - 1;
                if (isset($parts[$target_array_index])) $parts[$target_array_index] .= $html_to_inject;
                return implode('', $parts);
            }
            break;
        default:
            if (preg_match('/^after_([ph][23]?)_(\d+)$/', $location, $matches)) {
                $tag_type = $matches[1];
                $n = intval($matches[2]);
                if ($n > 0) {
                    $closing_tag = '</' . $tag_type . '>';
                    $parts = preg_split('/(' . preg_quote($closing_tag, '/') . ')/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $count = 0;
                    $new_content = '';
                    for ($j = 0; $j < count($parts); $j+=2) {
                        $new_content .= $parts[$j];
                        if(isset($parts[$j+1])) {
                            $new_content .= $parts[$j+1];
                            $count++;
                            if ($count === $n) $new_content .= $html_to_inject;
                        }
                    }
                    return $new_content;
                }
            }
            break;
    }
    return $content;
}

// 7. Add an uninstall hook to clean up the database
register_uninstall_hook(__FILE__, 'ci_uninstall');
function ci_uninstall() {
    if (!defined('WP_UNINSTALL_PLUGIN')) exit;
    delete_option('ci_settings');
}

// 8. Enqueue frontend assets and add pop-up HTML to the footer
add_action('wp_enqueue_scripts', 'ci_enqueue_frontend_assets');
function ci_enqueue_frontend_assets() {
    if (!is_singular('post')) return;

    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }

    if (empty($options['popup_enabled']) || empty($options['popup_desktop_image_url']) || empty($options['popup_mobile_image_url']) || empty($options['popup_dest_url'])) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'ci-popup-styles',
        plugin_dir_url(__FILE__) . 'assets/popup.css',
        [],
        '1.0.0'
    );

    // Enqueue JS
    wp_enqueue_script(
        'ci-popup-script',
        plugin_dir_url(__FILE__) . 'assets/popup.js',
        [],
        '1.0.0',
        true // Load in footer
    );
}

add_action('wp_footer', 'ci_add_popup_html_to_footer');
function ci_add_popup_html_to_footer() {
    if (!is_singular('post')) return;

    static $options = null;
    if (is_null($options)) {
        $options = get_option('ci_settings');
    }

    if (empty($options['popup_enabled']) || empty($options['popup_desktop_image_url']) || empty($options['popup_mobile_image_url']) || empty($options['popup_dest_url'])) {
        return;
    }
    $desktop_img = esc_url($options['popup_desktop_image_url']);
    $mobile_img = esc_url($options['popup_mobile_image_url']);
    $dest_url = esc_url($options['popup_dest_url']);
    ?>
    <div id="ci-popup-overlay">
        <div id="ci-popup-content">
            <button id="ci-popup-close" title="Close">&times;</button>
            <a href="<?php echo $dest_url; ?>" target="_blank" rel="nofollow sponsored">
                <picture>
                    <source media="(max-width: 600px)" srcset="<?php echo $mobile_img; ?>">
                    <img src="<?php echo $desktop_img; ?>" alt="Advertisement">
                </picture>
            </a>
        </div>
    </div>
    <?php
}
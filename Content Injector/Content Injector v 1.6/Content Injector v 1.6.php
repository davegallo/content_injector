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

// 1. Create the Admin Menu Item
add_action('admin_menu', 'ci_add_admin_menu');
function ci_add_admin_menu() {
    add_options_page('Content Injector Settings', 'Content Injector', 'manage_options', 'content-injector', 'ci_settings_page_html');
}

// 2. Register Settings using the Settings API
add_action('admin_init', 'ci_settings_init');
function ci_settings_init() {
    register_setting('content_injector_group', 'ci_settings');

    add_settings_section('ci_popup_section', 'Exit-Intent Pop-up Ad', null, 'content-injector-popup');
    add_settings_field("ci_popup", "Pop-up Settings", 'ci_render_popup_field', 'content-injector-popup', 'ci_popup_section');

    add_settings_section('ci_shortcode_section', 'Manage Your Shortcode Slots', null, 'content-injector');
    for ($i = 1; $i <= 4; $i++) {
        add_settings_field("ci_slot_{$i}", "Shortcode Slot {$i}", 'ci_render_shortcode_slot_field', 'content-injector', 'ci_shortcode_section', ['slot_number' => $i]);
    }

    add_settings_section('ci_ad_section', 'Manage Your Ad Slots', null, 'content-injector-ads');
    for ($i = 1; $i <= 4; $i++) {
        add_settings_field("ci_ad_slot_{$i}", "Ad Slot {$i}", 'ci_render_ad_slot_field', 'content-injector-ads', 'ci_ad_section', ['slot_number' => $i]);
    }
}

// 3a. Render the HTML for the POP-UP slot
function ci_render_popup_field() {
    $options = get_option('ci_settings');
    $enabled = isset($options["popup_enabled"]) ? $options["popup_enabled"] : 0;
    $desktop_url = isset($options["popup_desktop_image_url"]) ? $options["popup_desktop_image_url"] : '';
    $mobile_url = isset($options["popup_mobile_image_url"]) ? $options["popup_mobile_image_url"] : '';
    $dest_url = isset($options["popup_dest_url"]) ? $options["popup_dest_url"] : '';

    echo '<div><label><input type="checkbox" name="ci_settings[popup_enabled]" value="1" ' . checked(1, $enabled, false) . '> <strong>Enable Exit Pop-up</strong></label></div>';
    echo '<p class="description">This pop-up will appear when a user tries to exit your site on any blog post.</p>';
    echo '<div style="margin-top: 10px;"><label for="popup_desktop_image_url">Desktop Image URL (700x500px):</label><br>';
    echo '<input type="url" id="popup_desktop_image_url" name="ci_settings[popup_desktop_image_url]" value="' . esc_attr($desktop_url) . '" class="regular-text" placeholder="https://example.com/desktop-ad.jpg"></div>';
    echo '<div style="margin-top: 10px;"><label for="popup_mobile_image_url">Mobile Image URL (360x660px):</label><br>';
    echo '<input type="url" id="popup_mobile_image_url" name="ci_settings[popup_mobile_image_url]" value="' . esc_attr($mobile_url) . '" class="regular-text" placeholder="https://example.com/mobile-ad.jpg"></div>';
    echo '<div style="margin-top: 10px;"><label for="popup_dest_url">Destination URL (for both images):</label><br>';
    echo '<input type="url" id="popup_dest_url" name="ci_settings[popup_dest_url]" value="' . esc_attr($dest_url) . '" class="regular-text" placeholder="https://sponsor.com/product"></div>';
}

// 3b. Render the HTML for each SHORTCODE slot
function ci_render_shortcode_slot_field($args) {
    $options = get_option('ci_settings');
    $slot_number = $args['slot_number'];
    $enabled = isset($options["slot_{$slot_number}_enabled"]) ? $options["slot_{$slot_number}_enabled"] : 0;
    $shortcode = isset($options["slot_{$slot_number}_shortcode"]) ? $options["slot_{$slot_number}_shortcode"] : '';
    $location = isset($options["slot_{$slot_number}_location"]) ? $options["slot_{$slot_number}_location"] : 'disabled';
    
    ci_render_location_dropdown($location, "ci_settings[slot_{$slot_number}_location]");
    echo '<div><label><input type="checkbox" name="ci_settings[slot_' . $slot_number . '_enabled]" value="1" ' . checked(1, $enabled, false) . '> Enable this shortcode slot</label></div>';
    echo '<div style="margin-top: 10px;"><label>Shortcode:</label><br><input type="text" name="ci_settings[slot_' . $slot_number . '_shortcode]" value="' . esc_attr($shortcode) . '" class="regular-text" placeholder="[your_shortcode_here]"></div>';
}

// 3c. Render the HTML for each AD slot
function ci_render_ad_slot_field($args) {
    $options = get_option('ci_settings');
    $slot_number = $args['slot_number'];
    $enabled = isset($options["ad_slot_{$slot_number}_enabled"]) ? $options["ad_slot_{$slot_number}_enabled"] : 0;
    $image_url = isset($options["ad_slot_{$slot_number}_image_url"]) ? $options["ad_slot_{$slot_number}_image_url"] : '';
    $dest_url = isset($options["ad_slot_{$slot_number}_dest_url"]) ? $options["ad_slot_{$slot_number}_dest_url"] : '';
    $location = isset($options["ad_slot_{$slot_number}_location"]) ? $options["ad_slot_{$slot_number}_location"] : 'disabled';
    
    ci_render_location_dropdown($location, "ci_settings[ad_slot_{$slot_number}_location]");
    echo '<div><label><input type="checkbox" name="ci_settings[ad_slot_' . $slot_number . '_enabled]" value="1" ' . checked(1, $enabled, false) . '> Enable this ad slot</label></div>';
    echo '<div style="margin-top: 10px;"><label>Image URL:</label><br><input type="url" name="ci_settings[ad_slot_' . $slot_number . '_image_url]" value="' . esc_attr($image_url) . '" class="regular-text" placeholder="https://example.com/ad.jpg"></div>';
    echo '<div style="margin-top: 10px;"><label>Destination URL:</label><br><input type="url" name="ci_settings[ad_slot_' . $slot_number . '_dest_url]" value="' . esc_attr($dest_url) . '" class="regular-text" placeholder="https://sponsor.com/product"></div>';
}

// 3d. Reusable function for the location dropdown
function ci_render_location_dropdown($current_location, $name) {
    $locations = [
        'disabled' => 'Disabled', 'before_content' => 'Top of Post (Before Content)', 'after_content' => 'Bottom of Post (After Content)',
        'after_p_1' => 'Below 1st Paragraph', 'after_p_2' => 'Below 2nd Paragraph', 'after_p_3' => 'Below 3rd Paragraph',
        'after_h2_1' => 'Below 1st H2 Heading', 'after_h2_2' => 'Below 2nd H2 Heading', 'after_h2_3' => 'Below 3rd H2 Heading',
        'after_h3_1' => 'Below 1st H3 Heading', 'after_h3_2' => 'Below 2nd H3 Heading', 'after_h3_3' => 'Below 3rd H3 Heading',
        'after_h2_from_bottom_2' => 'Below 2nd H2 From Bottom', 'after_h2_from_bottom_3' => 'Below 3rd H2 From Bottom',
        'middle_content' => 'Middle of Post (Approx.)', 'before_last_p' => 'Before Last Paragraph',
    ];
    
    echo '<div style="margin-top: 10px; margin-bottom: 20px;">';
    echo '<label>Location:</label><br>';
    echo '<select name="' . esc_attr($name) . '">';
    foreach ($locations as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($current_location, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div>';
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
add_filter('the_content', 'ci_inject_content');
function ci_inject_content($content) {
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) return $content;
    $options = get_option('ci_settings');
    $injections = [];

    for ($i = 1; $i <= 4; $i++) {
        if (!empty($options["slot_{$i}_enabled"]) && !empty($options["slot_{$i}_shortcode"]) && $options["slot_{$i}_location"] !== 'disabled') {
            $injections[] = ['html' => do_shortcode($options["slot_{$i}_shortcode"]), 'location' => $options["slot_{$i}_location"]];
        }
    }
    
    for ($i = 1; $i <= 4; $i++) {
        if (!empty($options["ad_slot_{$i}_enabled"]) && !empty($options["ad_slot_{$i}_image_url"]) && !empty($options["ad_slot_{$i}_dest_url"]) && $options["ad_slot_{$i}_location"] !== 'disabled') {
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

// 8. Hook into `wp_footer` to add pop-up assets
add_action('wp_footer', 'ci_inject_popup_assets');
function ci_inject_popup_assets() {
    if (!is_singular('post')) return;
    $options = get_option('ci_settings');
    if (empty($options['popup_enabled']) || empty($options['popup_desktop_image_url']) || empty($options['popup_mobile_image_url']) || empty($options['popup_dest_url'])) {
        return;
    }
    $desktop_img = esc_url($options['popup_desktop_image_url']);
    $mobile_img = esc_url($options['popup_mobile_image_url']);
    $dest_url = esc_url($options['popup_dest_url']);
    ?>
    <style>
        #ci-popup-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7); z-index: 10000;
            justify-content: center; align-items: center;
        }
        #ci-popup-content {
            position: relative; background: #fff; padding: 0;
            max-width: 90%; max-height: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        #ci-popup-content img { display: block; max-width: 100%; height: auto; }
        #ci-popup-close {
            position: absolute; top: -15px; right: -15px;
            width: 30px; height: 30px; border-radius: 50%;
            background: #000; color: #fff; font-size: 20px;
            border: 2px solid #fff; cursor: pointer;
            display: flex; justify-content: center; align-items: center; line-height: 1;
        }
    </style>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('ci-popup-overlay');
            const closeBtn = document.getElementById('ci-popup-close');
            let hasBeenShown = sessionStorage.getItem('ci_popup_shown');

            function showPopup() {
                if (!hasBeenShown) {
                    overlay.style.display = 'flex';
                    sessionStorage.setItem('ci_popup_shown', 'true');
                    hasBeenShown = true;
                    // Disable body scroll when popup is active
                    document.body.style.overflow = 'hidden';
                }
            }

            function hidePopup() {
                overlay.style.display = 'none';
                // Re-enable body scroll
                document.body.style.overflow = '';
            }

            document.addEventListener('mouseout', function(e) {
                if (!e.toElement && !e.relatedTarget && e.clientY < 10) {
                    showPopup();
                }
            }, {once: true}); // Trigger only once

            closeBtn.addEventListener('click', hidePopup);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    hidePopup();
                }
            });
        });
    </script>
    <?php
}
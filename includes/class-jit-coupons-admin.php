<?php
if (! defined('ABSPATH')) {
    exit;
}

class JIT_Coupons_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add a submenu under "WooCommerce".
     */
    public function add_submenu_page()
    {
        add_submenu_page(
            'woocommerce',
            'JIT Coupons',
            'JIT Coupons',
            'manage_woocommerce',
            'jit-coupons',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue JS (and CSS if needed) only on our plugin page.
     */
    public function enqueue_admin_assets($hook)
    {
        // Check if we are on our jit-coupons page
        // $hook might look like: "woocommerce_page_jit-coupons"
        if ($hook !== 'woocommerce_page_jit-coupons') {
            return;
        }

        wp_enqueue_script(
            'jit-coupons-admin-js',
            plugin_dir_url(__FILE__) . 'admin-ui/jit-coupons-admin.js',
            array('jquery'),
            '1.0',
            true
        );
    }

    /**
     * Render the main JIT Coupons settings page (multiple references).
     */
    public function render_admin_page()
    {

        // If saving
        if (isset($_POST['jit_save_references']) && check_admin_referer('jit_save_settings')) {
            $templates = isset($_POST['jit_reference_templates'])
                ? (array) $_POST['jit_reference_templates']
                : array();

            $lists = isset($_POST['jit_reference_lists'])
                ? (array) $_POST['jit_reference_lists']
                : array();

            // This array will hold the final references we store in DB
            $reference_data = array();

            foreach ($templates as $index => $template_code) {
                // 1) Basic sanitize for template code
                $template_code = sanitize_text_field($template_code);

                // 2) For the child codes field, unslash first so we remove
                // any backslash-escaping that WP might have added:
                $raw_codes     = isset($lists[$index]) ? wp_unslash($lists[$index]) : '';

                // 3) Then do a light sanitize. We can still use sanitize_textarea_field
                // but it might re-escape quotes. If you want to keep them truly raw,
                // you can skip or do minimal sanitization. 
                // For safety, let's do:
                $codes_text = sanitize_textarea_field($raw_codes);

                // If you prefer truly raw JSON (and you trust your admin users),
                // you can do:  $codes_text = wp_strip_all_tags( $raw_codes );

                if (! empty($template_code) || ! empty($codes_text)) {
                    $reference_data[] = array(
                        'template_coupon' => $template_code,
                        'codes'           => $codes_text, // Store final text
                    );
                }
            }

            update_option('jit_references', $reference_data);
            echo '<div class="updated"><p>References saved.</p></div>';
        }


        // Load references
        $references = get_option('jit_references', array());

?>
        <div class="wrap">
            <h1>Just-In-Time Coupons: Multiple References</h1>

            <form method="post" action="">
                <?php wp_nonce_field('jit_save_settings'); ?>

                <p>
                    Reference existing coupons “template coupons” (existing in WooCommerce), each with a set
                    of “child” codes that will be created on demand. Click <strong>Add New Reference</strong>
                    to add more rows. Remove as needed.
                </p>
                <p>
                <code>COUPON_CODE</code> (required) and <code>{"dynamicvalue":"Some value"}</code> (optional). <code>{dynamicvalue}</code> can be placed inside the existing Template Coupon description then be replaced with this value.
                </p>

                <table class="widefat" id="jit-reference-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Template Coupon (existing)</th>
                            <th>Child Codes (one per line)</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="jit-reference-body">
                        <?php if (! empty($references)) : ?>
                            <?php foreach ($references as $index => $ref) :
                                $codes_text = wp_unslash($ref['codes']);
                            ?>
                                <tr class="jit-reference-row">
                                    <td>
                                        <input type="text"
                                            name="jit_reference_templates[]"
                                            value="<?php echo esc_attr($ref['template_coupon']); ?>"
                                            style="width: 90%;"
                                            placeholder="e.g. SpeakerCouponTemplate" />
                                    </td>
                                    <td>
                                        <textarea name="jit_reference_lists[]" rows="5" style="width: 90%;"
                                            placeholder="One code per line"><?php echo esc_textarea($codes_text); ?></textarea>

                                    </td>
                                    <td>
                                        <button class="button jit-remove-row">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <!-- If no references, start with 0 rows. -->
                        <?php endif; ?>
                    </tbody>
                </table>

                <p>
                    <button class="button" id="jit-add-row">Add New Reference</button>
                </p>

                <p class="submit">
                    <button type="submit" name="jit_save_references" class="button button-primary">Save References</button>
                </p>
            </form>

            <!-- A hidden template row (cloned by JS) -->
            <table style="display:none;">
                <tbody id="jit-reference-template">
                    <tr class="jit-reference-row">
                        <td>
                            <input type="text"
                                name="jit_reference_templates[]"
                                value=""
                                style="width: 90%;"
                                placeholder="e.g. SpeakerCouponTemplate" />
                        </td>
                        <td>
                            <textarea name="jit_reference_lists[]" rows="5" style="width: 90%;"
                                placeholder="One code per line"></textarea>
                        </td>
                        <td>
                            <button class="button jit-remove-row">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
<?php
    }
}

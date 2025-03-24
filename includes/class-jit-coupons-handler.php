<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class JIT_Coupons_Handler {

    public function __construct() {
        add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'maybe_create_coupon' ), 10, 2 );
    }

    /**
     * If a coupon is not found, check our references, parse potential JSON data,
     * and clone from the appropriate template with dynamic replacements.
     */
    public function maybe_create_coupon( $data, $coupon_code ) {

        if ( ! empty( $data ) ) {
            return $data; // Already found
        }

        $references = get_option( 'jit_references', array() );
        $matching_template = null;
        $matching_vars     = array();  // dynamic data from JSON

        // 1) Find which template row this coupon code belongs to
        foreach ( $references as $ref ) {
            $template_coupon = isset( $ref['template_coupon'] ) ? $ref['template_coupon'] : '';
            $codes           = isset( $ref['codes'] ) ? $ref['codes'] : '';

            // Split lines
            $codes_list = preg_split( '/[\r\n]+/', $codes );
            $codes_list = array_map( 'trim', $codes_list );
            $codes_list = array_filter( $codes_list );

            // For each line, parse out the code and optional JSON
            foreach ( $codes_list as $line ) {
                $parsed_line = $this->parse_coupon_line( $line );
                // If the code in this line matches user input, we found our template
                if ( $parsed_line['code'] === $coupon_code ) {
                    $matching_template = $template_coupon;
                    $matching_vars     = $parsed_line['vars'];
                    break 2; // break out of both loops
                }
            }
        }

        // If we didn't find any matching references, no coupon is created
        if ( ! $matching_template ) {
            return $data;
        }

        // Check that the template coupon actually exists
        $template_post = get_page_by_title( $matching_template, OBJECT, 'shop_coupon' );
        if ( ! $template_post ) {
            return $data;
        }

        // If a coupon with $coupon_code already exists (somehow), do nothing
        $existing_coupon = get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' );
        if ( $existing_coupon ) {
            return $data;
        }

        // 2) Build a new excerpt from the template's excerpt by injecting dynamic placeholders
        $original_excerpt   = $template_post->post_excerpt;  // e.g. "15% discount for speaker {presentername}"
        $final_excerpt      = $this->apply_dynamic_vars( $original_excerpt, $matching_vars );

        // 3) Insert the new coupon post
        $new_coupon_id = wp_insert_post( array(
            'post_title'   => $coupon_code,
            'post_name'    => $coupon_code,
            'post_type'    => 'shop_coupon',
            'post_status'  => 'publish',
            'post_excerpt' => $final_excerpt,
        ) );

        // 4) Clone coupon meta (discount amount, usage limits, etc.)
        $this->clone_coupon_meta( $template_post->ID, $new_coupon_id );

        // If you also want to do dynamic replacements in the meta, you could do so after cloning:
        // $this->apply_dynamic_vars_to_coupon_meta( $new_coupon_id, $matching_vars );

        return $data; // Let WooCommerce re-fetch coupon data
    }

    /**
     * Parse a single line from the "child codes" list.
     * Format can be:
     *   "CODE"  (simple)
     *   "CODE {"key":"value"}" (JSON present)
     * Returns ['code' => 'CODE', 'vars' => [ array from JSON or empty ] ]
     */
    private function parse_coupon_line( $line ) {
        $line = trim( $line );

        // If there's a space followed by '{', we assume there's JSON after the code
        // Example: "Darko25 {"presentername":"Darko Novak"}"
        $pattern = '/^(\S+)\s+(\{.*\})$/'; 
        if ( preg_match( $pattern, $line, $matches ) ) {
            $coupon_code = $matches[1];      // e.g. "Darko25"
            $json_part   = $matches[2];      // e.g. '{"presentername":"Darko Novak"}'

            // Attempt to decode JSON
            $vars = json_decode( $json_part, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $vars = array(); // If invalid JSON, fallback to empty
            }

            return array(
                'code' => $coupon_code,
                'vars' => $vars,
            );
        }

        // Otherwise, no JSON – entire line is the code
        return array(
            'code' => $line,
            'vars' => array(),
        );
    }

    /**
     * Replaces placeholders like {presentername} in the text with actual values
     * from $vars. Any placeholders that are not found in $vars are removed entirely.
     */
    private function apply_dynamic_vars( $text, $vars ) {
        // Find all {something} placeholders
        // We'll do a preg callback to handle them
        $callback = function( $matches ) use ( $vars ) {
            $placeholder = $matches[1]; // e.g. "presentername"
            if ( isset( $vars[ $placeholder ] ) ) {
                return $vars[ $placeholder ];
            }
            // If not found in $vars, remove the placeholder
            return '';
        };

        // Regex: \{(.*?)\} captures everything between { }
        $pattern = '/\{([^}]+)\}/'; 
        return preg_replace_callback( $pattern, $callback, $text );
    }

    /**
     * Clones meta from the template coupon to the new coupon.
     * If you need placeholders in meta, handle it after the clone.
     */
    private function clone_coupon_meta( $old_id, $new_id ) {
        $meta = get_post_meta( $old_id );
        if ( empty( $meta ) ) {
            return;
        }
        foreach ( $meta as $key => $values ) {
            foreach ( $values as $value ) {
                add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
            }
        }
    }

    /**
     * (Optional) If you want placeholders in certain meta fields,
     * you could implement logic here to loop through all meta on $new_id,
     * do replacements, and update them. Shown as a conceptual example:
     */
    private function apply_dynamic_vars_to_coupon_meta( $new_id, $vars ) {
        $all_meta = get_post_meta( $new_id );
        foreach ( $all_meta as $key => $values ) {
            // Suppose we only want to replace placeholders in certain fields,
            // like `_coupon_description` or something custom. Adjust as needed.
            if ( in_array( $key, array( '_coupon_description' ), true ) ) {
                foreach ( $values as $idx => $val ) {
                    $original = maybe_unserialize( $val );
                    if ( is_string( $original ) ) {
                        $replaced = $this->apply_dynamic_vars( $original, $vars );
                        update_post_meta( $new_id, $key, $replaced, $original );
                    }
                }
            }
        }
    }
}

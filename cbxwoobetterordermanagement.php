<?php
/**
 * Plugin Name: CBX Woo Better Order Management
 * Description: Advanced WooCommerce order date filtering with presets and AJAX support.
 * Plugin URI: https://github.com/codeboxrcodehub/cbxwoobetterordermanagement
 * Version: 1.0.1
 * Author: Codeboxr
 * Author URI: http://codeboxr.com
 * Text Domain: cbxwoobetterordermanagement
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CBXWooBetterOrderManagement {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        // HPOS hooks
        add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'render_date_filter_fields'], 20);
        add_filter('woocommerce_order_list_table_prepare_items_query_args', [$this, 'filter_orders_by_date_range'], 10, 1);

        // Legacy CPT hooks
        add_action('restrict_manage_posts', [$this, 'legacy_render_date_filter_fields'], 20, 1);
        add_action('pre_get_posts', [$this, 'legacy_filter_orders_by_date_range'], 10, 1);
    }

    public function enqueue_scripts($hook) {
        $screen = get_current_screen();
        if (!$screen) return;

        $is_orders_page = ($screen->id === 'woocommerce_page_wc-orders') || ($screen->id === 'edit-shop_order');
        if (!$is_orders_page) return;

        // Load Moment.js + daterangepicker (CDN)
        wp_enqueue_script('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js', [], '2.30.1', true);
        wp_enqueue_style('daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', [], '3.1');
        wp_enqueue_script('daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['jquery', 'moment-js'], '3.1', true);

        // Your custom JS
        wp_enqueue_script(
                'cbx-order-date-filter',
                plugins_url('assets/js/cbx-order-date-filter.js', __FILE__),
                ['jquery', 'daterangepicker-js'],
                '1.0.2',
                true
        );

        // Optional CSS
        wp_enqueue_style('cbx-order-date-filter', plugins_url('assets/css/cbx-order-date-filter.css', __FILE__), [], '1.0.2');
    }//end method enqueue_scripts

    public function render_date_filter_fields() {
        $current_range = isset($_GET['cbx_date_range']) ? sanitize_text_field($_GET['cbx_date_range']) : '';
        $start_date    = isset($_GET['cbx_start_date']) ? esc_attr($_GET['cbx_start_date']) : '';
        $end_date      = isset($_GET['cbx_end_date']) ? esc_attr($_GET['cbx_end_date']) : '';

        $display_value = $start_date && $end_date ? $start_date . ' - ' . $end_date : 'Select date range';

        ?>
        <div class="cbx-date-filter-wrap" style="margin: 10px 0; display: inline-block; vertical-align: middle;">
            <input type="text" id="cbx-daterange" name="cbx_daterange" value="<?php echo esc_attr($display_value); ?>" style="width: 240px; padding: 6px 10px;" readonly />
            <!-- Hidden fields to submit actual values -->
            <input type="hidden" name="cbx_start_date" id="cbx_start_date" value="<?php echo $start_date; ?>" />
            <input type="hidden" name="cbx_end_date" id="cbx_end_date" value="<?php echo $end_date; ?>" />
            <input type="hidden" name="cbx_date_range" id="cbx_date_range" value="<?php echo $current_range; ?>" />
        </div>
        <?php
    }//end method render_date_filter_fields

    public function legacy_render_date_filter_fields($post_type) {
        if ('shop_order' !== $post_type) {
            return;
        }
        $this->render_date_filter_fields();
    }

    private function get_date_query() {
        $preset = isset($_GET['cbx_date_range']) ? sanitize_text_field($_GET['cbx_date_range']) : '';

        if (empty($preset) && (!isset($_GET['cbx_start_date']) || !isset($_GET['cbx_end_date']))) {
            return [];
        }

        $today = current_time('Y-m-d');
        $date_query = ['inclusive' => true];

        if ($preset && $preset !== 'custom') {
            switch ($preset) {
                case 'today':
                    $date_query['after'] = $date_query['before'] = $today;
                    break;
                case 'yesterday':
                    $yest = gmdate('Y-m-d', strtotime('-1 day'));
                    $date_query['after'] = $date_query['before'] = $yest;
                    break;
                case 'this_week':
                    $date_query['after'] = gmdate('Y-m-d', strtotime('monday this week'));
                    $date_query['before'] = $today;
                    break;
                case 'last_7_days':
                    $date_query['after'] = gmdate('Y-m-d', strtotime('-7 days'));
                    $date_query['before'] = $today;
                    break;
                case 'this_month':
                    $date_query['after'] = gmdate('Y-m-01');
                    $date_query['before'] = $today;
                    break;
                case 'last_30_days':
                    $date_query['after'] = gmdate('Y-m-d', strtotime('-30 days'));
                    $date_query['before'] = $today;
                    break;
            }
        } else {
            // custom or direct dates
            if (!empty($_GET['cbx_start_date'])) {
                $date_query['after'] = sanitize_text_field($_GET['cbx_start_date']);
            }
            if (!empty($_GET['cbx_end_date'])) {
                $date_query['before'] = sanitize_text_field($_GET['cbx_end_date']);
            }
        }

        return !empty($date_query['after']) || !empty($date_query['before']) ? $date_query : [];
    }

    public function filter_orders_by_date_range($query_args) {
        $date_query = $this->get_date_query();
        if (!empty($date_query)) {
            $query_args['date_created'] = $date_query;
        }
        return $query_args;
    }

    public function legacy_filter_orders_by_date_range($query) {
        if (!is_admin() || !$query->is_main_query() || 'shop_order' !== $query->get('post_type')) {
            return;
        }

        $date_query = $this->get_date_query();
        if (!empty($date_query)) {
            $query->set('date_query', [$date_query]); // Nested for WP_Query
        }
    }
}

new CBXWooBetterOrderManagement();
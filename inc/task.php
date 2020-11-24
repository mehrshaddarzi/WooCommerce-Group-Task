<?php

namespace WC_Group_Task;

use WC_Group_Task\core\Utility;

/**
 * Class Helper Used in Custom Helper Method For This Plugin
 */
class Task
{

    public function __construct()
    {

        // Set A Stock for All Product Simple and Variable
        add_action('init', array($this, 'set_default_stock_product'));

        // Set SKU Number According To Product Category
        add_action('init', array($this, 'set_sku_by_category'));

        // Get Product Cat List
        add_action('init', array($this, 'get_product_cat_list'));

        // Set Sku From
        add_action('init', array($this, 'set_sku_from_number'));
    }

    public function set_default_stock_product()
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['wc-group-task'])) {
            return;
        }

        if ($_GET['wc-group-task'] != 'set-all-stock') {
            return;
        }

        if (!isset($_GET['_stock'])) {
            return;
        }

        if (!isset($_GET['_backorders'])) {
            return; // yes / no
        }

        // Stock Number
        $stock_number = trim($_GET['_stock']);

        // _backorders
        $_backorders = trim($_GET['_backorders']);

        // Get All Product
        $products_ids = Utility::wp_query(
            array(
                'post_type' => 'product',
                'post_status' => 'any',
                'posts_per_page' => '-1',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'wc-group-task-stock-' . $stock_number,
                            'value' => '', // Not required but necessary in this case
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => 'wc-group-task-stock-' . $stock_number,
                            'value' => $stock_number,
                            'compare' => 'NOT LIKE',
                        )
                    )
                )
            ),
            false
        );

        foreach ($products_ids as $product_id) {

            // Get Product Variables IDs
            $p_ids = Helper::getWooCommerceProductsChildren($product_id);
            foreach ($p_ids as $Product_ID) {

                // Set Manage Stock Of True
                update_post_meta($Product_ID, '_manage_stock', 'yes');

                // Disable backorders Product
                update_post_meta($Product_ID, '_backorders', $_backorders);

                // Get Current Number in WooCommerce
                $current_stock = get_post_meta($Product_ID, '_stock', true);

                // Get current Stock in API
                $api_stock = $stock_number;

                // Set New Status By number quantity
                if (empty($api_stock) || $api_stock == "0") {
                    $out_of_stock_status = 'outofstock';
                } else {
                    $out_of_stock_status = 'instock';
                }

                // 1. Updating the stock quantity
                update_post_meta($Product_ID, '_stock', $api_stock);

                // 2. Updating the stock quantity
                update_post_meta($Product_ID, '_stock_status', $out_of_stock_status);

                // 3. Updating post term relationship
                wp_set_post_terms($Product_ID, $out_of_stock_status, 'product_visibility', false);
            }

            // Set Post Meta
            update_post_meta($product_id, 'wc-group-task-stock-' . $stock_number, $stock_number);

            // Clean Post Cache
            clean_post_cache($product_id);

            // Echo Detail
            echo '<div>Product With ID is ' . $product_id . ' Set Stock To ' . $stock_number . '</div><hr>';
        }

        exit;
    }

    public function set_sku_by_category()
    {
        global $wpdb;

        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['wc-group-task'])) {
            return;
        }

        if ($_GET['wc-group-task'] != 'set-sku-by-category') {
            return;
        }

        if (!isset($_GET['_category_ids'])) {
            return;
        }

        if (!isset($_GET['_sku_start_from'])) {
            return;
        }

        if (!isset($_GET['_include_children'])) {
            return;
        }

        if (!isset($_GET['_task_id'])) {
            return;
        }

        // Get Category Ids
        $category_ids = explode(",", trim($_GET['_category_ids']));
        $task_post_meta = 'wc-group-task-sku-' . trim($_GET['_task_id']);
        $_sku_from = trim($_GET['_sku_start_from']);
        $include_children = true;
        if (isset($_GET['_include_children']) and $_GET['_include_children'] == "no") {
            $include_children = false;
        }

        // Get List Product
        $products_ids = Utility::wp_query(
            array(
                'post_type' => 'product',
                'post_status' => 'any',
                'posts_per_page' => '-1',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category_ids,
                        'operator' => 'IN',
                        'include_children' => $include_children
                    )
                ),
                'meta_query' => array(
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $task_post_meta,
                            'value' => '', // Not required but necessary in this case
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => $task_post_meta,
                            'value' => 'yes',
                            'compare' => 'NOT LIKE',
                        )
                    )
                )
            ),
            false
        );

        $SKU = $_sku_from;
        foreach ($products_ids as $product_id) {

            // Get Product Variables IDs
            $p_ids = Helper::getWooCommerceProductsChildren($product_id);
            foreach ($p_ids as $Product_ID) {
                // Set SKU
                update_post_meta($Product_ID, '_sku', $SKU);

                // Delete LockUp
                $wpdb->query("DELETE FROM `{$wpdb->wc_product_meta_lookup}` WHERE `product_id` = {$Product_ID}");

                // Echo Detail
                echo '<div>Product With ID is ' . $Product_ID . ' Set SKU To ' . $SKU . '</div><hr>';
                $SKU++;
            }

            // Set Post Meta
            update_post_meta($product_id, $task_post_meta, 'yes');

            // Clean Post Cache
            clean_post_cache($product_id);
        }

        exit;
    }

    public function set_sku_from_number()
    {
        global $wpdb;

        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['wc-group-task'])) {
            return;
        }

        if ($_GET['wc-group-task'] != 'set-sku') {
            return;
        }

        if (!isset($_GET['_sku_from'])) {
            return;
        }

        $task_post_meta = 'wc-group-task-set-sku-' . trim($_GET['_sku_from']);
        $_sku_from = trim($_GET['_sku_from']);

        // Get List Product
        $products_ids = Utility::wp_query(
            array(
                'post_type' => 'product',
                'post_status' => 'any',
                'posts_per_page' => '-1',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $task_post_meta,
                            'value' => '', // Not required but necessary in this case
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => $task_post_meta,
                            'value' => 'yes',
                            'compare' => 'NOT LIKE',
                        )
                    )
                )
            ),
            false
        );

        $SKU = $_sku_from;
        foreach ($products_ids as $product_id) {

            // Get Product Variables IDs
            $p_ids = Helper::getWooCommerceProductsChildren($product_id);
            foreach ($p_ids as $Product_ID) {
                // Set SKU
                update_post_meta($Product_ID, '_sku', $SKU);

                // Remove From Lockup
                $wpdb->query("DELETE FROM `{$wpdb->wc_product_meta_lookup}` WHERE `product_id` = {$Product_ID}");

                // Echo Detail
                echo '<div>Product With ID is ' . $Product_ID . ' Set SKU To ' . $SKU . '</div><hr>';
                $SKU++;
            }

            // Set Post Meta
            update_post_meta($product_id, $task_post_meta, 'yes');

            // Clean Post Cache
            clean_post_cache($product_id);
        }

        exit;
    }

    public function get_product_cat_list()
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['wc-group-task'])) {
            return;
        }

        if ($_GET['wc-group-task'] != 'get-product-cat') {
            return;
        }

        if (!isset($_GET['_parent'])) {
            return;
        }

        if (!isset($_GET['_with_children'])) {
            return;
        }

        if (!isset($_GET['_order'])) {
            return;
        }

        if (!isset($_GET['_hide_empty'])) {
            return;
        }

        $hide_empty = false;
        if (isset($_GET['_hide_empty']) and $_GET['_hide_empty'] == "yes") {
            $hide_empty = true;
        }

        $list = array();
        $provinces = get_terms('product_cat', array(
            'orderby' => 'term_id',
            'order' => $_GET['_order'],
            'parent' => $_GET['_parent'],
            'hide_empty' => $hide_empty
        ));
        foreach ($provinces as $term) {
            $item = array(
                'name' => $term->name,
                'id' => $term->term_id,
                'slug' => $term->slug,
            );

            // Add Children
            if ($_GET['_with_children'] == "yes") {
                $item['children'] = array();
                $cities = get_terms('product_cat', array(
                    'orderby' => 'count',
                    'parent' => $term->term_id,
                    'order' => $_GET['_order'],
                    'hide_empty' => $hide_empty
                ));
                foreach ($cities as $term_city) {
                    $item['children'][] = array(
                        'name' => $term_city->name,
                        'id' => $term_city->term_id,
                        'slug' => $term_city->slug
                    );
                }
            }

            $list[] = $item;
        }

        wp_send_json($list, 200);
        exit;
    }
}

new Task();
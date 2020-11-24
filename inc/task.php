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
    }

    public function set_default_stock_product()
    {
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

            // Echo Detail
            echo '<div>Product With ID is ' . $product_id . ' Set Stock To ' . $stock_number . '</div><hr>';
        }

        exit;
    }
}

new Task();
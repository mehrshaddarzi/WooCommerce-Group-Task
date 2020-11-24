<?php
namespace WC_Group_Task;

/**
 * Class Helper Used in Custom Helper Method For This Plugin
 */
class Helper {

    public static function getWooCommerceProductsChildren($product_id)
    {
        global $wpdb;

        $product_list_ID = array($product_id);
        $product_children_list_ID = $wpdb->get_results("SELECT `ID` FROM {$wpdb->posts} WHERE `post_parent` = {$product_id} AND `post_type` = 'product_variation'", ARRAY_A);
        if (!empty($product_children_list_ID)) {
            foreach ($product_children_list_ID as $post_data) {
                $product_list_ID[] = $post_data['ID'];
            }
        }
        return $product_list_ID;
    }

}
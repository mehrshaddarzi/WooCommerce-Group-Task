# WooCommerce Group Task

## Set a Stock Number For All Products

````
http://site.com/?wc-group-task=set-all-stock&_stock=40&_backorders=no
````

## Set Sku From Custom Number

````
http://site.com/?wc-group-task=set-sku&_sku_from=1000
````

## Set SKU Number According To Product Category

````
http://site.com/?wc-group-task=set-sku-by-category&_category_ids=18,13,15&_include_children=yes&_sku_start_from=1000&_task_id=1
````

## Get List Taxonomy Products Cat Json

````
http://site.com/?wc-group-task=get-product-cat&_parent=0&_with_children=no&_order=ASC&_hide_empty=no
````
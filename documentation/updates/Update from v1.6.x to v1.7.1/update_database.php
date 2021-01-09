<?php
define('BASEPATH', "/");
define('ENVIRONMENT', 'production');
require_once "application/config/database.php";
$license_code = '';
$purchase_code = '';
if (file_exists('license.php')) {
    include 'license.php';
}
if (!function_exists('curl_init')) {
    $error = 'cURL is not available on your server! Please enable cURL to continue the installation. You can read the documentation for more information.';
    exit();
}

//set database credentials
$database = $db['default'];
$db_host = $database['hostname'];
$db_name = $database['database'];
$db_user = $database['username'];
$db_password = $database['password'];

/* Connect */
$connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);
$connection->query("SET CHARACTER SET utf8");
$connection->query("SET NAMES utf8");
if (!$connection) {
    $error = "Connect failed! Please check your database credentials.";
}
if (isset($_POST["btn_submit"])) {
	$license_code = 'license_code';
	$purchase_code = 'purchase_code';
	update($license_code, $purchase_code, $connection);
	sleep(1);
	/* close connection */
	mysqli_close($connection);
	$success = 'The update has been successfully completed! Please delete the "update_database.php" file.';
}

function update($license_code, $purchase_code, $connection)
{
    update_16_to_17($license_code, $purchase_code, $connection);
}

function update_16_to_17($license_code, $purchase_code, $connection)
{
    //check version
    $result = mysqli_query($connection, "SELECT * FROM general_settings WHERE id = 1");
    while ($row = mysqli_fetch_array($result)) {
        if (empty($row['version'])) {
            mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `version` VARCHAR(30) DEFAULT '1.7';");
            mysqli_query($connection, "ALTER TABLE products ADD COLUMN `stock_unlimited` TINYINT(1) DEFAULT 0;");
            mysqli_query($connection, "ALTER TABLE slider ADD COLUMN `image_mobile` VARCHAR(255);");
            mysqli_query($connection, "ALTER TABLE earnings ADD COLUMN `order_product_id` INT;");
            $array = array();
            $array["unlimited_stock"] = "Unlimited Stock";
            $array["send_test_email"] = "Send Test Email";
            $array["send_test_email_exp"] = "You can send a test mail to check if your mail server is working.";
            add_lang_trans($connection, $array);
            mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'digital_product_stock_exp'");
            mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'smtp'");
        }
    }

    mysqli_query($connection, "RENAME TABLE user_payout_accounts TO users_payout_accounts;");
    mysqli_query($connection, "DROP TABLE `routes`;");

    $table_blog_images = "CREATE TABLE `blog_images` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `image_path` varchar(255) DEFAULT NULL,
      `image_path_thumb` varchar(255) DEFAULT NULL,
      `storage` varchar(20) DEFAULT 'local',
      `user_id` int(11) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_homepage_banners = "CREATE TABLE `homepage_banners` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `banner_url` varchar(1000) DEFAULT NULL,
      `banner_image_path` varchar(255) DEFAULT NULL,
      `banner_order` int(11) NOT NULL DEFAULT 1,
      `banner_width` double DEFAULT NULL,
      `banner_location` varchar(100) DEFAULT 'featured_products'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_membership_plans = "CREATE TABLE `membership_plans` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title_array` text DEFAULT NULL,
      `number_of_ads` int(11) DEFAULT NULL,
      `number_of_days` int(11) DEFAULT NULL,
      `price` bigint(20) DEFAULT NULL,
      `is_free` tinyint(1) DEFAULT 0,
      `is_unlimited_number_of_ads` tinyint(1) DEFAULT 0,
      `is_unlimited_time` tinyint(1) DEFAULT 0,
      `features_array` text DEFAULT NULL,
      `plan_order` smallint(6) DEFAULT 1,
      `is_popular` tinyint(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_membership_transactions = "CREATE TABLE `membership_transactions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int(11) DEFAULT NULL,
      `plan_id` int(11) DEFAULT NULL,
      `plan_title` varchar(500) DEFAULT NULL,
      `payment_method` varchar(100) DEFAULT NULL,
      `payment_id` varchar(255) DEFAULT NULL,
      `payment_amount` varchar(50) DEFAULT NULL,
      `currency` varchar(20) DEFAULT NULL,
      `payment_status` varchar(50) DEFAULT NULL,
      `ip_address` varchar(100) DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_users_membership_plans = "CREATE TABLE `users_membership_plans` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int(11) DEFAULT NULL,
      `plan_title` varchar(500) DEFAULT NULL,
      `number_of_ads` int(11) DEFAULT NULL,
      `number_of_days` int(11) DEFAULT NULL,
      `price` bigint(20) DEFAULT NULL,
      `currency` varchar(20) DEFAULT 'USD',
      `is_free` tinyint(1) DEFAULT 0,
      `is_unlimited_number_of_ads` tinyint(1) DEFAULT 0,
      `is_unlimited_time` tinyint(1) DEFAULT 0,
      `payment_method` varchar(50) DEFAULT NULL,
      `payment_status` varchar(50) DEFAULT NULL,
      `plan_status` tinyint(1) DEFAULT 0,
      `plan_start_date` timestamp NULL DEFAULT NULL,
      `plan_end_date` timestamp NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_routes = "CREATE TABLE `routes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `route_key` varchar(100) DEFAULT NULL,
      `route` varchar(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_blog_images);
    mysqli_query($connection, $table_homepage_banners);
    mysqli_query($connection, $table_membership_plans);
    mysqli_query($connection, $table_membership_transactions);
    mysqli_query($connection, $table_users_membership_plans);
    mysqli_query($connection, $table_routes);

    sleep(1);
    mysqli_query($connection, "ALTER TABLE categories ADD COLUMN `featured_order` mediumint(9) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE categories CHANGE `show_on_homepage` `is_featured` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE categories ADD COLUMN `show_products_on_index` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE categories DROP INDEX idx_show_on_homepage;");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_is_featured (is_featured);");
    mysqli_query($connection, "ALTER TABLE categories ADD INDEX idx_show_products_on_index (show_products_on_index);");

    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_conditions`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_conditions_required`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `quantity`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `quantity_required`;");
    mysqli_query($connection, "ALTER TABLE form_settings DROP COLUMN `product_location_required`;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `membership_plans_system` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings CHANGE `index_categories` `featured_categories` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `sort_categories` varchar(30) DEFAULT 'category_order';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `sort_parent_categories_by_order` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `pwa_status` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `last_cron_update` timestamp NULL DEFAULT NULL");

    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_email` varchar(255);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_phone_number` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_country` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_state` varchar(100);");
    mysqli_query($connection, "ALTER TABLE invoices ADD COLUMN `client_city` varchar(100);");
    mysqli_query($connection, "ALTER TABLE languages CHANGE `ckeditor_lang` `text_editor_lang` varchar(10) DEFAULT 'en';");

    mysqli_query($connection, "ALTER TABLE payment_settings CHANGE `default_product_currency` `default_currency` varchar(30) DEFAULT 'USD';");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `promoted_products_payment_currency`;");
    mysqli_query($connection, "ALTER TABLE payment_settings ADD COLUMN `stripe_locale` varchar(30) DEFAULT 'auto';");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `iyzico_type`;");
    mysqli_query($connection, "ALTER TABLE payment_settings DROP COLUMN `iyzico_submerchant_key`;");

    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `is_membership_plan_expired` TINYINT(1) DEFAULT 0;");

    /*==============================UPDATE PRODUCTS==============================*/
    $table_product_details = "CREATE TABLE `product_details` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `product_id` int(11) DEFAULT NULL,
      `lang_id` tinyint(4) DEFAULT NULL,
      `title` varchar(500) DEFAULT NULL,
      `description` longtext DEFAULT NULL,
      `seo_title` varchar(500) DEFAULT NULL,
      `seo_description` varchar(500) DEFAULT NULL,
      `seo_keywords` varchar(500) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    mysqli_query($connection, $table_product_details);
    //update products
    $lang_id = 1;
    $products = mysqli_query($connection, "SELECT * FROM products");
    while ($product = mysqli_fetch_array($products)) {
        $languages = mysqli_query($connection, "SELECT * FROM languages ORDER BY id");
        while ($language = mysqli_fetch_array($languages)) {
            mysqli_query($connection, "INSERT INTO `product_details` (`product_id`, `lang_id`, `title`, `description`, `seo_title`, `seo_description`, `seo_keywords`) 
        VALUES (" . $product['id'] . ", '" . $language['id'] . "', '" . $product['title'] . "', '" . $product['description'] . "', '', '', '');");
        }
    }

    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `title`;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `description`;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `product_condition`;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `is_special_offer` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products ADD COLUMN `special_offer_date` timestamp NULL DEFAULT NULL");
    mysqli_query($connection, "ALTER TABLE products CHANGE `hit` `pageviews` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE products DROP COLUMN `stock_unlimited`;");
    mysqli_query($connection, "ALTER TABLE product_details ADD INDEX idx_product_id (product_id);");
    mysqli_query($connection, "ALTER TABLE product_details ADD INDEX idx_lang_id (lang_id);");
    /*==============================UPDATE PRODUCTS END==============================*/


    /*==============================UPDATE CUSTOM FIELDS==============================*/
    mysqli_query($connection, "RENAME TABLE custom_fields_options TO custom_fields_options1;");
    $table_custom_fields_options = "CREATE TABLE `custom_fields_options` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `field_id` int(11) DEFAULT NULL,
      `option_key` varchar(500) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_options_lang = "CREATE TABLE `custom_fields_options_lang` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `option_id` int(11) DEFAULT NULL,
      `lang_id` int(11) DEFAULT NULL,
      `option_name` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_custom_fields_options);
    mysqli_query($connection, $table_options_lang);
    mysqli_query($connection, "ALTER TABLE custom_fields ADD COLUMN `name_array` text;");
    mysqli_query($connection, "ALTER TABLE custom_fields ADD COLUMN `sort_options` varchar(30) DEFAULT 'alphabetically';");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD COLUMN `selected_option_id` INT;");

    //update custom fields name_array
    $custom_fields = mysqli_query($connection, "SELECT * FROM custom_fields");
    if (!empty($custom_fields)) {
        while ($custom_field = mysqli_fetch_array($custom_fields)) {
            $custom_field_langs = mysqli_query($connection, "SELECT * FROM custom_fields_lang WHERE field_id=" . $custom_field['id']);
            $array_names = array();
            if (!empty($custom_field_langs)) {
                while ($custom_field_lang = mysqli_fetch_array($custom_field_langs)) {
                    $item = array(
                        'lang_id' => intval(@$custom_field_lang["lang_id"]),
                        'name' => @$custom_field_lang["name"]
                    );
                    array_push($array_names, $item);
                }
            }
            if (!empty($array_names)) {
                $array_names = @serialize($array_names);
                mysqli_query($connection, "UPDATE `custom_fields` SET `name_array`='" . $array_names . "' WHERE id=" . $custom_field['id']);
            }
        }
    }
    mysqli_query($connection, "DROP TABLE `custom_fields_lang`;");

    //fill custom_fields_options_lang table
    $common_ids = mysqli_query($connection, "SELECT common_id FROM custom_fields_options1 GROUP BY common_id");
    while ($common_id = mysqli_fetch_array($common_ids)) {
        $fields_options = mysqli_query($connection, "SELECT * FROM custom_fields_options1 WHERE common_id='" . $common_id['common_id'] . "'");
        $i = 0;
        $last_insert_id = 0;
        while ($fields_option = mysqli_fetch_array($fields_options)) {
            if ($i == 0) {
                $option_key = $fields_option['field_option'];
                mysqli_query($connection, "INSERT INTO `custom_fields_options` (`field_id`, `option_key`) VALUES (" . $fields_option['field_id'] . ", '" . $option_key . "');");
                $last_insert_id = mysqli_insert_id($connection);
            }
            mysqli_query($connection, "INSERT INTO `custom_fields_options_lang` (`option_id`, `lang_id`, `option_name`) VALUES (" . $last_insert_id . ", '" . $fields_option['lang_id'] . "', '" . $fields_option['field_option'] . "');");
            $i++;
        }
        //update selected options id
        mysqli_query($connection, "UPDATE `custom_fields_product` SET `selected_option_id` = " . $last_insert_id . " WHERE selected_option_common_id= '" . $common_id['common_id'] . "';");
    }
    mysqli_query($connection, "DROP TABLE `custom_fields_options1`;");
    mysqli_query($connection, "ALTER TABLE custom_fields_product DROP COLUMN `selected_option_common_id`;");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_field_id (field_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options ADD INDEX idx_option_key (option_key);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options_lang ADD INDEX idx_option_id (option_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_options_lang ADD INDEX idx_lang_id (lang_id);");
    mysqli_query($connection, "ALTER TABLE custom_fields_product ADD INDEX idx_selected_option_id (selected_option_id);");
    /*==============================UPDATE CUSTOM FIELDS END==============================*/

    //add routes
    $sql_routes = "INSERT INTO `routes` (`id`, `route_key`, `route`) VALUES(1, 'admin', 'admin'), (2, 'blog', 'blog'), (3, 'tag', 'tag'), (4, 'quote_requests', 'quote-requests'), (5, 'cart', 'cart'), (6, 'shipping', 'shipping'), (7, 'payment_method', 'payment-method'), (8, 'payment', 'payment'), (9, 'promote_payment_completed', 'promote-payment-completed'),
(10, 'orders', 'orders'), (11, 'order_details', 'order-details'), (12, 'order_completed', 'order-completed'), (13, 'completed_orders', 'completed-orders'), (14, 'messages', 'messages'), (15, 'conversation', 'conversation'), (16, 'dashboard', 'dashboard'), (17, 'profile', 'profile'), (18, 'wishlist', 'wishlist'),
(19, 'settings', 'settings'), (20, 'update_profile', 'update-profile'), (21, 'followers', 'followers'), (22, 'following', 'following'), (23, 'sales', 'sales'), (24, 'sale', 'sale'), (25, 'product', 'product'), (26, 'add_product', 'add-product'), (27, 'start_selling', 'start-selling'), (28, 'products', 'products'),
(29, 'product_details', 'product-details'), (30, 'edit_product', 'edit-product'), (31, 'pending_products', 'pending-products'), (32, 'hidden_products', 'hidden-products'), (33, 'latest_products', 'latest-products'), (34, 'featured_products', 'featured-products'), (35, 'drafts', 'drafts'), (36, 'bulk_product_upload', 'bulk-product-upload'),
(37, 'downloads', 'downloads'), (38, 'seller', 'seller'), (39, 'earnings', 'earnings'), (40, 'withdraw_money', 'withdraw-money'), (41, 'payouts', 'payouts'), (42, 'set_payout_account', 'set-payout-account'), (43, 'comments', 'comments'), (44, 'reviews', 'reviews'), (45, 'category', 'category'), (46, 'completed_sales', 'completed-sales'), (47, 'shop_settings', 'shop-settings'),
(48, 'personal_information', 'personal-information'), (49, 'shipping_address', 'shipping-address'), (50, 'social_media', 'social-media'), (51, 'search', 'search'), (52, 'register', 'register'), (53, 'members', 'members'), (54, 'forgot_password', 'forgot-password'), (55, 'change_password', 'change-password'), (56, 'reset_password', 'reset-password'),
(57, 'rss_feeds', 'rss-feeds'), (58, 'terms_conditions', 'terms-conditions'), (59, 'contact', 'contact'), (60, 'select_membership_plan', 'select-membership-plan'), (61, 'membership_payment_completed', 'membership-payment-completed'), (62, 'payment_history', 'payment-history'), (63, 'expired_products', 'expired-products');";
    mysqli_query($connection, $sql_routes);

    //delete translations
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'add_iframe'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'add_product_condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'checkout_form'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'company_title'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'create_key'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'default_product_currency'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'edit_product_condition'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'free_listing'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'free_plan'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'good'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'homepage_order'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_blog_slider'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_categories'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_featured_products'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_latest_products'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_featured_products_count'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'index_latest_products_count'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'new_with_tags'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'product_conditions'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'satisfactory'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'sent_quote_requests'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'show_on_homepage'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'submerchant'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'submerchant_key'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'tax_number'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'tax_office'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'very_good'");
    mysqli_query($connection, "DELETE FROM language_translations WHERE label = 'view_profile'");

    //add translations
    $t = array();
    $t['time_limit_for_plan'] = "A time limit for the plan";
    $t['add_banner'] = "Add Banner";
    $t['add_feature'] = "Add Feature";
    $t['add_to_special_offers'] = "Add to Special Offers";
    $t['address_2'] = "Address 2";
    $t['alphabetically'] = "Alphabetically";
    $t['banner_width'] = "Banner Width";
    $t['blog_slider'] = "Blog Slider";
    $t['bulk_category_upload'] = "Bulk Category Upload";
    $t['bulk_product_upload'] = "Bulk Product Upload";
    $t['by_category_order'] = "by Category Order";
    $t['by_date'] = "by Date";
    $t['category_id_finder'] = "Category Id Finder";
    $t['create_new_plan'] = "Create a New Plan";
    $t['csv_file'] = "CSV File";
    $t['current_plan'] = "Current Plan";
    $t['dashboard'] = "Dashboard";
    $t['data_type'] = "Data Type";
    $t['default_currency'] = "Default Currency";
    $t['digital_file_required'] = "Digital file is required!";
    $t['documentation'] = "Documentation";
    $t['download_csv_example'] = "Download CSV Example";
    $t['download_csv_template'] = "Download CSV Template";
    $t['draft_added'] = "Draft added successfully!";
    $t['duration'] = "Duration";
    $t['edit_banner'] = "Edit Banner";
    $t['edit_plan'] = "Edit Plan";
    $t['email_status'] = "Email Status";
    $t['example'] = "Example";
    $t['expired_products'] = "Expired Products";
    $t['export'] = "Export";
    $t['feature'] = "Feature";
    $t['featured_categories'] = "Featured Categories";
    $t['featured_products_system'] = "Featured Products System";
    $t['features'] = "Features";
    $t['field'] = "Field";
    $t['general_information'] = "General Information";
    $t['generate'] = "Generate";
    $t['go_back_to_products'] = "Go Back to the Products Page";
    $t['go_back_to_shop_settings'] = "Go Back to the Shop Settings";
    $t['help_documents'] = "Help Documents";
    $t['hi'] = "Hi";
    $t['homepage_banners'] = "Homepage Banners";
    $t['homepage_manager'] = "Homepage Manager";
    $t['pwa_warning'] = "If you enable PWA option, read \'Progressive Web App (PWA)\' section from the documentation to make the necessary settings.";
    $t['import_language'] = "Import Language";
    $t['json_language_file'] = "JSON Language File";
    $t['latest_sales'] = "Latest Sales";
    $t['left_to_right'] = "Left to Right (LTR)";
    $t['management_tools'] = "Management Tools";
    $t['membership'] = "Membership";
    $t['membership_payments'] = "Membership Payments";
    $t['membership_plan'] = "Membership Plan";
    $t['membership_plan_payment'] = "Membership Plan Payment";
    $t['membership_plans'] = "Membership Plans";
    $t['membership_transactions'] = "Membership Transactions";
    $t['monthly_sales'] = "Monthly sales";
    $t['membership_number_of_ads'] = "Number of Active Ads";
    $t['number_of_ads'] = "Number of Ads";
    $t['number_of_days'] = "Number of Days";
    $t['number_of_entries'] = "Number of Entries";
    $t['number_featured_products'] = "Number of Featured Products to Show";
    $t['number_latest_products'] = "Number of Latest Products to Show";
    $t['number_remaining_ads'] = "Number of Remaining Ads";
    $t['product_approve_published'] = "Once it is approved, it will be published on the site.";
    $t['payment_history'] = "Payment History";
    $t['plan_expiration_date'] = "Plan Expiration Date";
    $t['popular'] = "Popular";
    $t['product_added'] = "Product added successfully!";
    $t['product_promotion'] = "Product Promotion";
    $t['products_by_category'] = "Products by Category";
    $t['pwa'] = "Progressive Web App (PWA)";
    $t['promotion_payments'] = "Promotion Payments";
    $t['remove_from_special_offers'] = "Remove from Special Offers";
    $t['renew_your_plan'] = "Renew Your Plan";
    $t['reset'] = "Reset";
    $t['reset_filters'] = "Reset Filters";
    $t['reset_location'] = "Reset Location";
    $t['right_to_left'] = "Right to Left (RTL)";
    $t['sale_id'] = "Sale Id";
    $t['products_by_category_exp'] = "Select the categories you want to show their products";
    $t['featured_categories_exp'] = "Select the categories you want to show under the slider";
    $t['select_your_plan_exp'] = "Select your membership plan to continue";
    $t['select_your_plan'] = "Select Your Plan";
    $t['seo'] = "SEO";
    $t['set_as_default'] = "Set as Default";
    $t['sold'] = "Sold";
    $t['sort_categories'] = "Sort Categories";
    $t['sort_options'] = "Sort Options";
    $t['sort_parent_categories_by_category_order'] = "Sort Parent Categories by Category Order";
    $t['warning_category_sort'] = "Sorting with drag and drop will be active only when the 'by Category Order' option is selected.";
    $t['special_offers'] = "Special Offers";
    $t['text_direction'] = "Text Direction";
    $t['banner_location_exp'] = "The banner will be added under the selected section";
    $t['the_operation_completed'] = "The operation completed successfully!";
    $t['msg_request_sent'] = "The request has been sent successfully!";
    $t['iyzico_warning'] = 'This is the "Checkout Form" integration, not the "Marketplace" integration.';
    $t['msg_product_slug_used'] = "This slug is used by another product!";
    $t['unlimited'] = "Unlimited";
    $t['vendor'] = "Vendor";
    $t['msg_expired_plan'] = 'When your plan expires, if you do not renew your plan within 3 days, your ads will be added to the "Expired Products" section and will not be displayed on the site.';
    $t['bulk_category_upload_exp'] = "You can add your categories with a CSV file from this section";
    $t['bulk_product_upload_exp'] = "You can add your products with a CSV file from this section";
    $t['homepage_banners_exp'] = "You can manage the product banners on the homepage from this section";
    $t['help_documents_exp'] = "You can use these documents to generate your CSV file";
    $t['category_id_finder_exp'] = "You can use this section to find out the Id of a category";
    $t['do_not_have_membership_plan'] = "You do not have a membership plan. Click the button below to buy a membership plan.";
    $t['msg_reached_ads_limit'] = "You have reached your ad adding limit! If you want to add more ads, you can upgrade your current plan by clicking the button below.";
    $t['msg_accept_terms'] = "You have to accept the terms!";
    $t['msg_membership_renewed'] = "Your membership plan has been successfully renewed!";
    $t['msg_plan_expired'] = "Your membership plan has expired!";
    add_lang_trans($connection, $t);
    mysqli_query($connection, "UPDATE general_settings SET version='1.7.1' WHERE id='1'");
}

//add language translations
function add_lang_trans($connection, $array_translations)
{
    $languages = mysqli_query($connection, "SELECT * FROM languages ORDER BY language_order;");
    if (!empty($languages)) {
        while ($language = mysqli_fetch_array($languages)) {
            if (!empty($array_translations)) {
                foreach ($array_translations as $key => $value) {
                    $trans = mysqli_query($connection, "SELECT * FROM language_translations WHERE label ='" . $key . "' AND lang_id = " . $language['id']);
                    if (mysqli_num_rows($trans) < 1) {
                        mysqli_query($connection, "INSERT INTO `language_translations` (`lang_id`, `label`, `translation`) VALUES (" . $language['id'] . ", '" . $key . "', '" . $value . "');");
                    }
                }
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modesy - Update Wizard</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700" rel="stylesheet">
    <!-- Font-awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #444 !important;
            font-size: 14px;

            background: #007991; /* fallback for old browsers */
            background: -webkit-linear-gradient(to left, #007991, #6fe7c2); /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to left, #007991, #6fe7c2); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */

        }

        .logo-cnt {
            text-align: center;
            color: #fff;
            padding: 60px 0 60px 0;
        }

        .logo-cnt .logo {
            font-size: 42px;
            line-height: 42px;
        }

        .logo-cnt p {
            font-size: 22px;
        }

        .install-box {
            width: 100%;
            padding: 30px;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            background-color: #fff;
            border-radius: 4px;
            display: block;
            float: left;
            margin-bottom: 100px;
        }

        .form-input {
            box-shadow: none !important;
            border: 1px solid #ddd;
            height: 44px;
            line-height: 44px;
            padding: 0 20px;
        }

        .form-input:focus {
            border-color: #239CA1 !important;
        }

        .btn-custom {
            background-color: #239CA1 !important;
            border-color: #239CA1 !important;
            border: 0 none;
            border-radius: 4px;
            box-shadow: none;
            color: #fff !important;
            font-size: 16px;
            font-weight: 300;
            height: 40px;
            line-height: 40px;
            margin: 0;
            min-width: 105px;
            padding: 0 20px;
            text-shadow: none;
            vertical-align: middle;
        }

        .btn-custom:hover, .btn-custom:active, .btn-custom:focus {
            background-color: #239CA1;
            border-color: #239CA1;
            opacity: .8;
        }

        .tab-content {
            width: 100%;
            float: left;
            display: block;
        }

        .tab-footer {
            width: 100%;
            float: left;
            display: block;
        }

        .buttons {
            display: block;
            float: left;
            width: 100%;
            margin-top: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            margin-top: 0;
            text-align: center;
        }

        .sub-title {
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 30px;
            margin-top: 0;
            text-align: center;
        }

        .alert {
            text-align: center;
        }

        .alert strong {
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-sm-12 col-md-offset-2">
            <div class="row">
                <div class="col-sm-12 logo-cnt">
                    <h1>Modesy</h1>
                    <p>Welcome to the Update Wizard</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="install-box">
                        <h2 class="title">Update from v1.6.x to v1.7.1</h2>
                        <br><br>
                        <div class="messages">
                            <?php if (!empty($error)) { ?>
                                <div class="alert alert-danger">
                                    <strong><?php echo $error; ?></strong>
                                </div>
                            <?php } ?>
                            <?php if (!empty($success)) { ?>
                                <div class="alert alert-success">
                                    <strong><?php echo $success; ?></strong>
                                    <style>.alert-info {
                                            display: none;
                                        }</style>
                                </div>
                                <?php @unlink(__FILE__); ?>
                            <?php } ?>
                        </div>
                        <?php
                        if (empty($success)):
                            if (empty($license_array) || empty($license_array["purchase_code"]) || empty($license_array["license_code"])): ?>
                                <div class="alert alert-info" role="alert">
                                    You can get your license code from our support system: <a href="https://codingest.net/" target="_blank"><strong>https://codingest.net</strong></a>
                                </div>
                            <?php endif;
                        endif; ?>
                        <div class="step-contents">
                            <div class="tab-1">
                                <?php if (empty($success)): ?>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                        <div class="tab-content">
                                            <div class="tab_1">
                                                <?php if (empty($license_array) || empty($license_array["purchase_code"]) || empty($license_array["license_code"])): ?>
                                                    <div class="form-group">
                                                        <label for="email">License Code</label>
                                                        <textarea name="license_code" class="form-control form-input" style="resize: vertical; min-height: 80px; height: 80px; line-height: 24px;padding: 10px;" placeholder="Enter License Code" required><?php echo $license_code; ?></textarea>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="tab-footer text-center">
                                            <button type="submit" name="btn_submit" class="btn-custom">Update My Database</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

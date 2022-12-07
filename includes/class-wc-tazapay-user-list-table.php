<?php
if (!class_exists('WP_List_Table')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class TCPG_User_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        //Set parent defaults
        parent::__construct(
            array(
            'singular'  => 'tazapayuser',    //singular name of the listed records
            'plural'    => 'tazapayusers',   //plural name of the listed records
            'ajax'      => false             //does this table support ajax?
            )
        );
    }
    /*
    * WP_List_Table::single_row_columns()
    * 
    * @param array $item A singular item (one full row's worth of data)
    * @param array $column_name The name/slug of the column to be processed
    * @return string Text or HTML to be placed inside the column <td>
    */
    private function table_data()
    {
        global $wpdb;

        $data      = array();
        $tablename = $wpdb->prefix . 'tazapay_user';
        $results   = $wpdb->get_results("SELECT * FROM $tablename");

        foreach ($results as $result) {

            $first_name         = isset($result->first_name) ? esc_html($result->first_name) : '';
            $last_name          = isset($result->last_name) ? esc_html($result->last_name) : '';
            $user_type          = isset($result->user_type) ? esc_html($result->user_type) : '';
            $contact_code       = isset($result->contact_code) ? esc_html($result->contact_code) : '';
            $contact_number     = isset($result->contact_number) ? esc_html($result->contact_number) : '';
            $country_name       = isset($result->country) ? esc_html($result->country) : '';
            $ind_bus_type       = isset($result->ind_bus_type) ? esc_html($result->ind_bus_type) : '';
            $business_name      = isset($result->business_name) ? esc_html($result->business_name) : '';
            $created            = isset($result->created) ? esc_html($result->created) : '';
            $environment        = isset($result->environment) ? esc_html($result->environment) : '';
            $account_id         = isset($result->account_id) ? esc_html($result->account_id) : '';
            $countryName        = WC()->countries->countries[$country_name];

            if ($account_id) {
                $data[] = array(
                    'id'                => $result->id,
                    'account_id'        => $account_id,
                    'user_type'         => $user_type,
                    'email'             => $result->email,
                    'first_name'        => $first_name,
                    'last_name'         => $last_name,
                    'contact_code'      => $contact_code,
                    'contact_number'    => $contact_number,
                    'country_name'      => $countryName,
                    'ind_bus_type'      => $ind_bus_type,
                    'created'           => $created,
                    'business_name'     => $business_name,
                    'environment'       => $environment,
                );
            }
        }
        return $data;
    }
    function column_default($item, $column_name)
    {
        switch ($column_name) {
        case 'id':
        case 'account_id':
        case 'user_type':
        case 'email':
        case 'first_name':
        case 'last_name':
        case 'contact_code':
        case 'contact_number':
        case 'country_name':
        case 'ind_bus_type':
        case 'created':
        case 'business_name':
        case 'environment':
            return $item[$column_name];
        default:
            return print_r($item, true);
        }
    }

    /*
    * @see WP_List_Table::::single_row_columns()
    * @param array $item A singular item (one full row's worth of data)
    */
    function column_title($item)
    {
        //Build row actions
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&user=%s">Edit</a>', 'tazapay-user-edit', 'edit', $item['id']),
        );

        //Return the title contents
        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/
            $item['id'],
            /*$2%s*/
            $item['id'],
            /*$3%s*/
            $this->row_actions($actions)
        );
    }

    /*
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],
            /*$2%s*/
            $item['ID']
        );
    }

    /*
    * @see WP_List_Table::::single_row_columns()
    * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
    */
    function get_columns()
    {
        $columns = array(
            'title'             => __('ID', 'wc-tp-payment-gateway'),
            'account_id'        => __('Tazapay Account UUID', 'wc-tp-payment-gateway'),
            'user_type'         => __('User Type', 'wc-tp-payment-gateway'),
            'email'             => __('Email', 'wc-tp-payment-gateway'),
            'ind_bus_type'      => __('Entity Type', 'wc-tp-payment-gateway'),
            'first_name'        => __('First Name', 'wc-tp-payment-gateway'),
            'last_name'         => __('Last Name', 'wc-tp-payment-gateway'),
            'business_name'     => __('Bussiness Name', 'wc-tp-payment-gateway'),
            'contact_code'      => __('Contact Code', 'wc-tp-payment-gateway'),
            'contact_number'    => __('Contact Number', 'wc-tp-payment-gateway'),
            'country_name'      => __('Country', 'wc-tp-payment-gateway'),
            'environment'       => __('Environment', 'wc-tp-payment-gateway'),
            'created'           => __('Created', 'wc-tp-payment-gateway'),
        );
        return $columns;
    }

    /* 
    * Sortable columns
    */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'title'         => array('id', false),
            'country_name'  => array('country_name', false),
            'environment'   => array('environment', false)
        );
        return $sortable_columns;
    }
    /*
    * Prepare user data for display
    */
    function prepare_items()
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $data = $this->table_data();

        function usort_reorder($a, $b)
        {
            $orderby = !empty($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : 'id';
            $order   = !empty($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'DESC';
            $result  = strnatcmp($a[$orderby], $b[$orderby]);
            
            return ($order === 'asc') ? $result : -$result;
        }
        usort($data, 'usort_reorder');

        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;

        $this->set_pagination_args(
            array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
            )
        );
    }
}
/*
* Add tazapay Users menu
*/
function tcpg_add_menu_items()
{
    $woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');
    $tazapay_payment_method          = !empty($woocommerce_tz_tazapay_settings['enabled']) ? esc_html($woocommerce_tz_tazapay_settings['enabled']) : '';

    if ($tazapay_payment_method == 'yes') {
        add_submenu_page('woocommerce', __('Tazapay Users', 'wc-tp-payment-gateway'), __('Tazapay Users', 'wc-tp-payment-gateway'), 'manage_options', 'tazapay-user', 'tcpg_render_list_page');
        add_submenu_page('', '', '', 'manage_options', 'tazapay-user-edit', 'tcpg_render_edit_page');
        add_submenu_page('', '', '', 'manage_options', 'tazapay-signup-form', 'tcpg_signup_form');
    }
}
add_action('admin_menu', 'tcpg_add_menu_items');

/*
* Tazapay seller form
*/
function tcpg_signup_form($atts)
{
    include_once plugin_dir_path(__FILE__) . 'shortcodes/tazapay-accountform-shortcode.php';
}

/*
* List of tazapay users
*/
function tcpg_render_list_page()
{
    $tazapayListTable = new TCPG_User_List_Table();
    $tazapayListTable->prepare_items();

    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"><br /></div>
        <h2><?php esc_html_e('Tazapay Users', 'wc-tp-payment-gateway'); ?></h2>        
        <form id="user-filter" method="get">
            <?php $tazapayListTable->display() ?>
        </form>
    </div>
    <?php
}
/*
* Tazapay user uuid edit
*/
function tcpg_render_edit_page()
{
    global $wpdb;
    $user_id    = isset($_GET['user']) ? sanitize_text_field($_GET['user']) : '';
    $tablename  = $wpdb->prefix . 'tazapay_user';
    $row_user   = $wpdb->get_row("SELECT * FROM $tablename WHERE id = '" . $user_id . "'");

    if ('edit' === sanitize_text_field($_REQUEST['action'])) {
        $row_user = $wpdb->get_row("SELECT * FROM $tablename WHERE id = '" . $user_id . "'");

        if (count(array($row_user)) > 0) {
            $account_id = $row_user->account_id;

            if (isset($_POST['submit'])) {
                $new_value = isset($_POST['account_id']) ? sanitize_text_field($_POST['account_id']) : '';
                $wpdb->query($wpdb->prepare("UPDATE $tablename SET account_id = %s WHERE ID = %s", $new_value, $user_id));
                $success = true;
            }
            ?>
            <div class="wrap">
                <h2><?php esc_html_e('Edit Tazapay Account UUID', 'wc-tp-payment-gateway'); ?></h2>
                <div id="response-message">
                    <?php if (isset($success)) { ?>
                        <div class="notice notice-success">
                            <?php if ($success == true) { ?>
                                <p><?php esc_html_e('Tazapay Account UUID updated.', 'wc-tp-payment-gateway'); ?></p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="form">
                    <form method="post" action="">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><label><?php esc_html_e('Tazapay Account UUID', 'wc-tp-payment-gateway'); ?></label></th>
                                <td><input type="text" name="account_id" id="account_id" value="<?php esc_html_e($account_id, 'wc-tp-payment-gateway'); ?>" placeholder="<?php esc_attr_e('Tazapay Account UUID', 'wc-tp-payment-gateway'); ?>" size="50" required />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"></th>
                                <td>
                                    <input type="submit" name="submit" value="<?php esc_html_e('Update', 'wc-tp-payment-gateway'); ?>" class="button-primary" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
            <?php
        }
    }
}

add_filter('gettext', 'tcpg_nouserfound_keyword');
/*
* Change no items found text message.
*/
function tcpg_nouserfound_keyword($text)
{
    if (is_admin()) {
        $text = str_ireplace('No items found.', 'No user found.',  $text);
    }
    return $text;
}

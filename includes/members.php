<?php

namespace MemberZone;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Members extends \WP_List_Table
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        // add_action('admin_post_memberzone_quick_edit', array($this, 'handle_quick_edit'));
        // add_action('admin_post_memberzone_delete_member', array($this, 'handle_delete_member'));

        parent::__construct([
            'singular' => __('Member', 'memberzone'),
            'plural' => __('Members', 'memberzone'),
            'ajax' => false,
        ]);
    }

    public function get_users($args = [])
    {
        $users = get_users($args);
        return $users;
    }

    /**
     * Associative array of columns
     *
     * @return array
     */
    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'username' => __('Username', 'memberzone'),
            'email' => __('Email', 'memberzone'),
            'membership_level' => __('Membership Level', 'memberzone'),
            'status' => __('Status', 'memberzone'),
            'registered_date' => __('Registered Date', 'memberzone'),
            'expiration_date' => __('Expiration Date', 'memberzone'),
        ];
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'username' => ['user_login', false],
            'email' => ['user_email', false],
            'status' => ['status', false],
            'registered_date' => ['user_registered', true],
            'expiration_date' => ['expiration_date', false],
        ];
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-delete' => __('Delete', 'memberzone'),
        ];

        return $actions;
    }

    public function process_bulk_action()
    {
        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete') ||
                (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')) {
            $delete_ids = esc_sql($_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                // self::delete_customer($id);
                wp_delete_user($id);
            }

            // Clear the buffer and redirect after deletion
            ob_end_clean();
            wp_redirect(esc_url(add_query_arg('deleted', count($delete_ids))));
            exit;
        }
    }

    public function process_single_action()
    {
        // Check if the 'delete' action is triggered
        if (isset($_GET['action']) && $_GET['action'] === 'delete') {
            // Ensure 'nonce' is set before accessing it
            if (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'delete_member')) {
                $user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
                if ($user_id) {
                    wp_delete_user($user_id);

                    // Redirect after deletion
                    ob_end_clean();
                    wp_redirect(esc_url(add_query_arg('deleted', 1)));
                    exit;
                }
            } else {
                // Handle nonce verification failure or missing nonce
                wp_die(__('Security check failed or nonce missing', 'memberzone'));
            }
        }
    }

    public function prepare_items()
    {
        // Define arguments for retrieving users
        $per_page = 10;  // Fixed number of items per page
        $current_page = $this->get_pagenum();  // Get the current page number

        // Retrieve sorting parameters
        $orderby = !empty($_GET['orderby']) ? $_GET['orderby'] : 'registered';
        $order = !empty($_GET['order']) ? $_GET['order'] : 'DESC';

        // Retrieve search parameters
        $search = (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';

        $args = [
            'orderby' => $orderby,
            'order' => $order,
            'number' => $per_page,  // Set a fixed number of items per page
            'paged' => $current_page,  // Use pagination from WP_List_Table
        ];

        if (!empty($search)) {
            $args['search'] = $search;
        }

        $users = $this->get_users($args);
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $users;

        // Set pagination
        $total_items = count_users()['total_users'];  // Total number of users

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->ID);
    }

    /**
     * *The column_default method defines how each column is displayed.
     * It takes an item and a column name as parameters, and returns the corresponding data.
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'username':
                return $item->user_login;

            case 'email':
                return $item->user_email;

            case 'membership_level':
                $membership_level_user = get_user_meta($item->ID, 'memberzone_membership_level', true);
                $membership_level = Membership_Levels::get_instance()->get_membership_levels('active', $membership_level_user);

                // if not null
                if ($membership_level) {
                    return $membership_level['name'] ? esc_html($membership_level['name']) : __('None', 'memberzone');
                }

                return $membership_level_user ? esc_html($membership_level_user) : __('None', 'memberzone');

            case 'status':
                $status = get_user_meta($item->ID, 'membership_status', true);
                return $status ? esc_html(ucfirst($status)) : __('None', 'memberzone');

            case 'registered_date':
                return esc_html(date('Y-m-d H:i:s', strtotime($item->user_registered)));

            case 'expiration_date':
                return esc_html(date('Y-m-d H:i:s', strtotime($item->expiration_date)));

            default:
                return $item->$column_name;
        }
    }

    // Adding action links to column
    public function column_username($item)
    {
        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&user=%s">' . __('Edit', 'memberzone') . '</a>', $_REQUEST['page'], 'edit', $item->ID),
            'delete' => sprintf('<a href="?page=%s&action=%s&user=%s&nonce=%s">' . __('Delete', 'memberzone') . '</a>', $_REQUEST['page'], 'delete', $item->ID, wp_create_nonce('delete_member')),
        ];

        return sprintf('%1$s %2$s', $item->user_login, $this->row_actions($actions));
    }

    // Implement search box
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }
        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        if (!empty($_REQUEST['post_mime_type'])) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr($_REQUEST['post_mime_type']) . '" />';
        }
        if (!empty($_REQUEST['detached'])) {
            echo '<input type="hidden" name="detached" value="' . esc_attr($_REQUEST['detached']) . '" />';
        }

        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id) ?>"><?php echo esc_html($text); ?></label>
            <input type="search" id="<?php echo esc_attr($input_id) ?>" name="s" value="<?php echo esc_attr($_REQUEST['s'] ?? ''); ?>" />
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }

    public function render_members_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->process_bulk_action();
        $this->process_single_action();
        $this->prepare_items();
        ?>
    <div class="wrap">
        <h1><?php esc_html_e('Registered Members', 'memberzone'); ?></h1>
        <form method="post">
        <?php
        wp_nonce_field('bulk-members');
        $this->search_box(__('Search', 'memberzone'), 'member');
        $this->display();
        ?>
        </form>
    </div>
    <?php
    }
}

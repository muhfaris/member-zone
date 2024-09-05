<?php

namespace MemberZone;

class Membership_Levels
{
    private static $instance = null;

    private function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_save_membership_level', array($this, 'save_new_level'));
        add_action('admin_post_bulk_update_membership_levels', array($this, 'bulk_update_membership_levels'));
        add_action('admin_post_quick_edit_membership_level', array($this, 'quick_edit_membership_level'));
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_settings()
    {
        register_setting('memberzone_membership_levels', 'memberzone_membership_levels');
    }

    public function enqueue_scripts()
    {
        // Correctly enqueue the CSS file
        wp_enqueue_style(
            'memberzone-membership-levels',
            '/assets/css/membership-levels.css',
            array(),
            null
        );

        wp_enqueue_script(
            'memberzone-membership-levels',
            '/assets/js/membership-levels.js',
            array('jquery'),
            '1.0',
            true,
        );

        wp_localize_script('memberzone-membership-levels', 'memberzone_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('memberzone_register_nonce')
        ));
    }

    public function render_levels_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        // Check for success or error messages
        switch ($status) {
            case 'success':
                add_settings_error('memberzone_messages', 'memberzone_message', __('Membership levels updated successfully!', 'memberzone'), 'updated');
                break;
            case 'error':
                add_settings_error('memberzone_messages', 'memberzone_message', __('There was an error updating the membership levels.', 'memberzone'), 'error');
                break;
            case 'duplicated_membership_level':
                add_settings_error('memberzone_messages', 'memberzone_message', __('The membership level already exists. Please choose a different name.', 'memberzone'), 'error');
                break;
            case 'permission_denied':
                add_settings_error('memberzone_messages', 'memberzone_message', __('You do not have sufficient permissions to access this page.', 'memberzone'), 'error');
                break;
            case 'invalid_request':
                add_settings_error('memberzone_messages', 'memberzone_message', __('Invalid request.', 'memberzone'), 'error');
                break;
            case 'invalid_membership_level':
                add_settings_error('memberzone_messages', 'memberzone_message', __('Invalid membership level.', 'memberzone'), 'error');
                break;
            default:
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('memberzone_messages'); ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php
                wp_nonce_field('update_membership_levels_action', 'update_membership_levels_nonce');
        $membership_levels = get_option('memberzone_membership_levels', array());
        ?>
                <input type="hidden" name="action" value="bulk_update_membership_levels">

                <div class="tablenav top">
                    <!-- Bulk action selector (top) -->
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector-top">
                            <option value="-1">Bulk Actions</option>
                            <option value="update_status">Update Status</option>
                            <option value="update_duration">Update Duration</option>
                            <option value="update_role">Update Role</option>
                            <option value="update_delete">Delete</option>
                        </select>
                        <select name="bulk_status" style="display:none;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <input type="number" name="bulk_duration" style="display:none;" placeholder="New duration">
                        <select name="bulk_role" style="display:none;">
                            <?php wp_dropdown_roles(); ?>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="Apply">
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1"></td>
                            <th scope="col" class="manage-column column-name">Name</th>
                            <th scope="col" class="manage-column column-duration">Duration (days)</th>
                            <th scope="col" class="manage-column column-role">Role</th>
                            <th scope="col" class="manage-column column-status">Status</th>
                            <th scope="col" class="manage-column column-price">Price</th>
                        </tr>
                    </thead>
                    <tbody id="membership-levels-body">
                        <?php
                if (!empty($membership_levels)) {
                    foreach ($membership_levels as $index => $level) {
                        $this->render_level_row($index, $level);
                    }
                } else {
                    echo '<tr><td colspan="6">' . esc_attr('No membership levels found.', 'memberzone') . '</td></tr>';
                }
        ?>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <!-- Bulk action selector (bottom) -->
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
                        <select name="bulk_action" id="bulk-action-selector-bottom">
                            <option value="-1">Bulk Actions</option>
                            <option value="update_status">Update Status</option>
                            <option value="update_duration">Update Duration</option>
                            <option value="update_role">Update Role</option>
                            <option value="update_delete">Delete</option>
                        </select>
                        <select name="bulk_status" style="display:none;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <input type="number" name="bulk_duration" style="display:none;" placeholder="New duration">
                        <select name="bulk_role" style="display:none;">
                            <?php wp_dropdown_roles(); ?>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="Apply">
                    </div>
                </div>
            </form>

            <h2><?php esc_attr_e('Add New Level', 'memberzone'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php
                wp_nonce_field('save_membership_level_action', 'save_membership_level_nonce');
        settings_fields('memberzone_membership_levels');
        ?>
                <input type="hidden" name="action" value="save_membership_level">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_level_name"><?php esc_attr_e('Name', 'memberzone'); ?></label></th>
                        <td><input type="text" id="new_level_name" name="new_level[name]" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_level_duration"><?php esc_attr_e('Duration (days)', 'memberzone'); ?></label></th>
                        <td><input type="number" id="new_level_duration" name="new_level[duration]" value="30" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_level_price"><?php esc_attr_e('Price', 'memberzone'); ?></label></th>
                        <td>
                            <input type="number" id="new_level_price" name="new_level[price]" value="0" step="0.01" required>
                            <p class="description">
                                <?php
                        // Get the currency unit from plugin settings or set a default
                        $currency = get_option('memberzone_payment_currency', 'USD');
        echo sprintf(__('Unit price in %s', 'memberzone'), esc_html($currency));
        ?>

                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_level_role"><?php esc_attr_e('Role', 'memberzone'); ?></label></th>
                        <td>
                            <select id="new_level_role" name="new_level[role]">
                                <?php wp_dropdown_roles(); ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_level_status"><?php esc_attr_e('Status', 'memberzone'); ?></label></th>
                        <td>
                            <select id="new_level_status" name="new_level[status]">
                                <option value="active"><?php esc_attr_e('Active', 'memberzone'); ?></option>
                                <option value="inactive"><?php esc_attr_e('Inactive', 'memberzone'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add New Level', 'memberzone')); ?>
            </form>
        </div>
    <div id="loading-overlay" style="display:none;">
        <div id="loading-spinner"></div>
    </div>
        <table id="quick-edit-row" style="display: none;">
            <tbody>
                <tr>
                    <td colspan="6">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('quick_edit_membership_level_action', 'quick_edit_membership_level_nonce'); ?>
                            <input type="hidden" name="action" value="quick_edit_membership_level">
                            <input type="hidden" name="level_id" value="">
                            <table class="form-table">
                                <tr>
                                    <th><label for="quick_edit_name"><?php esc_attr_e('Name', 'memberzone'); ?></label></th>
                                    <td><input type="text" name="quick_edit_name" value=""></td>
                                </tr>
                                <tr>
                                    <th><label for="quick_edit_duration"><?php esc_attr_e('Duration (days)', 'memberzone'); ?></label></th>
                                    <td><input type="number" name="quick_edit_duration" value=""></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="quick_edit_price"><?php esc_attr_e('Price', 'memberzone'); ?></label></th>
                                    <td>
                                        <input type="number" id="new_level_price" name="" value="0" step="0.01" required>
                   <p class="description">
                                <?php
        // Get the currency unit from plugin settings or set a default
        $currency = get_option('memberzone_payment_currency', 'USD');
        echo sprintf(__('Unit price in %s', 'memberzone'), esc_html($currency));
        ?>
                            </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="quick_edit_role"><?php esc_attr_e('Role', 'memberzone'); ?></label></th>
                                    <td>
                                        <select name="quick_edit_role">
                                            <?php wp_dropdown_roles(); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="quick_edit_status"><?php esc_attr_e('Status', 'memberzone'); ?></label></th>
                                    <td>
                                        <select name="quick_edit_status">
                                            <option value="active"><?php esc_attr_e('Active', 'memberzone'); ?></option>
                                            <option value="inactive"><?php esc_attr_e('Inactive', 'memberzone'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <input type="hidden" name="level_id" value="">
                            <div class="quick-edit-buttons">
                                <?php submit_button(__('Update Level', 'memberzone')); ?>
                                <button type="button" id="cancel-quick-edit" class="button cancel-quick-edit"><?php esc_attr_e('Cancel', 'memberzone'); ?></button>
                            </div>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function render_level_row($index, $level)
    {
        $currency = get_option('memberzone_payment_currency', 'USD');
        ?>
    <tr>
        <th scope="row" class="check-column">
            <input type="checkbox" name="level_ids[]" value="<?php echo esc_attr($index); ?>">
        </th>
        <td>
            <strong><?php echo esc_html($level['name']); ?></strong>
            <div class="row-actions">
                <span class="inline hide-if-no-js">
                    <button type="button" class="quick-edit editinline" aria-label="<?php esc_attr_e('Quick edit', 'memberzone'); ?>" aria-expanded="false"><?php esc_attr_e('Quick&nbsp;Edit', 'memberzone'); ?></button> |
                </span>
                <span class="delete">
                    <a href="#" class="submitdelete" aria-label="<?php esc_attr_e('Delete', 'memberzone'); ?>"><?php esc_attr_e('Delete', 'memberzone'); ?></a>
                </span>
            </div>
        </td>
        <td><?php echo esc_html($level['duration']); ?></td>
        <td><?php echo esc_html($level['role']); ?> </td>
        <td><?php echo esc_html($level['status']); ?> </td>
        <td><?php echo esc_html($level['price']) . ' ' . esc_html($currency); ?> </td>
    </tr>
    <?php
    }

    public function save_new_level()
    {
        if (!current_user_can('manage_options')) {
            wp_redirect(add_query_arg('status', 'permission_denied', wp_get_referer()));
            return;
        }

        if (!isset($_POST['save_membership_level_nonce']) || !wp_verify_nonce($_POST['save_membership_level_nonce'], 'save_membership_level_action')) {
            wp_redirect(add_query_arg('status', 'invalid_request', wp_get_referer()));
            return;
        }

        if (isset($_POST['new_level'])) {
            $new_level = array_map('sanitize_text_field', $_POST['new_level']);

            $membership_levels = get_option('memberzone_membership_levels', array());
            if (!is_array($membership_levels)) {
                $membership_levels = array();
            }

            // Check for duplicate levels
            $is_duplicate = false;
            foreach ($membership_levels as $level) {
                if ($level['name'] === $new_level['name']) {
                    $is_duplicate = true;
                    break;
                }
            }

            if ($is_duplicate) {
                wp_redirect(add_query_arg('status', 'duplicated_membership_level', wp_get_referer()));
                return;
            }

            $membership_levels[] = $new_level;
            $updated = update_option('memberzone_membership_levels', $membership_levels);

            if ($updated) {
                wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
                exit;
            } else {
                wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
                exit;
            }
        } else {
            wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
            exit;
        }
    }

    public function bulk_update_membership_levels()
    {
        if (!current_user_can('manage_options')) {
            wp_redirect(add_query_arg('status', 'permission_denied', wp_get_referer()));
            return;
        }

        if (!isset($_POST['update_membership_levels_nonce']) || !wp_verify_nonce($_POST['update_membership_levels_nonce'], 'update_membership_levels_action')) {
            wp_redirect(add_query_arg('status', 'invalid_request', wp_get_referer()));
            return;
        }

        $membership_levels = get_option('memberzone_membership_levels', array());

        // Handle bulk actions
        if (isset($_POST['level_ids']) && is_array($_POST['level_ids'])) {
            $bulk_action = $_POST['bulk_action'];
            $level_ids = array_map('intval', $_POST['level_ids']);

            switch ($bulk_action) {
                case 'update_delete':
                    foreach ($level_ids as $id) {
                        if (isset($membership_levels[$id])) {
                            unset($membership_levels[$id]);
                        }
                    }
                    break;
                case 'update_status':
                    $new_status = sanitize_text_field($_POST['bulk_status']);
                    foreach ($level_ids as $id) {
                        if (isset($membership_levels[$id])) {
                            $membership_levels[$id]['status'] = $new_status;
                        }
                    }
                    break;
                case 'update_duration':
                    $new_duration = intval($_POST['bulk_duration']);
                    foreach ($level_ids as $id) {
                        if (isset($membership_levels[$id])) {
                            $membership_levels[$id]['duration'] = $new_duration;
                        }
                    }
                    break;
                case 'update_role':
                    $new_role = sanitize_text_field($_POST['bulk_role']);
                    foreach ($level_ids as $id) {
                        if (isset($membership_levels[$id])) {
                            $membership_levels[$id]['role'] = $new_role;
                        }
                    }
                    break;
            }
            // Reset bulk_action variable
            $bulk_action = '';
        }

        // Update individual level details
        if (isset($_POST['memberzone_membership_levels']) && is_array($_POST['memberzone_membership_levels'])) {
            foreach ($_POST['memberzone_membership_levels'] as $index => $level) {
                $membership_levels[$index] = array_map('sanitize_text_field', $level);
            }
        }

        $updated = update_option('memberzone_membership_levels', $membership_levels);

        if ($updated) {
            wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
        }
        exit;
    }

    public function quick_edit_membership_level()
    {
        if (!current_user_can('manage_options')) {
            wp_redirect(add_query_arg('status', 'permission_denied', wp_get_referer()));
            return;
        }

        if (!isset($_POST['quick_edit_membership_level_nonce']) || !wp_verify_nonce($_POST['quick_edit_membership_level_nonce'], 'quick_edit_membership_level_action')) {
            wp_redirect(add_query_arg('status', 'invalid_request', wp_get_referer()));
            return;
        }

        // Get and sanitize form data
        $level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
        $level_name = isset($_POST['quick_edit_name']) ? sanitize_text_field($_POST['quick_edit_name']) : '';
        $level_duration = isset($_POST['quick_edit_duration']) ? intval($_POST['quick_edit_duration']) : 0;
        $level_role = isset($_POST['quick_edit_role']) ? sanitize_text_field($_POST['quick_edit_role']) : '';
        $level_status = isset($_POST['quick_edit_status']) ? sanitize_text_field($_POST['quick_edit_status']) : '';

        // Validate level_id
        if ($level_id <= 0) {
            wp_redirect(add_query_arg('status', 'invalid_membership_level', wp_get_referer()));
            return;
        }

        // Retrieve the current membership levels
        $membership_levels = get_option('memberzone_membership_levels', array());

        if (isset($membership_levels[$level_id])) {
            // Update the membership level
            $membership_levels[$level_id] = array(
                'name' => $level_name,
                'duration' => $level_duration,
                'role' => $level_role,
                'status' => $level_status
            );

            // Save the updated membership levels
            $updated = update_option('memberzone_membership_levels', $membership_levels);

            if ($updated) {
                wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
            } else {
                wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
            }
        } else {
            wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
        }
        exit;
    }

    public function get_membership_levels($status = 'active', $id_request = null)
    {
        $membership_levels = get_option('memberzone_membership_levels', array());

        // Filter by status if provided
        if ($status !== 'all') {
            $membership_levels = array_filter($membership_levels, function ($level) use ($status) {
                return $level['status'] === $status;
            });
        }

        foreach ($membership_levels as $id => &$level) {
            $level['id'] = $id;  // Add the index as the 'id'
        }
        unset($level);  // Unset reference to avoid potential side effects

        if ($id_request === null) {
            return $membership_levels;
        }

        // Filter the membership levels by id
        $filtered_levels = array_filter($membership_levels, function ($level) use ($id_request) {
            return $level['id'] == $id_request;
        });

        // Return the single level object if found, or null if not found
        if (!empty($filtered_levels)) {
            return reset($filtered_levels);  // Return the first element of the filtered array
        }

        return null;  // Return null if no level matches the id_request
    }
}

Membership_Levels::get_instance();

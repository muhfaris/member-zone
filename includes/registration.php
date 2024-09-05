<?php

namespace MemberZone;

class Registration
{
    public function __construct()
    {
        add_shortcode('memberzone_register', array($this, 'render_registration_page'));
        add_action('init', array($this, 'process_registration'));  // Process registration on form submission
    }

    public function render_registration_page()
    {
        // Display registration status messages
        if (isset($_GET['registration'])) {
            $registration_status = sanitize_text_field($_GET['registration']);
            echo "<div class='memberzone-alert-registration-success'>";
            if ($registration_status === 'success') {
                echo '<p class="memberzone-registration-msg success">' . esc_html__('Registration successful. Please check your email for further instructions.', 'memberzone') . '</p>';
            } elseif ($registration_status === 'failed') {
                echo '<p class="memberzone-registration-msg error">' . esc_html__('Registration failed. Please try again.', 'memberzone') . '</p>';
            } elseif ($registration_status === 'error') {
                echo '<p class="memberzone-registration-msg error">' . esc_html__('An error occurred during registration. Please try again later.', 'memberzone') . '</p>';
            } elseif ($registration_status === 'invalid_membership_level') {
                echo '<p class="memberzone-registration-msg error">' . esc_html__('Invalid membership level. Please try again.', 'memberzone') . '</p>';
            } elseif ($registration_status === 'user_exists') {
                echo '<p class="memberzone-registration-msg error">' . esc_html__('Username already exists. Please try again.', 'memberzone') . '</p>';
            }
            echo '</div>';
        }

        // Check if the user is already logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            // Check if the user has the 'administrator' role
            if (!in_array('administrator', $current_user->roles, true)) {
?>
                <div class="memberzone-alert-registration">
                    <p class="memberzone-registration-msg">
                        <?php esc_html_e('You are already logged in.', 'memberzone'); ?>
                    </p>
                </div>
            <?php

                return;
            }

            ?>
        <?php
            return;
        }

        // Get the current page URL for the form action
        $form_action_url = esc_url(get_permalink());

        ?>
        <form id="memberzone-registration-form" class="memberzone-form" action="<?php echo $form_action_url; ?>" method="post">
            <?php
            do_action('memberzone_before_registration_form');  // Hook before the form starts
            ?>

            <div class="memberzone-field-group">
                <input type="text" name="username" required placeholder="<?php esc_attr_e('Username', 'memberzone'); ?>" class="memberzone-input memberzone-username">
            </div>

            <?php
            do_action('memberzone_after_username_field');  // Hook after the username field
            ?>

            <div class="memberzone-field-group">
                <input type="email" name="email" required placeholder="<?php esc_attr_e('Email', 'memberzone'); ?>" class="memberzone-input memberzone-email">
            </div>

            <?php
            do_action('memberzone_after_email_field');  // Hook after the email field
            ?>

            <div class="memberzone-field-group">
                <input type="password" name="password" required placeholder="<?php esc_attr_e('Password', 'memberzone'); ?>" class="memberzone-input memberzone-password">
            </div>

            <?php
            do_action('memberzone_after_password_field');  // Hook after the password field
            ?>

            <div class="memberzone-field-group">
                <?php
                $default_membership_level = get_option('memberzone_default_membership_level', '');
                $membership_levels = Membership_Levels::get_instance()->get_membership_levels();

                echo '<select name="memberzone_default_membership_level" class="memberzone-select memberzone-membership-level">';
                echo '<option value="">' . esc_html__('Select default membership level', 'memberzone') . '</option>';
                foreach ($membership_levels as $index => $level) {
                    echo '<option value="' . esc_attr($index) . '" ' . selected($default_membership_level, $index, false) . '>' . esc_html($level['name']) . '</option>';
                }
                echo '</select>';
                ?>
            </div>

            <?php
            do_action('memberzone_after_membership_level_field');  // Hook after the membership level field
            ?>

            <div class="memberzone-field-group">
                <input type="submit" value="<?php esc_attr_e('Register', 'memberzone'); ?>" class="memberzone-submit">
            </div>

            <?php
            wp_nonce_field('memberzone_register_nonce', 'memberzone_nonce');  // Use a custom nonce field
            ?>

            <input type="hidden" name="action" value="memberzone_register">

            <?php
            do_action('memberzone_after_registration_form');  // Hook after the form ends
            ?>
        </form>
<?php
        return ob_get_clean();
    }

    public function process_registration()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'memberzone_register') {
            check_admin_referer('memberzone_register_nonce', 'memberzone_nonce');

            // Sanitize and process form data
            $username = sanitize_user($_POST['username']);
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);
            $membership_level = isset($_POST['memberzone_default_membership_level']) ? sanitize_text_field($_POST['memberzone_default_membership_level']) : '';

            // Allow developers to modify the input values
            $username = apply_filters('memberzone_registration_username', $username);
            $email = apply_filters('memberzone_registration_email', $email);
            $password = apply_filters('memberzone_registration_password', $password);
            $membership_level = apply_filters('memberzone_registration_membership_level', $membership_level);

            // Check if username or email already exists
            if (username_exists($username) || email_exists($email)) {
                wp_redirect(add_query_arg('registration', 'user_exists', get_permalink()));
                exit;
            }

            // Allow custom code execution before user creation
            do_action('memberzone_before_user_registration', $username, $email, $password, $membership_level);

            // Create user
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                wp_redirect(add_query_arg('registration', 'error', get_permalink()));
                exit;
            }

            // Get membership levels
            $membership_levels = Membership_Levels::get_instance()->get_membership_levels('active', $membership_level);
            if (!$membership_levels) {
                wp_redirect(add_query_arg('registration', 'invalid_membership_level', get_permalink()));
                exit;
            }

            $duration = $membership_levels['duration'];

            // Get expiry date from today
            $expiry_date = date('Y-m-d', strtotime('+ ' . $duration . ' day'));
            update_user_meta($user_id, 'expiration_date', $expiry_date);

            // If option invoice landing page is true

            // Get default role and status
            $default_member_status = get_option('memberzone_default_member_status', 'pending');
            $default_member_role = get_option('memberzone_default_member_role', 'subscriber');

            // Set user role and status
            $uid = new \WP_User($user_id);
            $uid->set_role($default_member_role);
            update_user_meta($user_id, 'membership_status', $default_member_status);

            // Assign membership level
            if ($membership_level) {
                update_user_meta($user_id, 'memberzone_membership_level', $membership_level);
            }

            // Allow custom code execution after user creation
            do_action('memberzone_after_user_registration', $user_id, $membership_level);

            // Send welcome email
            $mail = new Email();
            $mail->welcome_email($username, $email, $membership_levels['name'], $expiry_date);

            // Redirect after successful registration
            wp_redirect(add_query_arg('registration', 'success', get_permalink()));
            exit;
        }
    }
}

new Registration();

<?php

namespace MemberZone;

class Login
{
    public function __construct()
    {
        add_shortcode('memberzone_login', array($this, 'render_login_page'));
        add_action('init', array($this, 'process_login'));  // Process login on form submission
    }

    public function render_login_page()
    {
        // If the user is already logged in, check if they are not an admin
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            // Check if the user has the 'administrator' role
            if (!in_array('administrator', $current_user->roles, true)) {
                echo '<p>' . esc_html__('You are already logged in.', 'memberzone') . '</p>';
                return;
            }
        }

        // Get the current page URL for form action
        $form_action_url = esc_url(get_permalink());

        ob_start();  // Start output buffering to store the form in a variable

        // Display login status messages
        if (isset($_GET['login'])) {
            $login_status = sanitize_text_field($_GET['login']);
            echo "<div class='memberzone-alert-login'>";
            if ($login_status === 'failed') {
                echo '<p class="memberzone-login-msg error">' . esc_html__('Login failed. Please try again.', 'memberzone') . '</p>';
            } elseif ($login_status === 'error') {
                echo '<p class="memberzone-login-msg error">' . esc_html__('An error occurred. Please try again.', 'memberzone') . '</p>';
            }
            echo '</div>';
        }

?>
        <form id="memberzone-login-form" class="memberzone-form" action="<?php echo $form_action_url; ?>" method="post">
            <div class="memberzone-field-group">
                <input type="text" name="username" required placeholder="<?php esc_attr_e('Username', 'memberzone'); ?>" class="memberzone-input memberzone-username">
            </div>
            <div class="memberzone-field-group">
                <input type="password" name="password" required placeholder="<?php esc_attr_e('Password', 'memberzone'); ?>" class="memberzone-input memberzone-password">
            </div>
            <div class="memberzone-field-group">
                <input type="submit" value="<?php esc_attr_e('Login', 'memberzone'); ?>" class="memberzone-submit">
            </div>
            <?php wp_nonce_field('memberzone_login_nonce', 'memberzone_nonce'); // Use a custom nonce field
            ?>
            <input type="hidden" name="action" value="memberzone_login">
        </form>
<?php

        return ob_get_clean();  // Return the form HTML
    }

    public function process_login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'memberzone_login') {
            check_admin_referer('memberzone_login_nonce', 'memberzone_nonce');

            // Sanitize the input data
            $username = sanitize_user($_POST['username']);
            $password = sanitize_text_field($_POST['password']);

            // Credentials for wp_signon
            $credentials = array(
                'user_login' => $username,
                'user_password' => $password,
                'remember' => true,
            );

            // Attempt to sign in
            $user = wp_signon($credentials, false);

            // Check if login was successful
            if (is_wp_error($user)) {
                wp_redirect(add_query_arg('login', 'failed', get_permalink()));
                exit;
            }

            // Redirect on successful login
            wp_redirect(home_url());  // You can change this to any URL
            exit;
        }
    }
}

new Login();

<?php

namespace MemberZone;

class Email
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
    }

    public function welcome_email($username, $email, $membership_type, $expiration_date)
    {
        // Ensure $email is a string (email address) not an object
        if (is_object($email) && method_exists($email, 'get_address')) {
            $email = $email->get_address();
        }

        $site_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');
        $subject = 'Welcome to ' . $site_name . '!';
        $message = '<!DOCTYPE html>
<html>
<head>
    <title>Welcome to ' . $site_name . '</title>
</head>
<body>
    <h1>Welcome to ' . $site_name . '!</h1>

    <p>Hi ' . $username . ",</p>

    <p>We're excited to have you join our community. Thank you for choosing " . $site_name . '.<p>

    <p>Your account details are as follows:</p>

    <ul>
        <li><strong>Username:</strong> ' . $username . '</li>
        <li><strong>Email:</strong> ' . $email . '</li>
        <li><strong>Membership:</strong> ' . $membership_type . '</li>
        <li><strong>Expires:</strong> ' . $expiration_date . "</li>
    </ul>

    <p>We hope you enjoy using our platform. If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

    <p>Best regards,</p>
    <p>" . $site_name . ' Team</p>
</body>
</html>';

        $headers = array(
            'Content-Type' => 'text/html',
            'From' => $admin_email . ' <' . $admin_email . '>'  // Use admin_email for both name and address
        );

        wp_mail($email, $subject, $message, $headers);
    }
}

// Email::get_instance();

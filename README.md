# Member Zone

## Screenshot

### Settings

![image](https://github.com/user-attachments/assets/5d4e3697-9897-4e57-b825-02d8680e2fbe)

### Membership

![image](https://github.com/user-attachments/assets/204e5d64-8f55-4f48-b31c-02d19a8b827a)

### Member list

![image](https://github.com/user-attachments/assets/daa2df68-0842-4cfd-ac6f-f6e3774bbd1f)

### Custom hooks registration

```php
// Add content before the registration form
add_action('memberzone_before_registration_form', function() {
    echo '<p class="memberzone-form-intro">' . esc_html__('Please fill out the form below to register.', 'memberzone') . '</p>';
});

// Add a custom field after the username field
add_action('memberzone_after_username_field', function() {
    echo '<div class="memberzone-field-group">';
    echo '<input type="text" name="first_name" placeholder="' . esc_attr__('First Name', 'memberzone') . '" class="memberzone-input memberzone-first-name">';
    echo '</div>';
});

// Add content after the registration form
add_action('memberzone_after_registration_form', function() {
    echo '<p class="memberzone-form-footer">' . esc_html__('Thank you for registering!', 'memberzone') . '</p>';
});
```

#### Add filter and action registration

```php

add_filter('memberzone_registration_username', function($username) {
    return strtoupper($username); // Example: Convert username to uppercase
});

```

```php

add_action('memberzone_after_user_registration', function($user_id, $membership_level) {
    // Custom code, like sending a welcome email
    wp_mail(get_userdata($user_id)->user_email, 'Welcome!', 'Thank you for registering.');
}, 10, 2);
```

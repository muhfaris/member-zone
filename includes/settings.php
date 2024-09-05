<?php

namespace MemberZone;

class Settings
{
    private static $instance = null;

    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        });
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
        register_setting('memberzone_settings_group', 'memberzone_default_membership_level');
        register_setting('memberzone_settings_group', 'memberzone_default_member_status');
        register_setting('memberzone_settings_group', 'memberzone_default_member_role');

        // Add new section for default settings
        add_settings_section('memberzone_default_settings', 'Default Settings', array($this, 'render_default_settings_section'), 'memberzone_settings_general');

        // Add settings fields for default membership level and status
        register_setting('memberzone_default_settings', 'memberzone_default_membership_level');
        register_setting('memberzone_default_settings', 'memberzone_default_member_status');
        register_setting('memberzone_default_settings', 'memberzone_default_member_role');

        add_settings_field('memberzone_default_membership_level_setting', 'Default Membership Level', array($this, 'render_default_membership_level_setting'), 'memberzone_settings_general', 'memberzone_default_settings');
        add_settings_field('memberzone_default_member_status_setting', 'Default Member Status', array($this, 'render_default_member_status_setting'), 'memberzone_settings_general', 'memberzone_default_settings');
        add_settings_field('memberzone_default_member_role_setting', 'Default Member Role', array($this, 'render_default_member_role_setting'), 'memberzone_settings_general', 'memberzone_default_settings');

        // Payment settings
        register_setting('memberzone_payment_group', 'memberzone_payment_gateway');
        register_setting('memberzone_payment_group', 'memberzone_payment_currency');

        add_settings_section('memberzone_payment_settings', 'Payment Settings', array($this, 'render_payment_settings_section'), 'memberzone_settings_payment');
        add_settings_field('memberzone_payment_gateway', 'Payment Gateway', array($this, 'render_payment_gateway_setting'), 'memberzone_settings_payment', 'memberzone_payment_settings');
        add_settings_field('memberzone_payment_currency', 'Currency', array($this, 'render_payment_currency_setting'), 'memberzone_settings_payment', 'memberzone_payment_settings');

        // Shortcode settings
        register_setting('memberzone_shortcodes_group', 'memberzone_shortcodes');

        add_settings_section('memberzone_shortcodes_settings', 'Shortcode Settings', array($this, 'render_shortcodes_settings_section'), 'memberzone_settings_shortcodes');
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

        echo '<div class="wrap">';
        echo '<h1>MemberZone Settings</h1>';

        // Add new tab navigation for Shortcodes
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=memberzone-settings&tab=general" class="nav-tab ' . ($active_tab == 'general' ? 'nav-tab-active' : '') . '">General</a>';
        echo '<a href="?page=memberzone-settings&tab=payment" class="nav-tab ' . ($active_tab == 'payment' ? 'nav-tab-active' : '') . '">Payment</a>';
        echo '<a href="?page=memberzone-settings&tab=shortcodes" class="nav-tab ' . ($active_tab == 'shortcodes' ? 'nav-tab-active' : '') . '">Shortcodes</a>';
        echo '</h2>';

        echo '<form method="post" action="options.php">';

        // Use switch for handling the active tab
        switch ($active_tab) {
            case 'general':
                settings_fields('memberzone_settings_group');
                do_settings_sections('memberzone_settings_general');
                break;

            case 'payment':
                settings_fields('memberzone_payment_group');
                do_settings_sections('memberzone_settings_payment');
                break;

            case 'shortcodes':
                settings_fields('memberzone_shortcodes_group');
                do_settings_sections('memberzone_settings_shortcodes');
                break;
        }

        submit_button();

        echo '</form>';
        echo '</div>';
    }

    public function render_default_settings_section()
    {
        // Render the section title
        echo '<p>These settings generalize the MemberZone system.</p>';
    }

    public function render_default_membership_level_setting()
    {
        $default_membership_level = get_option('memberzone_default_membership_level', '');
        $membership_levels = Membership_Levels::get_instance()->get_membership_levels();

        // Render the select field for default membership level
        echo '<select name="memberzone_default_membership_level">';
        echo '<option value="">Select default membership level</option>';
        foreach ($membership_levels as $index => $level) {
            echo '<option value="' . esc_attr($index) . '" ' . selected($default_membership_level, $index, false) . '>' . esc_html($level['name']) . '</option>';
        }
        echo '</select>';
    }

    public function render_default_member_status_setting()
    {
        $default_member_status = get_option('memberzone_default_member_status', 'pending');

        // Render the select field for default member status
        echo '<select name="memberzone_default_member_status">';
        echo '<option value="pending" ' . selected($default_member_status, 'pending', false) . '>Pending</option>';
        echo '<option value="active" ' . selected($default_member_status, 'active', false) . '>Active</option>';
        echo '<option value="inactive" ' . selected($default_member_status, 'inactive', false) . '>Inactive</option>';
        echo '<option value="banned" ' . selected($default_member_status, 'banned', false) . '>Banned</option>';
        echo '<option value="expired" ' . selected($default_member_status, 'expired', false) . '>Expired</option>';
        echo '</select>';
    }

    public function render_default_member_role_setting()
    {
        // Get the default member role from the options
        $default_member_role = get_option('memberzone_default_member_role', 'contributor');

        // Get the list of roles
        $roles = get_editable_roles();

        // Render the select field for default member role
        echo '<select name="memberzone_default_member_role">';
        echo '<option value="">' . esc_html__('Select default member role', 'memberzone') . '</option>';

        // Loop through roles and create option elements
        foreach ($roles as $role_key => $role) {
            echo '<option value="' . esc_attr($role_key) . '" ' . selected($default_member_role, $role_key, false) . '>' . esc_html($role['name']) . '</option>';
        }
        echo '</select>';
    }

    public function render_payment_settings_section()
    {
        echo '<p>Configure the payment options for your membership site.</p>';
    }

    public function render_payment_gateway_setting()
    {
        $payment_gateway = get_option('memberzone_payment_gateway', 'paypal');

        echo '<select name="memberzone_payment_gateway">';
        echo '<option value="paypal" ' . selected($payment_gateway, 'paypal', false) . '>PayPal</option>';
        echo '<option value="stripe" ' . selected($payment_gateway, 'stripe', false) . '>Stripe</option>';
        echo '</select>';
    }

    public function render_payment_currency_setting()
    {
        $payment_currency = get_option('memberzone_payment_currency', 'USD');

        // List of all currencies in the world
        $all_currencies = array(
            'AED' => 'United Arab Emirates Dirham',
            'AFN' => 'Afghan Afghani',
            'ALL' => 'Albanian Lek',
            'AMD' => 'Armenian Dram',
            'ANG' => 'Netherlands Antillean Guilder',
            'AOA' => 'Angolan Kwanza',
            'ARS' => 'Argentine Peso',
            'AUD' => 'Australian Dollar',
            'AWG' => 'Aruban Florin',
            'AZN' => 'Azerbaijani Manat',
            'BAM' => 'Bosnia-Herzegovina Convertible Mark',
            'BBD' => 'Barbadian Dollar',
            'BDT' => 'Bangladeshi Taka',
            'BGN' => 'Bulgarian Lev',
            'BHD' => 'Bahraini Dinar',
            'BIF' => 'Burundian Franc',
            'BMD' => 'Bermudian Dollar',
            'BND' => 'Brunei Dollar',
            'BOB' => 'Bolivian Boliviano',
            'BRL' => 'Brazilian Real',
            'BSD' => 'Bahamian Dollar',
            'BTN' => 'Bhutanese Ngultrum',
            'BWP' => 'Botswana Pula',
            'BYN' => 'Belarusian Ruble',
            'BZD' => 'Belize Dollar',
            'CAD' => 'Canadian Dollar',
            'CDF' => 'Congolese Franc',
            'CHF' => 'Swiss Franc',
            'CLP' => 'Chilean Peso',
            'CNY' => 'Chinese Yuan',
            'COP' => 'Colombian Peso',
            'CRC' => 'Costa Rican Colón',
            'CUP' => 'Cuban Peso',
            'CVE' => 'Cape Verdean Escudo',
            'CZK' => 'Czech Koruna',
            'DJF' => 'Djiboutian Franc',
            'DKK' => 'Danish Krone',
            'DOP' => 'Dominican Peso',
            'DZD' => 'Algerian Dinar',
            'EGP' => 'Egyptian Pound',
            'ERN' => 'Eritrean Nakfa',
            'ETB' => 'Ethiopian Birr',
            'EUR' => 'Euro',
            'FJD' => 'Fijian Dollar',
            'FKP' => 'Falkland Islands Pound',
            'FOK' => 'Faroese Króna',
            'GBP' => 'British Pound',
            'GEL' => 'Georgian Lari',
            'GGP' => 'Guernsey Pound',
            'GHS' => 'Ghanaian Cedi',
            'GIP' => 'Gibraltar Pound',
            'GMD' => 'Gambian Dalasi',
            'GNF' => 'Guinean Franc',
            'GTQ' => 'Guatemalan Quetzal',
            'GYD' => 'Guyanese Dollar',
            'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran Lempira',
            'HRK' => 'Croatian Kuna',
            'HTG' => 'Haitian Gourde',
            'HUF' => 'Hungarian Forint',
            'IDR' => 'Indonesian Rupiah',
            'ILS' => 'Israeli New Shekel',
            'IMP' => 'Isle of Man Pound',
            'INR' => 'Indian Rupee',
            'IQD' => 'Iraqi Dinar',
            'IRR' => 'Iranian Rial',
            'ISK' => 'Icelandic Króna',
            'JEP' => 'Jersey Pound',
            'JMD' => 'Jamaican Dollar',
            'JOD' => 'Jordanian Dinar',
            'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan Shilling',
            'KGS' => 'Kyrgyzstani Som',
            'KHR' => 'Cambodian Riel',
            'KID' => 'Kiribati Dollar',
            'KMF' => 'Comorian Franc',
            'KRW' => 'South Korean Won',
            'KWD' => 'Kuwaiti Dinar',
            'KYD' => 'Cayman Islands Dollar',
            'KZT' => 'Kazakhstani Tenge',
            'LAK' => 'Lao Kip',
            'LBP' => 'Lebanese Pound',
            'LKR' => 'Sri Lankan Rupee',
            'LRD' => 'Liberian Dollar',
            'LSL' => 'Lesotho Loti',
            'LYD' => 'Libyan Dinar',
            'MAD' => 'Moroccan Dirham',
            'MDL' => 'Moldovan Leu',
            'MGA' => 'Malagasy Ariary',
            'MKD' => 'Macedonian Denar',
            'MMK' => 'Myanmar Kyat',
            'MNT' => 'Mongolian Tögrög',
            'MOP' => 'Macanese Pataca',
            'MRU' => 'Mauritanian Ouguiya',
            'MUR' => 'Mauritian Rupee',
            'MVR' => 'Maldivian Rufiyaa',
            'MWK' => 'Malawian Kwacha',
            'MXN' => 'Mexican Peso',
            'MYR' => 'Malaysian Ringgit',
            'MZN' => 'Mozambican Metical',
            'NAD' => 'Namibian Dollar',
            'NGN' => 'Nigerian Naira',
            'NIO' => 'Nicaraguan Córdoba',
            'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese Rupee',
            'NZD' => 'New Zealand Dollar',
            'OMR' => 'Omani Rial',
            'PAB' => 'Panamanian Balboa',
            'PEN' => 'Peruvian Sol',
            'PGK' => 'Papua New Guinean Kina',
            'PHP' => 'Philippine Peso',
            'PKR' => 'Pakistani Rupee',
            'PLN' => 'Polish Złoty',
            'PYG' => 'Paraguayan Guaraní',
            'QAR' => 'Qatari Rial',
            'RON' => 'Romanian Leu',
            'RSD' => 'Serbian Dinar',
            'RUB' => 'Russian Ruble',
            'RWF' => 'Rwandan Franc',
            'SAR' => 'Saudi Riyal',
            'SBD' => 'Solomon Islands Dollar',
            'SCR' => 'Seychellois Rupee',
            'SDG' => 'Sudanese Pound',
            'SEK' => 'Swedish Krona',
            'SGD' => 'Singapore Dollar',
            'SHP' => 'Saint Helena Pound',
            'SLL' => 'Sierra Leonean Leone',
            'SOS' => 'Somali Shilling',
            'SRD' => 'Surinamese Dollar',
            'SSP' => 'South Sudanese Pound',
            'STN' => 'São Tomé and Príncipe Dobra',
            'SYP' => 'Syrian Pound',
            'SZL' => 'Eswatini Lilangeni',
            'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani Somoni',
            'TMT' => 'Turkmenistani Manat',
            'TND' => 'Tunisian Dinar',
            'TOP' => 'Tongan Paʻanga',
            'TRY' => 'Turkish Lira',
            'TTD' => 'Trinidad and Tobago Dollar',
            'TVD' => 'Tuvaluan Dollar',
            'TWD' => 'New Taiwan Dollar',
            'TZS' => 'Tanzanian Shilling',
            'UAH' => 'Ukrainian Hryvnia',
            'UGX' => 'Ugandan Shilling',
            'USD' => 'US Dollar',
            'UYU' => 'Uruguayan Peso',
            'UZS' => 'Uzbekistani Soʻm',
            'VES' => 'Venezuelan Bolívar',
            'VND' => 'Vietnamese Đồng',
            'VUV' => 'Vanuatu Vatu',
            'WST' => 'Samoan Tālā',
            'XAF' => 'Central African CFA Franc',
            'XCD' => 'East Caribbean Dollar',
            'XOF' => 'West African CFA Franc',
            'XPF' => 'CFP Franc',
            'YER' => 'Yemeni Rial',
            'ZAR' => 'South African Rand',
            'ZMW' => 'Zambian Kwacha',
            'ZWL' => 'Zimbabwean Dollar',
        );

        // Render the select field for payment currency
        echo '<select id="memberzone_payment_currency" name="memberzone_payment_currency" style="width: 30%;">';
        foreach ($all_currencies as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($payment_currency, $code, false) . '>' . esc_html($name . ' (' . $code . ')') . '</option>';
        }
        echo '</select>';

        echo '<p class="description">Select the currency for payments.</p>';

        // Add the script to enable search functionality
        echo '<script>
        jQuery(document).ready(function($) {
            $("#memberzone_payment_currency").select2({
                placeholder: "Select a currency",
                allowClear: true
            });
        });
    </script>';
    }

    public function render_shortcodes_settings_section()
    {
        global $shortcode_tags;

        // Define shortcode descriptions
        $shortcode_descriptions = array(
            'memberzone_login' => __('Displays a login form for the MemberZone plugin.', 'text-domain'),
            'memberzone_register' => __('Displays a registration form for the MemberZone plugin.', 'text-domain'),
        );

        // Filter to include only shortcodes that belong to MemberZone (starting with 'memberzone_')
        $memberzone_shortcodes = array_filter($shortcode_tags, function ($shortcode) {
            return strpos($shortcode, 'memberzone_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($memberzone_shortcodes)) {
            echo '<p>' . __('No MemberZone shortcodes found.', 'memberzone') . '</p>';
            return;
        }

        echo '<ul>';
        foreach ($memberzone_shortcodes as $shortcode => $function) {
            echo '<li>';
            echo '<strong>' . esc_html($shortcode) . '</strong>';

            // Check if description exists, if not provide a default message
            if (isset($shortcode_descriptions[$shortcode])) {
                echo ' - ' . esc_html($shortcode_descriptions[$shortcode]);
            } else {
                echo ' - ' . __('No description available.', 'text-domain');
            }

            echo '</li>';
        }
        echo '</ul>';
    }
}

Settings::get_instance();

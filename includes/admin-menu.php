<?php

namespace MemberZone;

class Admin_Menu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Member Zone',
            'Member Zone',
            'manage_options',
            'memberzone',
            array($this, 'display_main_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'memberzone',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'memberzone',
            array($this, 'display_main_page')
        );

        add_submenu_page(
            'memberzone',
            'Members',
            'Members',
            'manage_options',
            'memberzone-members',
            array(Members::get_instance(), 'render_members_page')
        );

        add_submenu_page(
            'memberzone',
            'Membership',
            'Membership',
            'manage_options',
            'memberzone-levels',
            array(Membership_Levels::get_instance(), 'render_levels_page')
        );

        add_submenu_page(
            'memberzone',
            'Settings',
            'Settings',
            'manage_options',
            'memberzone-settings',
            array(Settings::get_instance(), 'render_settings_page')
        );
    }

    public function display_main_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Member Zone Dashboard</h1>';
        echo '<p>Welcome to the MemberZone dashboard. Here you can manage your membership site.</p>';
        echo '</div>';
    }

    public function display_members_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Member Zone Members</h1>';
        echo '</div>';
    }
}

new Admin_Menu();

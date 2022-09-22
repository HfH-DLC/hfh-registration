<?php

namespace Hfh\Registration;

class Hfh_Registration_Menu
{
    private static $instance = false;

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    private function __construct()
    {
        add_action('network_admin_menu', array($this, 'add_menu'));
        add_action('network_admin_edit_hfh_registration_action', array($this, 'save_settings'));
        add_action('network_admin_notices', array($this, 'display_notice'));
    }

    public function add_menu()
    {
        add_menu_page("HfH", 'HfH', 'manage_options', 'hfh', array($this, 'display_menu'), 'dashicons-admin-generic', 26);
        add_submenu_page('hfh', 'REST Registration', 'REST Registration', 'manage_options', 'rest-registration', array($this, 'display_registration_menu'));
        add_site_option('hfh_registration_send_email', false);
    }

    public function display_menu()
    {
    }

    public function display_registration_menu()
    {
?>

        <div class="wrap">
            <h2>REST Registration</h2>

            <form method="post" action="edit.php?action=hfh_registration_action">
                <?php wp_nonce_field('hfh_registration_action', '_hfh_registration_wpnonce'); ?>
                <h2>Registration Emails</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="send_email">Send password reset mails to new users</label></th>
                        <td>
                            <input type="checkbox" name="hfh_registration_send_email" id="send_email" value="true" <?= get_site_option('hfh_registration_send_email') ? 'checked' : '' ?>>
                        </td>
                    </tr>
                </table>
                <?php
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    function save_settings()
    {
        check_admin_referer('hfh_registration_action', '_hfh_registration_wpnonce');
        update_site_option('hfh_registration_send_email', isset($_POST['hfh_registration_send_email']));
        $url = add_query_arg(
            array(
                'page' => 'rest-registration',
                'updated' => true,
            ),
            network_admin_url('admin.php')
        );
        wp_redirect($url);

        exit;
    }

    function display_notice()
    {

        if (isset($_GET['page']) && $_GET['page'] == 'rest-registration' && isset($_GET['updated'])) {
            echo '<div id="message" class="updated notice is-dismissible"><p>Settings updated.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        }
    }
}

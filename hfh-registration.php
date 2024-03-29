<?php

/**
 * 
 * 
 * @author      Matthias Nötzli
 * @copyright   2021 HfH
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: HfH Registration
 * Description: A plugin to allow adding users to a specific site within a multisite via REST Api.
 * Version:     1.0.3
 * Author:      Matthias Nötzli
 * Text Domain: hfh-registration
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 */

use Hfh\Registration\HfH_Registration_Controller;
use Hfh\Registration\Hfh_Registration_Menu;

//Includes
include('includes/hfh_registration_controller.php');
include('includes/hfh_registration_menu.php');

//Hooks
function hfh_registration_register_routes()
{
    $controller = new HfH_Registration_Controller();
    $controller->register_routes();
}

add_action('rest_api_init', 'hfh_registration_register_routes');

function hfh_registration_add_roles_on_plugin_activation()
{
    add_role('rest_registrator', 'REST Registrator', array('read' => true, 'hfh_register_users' => true));
}

register_activation_hook(__FILE__, 'hfh_registration_add_roles_on_plugin_activation');

function hfh_registration_remove_roles_on_plugin_deactivation()
{
    remove_role('rest_registrator');
}

register_deactivation_hook(__FILE__, 'hfh_registration_remove_roles_on_plugin_deactivation');

Hfh_Registration_Menu::get_instance();

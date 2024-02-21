<?php

namespace Hfh\Registration;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

class HfH_Registration_Controller extends WP_REST_Controller
{

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $version = '1';
        $namespace = 'hfh/v' . $version;
        $base = 'registration';
        register_rest_route($namespace, '/' . $base, array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_registration'),
                'permission_callback' => array($this, 'create_registration_permissions_check'),
                'args'                => array(
                    'username' => array(
                        'type'         => 'string',
                        'required'     => true,
                    ),
                    'email' => array(
                        'type'         => 'string',
                        'format'       => 'email',
                        'required'     => true,
                    ),
                    'book_id' => array(
                        'type'        => 'integer',
                        'required'     => true,
                    ),
                    'first_name' => array(
                        'type'         => 'string',
                        'required'     => false,
                    ),
                    'last_name' => array(
                        'type'         => 'string',
                        'required'     => false,
                    ),
                ),
            ),
        ));
    }

    /**
     * Create one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_registration($request)
    {
        $this->log_registration_info("Creating registration...");
        $registration = $this->prepare_item_for_database($request);
        $this->log_registration_info(json_encode($registration));
        if (method_exists($this, 'register')) {
            $result = $this->register($registration, $request);
            if (is_array($result)) {
                $this->log_registration_info("Registration complete.");
                return new WP_REST_Response($result, 200);
            }
            if (is_wp_error($result)) {
                $messages = $result->get_error_messages();
                $this->log_registration_error(implode(", ", $messages));
                return $result;
            }
        }

        $this->log_registration_error("An error occured during the registration.");
        return new WP_Error('registration-failed', __('An error occured during the registration.', 'hfh-registration'), array('status' => 500));
    }


    /**
     * 
     *
     * @param object $registration Registration data.
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    private function register($registration, $request)
    {
        $main_site_id = get_main_site_id();
        $book_id = $registration['book_id'];

        if (!$book_id || !get_blog_details($book_id)) {
            return new WP_Error('book-not-found', __('No book found for the requested book ID.', 'hfh-registration'), array('status' => 422));
        }

        $user = get_user_by('email', $registration['email']);

        if (!$user) {
            $this->log_registration_info("No existing user found. Creating new user.");
            $password = wp_generate_password();

            $userdata = array(
                'user_pass' => $password,
                'user_login' => $registration['username'],
                'user_email' => $registration['email'],
                'first_name' => $registration['first_name'],
                'last_name' => $registration['last_name']
            );
            $result = wp_insert_user($userdata);
            if (is_wp_error($result)) {
                return $result;
            }
            if ($main_site_id != $book_id) {
                $remove_result = remove_user_from_blog($result, $main_site_id);
                if (is_wp_error($remove_result)) {
                    return $remove_result;
                }
            }
            $user = get_user_by('id', $result);
            if ($user && get_site_option('hfh_registration_send_email')) {
                $this->log_registration_info("Sending registration notification mail.");
                wp_new_user_notification($user->ID, null, 'user');
            }
        }

        if (!$user) {
            return  new WP_Error('user-not-created', __('User could not be created.', 'hfh-registration'), array('status' => 500));
        }
        if (!is_user_member_of_blog($book_id, $user->ID)) {
            $add_result = add_user_to_blog($book_id, $user->ID, 'subscriber');
            if (is_wp_error($add_result)) {
                return $add_result;
            }
        }
        return $this->prepare_item_for_response($user, $request);
    }



    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function create_registration_permissions_check($request)
    {
        return current_user_can('hfh_register_users');
    }

    /**
     * Prepare the item for create operation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database($request)
    {
        return array(
            "username" => $request->get_param('username'),
            "email" => $request->get_param('email'),
            "book_id" => $request->get_param('book_id'),
            "first_name" => $request->get_param('first_name'),
            "last_name" => $request->get_param('last_name'),
        );
    }

    /**
     * Prepare the item for the REST response
     *
     * @param WP_User $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return mixed
     */
    public function prepare_item_for_response($item, $request)
    {
        return array(
            "id" => $item->ID,
            "username" => $item->user_login,
            "email" => $item->user_email,
            "first_name" => $item->first_name,
            "last_name" => $item->last_name,
        );
    }

    private function log_registration_info(string $message)
    {
        error_log("hfh-registration::info:: " . $message);
    }

    private function log_registration_error(string $message)
    {
        error_log("hfh-registration::error:: " . $message);
    }
}

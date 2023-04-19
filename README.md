# HfH Registration

This plugin adds an additional REST endpoint at `/wp-json/hfh/v1/registration`. The purpose of this endpoint is to add a user to a certain subsite of a multisite. If the user does not exist, it will be created first.

The endpoint is implementend in `hfh_registration_controller.php`.

## Settings

The plugin adds a small admin menu at `HfH > REST Registration`. There you can configure whether newly created users should receive a password reset email.
This menu is implemented in `hfh_registration_menu`.

## Usage

### Setting up a User to call the Endpoint

This plugin adds a `REST Registrator` role which allows a user to register users. A separate user with that role should be created to call the endpoint. You should also setup an application password for the user in Wordpress.

### Calling the Endpoint

The endpoint expects a POST request with the following data:

- `username` (string): The name of the new or existing Wordpress user.
- `email` (string): The email of the user.
- `book_id` (integer): The id of the subsite the user should be added to.
- `first_name` (string): The first name of the user. Optional.
- `last_name` (string): The last name of the user. Optional.

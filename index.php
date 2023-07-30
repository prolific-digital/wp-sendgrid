<?php
/*
Plugin Name: SendGrid Integration
Plugin URI: https://www.prolificdigital.com/
Description: A plugin that replaces the default wp_mail function to use SendGrid instead. Enjoy seamless email delivery for your WordPress site with the power of SendGrid.
Version: 1.0
Author: Prolific Digital
Author URI: https://www.prolificdigital.com/
Requires at least: WordPress 5.5
Tested up to: WordPress 6.2.2
Requires PHP: 7.3
Text Domain: sendgrid-integration
Domain Path: /languages/
*/


if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

require_once 'vendor/autoload.php'; // Include the Composer autoloader

class SendGrid_Integration {
  // Constructor to initialize the plugin
  public function __construct() {
    // Hook into WordPress mail functionality and replace it with SendGrid
    add_filter('wp_mail', array($this, 'send_mail_with_sendgrid'));

    // Add plugin options in WordPress admin
    add_action('admin_menu', array($this, 'add_plugin_options'));
    add_action('admin_init', array($this, 'register_settings'));

    // Add action hooks to handle test email form submission
    add_action('admin_post_send_test_email', array($this, 'handle_test_email_submission'));
    add_action('admin_post_nopriv_send_test_email', array($this, 'handle_test_email_submission'));
  }

  public function send_mail_with_sendgrid($args) {
    $is_html = false;
    if (preg_match('/<[^>]+>/', $args['message'])) {
      $is_html = true;
    }

    $sendgrid_api_key = get_option('sendgrid_api_key');

    $from_name = get_bloginfo('name'); // Use the site name as the default "From" name.
    $from_email = get_option('admin_email'); // Use the admin email as the default "From" email.

    // Loop through each header if it exists
    if (isset($args['headers']) && is_array($args['headers'])) {
      foreach ($args['headers'] as $header) {
        if (strpos($header, 'From:') !== false) {
          // Parse out name and email from "From" header
          preg_match('/From:\s*"?([^<"]*)"?\s*<(.+)>/', $header, $matches);
          if (isset($matches[1], $matches[2])) {
            $from_name = trim($matches[1]);
            $from_email = trim($matches[2]);
          }
          break; // No need to loop further
        }
      }
    }

    $email = new \SendGrid\Mail\Mail();
    $email->setFrom($from_email, $from_name);
    $email->setSubject($args['subject']);
    $email->addTo($args['to']);

    if ($is_html) {
      $email->addContent('text/html', $args['message']);
    } else {
      $email->addContent('text/plain', $args['message']);
    }

    // Update the email to include the category as a tag or category
    $category = get_option('sendgrid_email_category'); // Retrieve the category value
    if (!empty($category)) {
      $email->addCategory($category);
    }

    $sendgrid = new \SendGrid($sendgrid_api_key);
    try {
      $response = $sendgrid->send($email);
    } catch (Exception $e) {
      wp_mail($args['to'], $args['subject'], $args['message'], $args['headers']);
    }

    return false;
  }


  // Method to add plugin options in WordPress admin
  public function add_plugin_options() {
    add_options_page('SendGrid Integration', 'SendGrid Integration', 'manage_options', 'sendgrid-integration', array($this, 'render_options_page'));
  }

  // Modify the render_options_page() method to handle form submission
  public function render_options_page() {
    // Check if user has permissions
    if (!current_user_can('manage_options')) {
      return;
    }

    // Trigger admin notices (moved this line to the beginning)
    do_action('admin_notices');
?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <?php
      // Check if the test email was sent successfully and display success message if needed
      if (isset($_GET['test_email_sent']) && $_GET['test_email_sent'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>Test email sent successfully!</p></div>';
      }
      ?>
      <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting "sendgrid_options"
        settings_fields('sendgrid_options');
        // Output setting sections and their fields
        do_settings_sections('sendgrid-integration');
        // Output submit button
        submit_button('Save Settings');
        ?>
      </form>

      <!-- New: Include the test email form -->
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <h2 class="title">Send Test Email</h2>
        <p>
          <label for="test_email">Recipient Email:</label><br>
          <input type="email" name="test_email" id="test_email" class="regular-text code" required>
        </p>
        <p>
          <label for="test_message">Test Message:</label><br>
          <textarea name="test_message" id="test_message" class="large-text code" rows="5" required></textarea>
        </p>
        <p class="submit">
          <?php wp_nonce_field('send_test_email', 'send_test_email_nonce'); ?>
          <input type="hidden" name="action" value="send_test_email">
          <input type="submit" name="send_test_email" id="send_test_email" class="button button-primary" value="Send Test Email">
        </p>
      </form>
    </div>
  <?php
  }



  // Modify the send_test_email() method to handle form submission
  public function send_test_email() {
    if (isset($_POST['send_test_email'])) {
      // No need to check the nonce in this case

      // Retrieve the entered email and message
      $recipient_email = sanitize_email($_POST['test_email']);
      $test_message = sanitize_textarea_field($_POST['test_message']);

      // Prepare the email arguments
      $email_args = array(
        'to' => $recipient_email,
        'subject' => 'Test from WP SendGrid', // Updated subject
        'message' => $test_message,
      );

      // Send the test email
      $this->send_mail_with_sendgrid($email_args);

      // Redirect back to the settings page with a success message
      wp_redirect(add_query_arg('test_email_sent', 'true', admin_url('options-general.php?page=sendgrid-integration')));
      exit();
    }
  }


  // Method to handle the test email form submission
  public function handle_test_email_submission() {
    if (isset($_POST['send_test_email'])) {
      // Verify the nonce for security
      if (!isset($_POST['send_test_email_nonce']) || !wp_verify_nonce($_POST['send_test_email_nonce'], 'send_test_email')) {
        wp_die('Security check failed. Please try again.');
      }

      // Retrieve the entered email and message
      $recipient_email = sanitize_email($_POST['test_email']);
      $test_message = sanitize_textarea_field($_POST['test_message']);

      // Prepare the email arguments
      $email_args = array(
        'to' => $recipient_email,
        'subject' => 'Test from WP SendGrid', // Updated subject
        'message' => $test_message,
      );

      // Send the test email
      $this->send_mail_with_sendgrid($email_args);

      // Redirect back to the settings page with a success message
      wp_redirect(add_query_arg('test_email_sent', 'true', admin_url('options-general.php?page=sendgrid-integration')));
      exit();
    }
  }


  // Method to register plugin settings
  public function register_settings() {
    // Register a new setting for "sendgrid_options" section
    register_setting('sendgrid_options', 'sendgrid_api_key');

    // New: Register a new setting for "sendgrid_options" section - Email category
    register_setting('sendgrid_options', 'sendgrid_email_category');

    // Add a section to the settings page
    add_settings_section(
      'sendgrid_settings_section',
      'SendGrid Settings',
      array($this, 'settings_section_callback'),
      'sendgrid-integration'
    );

    // Add a field to the section we just created
    add_settings_field(
      'sendgrid_api_key',
      'SendGrid API Key',
      array($this, 'api_key_field_callback'),
      'sendgrid-integration',
      'sendgrid_settings_section'
    );

    // New: Email category field
    add_settings_field(
      'sendgrid_email_category',
      'Email Category',
      array($this, 'email_category_field_callback'),
      'sendgrid-integration',
      'sendgrid_settings_section'
    );
  }

  // Callback for Email category field
  public function email_category_field_callback() {
    $category = get_option('sendgrid_email_category', ''); // Get the saved category or use an empty string
  ?>
    <input type="text" name="sendgrid_email_category" value="<?php echo esc_attr($category); ?>" class="regular-text">
    <p class="description">Enter a category for the emails sent via SendGrid (optional).</p>
  <?php
  }


  // Callback for section description
  public function settings_section_callback() {
    echo 'Enter your SendGrid API key below. You can generate an API key by visiting your <a href="https://app.sendgrid.com/settings/api_keys" target="_blank">SendGrid settings screen</a>.';
  }

  // Callback for API key field
  public function api_key_field_callback() {
    $api_key = get_option('sendgrid_api_key');
    $is_valid = $this->is_sendgrid_api_key_valid($api_key);
    $is_empty = empty($api_key);

    $category = get_option('sendgrid_email_category', ''); // Get the saved category or use an empty string
  ?>
    <div style="display: flex; align-items: center;">
      <input type="password" name="sendgrid_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
      <?php if ($is_valid) : ?>
        <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 10px; line-height: 1;"></span>
      <?php elseif (!$is_empty) : ?>
        <span class="dashicons dashicons-dismiss" style="color: #dc3232; margin-left: 10px; line-height: 1; cursor: pointer;" onclick="document.querySelector('.notice-error').style.display = 'none';"></span>
      <?php endif; ?>
    </div>
    <?php if ($is_empty || ($api_key && !$is_valid)) : ?>
      <div class="notice notice-error is-dismissible">
        <?php if ($is_empty) : ?>
          <p>The SendGrid API key cannot be empty. Please enter a valid API key.</p>
        <?php else : ?>
          <p>The SendGrid API key is invalid. Please check your settings and try again.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
<?php
  }

  // Add a new method to validate the SendGrid API key
  private function is_sendgrid_api_key_valid($api_key) {
    if (empty($api_key)) {
      return false; // If the API key is empty, consider it invalid.
    }

    $sendgrid = new \SendGrid($api_key);
    $response = $sendgrid->client->api_keys()->_($api_key)->get();

    return $response->statusCode() === 200;
  }
}

// Initialize the plugin
new SendGrid_Integration();

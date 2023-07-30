# SendGrid Integration for WordPress

The SendGrid Integration plugin for WordPress is designed to replace the default `wp_mail` function with SendGrid's API. This enhances the email deliverability of your WordPress site.

This plugin is developed by [Prolific Digital](https://www.prolificdigital.com/), a creative digital agency. Prolific Digital designs web solutions with a deep strategy and a user-centric approach.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Support](#support)
- [Contributing](#contributing)

## Features

- Replaces the default `wp_mail` function to use SendGrid's API.
- Enables you to manage SendGrid settings within WordPress Admin.
- Includes an option to send a test email from the admin interface.

## Installation

1. Download the latest release from our GitHub repository.
2. Upload the plugin folder to your WordPress plugin directory (usually `wp-content/plugins/`).
3. Activate the plugin through the 'Plugins' menu in your WordPress admin.

> Please note that this plugin requires PHP 7.0+ and WordPress 5.0+.

## Usage

After activating the plugin, you'll need to input your SendGrid API key.

1. Navigate to the SendGrid Integration settings page in your WordPress admin panel.
2. Enter your SendGrid API Key and click "Save Settings".

You can also specify an email category if you would like to tag emails sent via SendGrid.

There is an option to send a test email:

1. Enter the recipient's email address.
2. Write a test message.
3. Click "Send Test Email".

## Support

For support, please [submit an issue](https://github.com/prolific-digital/wp-sendgrid/issues) here within GitHub.

## Contributing

We welcome contributions to improve this plugin. To contribute:

1. Fork the project.
2. Make your feature addition or bug fix.
3. Send us a pull request with a brief description of your changes.

> Before sending a pull request for a feature addition, please discuss it with us first. We want to ensure that changes align with the project's direction and goals.

## License

This project is licensed under the GPL-3.0 License. Please see the [LICENSE file](LICENSE) for more information.

## Versioning

We use Semantic Versioning for versioning. For the versions available, see the [tags on this repository](https://github.com/prolific-digital/wp-sendgrid/tags).

Happy mailing!

# WP-CLI Delete Before

A WordPress plugin that provides a WP-CLI command to delete posts (or custom post types) of a specified type and status that were created before a given date. This tool is useful for bulk cleanup of old content, such as drafts, attachments, or outdated posts.

> **DISCLAIMER: use the WP-CLI command described below with caution and at your own risk. It deletes entries in the database and files in the server, so it may make your site unusable. Testing and backing up the site before using it, especially on a production server, is highly recommended.**

## Features

- Deletes posts based on post type, status, and date criteria.
- Supports all WordPress post types (e.g., posts, pages, attachments).
- Validates input parameters to prevent errors.
- Provides detailed logging and confirmation prompts for safety.
- Handles attachments with special warnings about media file deletion.
- Configurable number of posts to delete and date column for comparison.

## Requirements

- WordPress 4.0 or higher
- WP-CLI 1.0 or higher
- PHP 7.0 or higher

## Installation

1. Download or clone the plugin into your WordPress plugins directory:
   ```
   cd wp-content/plugins
   git clone https://github.com/your-repo/wp-cli-delete-before.git
   ```

2. Install dependencies using Composer:
   ```
   composer install
   ```

3. Activate the plugin in WordPress (or ensure it's loaded in your environment).

The plugin registers the `delete-before` command automatically when WP-CLI is available.

## Usage

Run the command via WP-CLI:

```
wp delete-before <post_type> <post_status> <year> <month> <day> [--number=<number_of_posts>] [--date=<date_column>]
```

### Parameters

- `<post_type>`: The post type to delete (e.g., `post`, `page`, `attachment`).
- `<post_status>`: The post status to delete (e.g., `publish`, `draft`, `inherit` for attachments).
- `<year>`, `<month>`, `<day>`: The cutoff date (YYYY MM DD format). Posts created before this date will be deleted.
- `--number=<number_of_posts>`: (Optional) Maximum number of posts to delete. Default is `-1` (all matching posts).
- `--date=<date_column>`: (Optional) Date column to use for comparison. Options: `post_date_gmt`, `post_modified_gmt`, `post_date`, `post_modified`. Default is `post_date_gmt`.

### Examples

Delete all published posts created before January 1, 2020:
```
wp delete-before post publish 2020 1 1
```

Delete up to 50 draft posts created before March 15, 2019, using the modified date:
```
wp delete-before post draft 2019 3 15 --number=50 --date=post_modified
```

Delete attachments with inherit status created before December 31, 2018:
```
wp delete-before attachment inherit 2018 12 31
```

## Safety Features

- **Validation**: Checks for valid post types, statuses, dates, and columns.
- **Confirmations**: Prompts for confirmation before deletion, especially for attachments.
- **Logging**: Provides detailed output for each deletion attempt, including successes and failures.
- **No Undo**: Deletions are permanent; always back up your database before running.

## Code Structure

The plugin is organized as follows:

- [`index.php`](index.php ): Main plugin file that loads the autoloader and registers the command.
- [`includes/DeleteBeforeCommand.php`](includes/DeleteBeforeCommand.php ): Core command class handling validation, querying, and deletion logic.
- [`includes/DeleteBeforeRegistrar.php`](includes/DeleteBeforeRegistrar.php ): Registers the WP-CLI command.
- [`includes/DeleteBeforeFactory.php`](includes/DeleteBeforeFactory.php ): Factory for creating command instances from CLI arguments.
- [`composer.json`](composer.json ): Autoloading configuration.

## Contributing

Contributions are welcome! Please submit issues or pull requests on GitHub.

## License

This plugin is licensed under the GPL v2 or later. See [`LICENSE`](LICENSE ) for details.

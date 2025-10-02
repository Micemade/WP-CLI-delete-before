<?php
/**
 * Registers the delete-before command for WP-CLI.
 *
 * This command allows users to delete posts of a specified type and status that were created before a given date.
 * It includes validation for post type, post status, date, and date column.
 * The command can be executed with parameters for post type, post status, year, month, day, number of posts to delete, and the date column to use.
 *
 * Example usage:
 * wp delete-before <post_type> <post_status> <year> <month> <day> [--number=<number_of_posts>] [--date=<date_column>]
 * Where:
 * - <post_type>: The type of posts to delete (e.g., post, page, attachment).
 * - <post_status>: The status of posts to delete (e.g., published, draft, inherit).
 * - <year>, <month>, <day>: The date before which posts will be deleted.
 * - --number: (Optional) The maximum number of posts to delete. Default is -1 (all matching posts).
 * - --date: (Optional) The date column to use for comparison. Accepted values are post_date_gmt, post_modified_gmt, post_date, or post_modified. Default is post_date_gmt.
 * - The command provides feedback on the deletion process, including the number of successful and failed deletions.
 * - The command is only available when WP-CLI is defined and active.
 *
 * @package micemade-wpcli-delete-before
 * @since 1.0.0
 */
declare(strict_types=1);

namespace Micemade\WPCliDeleteBefore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_CLI;
use WP_Query;

class DeleteBeforeRegistrar
{
	public static function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'delete-before', function ( $args, $assoc_args ) {
				if ( empty( $args ) || count( $args ) !== 5 ) {
					WP_CLI::error( __( 'Parameters: POST TYPE, POST STATUS, YEAR, MONTH and DAY are required (in this exact order). Please check your parameters. Command syntax is: wp delete-before <post_type> <post_status> <year> <month> <day> < --number=100 > < --date=post_date >.', 'micemade-wpcli-delete-before' ) );
					return;
				}

				$cmd = DeleteBeforeFactory::create_from_args( $args, $assoc_args );
				$cmd->run( $assoc_args );
			} );
		}
	}
}

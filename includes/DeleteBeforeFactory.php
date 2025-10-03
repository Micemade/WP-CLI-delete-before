<?php
/**
 * Factory for creating DeleteBeforeCommand instances from command line arguments.
 *
 * @package wp-cli-delete-before
 * @since 1.0.0
 */
declare(strict_types=1);

namespace Micemade\WPCliDeleteBefore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DeleteBeforeFactory {

	/**
	 * Create a DeleteBeforeCommand instance from WP-CLI command arguments.
	 *
	 * @param array $args Positional arguments: post_type, post_status, year, month, day.
	 * @param array $assoc_args Associative arguments: number (default -1), date (default 'post_date_gmt').
	 * @return DeleteBeforeCommand Configured command instance.
	 */
	public static function create_from_args(array $args, array $assoc_args): DeleteBeforeCommand {

		// Assumed args order validated by caller. Provided reasonable defaults.
		$post_type   = $args[0] ?? '';
		$post_status = $args[1] ?? '';
		$year        = isset( $args[2] ) ? (int) $args[2] : 0;
		$month       = isset( $args[3] ) ? (int) $args[3] : 0;
		$day         = isset( $args[4] ) ? (int) $args[4] : 0;

		$posts_num = $assoc_args['number'] ?? -1;
		$date_col  = $assoc_args['date'] ?? 'post_date_gmt';

		return new DeleteBeforeCommand( $post_type, $post_status, $year, $month, $day, $posts_num, $date_col );
	}
}

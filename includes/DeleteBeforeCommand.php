<?php
/**
 * Implements the delete-before command for WP-CLI.
 * This command deletes posts of a specified type and status that were created before a given date.
 * It includes validation for post type, post status, date, and date column.
 * The command can be executed with parameters for post type, post status, year, month, day, number of posts to delete, and the date column to use.
 *
 * @package wp-cli-delete-before
 * @since 1.0.0
 */
declare(strict_types=1);

namespace Micemade\WPCliDeleteBefore;

use WP_CLI;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DeleteBeforeCommand {

	/** @var string The post type to delete. */
	private string $post_type;

	/** @var string The post status to delete. */
	private string $post_status;

	/** @var int The year for the cutoff date. */
	private int $year;

	/** @var int The month for the cutoff date. */
	private int $month;

	/** @var int The day for the cutoff date. */
	private int $day;

	/** @var int|string Maximum number of posts to delete. */
	private int|string $posts_num;

	/** @var string Date column to use for comparison. */
	private string $post_date_column;

	/** @var string Text domain for translations. */
	private string $textdomain = 'wp-cli-delete-before';

	/**
	 * Constructor for DeleteBeforeCommand.
	 *
	 * @param string $post_type The post type to delete.
	 * @param string $post_status The post status to delete.
	 * @param int $year The year for the cutoff date.
	 * @param int $month The month for the cutoff date.
	 * @param int $day The day for the cutoff date.
	 * @param int|string $posts_num Maximum number of posts to delete (default -1 for all).
	 * @param string $post_date_column Date column to use for comparison (default 'post_date_gmt').
	 */
	public function __construct(
		string $post_type,
		string $post_status,
		int $year,
		int $month,
		int $day,
		$posts_num = -1,
		string $post_date_column = 'post_date_gmt'
	) {
		$this->post_type = $post_type;
		$this->post_status = $post_status;
		$this->year = $year;
		$this->month = $month;
		$this->day = $day;
		$this->posts_num = $posts_num;
		$this->post_date_column = $post_date_column;
	}

	/**
	 * Executes the delete before command.
	 *
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	public function run(array $assoc_args = []): void {

		// Validations.
		$this->validate_post_type();
		$this->validate_post_status();
		$this->normalize_attachment_status();
		$this->validate_date();
		$this->validate_column();

		// Logging overview.
		$pt_obj = get_post_type_object( $this->post_type );
		$plural = $pt_obj ? $pt_obj->labels->name : $this->post_type;
		WP_CLI::log(
			sprintf(
				__( '%1$s with status "%2$s", created before: %3$s.%4$s.%5$s. ready to be deleted.', $this->textdomain ),
				$plural,
				$this->post_status,
				$this->day,
				$this->month,
				$this->year
			)
		);

		$query = $this->get_query();
		$found = $query->found_posts;

		if ( 0 === $found ) {
			WP_CLI::warning( __( 'No items matching given parameters. Check your parameters and try again.', $this->textdomain ) );
			return;
		}

		if ( 'attachment' === $this->post_type ) {
			WP_CLI::confirm( __( '➡️  Deleting attachments will also permanently delete media from "wp-content/uploads" directory. Also, some newer posts might still use these attachments. Are you sure you want to proceed?', $this->textdomain ) );
		}

		WP_CLI::confirm(
			sprintf(
				__( 'Found %s items. Are you really sure you want to delete them all? Please reconsider, this action cannot be reverted. This is the final warning.', $this->textdomain ),
				$found
			),
			$assoc_args
		);

		$this->process_deletion( $query );
	}

	/**
	 * Validates the post type.
	 *
	 * @return void
	 */
	private function validate_post_type(): void {
		if ( ! post_type_exists( $this->post_type ) ) {
			WP_CLI::error( sprintf( __( 'There is no "%s" post type, please check the "post_type" parameter.', $this->textdomain ), $this->post_type ) );
		}
	}

	/**
	 * Validates the post status.
	 *
	 * @return void
	 */
	private function validate_post_status(): void {
		$statuses = get_available_post_statuses();
		if ( ! in_array( $this->post_status, $statuses, true ) ) {
			WP_CLI::error( sprintf( __( 'There is no "%s" post status, please check the "post status" parameter.', $this->textdomain ), $this->post_status ) );
		}
	}

	/**
	 * Normalizes the post status for attachments to 'inherit'.
	 *
	 * @return void
	 */
	private function normalize_attachment_status(): void {
		if ( 'attachment' === $this->post_type && 'inherit' !== $this->post_status ) {
			WP_CLI::log( sprintf( __( 'Attachments can have only "inherit" post status. Argument "%s" changed to "inherit"', $this->textdomain ), $this->post_status ) );
			$this->post_status = 'inherit';
		}
	}

	/**
	 * Validates the provided date.
	 *
	 * @return void
	 */
	private function validate_date(): void {
		if ( ! checkdate( (int) $this->month, (int) $this->day, (int) $this->year ) ) {
			WP_CLI::error( sprintf( __( 'You entered a non valid date, which do not exist in Gregorian calendar. Year: %1$s, Month: %2$s, Day: %3$s. Please check the date you entered.', $this->textdomain ), $this->year, $this->month, $this->day ) );
		}
	}

	/**
	 * Validates the date column.
	 *
	 * @return void
	 */
	private function validate_column(): void {
		$allowed = [ 'post_date_gmt', 'post_modified_gmt', 'post_date', 'post_modified' ];
		if ( ! in_array( $this->post_date_column, $allowed, true ) ) {
			WP_CLI::error( sprintf( __( 'The "%s" date argument is invalid. Accepted values are "post_date_gmt", "post_modified_gmt", "post_date", or "post_modified".', $this->textdomain ), $this->post_date_column ) );
		}
	}

	/**
	 * Builds and returns the WP_Query for fetching posts to delete based on the command parameters.
	 *
	 * @return WP_Query The query object.
	 */
	private function get_query(): WP_Query {
		$args = [
			'fields'         => 'ids',
			'post_type'      => $this->post_type,
			'posts_per_page' => $this->posts_num,
			'post_status'    => $this->post_status,
			'date_query'     => [
				'column' => $this->post_date_column,
				'before' => [
					'year'  => $this->year,
					'month' => $this->month,
					'day'   => $this->day,
				],
			],
			'no_found_rows'  => false, // need found_posts.
		];

		return new WP_Query( $args );
	}

	/**
	 * Processes the deletion of posts returned by the query.
	 *
	 * @param WP_Query $query The query object containing posts to delete.
	 * @return void
	 */
	private function process_deletion( WP_Query $query ): void {
		$successful = 0;
		$failed = 0;
		$ids = $query->posts; // because 'fields' => 'ids' we get IDs array.

		// Iterate IDs directly — avoids setting global postdata repeatedly.
		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			$published = get_the_date( 'd.m.Y', $id );
			$modified = get_the_modified_date( '', $id );

			WP_CLI::log(
				/* translators: 1: post type singular label, 2: post title, 3: post ID, 4: published date, 5: modified date */
				sprintf(
					__( 'Deleting %1$s "%2$s" (ID: %3$s) published: %4$s, last modified: %5$s...', $this->textdomain ),
					strtolower( $this->get_singular_label() ),
					$title,
					$id,
					$published,
					$modified
				)
			);

			$result = ( 'attachment' === $this->post_type ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );

			if ( $result ) {
				$successful++;
				WP_CLI::log( sprintf( __( '✅  Item deleted: "%1$s", %2$s of %3$s.', $this->textdomain ), $title, $successful, count( $ids ) ) );
			} else {
				$failed++;
				WP_CLI::log( sprintf( __( '❌  Error! Item "%s" could not be deleted!', $this->textdomain ), $title ) );
			}

			WP_CLI::log( '================================' );
		}

		WP_CLI::log(
			sprintf(
				__( '%1$s items deleted.%2$s', $this->textdomain ),
				$successful,
				$failed ? __( ' Unsuccesfull deletion of ', $this->textdomain ) . $failed . __( ' items.', $this->textdomain ) : ''
			)
		);

		wp_reset_postdata();
	}

	/**
	 * Retrieves the singular label for the post type.
	 *
	 * @return string The singular label of the post type.
	 */
	private function get_singular_label(): string {
		$obj = get_post_type_object( $this->post_type );
		return $obj ? $obj->labels->singular_name : $this->post_type;
	}
}

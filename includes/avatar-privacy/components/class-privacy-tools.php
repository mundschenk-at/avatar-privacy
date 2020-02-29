<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Core;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Options;

/**
 * Integrates with the new privacy tools added in WordPress 4.9.6.
 *
 * @since 1.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Privacy_Tools implements \Avatar_Privacy\Component {

	const PAGING = 500;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The cache handler.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Creates a new instance.
	 *
	 * @param Core  $core  The core API.
	 * @param Cache $cache Required.
	 */
	public function __construct( Core $core, Cache $cache ) {
		$this->core  = $core;
		$this->cache = $cache;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'admin_init', [ $this, 'admin_init' ] );
	}

	/**
	 * Initializes additional plugin hooks.
	 */
	public function admin_init() {
		// Add privacy notice suggestion.
		$this->add_privacy_notice_content();

		// Register data exporter.
		\add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_personal_data_exporter' ], 0 ); // Priority 0 to follow after the built-in exporters. Watch https://core.trac.wordpress.org/ticket/44151.

		// Register data eraser.
		\add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_personal_data_eraser' ] );
	}

	/**
	 * Adds a privacy notice snippet.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 */
	protected function add_privacy_notice_content() {
		// Don't crash on older versions of WordPress.
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$suggested_text = '<strong class="privacy-policy-tutorial">' . \__( 'Suggested text:' ) . ' </strong>'; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Missing text domain is intentional to use Core translation.

		$content  = '<div class="wp-suggested-text">';
		$content .= '<h3>' . \__( 'Comments', 'avatar-privacy' ) . '</h3>';
		$content .= '<p class="privacy-policy-tutorial">' . \__( 'The information in this subsection supersedes the paragraph on Gravatar in the default "Comments" subsection provided by WordPress.', 'avatar-privacy' ) . '</p>';
		$content .= "<p>{$suggested_text}" . \__( 'At your option, an anonymized string created from your email address (also called a hash) may be provided to the Gravatar service to see if you are using it. The Gravatar service privacy policy is available here: https://automattic.com/privacy/. After approval of your comment, your profile picture is visible to the public in the context of your comment. Neither the hash nor your actual email address will be exposed to the public.', 'avatar-privacy' ) . '</p>';
		$content .= '<h3>' . \__( 'Cookies', 'avatar-privacy' ) . '</h3>';
		$content .= '<p class="privacy-policy-tutorial">' . \__( 'The information in this subsection should be included in addition to the information about any other cookies set by either WordPress or another plugin.', 'avatar-privacy' ) . '</p>';
		$content .= "<p>{$suggested_text}" . \__( 'If you leave a comment on our site and opt-in to display your Gravatar image, your choice will be stored in a cookie. This is for your convenience so that you do not have to fill the checkbox again when you leave another comment. This cookie will last for one year.', 'avatar-privacy' ) . '</p>';
		$content .= '</div>';

		\wp_add_privacy_policy_content( \__( 'Avatar Privacy', 'avatar-privacy' ), $content );
	}

	/**
	 * Registers an exporter function for the personal data collected by this plugin.
	 *
	 * @param  array $exporters The registered exporter callbacks.
	 *
	 * @return array
	 */
	public function register_personal_data_exporter( array $exporters ) {
		$exporters['avatar-privacy-user']           = [
			'exporter_friendly_name' => \__( 'Avatar Privacy Plugin User Data', 'avatar-privacy' ),
			'callback'               => [ $this, 'export_user_data' ],
		];
		$exporters['avatar-privacy-comment-author'] = [
			'exporter_friendly_name' => \__( 'Avatar Privacy Plugin Comment Author Data', 'avatar-privacy' ),
			'callback'               => [ $this, 'export_comment_author_data' ],
		];

		return $exporters;
	}

	/**
	 * Registers an eraser function for the personal data collected by this plugin.
	 *
	 * @param  array $erasers The registered eraser callbacks.
	 *
	 * @return array
	 */
	public function register_personal_data_eraser( array $erasers ) {
		$erasers['avatar-privacy'] = [
			'eraser_friendly_name' => \__( 'Avatar Privacy Plugin', 'avatar-privacy' ),
			'callback'             => [ $this, 'erase_data' ],
		];

		return $erasers;
	}

	/**
	 * Exports the data associated with a user account.
	 *
	 * @param  string $email The email address.
	 * @param  int    $page  Optional. Default 1.
	 *
	 * @return array {
	 *     @type mixed $data The exported data.
	 *     @type bool  $done True if there is no more data to export, false otherwise.
	 * }
	 */
	public function export_user_data( $email, /* @scrutinizer ignore-unused */ $page = 1 ) {
		$user = \get_user_by( 'email', $email );
		if ( empty( $user ) ) {
			return [
				'data' => [],
				'done' => true,
			];
		}

		// Initialize export data.
		$user_data = [];

		// Export the hashed email.
		$user_data[] = [
			'name'  => \__( 'User Email Hash', 'avatar-privacy' ),
			'value' => $this->core->get_user_hash( $user->ID ),
		];

		// Export the `use_gravatar` setting.
		$user_data[] = [
			'name'  => \__( 'Use Gravatar.com', 'avatar-privacy' ),
			'value' => \get_user_meta( $user->ID, Core::GRAVATAR_USE_META_KEY, true ) === 'true',
		];

		// Export the `allow_anonymous` setting.
		$user_data[] = [
			'name'  => \__( 'Logged-out Commenting', 'avatar-privacy' ),
			'value' => \get_user_meta( $user->ID, Core::ALLOW_ANONYMOUS_META_KEY, true ) === 'true',
		];

		// Export the uploaded avatar.
		// We don't want to use the filtered value here.
		$local_avatar = \get_user_meta( $user->ID, Core::USER_AVATAR_META_KEY, true );
		if ( ! empty( $local_avatar['file'] ) ) {
			$user_data[] = [
				'name'  => \__( 'User Profile Picture', 'avatar-privacy' ),
				'value' => \str_replace( \ABSPATH, \trailingslashit( \site_url() ), $local_avatar['file'] ),
			];
		}

		return [
			'data' => [
				[
					'group_id'    => 'user',             // Existing Core group.
					'group_label' => \__( 'User' ),       // // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Missing text domain is intentional to use Core translation.
					'item_id'     => "user-{$user->ID}", // Existing Core item ID.
					'data'        => $user_data,         // The personal data that should be exported.
				],
			],
			'done' => true,
		];
	}

	/**
	 * Exports the data associated with a comment author email address.
	 *
	 * @param  string $email The email address.
	 * @param  int    $page  Optional. Default 1.
	 *
	 * @return array {
	 *     @type mixed $data The exported data.
	 *     @type bool  $done True if there is no more data to export, false otherwise.
	 * }
	 */
	public function export_comment_author_data( $email, /* @scrutinizer ignore-unused */ $page = 1 ) {
		// Load raw data.
		$raw_data = $this->core->load_data( $email );
		if ( empty( $raw_data ) ) {
			return [
				'data' => [],
				'done' => true,
			];
		}

		// Export the avatar privacy ID.
		$data   = [];
		$id     = $raw_data->id;
		$data[] = [
			'name'  => \__( 'Avatar Privacy Comment Author ID', 'avatar-privacy' ),
			'value' => $id,
		];

		// Export the email.
		$data[] = [
			'name'  => \__( 'Comment Author Email', 'avatar-privacy' ),
			'value' => $raw_data->email,
		];

		// Export the hashed email.
		$data[] = [
			'name'  => \__( 'Comment Author Email Hash', 'avatar-privacy' ),
			'value' => $raw_data->hash,
		];

		// Export the `use_gravatar` setting.
		$data[] = [
			'name'  => \__( 'Use Gravatar.com', 'avatar-privacy' ),
			'value' => $raw_data->use_gravatar,
		];

		// Export the last modified date.
		$data[] = [
			'name'  => \__( 'Last Updated', 'avatar-privacy' ),
			'value' => $raw_data->last_updated,
		];

		// Export the log message.
		$data[] = [
			'name'  => \__( 'Log Message', 'avatar-privacy' ),
			'value' => $raw_data->log_message,
		];

		return [
			'data' => [
				[
					'group_id'    => 'avatar-privacy',                         // An ID to identify this particular group of information.
					'group_label' => \__( 'Avatar Privacy', 'avatar-privacy' ), // A translatable string to label this group of information.
					'item_id'     => "avatar-privacy-{$id}",                   // The item ID of what we're exporting.
					'data'        => $data,                                    // The personal data that should be exported.
				],
			],
			'done' => true,
		];
	}

	/**
	 * Erases the data collected by this plugin.
	 *
	 * @param  string $email The email address.
	 * @param  int    $page  Optional. Default 1.
	 *
	 * @return array {
	 *     @type int   $items_removed  The number of removed items.
	 *     @type int   $items_retained The number of items that were retained and anonymized.
	 *     @type array $messages       Any additional information for the admin associated with the removal request.
	 *     @type bool  $done           True if there is no more data to erase, false otherwise.
	 * }
	 */
	public function erase_data( $email, /* @scrutinizer ignore-unused */ $page = 1 ) {
		$items_removed  = 0;
		$items_retained = 0;
		$messages       = [];

		// Remove user data.
		$user = \get_user_by( 'email', $email );
		if ( ! empty( $user ) ) {
			$items_removed += (int) \delete_user_meta( $user->ID, Core::EMAIL_HASH_META_KEY );
			$items_removed += (int) \delete_user_meta( $user->ID, Core::GRAVATAR_USE_META_KEY );
			$items_removed += (int) \delete_user_meta( $user->ID, Core::ALLOW_ANONYMOUS_META_KEY );
			$items_removed += (int) \delete_user_meta( $user->ID, Core::USER_AVATAR_META_KEY );
		}

		// Remove comment author data.
		$id = $this->core->get_comment_author_key( $email );
		if ( ! empty( $id ) ) {
			$items_removed += $this->delete_comment_author_data( $id, $email );
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => true,
		];
	}

	/**
	 * Deletes an entry from the avatar_privacy table.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  int    $id    The row ID.
	 * @param  string $email The original email.
	 *
	 * @return int           The number of deleted rows (1 or 0).
	 */
	protected function delete_comment_author_data( $id, $email ) {
		global $wpdb;

		// Delete data from database.
		$rows = (int) $wpdb->delete( $wpdb->avatar_privacy, [ 'id' => $id ], [ '%d' ] ); // WPCS: db call ok, cache ok.

		// Delete cached data.
		$this->cache->delete( Core::EMAIL_CACHE_PREFIX . $this->core->get_hash( $email ) );

		return $rows;
	}
}

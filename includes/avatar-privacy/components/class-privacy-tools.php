<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

use Avatar_Privacy\Component;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;

/**
 * Integrates with the new privacy tools added in WordPress 4.9.6.
 *
 * @since 1.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Privacy_Tools implements Component {

	const PAGING = 500;

	/**
	 * The user fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private $registered_user;

	/**
	 * The comment author API.
	 *
	 * @since 2.4.0
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Fields           $registered_user The user fields API.
	 * @param Comment_Author_Fields $comment_author  The comment author fields API.
	 */
	public function __construct( User_Fields $registered_user, Comment_Author_Fields $comment_author ) {
		$this->registered_user = $registered_user;
		$this->comment_author  = $comment_author;
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
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	protected function add_privacy_notice_content() {
		// Don't crash on older versions of WordPress.
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$suggested_text = '<strong class="privacy-policy-tutorial">' . \__( 'Suggested text:' ) . ' </strong>'; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Missing text domain is intentional to use Core translation.

		$content  = '<h3>' . \__( 'Comments', 'avatar-privacy' ) . '</h3>';
		$content .= '<p class="privacy-policy-tutorial">' . \__( 'The information in this subsection supersedes the paragraph on Gravatar in the default "Comments" subsection provided by WordPress.', 'avatar-privacy' ) . '</p>';
		$content .= "<p>{$suggested_text}" . \__( 'At your option, an anonymized string created from your email address (also called a hash) may be provided to the Gravatar service to see if you are using it. The Gravatar service privacy policy is available here: https://automattic.com/privacy/. After approval of your comment, your profile picture is visible to the public in the context of your comment. Neither the hash nor your actual email address will be exposed to the public.', 'avatar-privacy' ) . '</p>';
		$content .= '<h3>' . \__( 'Cookies', 'avatar-privacy' ) . '</h3>';
		$content .= '<p class="privacy-policy-tutorial">' . \__( 'The information in this subsection should be included in addition to the information about any other cookies set by either WordPress or another plugin.', 'avatar-privacy' ) . '</p>';
		$content .= "<p>{$suggested_text}" . \__( 'If you leave a comment on our site and opt-in to display your Gravatar image, your choice will be stored in a cookie. This is for your convenience so that you do not have to fill the checkbox again when you leave another comment. This cookie will last for one year.', 'avatar-privacy' ) . '</p>';

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
	public function export_user_data( $email, $page = 1 ) {
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
			'value' => $this->registered_user->get_hash( $user->ID ),
		];

		// Export the `use_gravatar` setting.
		if ( $this->registered_user->has_gravatar_policy( $user->ID ) ) {
			$user_data[] = [
				'name'  => \__( 'Use Gravatar.com', 'avatar-privacy' ),
				'value' => $this->registered_user->allows_gravatar_use( $user->ID ),
			];
		}

		// Export the `allow_anonymous` setting.
		if ( $this->registered_user->has_anonymous_commenting_policy( $user->ID ) ) {
			$user_data[] = [
				'name'  => \__( 'Logged-out Commenting', 'avatar-privacy' ),
				'value' => $this->registered_user->allows_anonymous_commenting( $user->ID ),
			];
		}

		// Export the uploaded avatar.
		// We don't want to use the filtered value here.
		$local_avatar = $this->registered_user->get_local_avatar( $user->ID );
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
					'group_label' => \__( 'User' ),      // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Missing text domain is intentional to use Core translation.
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
	public function export_comment_author_data( $email, $page = 1 ) {
		// Load raw data.
		$raw_data = $this->comment_author->load( $email );
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
	public function erase_data( $email, $page = 1 ) {
		$items_removed  = 0;
		$items_retained = 0; // We currently don't track this information.
		$messages       = [];

		// Remove user data.
		$user = \get_user_by( 'email', $email );
		if ( ! empty( $user ) ) {
			$items_removed += $this->registered_user->delete( $user->ID );
		}

		// Remove comment author data.
		$items_removed += $this->comment_author->delete( $email );

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => true,
		];
	}
}

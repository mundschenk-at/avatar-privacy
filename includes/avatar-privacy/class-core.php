<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
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

namespace Avatar_Privacy;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\Default_Avatars;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Tools\Hasher;

use Avatar_Privacy\Exceptions\File_Deletion_Exception;  // phpcs:ignore ImportDetection.Imports.RequireImports -- needed for PHPDoc annotation.
use Avatar_Privacy\Exceptions\Upload_Handling_Exception;  // phpcs:ignore ImportDetection.Imports.RequireImports -- needed for PHPDoc annotation.

/**
 * The core database API of the Avatar Privacy plugin.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Johannes Freudendahl <wordpress@freudendahl.net>
 */
class Core {

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The hashing helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * The user data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * The comment author data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author_fields;

	/**
	 * The default avatars API.
	 *
	 * @since 2.4.0
	 *
	 * @var Default_Avatars
	 */
	private $default_avatars;

	/**
	 * The singleton instance.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * Creates a \Avatar_Privacy\Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameters $hasher, $user_fields, $comment_author_fields, and
	 *              $default_avatars added, $transients, $version, $options,
	 *              $site_transients, and $cache removed.
	 *
	 * @param Settings              $settings              Required.
	 * @param Hasher                $hasher                Required.
	 * @param User_Fields           $user_fields           Required.
	 * @param Comment_Author_Fields $comment_author_fields Required.
	 * @param Default_Avatars       $default_avatars       Required.
	 */
	public function __construct(
		Settings $settings,
		Hasher $hasher,
		User_Fields $user_fields,
		Comment_Author_Fields $comment_author_fields,
		Default_Avatars $default_avatars
	) {
		$this->settings              = $settings;
		$this->hasher                = $hasher;
		$this->user_fields           = $user_fields;
		$this->comment_author_fields = $comment_author_fields;
		$this->default_avatars       = $default_avatars;
	}

	/**
	 * Retrieves (and if necessary creates) the API instance. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param  Core $instance Only used for plugin initialization. Don't ever pass a value in user code.
	 *
	 * @return void
	 *
	 * @throws \BadMethodCallException Thrown when Avatar_Privacy_Core::set_instance after plugin initialization.
	 */
	public static function set_instance( Core $instance ) {
		if ( null === self::$instance ) {
			self::$instance = $instance;
		} else {
			throw new \BadMethodCallException( __METHOD__ . ' called more than once.' );
		}
	}

	/**
	 * Retrieves the plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @throws \BadMethodCallException Thrown when Avatar_Privacy_Core::get_instance is called before plugin initialization.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called without prior plugin intialization.' );
		}

		return self::$instance;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->settings->get_version();
	}

	/**
	 * Retrieves the full path to the main plugin file.
	 *
	 * @deprecated 2.3.0 Use AVATAR_PRIVACY_PLUGIN_FILE instead.
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		\_deprecated_function( __METHOD__, '2.3.0' );

		return \AVATAR_PRIVACY_PLUGIN_FILE;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @since 2.0.0 Parameter $force added.
	 *
	 * @param bool $force Optional. Forces retrieval of settings from database. Default false.
	 *
	 * @return array
	 */
	public function get_settings( $force = false ) {
		return $this->settings->get_all_settings( $force );
	}

	/**
	 * Checks whether an anonymous comment author has opted-in to Gravatar usage.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function comment_author_allows_gravatar_use( $email_or_hash ) {
		return $this->comment_author_fields->allows_gravatar_use( $email_or_hash );
	}

	/**
	 * Checks whether an anonymous comment author is in our Gravatar policy database.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function comment_author_has_gravatar_policy( $email_or_hash ) {
		return $this->comment_author_fields->has_gravatar_policy( $email_or_hash );
	}

	/**
	 * Retrieves the database primary key for the given email address.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return int                   The database key for the given email address (or 0).
	 */
	public function get_comment_author_key( $email_or_hash ) {
		return $this->comment_author_fields->get_key( $email_or_hash );
	}

	/**
	 * Retrieves the hash for the given user ID. If there currently is no hash,
	 * a new one is generated.
	 *
	 * @since 2.1.0 False is returned on error.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string|false The hashed email, or `false` on failure.
	 */
	public function get_user_hash( $user_id ) {
		return $this->user_fields->get_hash( $user_id );
	}

	/**
	 * Retrieves the email for the given comment author database key.
	 *
	 * @param  string $hash The hashed mail address.
	 *
	 * @return string
	 */
	public function get_comment_author_email( $hash ) {
		return $this->comment_author_fields->get_email( $hash );
	}

	/**
	 * Ensures that the comment author gravatar policy is updated.
	 *
	 * @param  string $email        The comment author's mail address.
	 * @param  int    $comment_id   The comment ID.
	 * @param  int    $use_gravatar 1 if Gravatar.com is enabled, 0 otherwise.
	 *
	 * @return void
	 */
	public function update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar ) {
		$this->comment_author_fields->update_gravatar_use( $email, $comment_id, $use_gravatar );
	}

	/**
	 * Updates the hash using the ID and email.
	 *
	 * @param  int    $id    The database key. Deprecated.
	 * @param  string $email The email.
	 *
	 * @return void
	 */
	public function update_comment_author_hash( /* @scrutinizer ignore-unused */ $id, $email ) {
		$this->comment_author_fields->update_hash( $email );
	}

	/**
	 * Retrieves the salt for current the site/network.
	 *
	 * @deprecated 2.4.0
	 *
	 * @return string
	 */
	public function get_salt() {
		\_deprecated_function( __METHOD__, '2.4.0' );

		return $this->hasher->get_salt();
	}

	/**
	 * Generates a salted SHA-256 hash for the given e-mail address.
	 *
	 * @since 2.4.0 Implementation extracted to \Avatar_Privacy\Tools\Hasher
	 *
	 * @param  string $email The mail address.
	 *
	 * @return string
	 */
	public function get_hash( $email ) {
		return $this->hasher->get_hash( $email );
	}

	/**
	 * Retrieves a user by email hash.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hash The user's email hash.
	 *
	 * @return \WP_User|null
	 */
	public function get_user_by_hash( $hash ) {
		return $this->user_fields->get_user_by_hash( $hash );
	}

	/**
	 * Retrieves the full-size local avatar for a user (if one exists).
	 *
	 * @since 2.2.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return array {
	 *     An avatar definition, or the empty array.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 */
	public function get_user_avatar( $user_id ) {
		return $this->user_fields->get_local_avatar( $user_id );
	}

	/**
	 * Sets the local avatar for the given user.
	 *
	 * @since 2.4.0
	 *
	 * @param  int    $user_id The user ID.
	 * @param  string $image   Raw image data.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException An exception is thrown if the user ID does
	 *                                   not exist or the upload result does not
	 *                                   contain the 'file' key.
	 * @throws \RuntimeException         A `RuntimeException` is thrown if the sideloading
	 *                                   fails for some reason.
	 */
	public function set_user_avatar( $user_id, $image ) {
		$this->user_fields->set_local_avatar( $user_id, $image );
	}

	/**
	 * Checks whether a user has opted-in to Gravatar usage.
	 *
	 * @since 2.4.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function user_allows_gravatar_use( $user_id ) {
		return $this->user_fields->allows_gravatar_use( $user_id );
	}

	/**
	 * Checks whether a user has set a Gravatar usage policy.
	 *
	 * @since 2.4.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function user_has_gravatar_policy( $user_id ) {
		return $this->user_fields->has_gravatar_policy( $user_id );
	}

	/**
	 * Updates a user's gravatar policy.
	 *
	 * @since 2.4.0
	 *
	 * @param  int  $user_id      The user ID.
	 * @param  bool $use_gravatar Whether using Gravatar should be allowed or not.
	 *
	 * @return void
	 */
	public function update_user_gravatar_use( $user_id, $use_gravatar ) {
		$this->user_fields->update_gravatar_use( $user_id, $use_gravatar );
	}

	/**
	 * Checks whether a user has opted-in to anonymous commenting.
	 *
	 * @since 2.4.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function user_allows_anonymous_commenting( $user_id ) {
		return $this->user_fields->allows_anonymous_commenting( $user_id );
	}

	/**
	 * Updates a user's gravatar policy.
	 *
	 * @since 2.4.0
	 *
	 * @param  int  $user_id   The user ID.
	 * @param  bool $anonymous Whether anonymous commenting should be allowed or not.
	 *
	 * @return void
	 */
	public function update_user_anonymous_commenting( $user_id, $anonymous ) {
		$this->user_fields->update_anonymous_commenting( $user_id, $anonymous );
	}

	/**
	 * Retrieves the full-size custom default avatar for the current site.
	 *
	 * Note: On multisite, the caller is responsible for switching to the site
	 * (using `switch_to_blog`) before calling this method, and for restoring
	 * the original site afterwards (using `restore_current_blog`).
	 *
	 * @since 2.4.0
	 *
	 * @return array {
	 *     An avatar definition, or the empty array.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 */
	public function get_custom_default_avatar() {
		return $this->default_avatars->get_custom_default_avatar();
	}

	/**
	 * Sets the custom default avatar for the current site.
	 *
	 * Please note that the calling function is responsible for cleaning up the
	 * provided image if it is a temporary file (i.e the image is copied before
	 * being used as the new avatar).
	 *
	 * On multisite, the caller is responsible for switching to the site
	 * (using `switch_to_blog`) before calling this method, and for restoring
	 * the original site afterwards (using `restore_current_blog`).
	 *
	 * @since 2.4.0
	 *
	 * @param  string $image_url The image URL or filename.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException An exception is thrown if the image URL
	 *                                   is invalid.
	 * @throws Upload_Handling_Exception An exception is thrown if there was an
	 *                                   while processing the image sideloading.
	 * @throws File_Deletion_Exception   An exception is thrown if the previously
	 *                                   set image could not be deleted.
	 */
	public function set_custom_default_avatar( $image_url ) {
		$this->default_avatars->set_custom_default_avatar( $image_url );
	}
}

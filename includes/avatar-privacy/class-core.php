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

use Avatar_Privacy\Settings;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\Hasher;
use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Data_Storage\Options;

/**
 * The core database API of the Avatar Privacy plugin.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Johannes Freudendahl <wordpress@freudendahl.net>
 */
class Core {

	/**
	 * The name of the combined settings in the database.
	 */
	const SETTINGS_NAME = 'settings';

	/**
	 * The user's settings.
	 *
	 * @var array
	 */
	private $settings = [];

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings_template;

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
	 * @since 2.4.0 Parameters $hasher, $user_fields and $comment_author_fields added, $transients,
	 *              $site_transients and $cache removed.
	 *
	 * @param string                $version               The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Options               $options               Required.
	 * @param Settings              $settings_template     Required.
	 * @param Hasher                $hasher                Required.
	 * @param User_Fields           $user_fields           Required.
	 * @param Comment_Author_Fields $comment_author_fields Required.
	 */
	public function __construct( $version, Options $options, Settings $settings_template, Hasher $hasher, User_Fields $user_fields, Comment_Author_Fields $comment_author_fields ) {
		$this->version               = $version;
		$this->options               = $options;
		$this->settings_template     = $settings_template;
		$this->hasher                = $hasher;
		$this->user_fields           = $user_fields;
		$this->comment_author_fields = $comment_author_fields;
	}

	/**
	 * Retrieves (and if necessary creates) the API instance. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param Core $instance Only used for plugin initialization. Don't ever pass a value in user code.
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
	 * @var string
	 */
	public function get_version() {
		return $this->version;
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
		// Force a re-read if the cached settings do not appear to be from the current version.
		if ( empty( $this->settings ) || empty( $this->settings[ Options::INSTALLED_VERSION ] )
			|| $this->version !== $this->settings[ Options::INSTALLED_VERSION ] || $force ) {
			$this->settings = (array) $this->options->get( self::SETTINGS_NAME, $this->settings_template->get_defaults() );
		}

		return $this->settings;
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
	 */
	public function update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar ) {
		$this->comment_author_fields->update_gravatar_use( $email, $comment_id, $use_gravatar );
	}

	/**
	 * Updates the hash using the ID and email.
	 *
	 * @param  int    $id    The database key.
	 * @param  string $email The email.
	 */
	public function update_comment_author_hash( $id, $email ) {
		$this->comment_author_fields->update_hash( $id, $email );
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
	 * @since 2.4.0 Implementation extracted to \Avatar_Privacy\Core\Hasher
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
}

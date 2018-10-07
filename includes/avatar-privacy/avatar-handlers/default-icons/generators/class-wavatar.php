<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2007-2008 Shamus Young.
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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Tools\Images;

/**
 * A wavatar generator.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 */
class Wavatar extends PNG_Generator {

	const SIZE = 80;

	const WAVATAR_BACKGROUNDS = 4;
	const WAVATAR_FACES       = 11;
	const WAVATAR_BROWS       = 8;
	const WAVATAR_EYES        = 13;
	const WAVATAR_PUPILS      = 11;
	const WAVATAR_MOUTHS      = 19;

	/**
	 * Creates a new Wavatars generator.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		parent::__construct( \dirname( $plugin_file ) . '/public/images/wavatars' );
	}

	/**
	 * Extract a "random" value from the seed string.
	 *
	 * @param  string $seed   The seed.
	 * @param  int    $index  The index.
	 * @param  int    $length The number of bytes.
	 * @param  int    $modulo The maximum value of the result.
	 *
	 * @return int
	 */
	private function seed( $seed, $index, $length, $modulo ) {
		return \hexdec( \substr( $seed, $index, $length ) ) % $modulo;
	}

	/**
	 * Build the avatar icon.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size The size in pixels.
	 *
	 * @return string       The image data.
	 */
	public function build( $seed, $size ) {
		// Look at the seed (an md5 hash) and use pairs of digits to determine our
		// "random" parts and colors.
		$face      = 1 + $this->seed( $seed, 1, 2, self::WAVATAR_FACES );
		$bg_color  = ( $this->seed( $seed, 3, 2, 240 ) / 255 * self::DEGREE );
		$fade      = 1 + $this->seed( $seed, 5, 2, self::WAVATAR_BACKGROUNDS );
		$wav_color = $this->seed( $seed, 7, 2, 240 ) / 255 * self::DEGREE;
		$brow      = 1 + $this->seed( $seed, 9, 2, self::WAVATAR_BROWS );
		$eyes      = 1 + $this->seed( $seed, 11, 2, self::WAVATAR_EYES );
		$pupil     = 1 + $this->seed( $seed, 13, 2, self::WAVATAR_PUPILS );
		$mouth     = 1 + $this->seed( $seed, 15, 2, self::WAVATAR_MOUTHS );

		// Create backgound.
		$avatar = \imagecreatetruecolor( self::SIZE, self::SIZE );

		// Pick a random color for the background.
		$this->fill( $avatar, $bg_color, 94, 20, 1, 1 );

		// Now add the various layers onto the image.
		$this->apply_image( $avatar, "fade{$fade}.png", self::SIZE, self::SIZE );
		$this->apply_image( $avatar, "mask{$face}.png", self::SIZE, self::SIZE );
		$this->fill( $avatar, $wav_color, 94, 66, (int) ( self::SIZE / 2 ), (int) ( self::SIZE / 2 ) );
		$this->apply_image( $avatar, "shine{$face}.png", self::SIZE, self::SIZE );
		$this->apply_image( $avatar, "brow${brow}.png", self::SIZE, self::SIZE );
		$this->apply_image( $avatar, "eyes{$eyes}.png", self::SIZE, self::SIZE );
		$this->apply_image( $avatar, "pupils{$pupil}.png", self::SIZE, self::SIZE );
		$this->apply_image( $avatar, "mouth{$mouth}.png", self::SIZE, self::SIZE );

		// Resize if needed.
		return Images\Editor::get_resized_image_data( Images\Editor::create_from_image_resource( $avatar ), $size, $size, false, 'image/png' );
	}
}

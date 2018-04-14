<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Default_Icons\Generator;

use Avatar_Privacy\Default_Icons\Generator\Identicon\Center;
use Avatar_Privacy\Default_Icons\Generator\Identicon\Sprite;


/**
 * Generates an SVG icon based on a hash.
 *
 * This implementation is inspired by:
 *    - identicon.sh by Harald Lapp (https://github.com/aurora/identicon), and
 *    - Jdenticon by Daniel Mester Pirttijärvi (https://github.com/aurora/identicon).
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Identicon implements Generator {
	const SPRITE_SIZE = 128;

	const CENTER_EMPTY           = 0;
	const CENTER_FILL            = 1;
	const CENTER_DIAMOND         = 2;
	const CENTER_REVERSE_DIAMOND = 3;
	const CENTER_CROSS           = 4;
	const CENTER_MORNING_STAR    = 5;
	const CENTER_SMALL_SQUARE    = 6;
	const CENTER_CHECKERBOARD    = 7;
	const MAX_CENTER             = self::CENTER_CHECKERBOARD + 1;

	const CENTER_SHAPES = [
		self::CENTER_EMPTY           => Center\Empty_Center::class,
		self::CENTER_FILL            => Center\Fill::class,
		self::CENTER_DIAMOND         => Center\Diamond::class,
		self::CENTER_REVERSE_DIAMOND => Center\Reverse_Diamond::class,
		self::CENTER_CROSS           => Center\Cross::class,
		self::CENTER_MORNING_STAR    => Center\Morning_Star::class,
		self::CENTER_SMALL_SQUARE    => Center\Small_Square::class,
		self::CENTER_CHECKERBOARD    => Center\Checkerboard::class,
	];

	const SPRITE_TRIANGLE       = 0;
	const SPRITE_PARALLELOGRAM  = 1;
	const SPRITE_MOUSE_EARS     = 2;
	const SPRITE_RIBBON         = 3;
	const SPRITE_SAILS          = 4;
	const SPRITE_FINS           = 5;
	const SPRITE_BEAK           = 6;
	const SPRITE_CHEVRON        = 7;
	const SPRITE_FISH           = 8;
	const SPRITE_KITE           = 9;
	const SPRITE_TROUGH         = 10;
	const SPRITE_RAYS           = 11;
	const SPRITE_DOUBLE_RHOMBUS = 12;
	const SPRITE_CROWN          = 13;
	const SPRITE_RADIOACTIVE    = 14;
	const SPRITE_TILES          = 15;
	const MAX_SPRITE            = self::SPRITE_TILES + 1;

	const SPRITE_SHAPES = [
		self::SPRITE_TRIANGLE       => Sprite\Triangle::class,
		self::SPRITE_PARALLELOGRAM  => Sprite\Parallelogram::class,
		self::SPRITE_MOUSE_EARS     => Sprite\Mouse_Ears::class,
		self::SPRITE_RIBBON         => Sprite\Ribbon::class,
		self::SPRITE_SAILS          => Sprite\Sails::class,
		self::SPRITE_FINS           => Sprite\Fins::class,
		self::SPRITE_BEAK           => Sprite\Beak::class,
		self::SPRITE_CHEVRON        => Sprite\Chevron::class,
		self::SPRITE_FISH           => Sprite\Fish::class,
		self::SPRITE_KITE           => Sprite\Kite::class,
		self::SPRITE_TROUGH         => Sprite\Trough::class,
		self::SPRITE_RAYS           => Sprite\Rays::class,
		self::SPRITE_DOUBLE_RHOMBUS => Sprite\Double_Rhombus::class,
		self::SPRITE_CROWN          => Sprite\Crown::class,
		self::SPRITE_RADIOACTIVE    => Sprite\Radioactive::class,
		self::SPRITE_TILES          => Sprite\Tiles::class,
	];

	/**
	 * Builds an icon based on the given seed returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size Optional. The size in pixels. Default 128 (but really ignored).
	 *
	 * @return string
	 */
	public function build( $seed, $size = 128 ) {
		$icon = '';

		// Parse seed.
		$csh = $this->parse_hash( $seed, 0, 1 );     // Corner sprite shape.
		$ssh = $this->parse_hash( $seed, 1, 1 );     // Side sprite shape.
		$xsh = $this->parse_hash( $seed, 2, 1 ) & 7; // Center sprite shape.

		$cro = $this->parse_hash( $seed, 3, 1 ) & 3; // Corner sprite rotation.
		$sro = $this->parse_hash( $seed, 4, 1 ) & 3; // Side sprite rotation.
		$xbg = $this->parse_hash( $seed, 5, 1 ) % 2; // Center sprite background.

		$cfr = $this->parse_hash( $seed, 6, 2 );     // Corner sprite foreground color.
		$cfg = $this->parse_hash( $seed, 8, 2 );
		$cfb = $this->parse_hash( $seed, 10, 2 );

		$sfr = $this->parse_hash( $seed, 12, 2 );    // Side sprite foreground color.
		$sfg = $this->parse_hash( $seed, 14, 2 );
		$sfb = $this->parse_hash( $seed, 16, 2 );

		// Generate corner sprites.
		$color = "rgb($cfr,$cfg,$cfb)";
		$icon .= $this->get_sprite( $csh, $color, ( $cro * 270 ) % 360, 0, 0 );
		$icon .= $this->get_sprite( $csh, $color, ( $cro * 270 + 90 ) % 360, self::SPRITE_SIZE * 2, 0 );
		$icon .= $this->get_sprite( $csh, $color, ( $cro * 270 + 180 ) % 360, self::SPRITE_SIZE * 2, self::SPRITE_SIZE * 2 );
		$icon .= $this->get_sprite( $csh, $color, ( $cro * 270 + 270 ) % 360, 0, self::SPRITE_SIZE * 2 );

		// Generate side sprites.
		$color = "rgb($sfr,$sfg,$sfb)";
		$icon .= $this->get_sprite( $ssh, $color, ( $sro * 270 ) % 360, self::SPRITE_SIZE, 0 );
		$icon .= $this->get_sprite( $ssh, $color, ( $sro * 270 + 90 ) % 360,  self::SPRITE_SIZE * 2, self::SPRITE_SIZE );
		$icon .= $this->get_sprite( $ssh, $color, ( $sro * 270 + 180 ) % 360, self::SPRITE_SIZE, self::SPRITE_SIZE * 2 );
		$icon .= $this->get_sprite( $ssh, $color, ( $sro * 270 + 270 ) % 360, 0, self::SPRITE_SIZE );

		// Generate center sprite.
		$dr = $cfr - $sfr;
		$dg = $cfg - $sfg;
		$db = $cfb - $sfb;
		if ( $xbg > 0 && $dr > 127 && $dg > 127 && $db > 127 ) {
			$bg_color = "rgb($dr,$dg,$db)";
		} else {
			$bg_color = 'rgb(255,255,255)';
		}
		$color = "rgb($cfr,$cfg,$cfb)";
		$icon .= $this->get_center( $xsh, $color, $bg_color, self::SPRITE_SIZE, self::SPRITE_SIZE );

		// Scale icon.
		$scale = $size / ( self::SPRITE_SIZE * 3 );

		return "<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\" viewbox=\"0 0 {$size} {$size}\" width=\"{$size}\" height=\"{$size}\"><g transform=\"scale({$scale})\">{$icon}</g></svg>";
	}

	/**
	 * Turns part of a hash string into a decimal number.
	 *
	 * @param  string $hash   A hexadecimal string auf at least 18 characters.
	 * @param  int    $offset The byte offset.
	 * @param  int    $length The number of bytes to read.
	 *
	 * @return int
	 */
	private function parse_hash( $hash, $offset, $length ) {
		return \hexdec( \substr( $hash, $offset, $length ) );
	}

	/**
	 * Builds the center sprite.
	 *
	 * @param  int    $shape    The CENTER_SHAPES index.
	 * @param  string $fore_rgb The foreground color.
	 * @param  string $back_rgb The background color.
	 * @param  int    $x        The horizontal coordinate.
	 * @param  int    $y        The vertical coordinate.
	 *
	 * @return string
	 */
	private function get_center( $shape, $fore_rgb, $back_rgb, $x, $y ) {
		$shape_class = self::CENTER_SHAPES[ $shape % self::MAX_CENTER ];

		return ( new Center\Center_Shape( new $shape_class( $fore_rgb ), $back_rgb ) )->render( $x, $y, 0 );
	}

	/**
	 * Builds an edge sprite.
	 *
	 * @param  int    $shape    The CENTER_SHAPES index.
	 * @param  string $color    The color.
	 * @param  int    $rotation The sprite rotation.
	 * @param  int    $x        The horizontal coordinate.
	 * @param  int    $y        The vertical coordinate.
	 *
	 * @return string
	 */
	private function get_sprite( $shape, $color, $rotation, $x, $y ) {
		$shape_class = self::SPRITE_SHAPES[ $shape % self::MAX_SPRITE ];

		return ( new $shape_class( $color ) )->render( $x, $y, $rotation );
	}
}

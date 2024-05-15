<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Tools\Images\SVG;

/**
 * RoboHash SVG image.
 *
 * Required template variables:
 *
 * @var string $color     The robot body color as CSS color string (e.g. `#ff9800`).
 * @var string $bg_color  The background color as a CSS color string (e.g. `#80d8ff`).
 * @var string $body      The SVG elements making up the robot's body.
 * @var string $face      The SVG elements making up the robot's face.
 * @var string $eyes      The SVG elements making up the robot's eyes.
 * @var string $mouth     The SVG elements making up the robot's mouth.
 * @var string $accessory The SVG elements making up the robot's accessory.
 */

?>
<svg viewBox="0 0 320 320" width="320" height="320" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2000/xlink">
	<g style="color:<?php echo \esc_attr( $color ); ?>">
		<rect fill="<?php echo \esc_attr( $bg_color ); ?>" x="0" y="0" width="320" height="320"></rect>
		<?php echo \wp_kses( $body . $face . $eyes . $mouth . $accessory, SVG::ALLOWED_ELEMENTS ); ?>
	</g>
</svg>
<?php

<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2023 Peter Putzer.
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
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *     Copyright (c) 2013, 2014, 2016 Benjamin Laugueux <benjamin@yzalis.com>
 *     Copyright (c) 2015 Grummfy <grummfy@gmail.com>
 *     Copyright (c) 2016, 2017 Lucas Michot
 *     Copyright (c) 2019 Arjen van der Meijden
 *
 *     Permission is hereby granted, free of charge, to any person obtaining a copy
 *     of this software and associated documentation files (the "Software"), to deal
 *     in the Software without restriction, including without limitation the rights
 *     to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *     copies of the Software, and to permit persons to whom the Software is furnished
 *     to do so, subject to the following conditions:
 *
 *     The above copyright notice and this permission notice shall be included in all
 *     copies or substantial portions of the Software.
 *
 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *     IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *     FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *     AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *     LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *     OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *     THE SOFTWARE.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Retro SVG image.
 *
 * Required template variables:
 *
 * @var int    $rows      The number of rows.
 * @var int    $columns   The number of columns.
 * @var string $path      The path to draw.
 * @var string $color     The robot body color as CSS color string (e.g. `#ff9800`).
 * @var string $bg_color  The background color as a CSS color string (e.g. `#80d8ff`).
 */
?>
<svg viewBox="0 0 <?php echo \esc_attr( "{$columns} {$rows}" ); ?>" width="320" height="320" xmlns="http://www.w3.org/2000/svg">
	<rect width="<?php echo \esc_attr( "$columns" ); ?>" height="<?php echo \esc_attr( "$rows" ); ?>" fill="<?php echo \esc_attr( $bg_color ); ?>" stroke-width="0"/>
	<path fill="<?php echo \esc_attr( $color ); ?>" stroke-width="0" d="<?php echo \esc_attr( $path ); ?>"/>
</svg>
<?php

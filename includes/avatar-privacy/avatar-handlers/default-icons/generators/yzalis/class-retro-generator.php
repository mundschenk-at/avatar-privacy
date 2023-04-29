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
 */

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Yzalis;

/**
 * @author Grummfy <grummfy@gmail.com>
 */
class Retro_Generator
{
    /**
     * @var mixed
     */
    protected $generated_image;

    /**
     * @var array
     */
    protected $color;

    /**
     * @var array
     */
    protected $background_color;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $pixel_ratio;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var array
     */
    private $array_of_square = [];

    /**
     * Set the image color.
     *
     * @param string|array $color The color in hexa (3 or 6 chars) or rgb array
     *
     * @return $this
     */
    public function set_color( $color )
    {
        if (null === $color) {
            return $this;
        }

        $this->color = $this->convert_color( $color );

        return $this;
    }

    /**
     * Set the image background color.
     *
     * @param string|array $background_color The color in hexa (3 or 6 chars) or rgb array
     *
     * @return $this
     */
    public function set_background_color( $background_color )
    {
        if (null === $background_color) {
            return $this;
        }

        $this->background_color = $this->convert_color($background_color);

        return $this;
    }

    /**
     * @param array|string $color
     *
     * @return array
     */
    private function convert_color($color)
    {
        if (is_array($color)) {
            return $color;
        }

        if (preg_match('/^#?([a-z\d])([a-z\d])([a-z\d])$/i', $color, $matches)) {
            $color = $matches[1].$matches[1];
            $color .= $matches[2].$matches[2];
            $color .= $matches[3].$matches[3];
        }

        preg_match('/#?([a-z\d]{2})([a-z\d]{2})([a-z\d]{2})$/i', $color, $matches);

        return array_map(function ($value) {
            return hexdec($value);
        }, array_slice($matches, 1, 3));
    }

    /**
     * Get the color.
     *
     * @return array
     */
    public function get_color()
    {
        return $this->color;
    }

    /**
     * Get the background color.
     *
     * @return array
     */
    public function get_background_color()
    {
        return $this->background_color;
    }

    /**
     * Convert the hash into an multidimensional array of boolean.
     *
     * @return $this
     */
    private function convert_hash_to_array_of_boolean()
    {
        preg_match_all('/(\w)(\w)/', $this->hash, $chars);

        foreach ($chars[1] as $i => $char) {
            $index = (int) ($i / 3);
            $data = $this->convert_hexa_to_boolean($char);

            $items = [
                0 => [0, 4],
                1 => [1, 3],
                2 => [2],
            ];

            foreach ($items[$i % 3] as $item) {
                $this->array_of_square[$index][$item] = $data;
            }

            ksort($this->array_of_square[$index]);
        }

        $this->color = array_map(function ($data) {
            return hexdec($data) * 16;
        }, array_reverse($chars[1]));

        return $this;
    }

    /**
     * Convert an hexadecimal number into a boolean.
     *
     * @param string $hexa
     *
     * @return bool
     */
    private function convert_hexa_to_boolean($hexa)
    {
        return (bool) round(hexdec($hexa) / 10);
    }

    /**
     * @return array
     */
    public function get_array_of_square()
    {
        return $this->array_of_square;
    }

    /**
     * Get the identicon string hash.
     *
     * @return string
     */
    public function get_hash()
    {
        return $this->hash;
    }

    /**
     * Generate a hash from the original string.
     *
     * @param string $string
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function set_string($string)
    {
        if (null === $string) {
            throw new \Exception('The string cannot be null.');
        }

        $this->hash = md5($string);

        $this->convert_hash_to_array_of_boolean();

        return $this;
    }

    /**
     * Set the image size.
     *
     * @param int $size
     *
     * @return $this
     */
    public function set_size($size)
    {
        if (null === $size) {
            return $this;
        }

        $this->size = $size;
        $this->pixel_ratio = (int) round($size / 5);

        return $this;
    }

    /**
     * Get the image size.
     *
     * @return int
     */
    public function get_size()
    {
        return $this->size;
    }

    /**
     * Get the pixel ratio.
     *
     * @return int
     */
    public function get_pixel_ratio()
    {
        return $this->pixel_ratio;
    }

    /**
     * Return the mime-type of this identicon.
     *
     * @return string
     */
    public function get_mime_type()
    {
        return 'image/svg+xml';
    }

    /**
     * @param string       $string
     * @param int          $size
     * @param array|string $color
     * @param array|string $backgroundColor
     *
     * @return mixed
     */
    public function get_image_binary_data($string, $size = null, $color = null, $backgroundColor = null)
    {
        return $this->get_image_resource($string, $size, $color, $backgroundColor);
    }

    /**
     * @param string       $string
     * @param int          $size
     * @param array|string $color
     * @param array|string $backgroundColor
     *
     * @return string
     */
    public function get_image_resource($string, $size = null, $color = null, $backgroundColor = null)
    {
        $this
            ->set_string($string)
            ->set_size($size)
            ->set_color($color)
            ->set_background_color($backgroundColor)
            ->_generate_image();

        return $this->generated_image;
    }

    /**
     * @return $this
     */
    protected function _generate_image()
    {
        // prepare image
        $w = $this->get_pixel_ratio() * 5;
        $h = $this->get_pixel_ratio() * 5;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$w.'" height="'.$h.'" viewBox="0 0 5 5">';

        $backgroundColor = '#FFF';
        $rgbBackgroundColor = $this->get_background_color();
        if (!is_null($rgbBackgroundColor)) {
            $backgroundColor = $this->_to_understandable_color($rgbBackgroundColor);
        }

        $svg .= '<rect width="5" height="5" fill="'.$backgroundColor.'" stroke-width="0"/>';

        $rects = [];
        // draw content
        foreach ($this->get_array_of_square() as $lineKey => $lineValue) {
            foreach ($lineValue as $colKey => $colValue) {
                if (true === $colValue) {
                    $rects[] = 'M'.$colKey.','.$lineKey.'h1v1h-1v-1';
                }
            }
        }

        $rgbColor = $this->_to_understandable_color($this->get_color());
        $svg .= '<path fill="'.$rgbColor.'" stroke-width="0" d="' . implode('', $rects) . '"/>';
        $svg .= '</svg>';

        $this->generated_image = $svg;

        return $this;
    }

    /**
     * @param array|string $color
     *
     * @return string
     */
    protected function _to_understandable_color($color)
    {
        if (is_array($color)) {
            return sprintf('#%X%X%X', $color[0], $color[1], $color[2]);
        }

        return $color;
    }
}

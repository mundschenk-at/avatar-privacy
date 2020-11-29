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

namespace Avatar_Privacy\Tools\Images;

/**
 * A utility class providing some methods for dealing with SVG images.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class SVG {

	// SVG attributes.
	const ATTR_CLASS                       = 'class';
	const ATTR_CLIPPATHUNITS               = 'clippathunits';
	const ATTR_CLIP_PATH                   = 'clip-path';
	const ATTR_CLIP_RULE                   = 'clip-rule';
	const ATTR_COLOR_INTERPOLATION_FILTERS = 'color-interpolation-filters';
	const ATTR_CX                          = 'cx';
	const ATTR_CY                          = 'cy';
	const ATTR_D                           = 'd';
	const ATTR_DISPLAY                     = 'display';
	const ATTR_DX                          = 'dx';
	const ATTR_DY                          = 'dy';
	const ATTR_FILL                        = 'fill';
	const ATTR_FILL_OPACITY                = 'fill-opacity';
	const ATTR_FILL_RULE                   = 'fill-rule';
	const ATTR_FILTER                      = 'filter';
	const ATTR_FILTERRES                   = 'filterres';
	const ATTR_FILTERUNITS                 = 'filterunits';
	const ATTR_FONT_FAMILY                 = 'font-family';
	const ATTR_FONT_SIZE                   = 'font-size';
	const ATTR_FONT_STYLE                  = 'font-style';
	const ATTR_FONT_WEIGHT                 = 'font-weight';
	const ATTR_FX                          = 'fx';
	const ATTR_FY                          = 'fy';
	const ATTR_GRADIENTTRANSFORM           = 'gradienttransform';
	const ATTR_GRADIENTUNITS               = 'gradientunits';
	const ATTR_HEIGHT                      = 'height';
	const ATTR_HREF                        = 'href';
	const ATTR_ID                          = 'id';
	const ATTR_MARKERHEIGHT                = 'markerheight';
	const ATTR_MARKERUNITS                 = 'markerunits';
	const ATTR_MARKERWIDTH                 = 'markerwidth';
	const ATTR_MARKER_END                  = 'marker-end';
	const ATTR_MARKER_MID                  = 'marker-mid';
	const ATTR_MARKER_START                = 'marker-start';
	const ATTR_MASK                        = 'mask';
	const ATTR_MASKEDCONTENTUNITS          = 'maskcontentunits';
	const ATTR_MASKUNITS                   = 'maskunits';
	const ATTR_METHOD                      = 'method';
	const ATTR_OFFSET                      = 'offset';
	const ATTR_OPACITY                     = 'opacity';
	const ATTR_ORIENT                      = 'orient';
	const ATTR_PATTERNCONTENTUNITS         = 'patterncontentunits';
	const ATTR_PATTERNTRANSFORM            = 'patterntransform';
	const ATTR_PATTERNUNITS                = 'patternunits';
	const ATTR_POINTS                      = 'points';
	const ATTR_PRESERVEASPECTRATIO         = 'preserveaspectratio';
	const ATTR_PRIMITIVEUNITS              = 'primitiveunits';
	const ATTR_R                           = 'r';
	const ATTR_REFX                        = 'refx';
	const ATTR_REFY                        = 'refy';
	const ATTR_REQUIREDFEATURES            = 'requiredfeatures';
	const ATTR_ROTATE                      = 'rotate';
	const ATTR_RX                          = 'rx';
	const ATTR_RY                          = 'ry';
	const ATTR_SPACING                     = 'spacing';
	const ATTR_SPREADMETHOD                = 'spreadmethod';
	const ATTR_STARTOFFSET                 = 'startoffset';
	const ATTR_STDDEVIATION                = 'stddeviation';
	const ATTR_STOP_COLOR                  = 'stop-color';
	const ATTR_STOP_OPACITY                = 'stop-opacity';
	const ATTR_STROKE                      = 'stroke';
	const ATTR_STROKE_DASHARRAY            = 'stroke-dasharray';
	const ATTR_STROKE_DASHOFFSET           = 'stroke-dashoffset';
	const ATTR_STROKE_LINECAP              = 'stroke-linecap';
	const ATTR_STROKE_LINEJOIN             = 'stroke-linejoin';
	const ATTR_STROKE_MITERLIMIT           = 'stroke-miterlimit';
	const ATTR_STROKE_OPACITY              = 'stroke-opacity';
	const ATTR_STROKE_WIDTH                = 'stroke-width';
	const ATTR_STYLE                       = 'style';
	const ATTR_SYSTEMLANGUAGE              = 'systemlanguage';
	const ATTR_TEXTLENGTH                  = 'textlength';
	const ATTR_TEXT_ANCHOR                 = 'text-anchor';
	const ATTR_TRANSFORM                   = 'transform';
	const ATTR_TYPE                        = 'type';
	const ATTR_WIDTH                       = 'width';
	const ATTR_VIEWBOX                     = 'viewbox';
	const ATTR_X                           = 'x';
	const ATTR_X1                          = 'x1';
	const ATTR_X2                          = 'x2';
	const ATTR_XLINK_HREF                  = 'xlink:href';
	const ATTR_XLINK_TITLE                 = 'xlink:title';
	const ATTR_XMLNS                       = 'xmlns';
	const ATTR_XMLNS_SE                    = 'xmlns:se';
	const ATTR_XMLNS_XLINK                 = 'xmlns:xlink';
	const ATTR_XML_SPACE                   = 'xml:space';
	const ATTR_Y                           = 'y';
	const ATTR_Y1                          = 'y1';
	const ATTR_Y2                          = 'y2';

	/**
	 * An array of allowed elements and attributes in wp_kses syntax.
	 *
	 * List initially compiled by Michael Pollett in Trac #24251.
	 *
	 * @link https://core.trac.wordpress.org/ticket/24251
	 *
	 * @var array
	 */
	const ALLOWED_ELEMENTS = [
		'a'              => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_HREF              => true,
			self::ATTR_XLINK_HREF        => true,
			self::ATTR_XLINK_TITLE       => true,
		],
		'circle'         => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_CX                => true,
			self::ATTR_CY                => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_R                 => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
		],
		'clippath'       => [
			self::ATTR_CLASS         => true,
			self::ATTR_CLIPPATHUNITS => true,
			self::ATTR_ID            => true,
		],
		'defs'           => true,
		'style'          => [
			self::ATTR_TYPE => true,
		],
		'desc'           => true,
		'ellipse'        => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_CX                => true,
			self::ATTR_CY                => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_RX                => true,
			self::ATTR_RY                => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
		],
		'fegaussianblur' => [
			self::ATTR_CLASS                       => true,
			self::ATTR_COLOR_INTERPOLATION_FILTERS => true,
			self::ATTR_ID                          => true,
			self::ATTR_REQUIREDFEATURES            => true,
			self::ATTR_STDDEVIATION                => true,
		],
		'filter'         => [
			self::ATTR_CLASS                       => true,
			self::ATTR_COLOR_INTERPOLATION_FILTERS => true,
			self::ATTR_FILTERRES                   => true,
			self::ATTR_FILTERUNITS                 => true,
			self::ATTR_HEIGHT                      => true,
			self::ATTR_ID                          => true,
			self::ATTR_PRIMITIVEUNITS              => true,
			self::ATTR_REQUIREDFEATURES            => true,
			self::ATTR_WIDTH                       => true,
			self::ATTR_X                           => true,
			self::ATTR_XLINK_HREF                  => true,
			self::ATTR_Y                           => true,
		],
		'foreignobject'  => [
			self::ATTR_CLASS            => true,
			self::ATTR_FONT_SIZE        => true,
			self::ATTR_HEIGHT           => true,
			self::ATTR_ID               => true,
			self::ATTR_OPACITY          => true,
			self::ATTR_REQUIREDFEATURES => true,
			self::ATTR_STYLE            => true,
			self::ATTR_TRANSFORM        => true,
			self::ATTR_WIDTH            => true,
			self::ATTR_X                => true,
			self::ATTR_Y                => true,
		],
		'g'              => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_ID                => true,
			self::ATTR_DISPLAY           => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_FONT_FAMILY       => true,
			self::ATTR_FONT_SIZE         => true,
			self::ATTR_FONT_STYLE        => true,
			self::ATTR_FONT_WEIGHT       => true,
			self::ATTR_TEXT_ANCHOR       => true,
		],
		'image'          => [
			self::ATTR_CLASS            => true,
			self::ATTR_CLIP_PATH        => true,
			self::ATTR_CLIP_RULE        => true,
			self::ATTR_FILTER           => true,
			self::ATTR_HEIGHT           => true,
			self::ATTR_ID               => true,
			self::ATTR_MASK             => true,
			self::ATTR_OPACITY          => true,
			self::ATTR_REQUIREDFEATURES => true,
			self::ATTR_STYLE            => true,
			self::ATTR_SYSTEMLANGUAGE   => true,
			self::ATTR_TRANSFORM        => true,
			self::ATTR_WIDTH            => true,
			self::ATTR_X                => true,
			self::ATTR_XLINK_HREF       => true,
			self::ATTR_XLINK_TITLE      => true,
			self::ATTR_Y                => true,
		],
		'line'           => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MARKER_END        => true,
			self::ATTR_MARKER_MID        => true,
			self::ATTR_MARKER_START      => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_X1                => true,
			self::ATTR_X2                => true,
			self::ATTR_Y1                => true,
			self::ATTR_Y2                => true,
		],
		'lineargradient' => [
			self::ATTR_CLASS             => true,
			self::ATTR_ID                => true,
			self::ATTR_GRADIENTTRANSFORM => true,
			self::ATTR_GRADIENTUNITS     => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_SPREADMETHOD      => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_X1                => true,
			self::ATTR_X2                => true,
			self::ATTR_XLINK_HREF        => true,
			self::ATTR_Y1                => true,
			self::ATTR_Y2                => true,
		],
		'marker'         => [
			self::ATTR_ID                  => true,
			self::ATTR_CLASS               => true,
			self::ATTR_MARKERHEIGHT        => true,
			self::ATTR_MARKERUNITS         => true,
			self::ATTR_MARKERWIDTH         => true,
			self::ATTR_ORIENT              => true,
			self::ATTR_PRESERVEASPECTRATIO => true,
			self::ATTR_REFX                => true,
			self::ATTR_REFY                => true,
			self::ATTR_SYSTEMLANGUAGE      => true,
			self::ATTR_VIEWBOX             => true,
		],
		'mask'           => [
			self::ATTR_CLASS              => true,
			self::ATTR_HEIGHT             => true,
			self::ATTR_ID                 => true,
			self::ATTR_MASKEDCONTENTUNITS => true,
			self::ATTR_MASKUNITS          => true,
			self::ATTR_WIDTH              => true,
			self::ATTR_X                  => true,
			self::ATTR_Y                  => true,
		],
		'metadata'       => [
			self::ATTR_CLASS => true,
			self::ATTR_ID    => true,
		],
		'path'           => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_D                 => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MARKER_END        => true,
			self::ATTR_MARKER_MID        => true,
			self::ATTR_MARKER_START      => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
		],
		'pattern'        => [
			self::ATTR_CLASS               => true,
			self::ATTR_HEIGHT              => true,
			self::ATTR_ID                  => true,
			self::ATTR_PATTERNCONTENTUNITS => true,
			self::ATTR_PATTERNTRANSFORM    => true,
			self::ATTR_PATTERNUNITS        => true,
			self::ATTR_REQUIREDFEATURES    => true,
			self::ATTR_STYLE               => true,
			self::ATTR_SYSTEMLANGUAGE      => true,
			self::ATTR_VIEWBOX             => true,
			self::ATTR_WIDTH               => true,
			self::ATTR_X                   => true,
			self::ATTR_XLINK_HREF          => true,
			self::ATTR_Y                   => true,
		],
		'polygon'        => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_ID                => true,
			self::ATTR_MARKER_END        => true,
			self::ATTR_MARKER_MID        => true,
			self::ATTR_MARKER_START      => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_POINTS            => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
		],
		'polyline'       => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_ID                => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_MARKER_END        => true,
			self::ATTR_MARKER_MID        => true,
			self::ATTR_MARKER_START      => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_POINTS            => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
		],
		'radialgradient' => [
			self::ATTR_CLASS             => true,
			self::ATTR_CX                => true,
			self::ATTR_CY                => true,
			self::ATTR_FX                => true,
			self::ATTR_FY                => true,
			self::ATTR_GRADIENTTRANSFORM => true,
			self::ATTR_GRADIENTUNITS     => true,
			self::ATTR_ID                => true,
			self::ATTR_R                 => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_SPREADMETHOD      => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_XLINK_HREF        => true,
		],
		'rect'           => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_HEIGHT            => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_RX                => true,
			self::ATTR_RY                => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_WIDTH             => true,
			self::ATTR_X                 => true,
			self::ATTR_Y                 => true,
		],
		'stop'           => [
			self::ATTR_CLASS            => true,
			self::ATTR_ID               => true,
			self::ATTR_OFFSET           => true,
			self::ATTR_REQUIREDFEATURES => true,
			self::ATTR_STOP_COLOR       => true,
			self::ATTR_STOP_OPACITY     => true,
			self::ATTR_STYLE            => true,
			self::ATTR_SYSTEMLANGUAGE   => true,
		],
		'svg'            => [
			self::ATTR_CLASS               => true,
			self::ATTR_CLIP_PATH           => true,
			self::ATTR_CLIP_RULE           => true,
			self::ATTR_FILTER              => true,
			self::ATTR_ID                  => true,
			self::ATTR_HEIGHT              => true,
			self::ATTR_MASK                => true,
			self::ATTR_PRESERVEASPECTRATIO => true,
			self::ATTR_REQUIREDFEATURES    => true,
			self::ATTR_STYLE               => true,
			self::ATTR_SYSTEMLANGUAGE      => true,
			self::ATTR_VIEWBOX             => true,
			self::ATTR_WIDTH               => true,
			self::ATTR_X                   => true,
			self::ATTR_XMLNS               => true,
			self::ATTR_XMLNS_SE            => true,
			self::ATTR_XMLNS_XLINK         => true,
			self::ATTR_Y                   => true,
		],
		'switch'         => [
			self::ATTR_CLASS            => true,
			self::ATTR_ID               => true,
			self::ATTR_REQUIREDFEATURES => true,
			self::ATTR_SYSTEMLANGUAGE   => true,
		],
		'symbol'         => [
			self::ATTR_CLASS               => true,
			self::ATTR_FILL                => true,
			self::ATTR_FILL_OPACITY        => true,
			self::ATTR_FILL_RULE           => true,
			self::ATTR_FILTER              => true,
			self::ATTR_FONT_FAMILY         => true,
			self::ATTR_FONT_SIZE           => true,
			self::ATTR_FONT_STYLE          => true,
			self::ATTR_FONT_WEIGHT         => true,
			self::ATTR_ID                  => true,
			self::ATTR_OPACITY             => true,
			self::ATTR_PRESERVEASPECTRATIO => true,
			self::ATTR_REQUIREDFEATURES    => true,
			self::ATTR_STROKE              => true,
			self::ATTR_STROKE_DASHARRAY    => true,
			self::ATTR_STROKE_DASHOFFSET   => true,
			self::ATTR_STROKE_LINECAP      => true,
			self::ATTR_STROKE_LINEJOIN     => true,
			self::ATTR_STROKE_MITERLIMIT   => true,
			self::ATTR_STROKE_OPACITY      => true,
			self::ATTR_STROKE_WIDTH        => true,
			self::ATTR_STYLE               => true,
			self::ATTR_SYSTEMLANGUAGE      => true,
			self::ATTR_TRANSFORM           => true,
			self::ATTR_VIEWBOX             => true,
		],
		'text'           => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_FONT_FAMILY       => true,
			self::ATTR_FONT_SIZE         => true,
			self::ATTR_FONT_STYLE        => true,
			self::ATTR_FONT_WEIGHT       => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TEXT_ANCHOR       => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_X                 => true,
			self::ATTR_XML_SPACE         => true,
			self::ATTR_Y                 => true,
		],
		'textpath'       => [
			self::ATTR_CLASS            => true,
			self::ATTR_ID               => true,
			self::ATTR_METHOD           => true,
			self::ATTR_REQUIREDFEATURES => true,
			self::ATTR_SPACING          => true,
			self::ATTR_STARTOFFSET      => true,
			self::ATTR_STYLE            => true,
			self::ATTR_SYSTEMLANGUAGE   => true,
			self::ATTR_TRANSFORM        => true,
			self::ATTR_XLINK_HREF       => true,
		],
		'title'          => true,
		'tspan'          => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_DX                => true,
			self::ATTR_DY                => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_FONT_FAMILY       => true,
			self::ATTR_FONT_SIZE         => true,
			self::ATTR_FONT_STYLE        => true,
			self::ATTR_FONT_WEIGHT       => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_OPACITY           => true,
			self::ATTR_REQUIREDFEATURES  => true,
			self::ATTR_ROTATE            => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_SYSTEMLANGUAGE    => true,
			self::ATTR_TEXT_ANCHOR       => true,
			self::ATTR_TEXTLENGTH        => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_X                 => true,
			self::ATTR_XML_SPACE         => true,
			self::ATTR_Y                 => true,
		],
		'use'            => [
			self::ATTR_CLASS             => true,
			self::ATTR_CLIP_PATH         => true,
			self::ATTR_CLIP_RULE         => true,
			self::ATTR_FILL              => true,
			self::ATTR_FILL_OPACITY      => true,
			self::ATTR_FILL_RULE         => true,
			self::ATTR_FILTER            => true,
			self::ATTR_HEIGHT            => true,
			self::ATTR_ID                => true,
			self::ATTR_MASK              => true,
			self::ATTR_STROKE            => true,
			self::ATTR_STROKE_DASHARRAY  => true,
			self::ATTR_STROKE_DASHOFFSET => true,
			self::ATTR_STROKE_LINECAP    => true,
			self::ATTR_STROKE_LINEJOIN   => true,
			self::ATTR_STROKE_MITERLIMIT => true,
			self::ATTR_STROKE_OPACITY    => true,
			self::ATTR_STROKE_WIDTH      => true,
			self::ATTR_STYLE             => true,
			self::ATTR_TRANSFORM         => true,
			self::ATTR_WIDTH             => true,
			self::ATTR_X                 => true,
			self::ATTR_XLINK_HREF        => true,
			self::ATTR_Y                 => true,
		],
	];
}

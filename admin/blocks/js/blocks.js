/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin/blocks/src/blocks.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin/blocks/src/avatar.js":
/*!************************************!*\
  !*** ./admin/blocks/src/avatar.js ***!
  \************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/editor */ "@wordpress/editor");
/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_editor__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);

/**
 * Avatar Privacy Avatar Block
 *
 * The block is rendered server-side to be current (avatars can change frequently).
 */

/**
 * WordPress dependencies
 */








/**
 * The attributes of the block.
 *
 * @type {Object}
 */

var blockAttributes = {
  avatar_size: {
    type: 'integer',
    default: 96
  },
  user_id: {
    type: 'integer',
    default: 0
  },
  align: {
    type: 'string',
    default: ''
  },
  // Just temporary information during editing.
  user: {
    type: 'object',
    source: 'attribute',
    selector: '*',
    default: undefined
  }
};
/**
 * Edits the block attributes.
 *
 * Makes the markup for the editor interface.
 *
 * @param {Object} props {
 *     attributes    - The block attributes.
 *     setAttributes - The attribute setter function.
 * }
 *
 * @return {Object} ECMAScript JSX Markup for the editor
 */

var edit = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withSelect"])( // Retrieve WordPress authors.
function (select) {
  return {
    users: select('core').getAuthors()
  };
})(function (_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      users = _ref.users;

  // The authors list has not finished loading yet.
  if (!users || users.length < 1) {
    return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Loading...', 'avatar-privacy');
  } //  Set default for user_id.


  attributes.user_id = attributes.user_id || users[0].id; // Find the current user object.

  var findUser = function findUser(userID) {
    return users.find(function (user) {
      return parseInt(userID) === user.id;
    });
  };

  var currentUser = findUser(attributes.user_id); // Set the user attribute if missing.

  attributes.user = attributes.user || currentUser;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_editor__WEBPACK_IMPORTED_MODULE_4__["InspectorControls"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelBody"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Form', 'avatar-privacy')
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["SelectControl"], {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('User', 'avatar-privacy'),
    value: attributes.user_id,
    options: users.map(function (user) {
      return {
        label: user.name,
        value: user.id
      };
    }),
    onChange: function onChange(newUser) {
      return setAttributes({
        user_id: parseInt(newUser),
        user: findUser(newUser)
      });
    }
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["RangeControl"], {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Avatar Size', 'avatar-privacy'),
    value: attributes.avatar_size,
    initialPosition: attributes.avatar_size,
    onChange: function onChange(newSize) {
      return setAttributes({
        avatar_size: newSize
      });
    },
    min: 48,
    max: 240
  })))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
    width: attributes.avatar_size,
    src: attributes.user.avatar_urls[96],
    alt: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Avatar of %s', 'avatar-privacy'), attributes.user.name)
  }));
});
/**
 * Registers and creates block
 *
 * @param {string} Name Name of the block with a required name space
 * @param {object} ObjectArgs Block configuration {
 *      title - Title, displayed in the editor
 *      icon - Icon, from WP icons
 *      category - Block category, where the block will be added in the editor
 *      attributes - Object with all binding elements between the view HTML and the functions
 *      edit function - Returns the markup for the editor interface.
 *      save function - Returns the markup that will be rendered on the site page
 * }
 */

Object(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__["registerBlockType"])('avatar-privacy/avatar', {
  // Metadata.
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Avatar', 'avatar-privacy'),
  icon: 'admin-users',
  category: 'common',
  // The meat.
  attributes: blockAttributes,
  supports: {
    align: ['left', 'center', 'right'],
    html: false
  },
  edit: edit,
  save: function save() {}
});

/***/ }),

/***/ "./admin/blocks/src/blocks.js":
/*!************************************!*\
  !*** ./admin/blocks/src/blocks.js ***!
  \************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _frontend_form_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./frontend-form.js */ "./admin/blocks/src/frontend-form.js");
/* harmony import */ var _avatar_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./avatar.js */ "./admin/blocks/src/avatar.js");

/**
 * Avatar Privacy
 *
 * The blocks provided by the Avatar Privacy plugin.
 *
 * @requires Gutenberg 4.3
 */

/**
 * Internal dependencies
 */




/***/ }),

/***/ "./admin/blocks/src/frontend-form.js":
/*!*******************************************!*\
  !*** ./admin/blocks/src/frontend-form.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/editor */ "@wordpress/editor");
/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);

/**
 * Avatar Privacy Frontend Form Block
 *
 * The block is rendered server-side for consistency with the classic editor shortcode.
 */

/**
 * WordPress dependencies
 */







/**
 * Registers and creates block
 *
 * @param {string} Name Name of the block with a required name space
 * @param {object} ObjectArgs Block configuration {
 *      title - Title, displayed in the editor
 *      icon - Icon, from WP icons
 *      category - Block category, where the block will be added in the editor
 *      attributes - Object with all binding elements between the view HTML and the functions
 *      edit function - Returns the markup for the editor interface.
 *      save function - Returns the markup that will be rendered on the site page
 * }
 */

Object(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__["registerBlockType"])('avatar-privacy/form', {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Avatar Privacy Form', 'avatar-privacy'),
  icon: 'admin-users',
  category: 'common',

  /**
   * An object containing the block attributes and their storage location.
   *
   * @type {Object}
   */
  attributes: {
    avatar_size: {
      type: 'integer',
      default: 96
    },
    show_descriptions: {
      type: 'boolean',
      default: true
    }
  },

  /**
   * Edits the block attributes.
   *
   * Makes the markup for the editor interface.
   *
   * @param {Object} props {
   *     attributes    - The block attributes.
   *     className     - Optional class name from the block editor.
   *     setAttributes - The attribute setter function.
   * }
   *
   * @return {Object} ECMAScript JSX Markup for the editor
   */
  edit: function edit(_ref) {
    var attributes = _ref.attributes,
        className = _ref.className,
        setAttributes = _ref.setAttributes;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_editor__WEBPACK_IMPORTED_MODULE_3__["InspectorControls"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelBody"], {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Form', 'avatar-privacy')
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["RangeControl"], {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Avatar Size', 'avatar-privacy'),
      value: attributes.avatar_size,
      initialPosition: attributes.avatar_size,
      onChange: function onChange(newSize) {
        return setAttributes({
          avatar_size: newSize
        });
      },
      min: 48,
      max: 240
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["ToggleControl"], {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Show Descriptions', 'avatar-privacy'),
      checked: !!attributes.show_descriptions,
      onChange: function onChange() {
        return setAttributes({
          show_descriptions: !attributes.show_descriptions
        });
      }
    })))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["ServerSideRender"], {
      block: "avatar-privacy/form",
      attributes: {
        avatar_size: attributes.avatar_size,
        show_descriptions: attributes.show_descriptions,
        className: className,
        preview: true
      }
    }));
  },

  /**
   * Saves the rendered block.
   *
   * As rendering is done on the PHP side, we always return null.
   *
   * @return {Object} ECMAScript JSX Markup for the site
   */
  save: function save() {
    return null;
  }
});

/***/ }),

/***/ "@wordpress/blocks":
/*!*****************************************!*\
  !*** external {"this":["wp","blocks"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["blocks"]; }());

/***/ }),

/***/ "@wordpress/components":
/*!*********************************************!*\
  !*** external {"this":["wp","components"]} ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["components"]; }());

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ "@wordpress/editor":
/*!*****************************************!*\
  !*** external {"this":["wp","editor"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["editor"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ })

/******/ });
//# sourceMappingURL=blocks.js.map
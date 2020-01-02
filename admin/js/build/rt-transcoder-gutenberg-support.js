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
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin/js/rt-transcoder-gutenberg-support.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin/js/rt-transcoder-amp-video-quality.js":
/*!*****************************************************!*\
  !*** ./admin/js/rt-transcoder-amp-video-quality.js ***!
  \*****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "@babel/runtime/regenerator");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__);












var _select = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__["select"])('core/block-editor'),
    getBlocksByClientId = _select.getBlocksByClientId,
    getClientIdsWithDescendants = _select.getClientIdsWithDescendants; // Enable Transcoder settings on the following blocks


var enableTranscoderSettingsOnBlocks = ['amp/amp-story-page', 'core/video'];
var _window = window,
    rtTranscoderBlockEditorSupport = _window.rtTranscoderBlockEditorSupport; // Default Video Quality for for selection.

var defaultVideoQuality = typeof rtTranscoderBlockEditorSupport.rt_default_video_quality !== 'undefined' ? rtTranscoderBlockEditorSupport.rt_default_video_quality : 'high';
/**
 * Add background video quality attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */

var addBackgroundVideoQualityControlAttribute = function addBackgroundVideoQualityControlAttribute(settings, name) {
  if (!enableTranscoderSettingsOnBlocks.includes(name)) {
    return settings;
  } //check if object exists for old Gutenberg version compatibility


  if (typeof settings.attributes !== 'undefined') {
    settings.attributes = Object.assign(settings.attributes, {
      rtBackgroundVideoInfo: {
        type: 'object'
      },
      rtBackgroundVideoQuality: {
        type: 'string',
        default: defaultVideoQuality
      }
    });
  }

  return settings;
};

Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_5__["addFilter"])('blocks.registerBlockType', 'transcoder/ampStoryBackgroundVideoQuality', addBackgroundVideoQualityControlAttribute, 9);
/**
 * Create HOC to add Transcoder settings controls to inspector controls of block.
 */

var withTranscoderSettings = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__["createHigherOrderComponent"])(function (BlockEdit) {
  return function (props) {
    // Do nothing if it's another block than our defined ones.
    if (!enableTranscoderSettingsOnBlocks.includes(props.name)) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(BlockEdit, props);
    }

    var mediaAttributes = props.attributes;
    var isAMPStory = 'amp/amp-story-page' === props.name;
    var isVideoBlock = 'core/video' === props.name;
    var mediaType = mediaAttributes.mediaType ? mediaAttributes.mediaType : '';
    var rtBackgroundVideoQuality = mediaAttributes.rtBackgroundVideoQuality;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(BlockEdit, props), (isVideoBlock || isAMPStory && 'video' === mediaType) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_7__["InspectorControls"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["PanelBody"], {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Transcoder Settings', 'transcoder'),
      initialOpen: true
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["SelectControl"], {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Background Video Quality', 'transcoder'),
      value: rtBackgroundVideoQuality,
      options: [{
        value: 'low',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Low', 'transcoder')
      }, {
        value: 'medium',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Medium', 'transcoder')
      }, {
        value: 'high',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('High', 'transcoder')
      }],
      onChange: function onChange(selectedQuality) {
        props.setAttributes({
          rtBackgroundVideoQuality: selectedQuality
        });
      }
    }))));
  };
}, 'withTranscoderSettings');
Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_5__["addFilter"])('editor.BlockEdit', 'rt-transcoder-amp/with-transcoder-settings', withTranscoderSettings, 12);
/**
 * Get Transcoded Media Data.
 */

var getMediaInfo =
/*#__PURE__*/
function () {
  var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()(
  /*#__PURE__*/
  _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee(mediaId) {
    var restBase, response;
    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.prev = 0;
            restBase = '/transcoder/v1/amp-media';
            _context.next = 4;
            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_8___default()({
              path: "".concat(restBase, "/").concat(mediaId),
              method: 'GET'
            });

          case 4:
            response = _context.sent;

            if (!(false !== response && null !== response)) {
              _context.next = 9;
              break;
            }

            return _context.abrupt("return", response);

          case 9:
            return _context.abrupt("return", false);

          case 10:
            _context.next = 15;
            break;

          case 12:
            _context.prev = 12;
            _context.t0 = _context["catch"](0);
            console.log(_context.t0);

          case 15:
          case "end":
            return _context.stop();
        }
      }
    }, _callee, null, [[0, 12]]);
  }));

  return function getMediaInfo(_x) {
    return _ref.apply(this, arguments);
  };
}();

var updateAMPStoryMedia = function updateAMPStoryMedia(BlockEdit) {
  return function (props) {
    // Do nothing if it's another block than our defined ones.
    if (!enableTranscoderSettingsOnBlocks.includes(props.name)) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(BlockEdit, props);
    }

    var mediaAttributes = props.attributes;
    var isAMPStory = 'amp/amp-story-page' === props.name;
    var isVideoBlock = 'core/video' === props.name;
    var mediaId = isAMPStory ? mediaAttributes.mediaId : mediaAttributes.id;

    if (typeof mediaId !== 'undefined') {
      if (typeof mediaAttributes.poster === 'undefined' && 'amp_story' === rtTranscoderBlockEditorSupport.current_post_type) {
        if (isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' && 'video' === mediaAttributes.mediaType && !mediaAttributes.mediaUrl.endsWith('mp4')) {
          props.setAttributes({
            poster: rtTranscoderBlockEditorSupport.amp_story_fallback_poster
          });
        } else if (isVideoBlock && typeof mediaAttributes.src !== 'undefined' && mediaAttributes.src.indexOf('blob:') !== 0 && !mediaAttributes.src.endsWith('mp4')) {
          props.setAttributes({
            poster: rtTranscoderBlockEditorSupport.amp_video_fallback_poster
          });
        }
      } else {
        if (typeof props.attributes.rtBackgroundVideoInfo !== 'undefined') {
          var mediaInfo = props.attributes.rtBackgroundVideoInfo;
          var videoQuality = props.attributes.rtBackgroundVideoQuality ? props.attributes.rtBackgroundVideoQuality : defaultVideoQuality;

          if (mediaInfo.poster.length && mediaInfo[videoQuality].transcodedMedia.length) {
            if (isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' && 'video' === mediaAttributes.mediaType) {
              props.setAttributes({
                poster: mediaInfo.poster,
                mediaUrl: mediaInfo[videoQuality].transcodedMedia,
                src: mediaInfo[videoQuality].transcodedMedia,
                rtBackgroundVideoQuality: videoQuality
              });
            } else if (isVideoBlock) {
              props.setAttributes({
                poster: mediaInfo.poster,
                src: mediaInfo[videoQuality].transcodedMedia,
                rtBackgroundVideoQuality: videoQuality
              });
            }
          }
        }
      }
    }

    var rtBackgroundVideoQuality = props.attributes.rtBackgroundVideoQuality; // add has-quality-xy class to block

    if (rtBackgroundVideoQuality) {
      props.setAttributes({
        className: "has-quality-".concat(rtBackgroundVideoQuality)
      });
    } else {
      props.setAttributes({
        rtBackgroundVideoQuality: defaultVideoQuality
      });
    }

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(BlockEdit, props);
  };
};

Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_5__["addFilter"])('editor.BlockEdit', 'rt-transcoder-amp/set-media-attributes', updateAMPStoryMedia, 11);
setInterval(function () {
  var allBlocks = getBlocksByClientId(getClientIdsWithDescendants());

  if (allBlocks.length) {
    var _iteratorNormalCompletion = true;
    var _didIteratorError = false;
    var _iteratorError = undefined;

    try {
      for (var _iterator = allBlocks[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
        var currentBlock = _step.value;

        if (currentBlock.name.length && enableTranscoderSettingsOnBlocks.includes(currentBlock.name)) {
          (function () {
            var blockAttributes = currentBlock.attributes;
            var clientId = currentBlock.clientId;

            if (typeof clientId !== 'undefined' && typeof blockAttributes.rtBackgroundVideoInfo === 'undefined') {
              var isAMPStory = 'amp/amp-story-page' === currentBlock.name;
              var isVideoBlock = 'core/video' === currentBlock.name;
              var mediaId = isAMPStory ? blockAttributes.mediaId : blockAttributes.id;

              if (typeof mediaId !== 'undefined') {
                getMediaInfo(mediaId).then(function (data) {
                  if (false !== data && null !== data) {
                    var mediaInfo = data;
                    console.log('media info');
                    console.log(mediaInfo);
                    var videoQuality = blockAttributes.rtBackgroundVideoQuality ? blockAttributes.rtBackgroundVideoQuality : defaultVideoQuality;

                    if (typeof mediaInfo !== 'undefined' && mediaInfo.poster.length && mediaInfo[videoQuality].transcodedMedia.length) {
                      if (isAMPStory && typeof blockAttributes.mediaType !== 'undefined' && 'video' === blockAttributes.mediaType) {
                        Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__["dispatch"])('core/block-editor').updateBlockAttributes(clientId, {
                          poster: mediaInfo.poster,
                          mediaUrl: mediaInfo[videoQuality].transcodedMedia,
                          src: mediaInfo[videoQuality].transcodedMedia,
                          rtBackgroundVideoInfo: data
                        });
                      } else if (isVideoBlock) {
                        Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__["dispatch"])('core/block-editor').updateBlockAttributes(clientId, {
                          poster: mediaInfo.poster,
                          src: mediaInfo[videoQuality].transcodedMedia,
                          rtBackgroundVideoInfo: data
                        });
                      }
                    }
                  }
                });
              }
            }
          })();
        }
      }
    } catch (err) {
      _didIteratorError = true;
      _iteratorError = err;
    } finally {
      try {
        if (!_iteratorNormalCompletion && _iterator.return != null) {
          _iterator.return();
        }
      } finally {
        if (_didIteratorError) {
          throw _iteratorError;
        }
      }
    }
  }
}, 10000);

/***/ }),

/***/ "./admin/js/rt-transcoder-gutenberg-support.js":
/*!*****************************************************!*\
  !*** ./admin/js/rt-transcoder-gutenberg-support.js ***!
  \*****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _rt_transcoder_amp_video_quality__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./rt-transcoder-amp-video-quality */ "./admin/js/rt-transcoder-amp-video-quality.js");


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/asyncToGenerator.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }

  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}

function _asyncToGenerator(fn) {
  return function () {
    var self = this,
        args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);

      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }

      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }

      _next(undefined);
    });
  };
}

module.exports = _asyncToGenerator;

/***/ }),

/***/ "@babel/runtime/regenerator":
/*!**********************************************!*\
  !*** external {"this":"regeneratorRuntime"} ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["regeneratorRuntime"]; }());

/***/ }),

/***/ "@wordpress/api-fetch":
/*!*******************************************!*\
  !*** external {"this":["wp","apiFetch"]} ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["apiFetch"]; }());

/***/ }),

/***/ "@wordpress/block-editor":
/*!**********************************************!*\
  !*** external {"this":["wp","blockEditor"]} ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["blockEditor"]; }());

/***/ }),

/***/ "@wordpress/components":
/*!*********************************************!*\
  !*** external {"this":["wp","components"]} ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["components"]; }());

/***/ }),

/***/ "@wordpress/compose":
/*!******************************************!*\
  !*** external {"this":["wp","compose"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["compose"]; }());

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/hooks":
/*!****************************************!*\
  !*** external {"this":["wp","hooks"]} ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["hooks"]; }());

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
//# sourceMappingURL=rt-transcoder-gutenberg-support.js.map
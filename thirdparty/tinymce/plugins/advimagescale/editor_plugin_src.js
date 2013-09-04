/**
 * TinyMCE Advanced Image Resize Helper Plugin
 *
 * Forces images to maintain aspect ratio while scaling - also optionally enforces
 * min/max image dimensions, and appends width/height to the image URL for server-side
 * resizing
 *
 * @author     Marc Hodgins (modified to remove global variables: http://sourceforge.net/p/tinymce/plugins/186/)
 * @link       http://www.hodginsmedia.com Hodgins Media Ventures Inc.
 * @copyright  Copyright (C) 2008-2010 Hodgins Media Ventures Inc., All right reserved.
 * @license    http://www.opensource.org/licenses/lgpl-3.0.html LGPLv3
 */
(function() {

    tinymce.create('tinymce.plugins.AdvImageScale', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {

            /**
             * Stores pre-resize image dimensions
             * @var {array} (w,h)
             */
            ed.originalDimensions = new Array();

            /**
             * Stores last dimensions before a resize
             * @var {array} (w,h)
             */
            ed.lastDimensions = new Array();

            /**
             * Track mousedown status in editor
             * @var {boolean}
             */
            ed.edMouseDown = false;

            // Watch for mousedown (as a fall through to ensure that prepareImage() definitely
            // got called on an image tag before mouseup).
            // 
            // Normally this should have happened via the onPreProcess/onSetContent listeners, but
            // for completeness we check once more here in case there are edge cases we've missed.
            ed.onMouseDown.add(function(ed, e) {
                var el = tinyMCE.activeEditor.selection.getNode();
                if (el != null && el.nodeName == 'IMG') {
                    // prepare image for resizing
                    prepareImage(ed, e.target);
                }
                return true;
            });
            
            // Watch for mouseup (catch image resizes)
            ed.onMouseUp.add(function(ed, e) {
                var el = tinyMCE.activeEditor.selection.getNode();
                if (el != null && el.nodeName == 'IMG') {
                    // setTimeout is necessary to allow the browser to complete the resize so we have new dimensions
                    setTimeout(function() {
                        constrainSize(ed, el);
                    }, 100);
                }
                return true;
            });

            /*****************************************************
             * ENFORCE CONSTRAINTS ON CONTENT INSERTED INTO EDITOR
             *****************************************************/

            // Catch editor.setContent() events via onPreProcess (because onPreProcess allows us to 
            // modify the DOM before it is inserted, unlike onSetContent)
            ed.onPreProcess.add(function(ed, o) {
                if (!o.set) return; // only 'set' operations let us modify the nodes
                
                // loop in each img node and run constrainSize
                tinymce.each(ed.dom.select('img', o.node), function(currentNode) {
                    constrainSize(ed, currentNode);
                });
            });

            // To be complete, we also need to watch for setContent() calls on the selection object so that
            // constraints are enforced (i.e. in case an <img> tag is inserted via mceInsertContent).
            // So, catch all insertions using the editor's selection object
            ed.onInit.add(function(ed) {
                // http://wiki.moxiecode.com/index.php/TinyMCE:API/tinymce.dom.Selection/onSetContent
                ed.selection.onSetContent.add(function(se, o) {
                    // @todo This seems to grab the entire editor contents - it works but could
                    //       perform poorly on large documents
                    var currentNode = se.getNode();
                    tinymce.each(ed.dom.select('img', currentNode), function (currentNode) {
                        // IF condition required as tinyMCE inserts 24x24 placeholders uner some conditions
                        if (currentNode.id != "__mce_tmp") 
                            constrainSize(ed, currentNode);
                    });
                });
            });

            /*****************************
             * DISALLOW EXTERNAL IMAGE DRAG/DROPS
             *****************************/
            // This is a hack.  Listening for drag events wasn't working.
            // 
            // Watches for mousedown and mouseup/dragdrop events within the editor.  If a mouseup or
            // dragdrop occurs in the editor without a preceeding mousedown, we assume it is an external
            // dragdrop that should be rejected.
            if (ed.getParam('advimagescale_reject_external_dragdrop', true)) {

                // catch mousedowns mouseups and dragdrops (which are basically mouseups too..)
                ed.onMouseDown.add(function(e) { ed.edMouseDown = true; });
                ed.onMouseUp.add(function(e) { ed.edMouseDown = false; });
                ed.onInit.add(function(ed, o) {
                    tinymce.dom.Event.add(ed.getBody().parentNode, 'dragdrop', function(e) { ed.edMouseDown = false; });
                });
    
                // watch for drag attempts
                var evt = (tinymce.isIE) ? 'dragenter' : 'dragover'; // IE allows dragdrop reject on dragenter (more efficient)
                ed.onInit.add(function(ed, o) {
                    // use parentNode to go above editor content, to cover entire editor area
                    tinymce.dom.Event.add(ed.getBody().parentNode, evt, function (e) {
                        if (!ed.edMouseDown) {
                            // disallow drop
                            return tinymce.dom.Event.cancel(e);
                        }
                    });
                });
                
            }
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                longname  : 'Advanced Image Resize Helper',
                author    : 'Marc Hodgins',
                authorurl : 'http://www.hodginsmedia.com',
                infourl   : 'http://code.google.com/p/tinymce-plugin-advimagescale',
                version   : '1.1.3'
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('advimagescale', tinymce.plugins.AdvImageScale);

    /**
     * Store image dimensions, pre-resize
     *
     * @param {object} el HTMLDomNode
     */
    function storeDimensions(ed, el) {
        var dom = ed.dom;
        var elId = dom.getAttrib(el, 'mce_advimageresize_id');

        // store original dimensions if this is the first resize of this element
        if (!ed.originalDimensions[elId]) {
            ed.originalDimensions[elId] = ed.lastDimensions[elId] = {width: dom.getAttrib(el, 'width', el.width), height: dom.getAttrib(el, 'height', el.height)};
        }
        return true;
    }

    /**
     * Prepare image for resizing
     * Check to see if we've seen this IMG tag before; does tasks such as adding
     * unique IDs to image tags, saving "original" image dimensions, etc.
     * @param {object} e is optional
     */
    function prepareImage(ed, el) {
        var dom  = ed.dom;
        var elId = dom.getAttrib(el, 'mce_advimageresize_id');

        // is this the first time this image tag has been seen?
        if (!elId) {
            var elId = ed.id + "_" + ed.dom.uniqueId();
            dom.setAttrib(el, 'mce_advimageresize_id', elId);
            storeDimensions(ed, el);
        }
        
        return elId;
    }

    /**
     * Adjusts width and height to keep within min/max bounds and also maintain aspect ratio
     * If mce_noresize attribute is set to image tag, then image resize is disallowed
     */
    function constrainSize(ed, el, e) {
        var dom     = ed.dom;
        var elId    = prepareImage(ed, el); // also calls storeDimensions
        var resized = (dom.getAttrib(el, 'width') != ed.lastDimensions[elId].width || dom.getAttrib(el, 'height') != ed.lastDimensions[elId].height);

        if (!resized)
            return; // nothing to do

        // disallow image resize if mce_noresize or the noresize class is set on the image tag
        if (dom.getAttrib(el, 'mce_noresize') || dom.hasClass(el, ed.getParam('advimagescale_noresize_class', 'noresize')) || ed.getParam('advimagescale_noresize_all')) {
            dom.setAttrib(el, 'width', ed.lastDimensions[elId].width);
            dom.setAttrib(el, 'height', ed.lastDimensions[elId].height);
            if (tinymce.isGecko)
                fixGeckoHandles(ed);
            return;
        }

        // Both IE7 and Gecko (as of FF3.0.03) has a "expands image by border width" bug before doing anything else
        if (ed.getParam('advimagescale_fix_border_glitch', true /* default to true */)) {
            fixImageBorderGlitch(ed, el);
            storeDimensions(ed, el); // store adjusted dimensions
        }

        // filter by regexp so only some images get constrained
        var src_filter = ed.getParam('advimagescale_filter_src');
        if (src_filter) {
            var r = new RegExp(src_filter);
            if (!el.src.match(r)) {
                return; // skip this element
            }
        }
        
        // allow filtering by classname
        var class_filter = ed.getParam('advimagescale_filter_class');
        if (class_filter) {
            if (!dom.hasClass(el, class_filter)) {
                return; // skip this element, doesn't have the class we want
            }
        }

        // populate new dimensions object
        var newDimensions = { width: dom.getAttrib(el, 'width', el.width), height: dom.getAttrib(el, 'height', el.height) };

        // adjust w/h to maintain aspect ratio
        if (ed.getParam('advimagescale_maintain_aspect_ratio', true /* default to true */)) {
                newDimensions = maintainAspect(ed, el, newDimensions.width, newDimensions.height);
        }
        
        // enforce minW/minH/maxW/maxH
        newDimensions = checkBoundaries(ed, el, newDimensions.width, newDimensions.height);

        // was an adjustment made?
        var adjusted      = (dom.getAttrib(el, 'width', el.width) != newDimensions.width || dom.getAttrib(el, 'height', el.height) != newDimensions.height);
        
        // apply new w/h
        if (adjusted) {
            dom.setAttrib(el, 'width',  newDimensions.width);
            dom.setAttrib(el, 'height', newDimensions.height);
            if (tinymce.isGecko) fixGeckoHandles(ed);
        }

        if (ed.getParam('advimagescale_append_to_url')) {
            appendToUri(ed, el, dom.getAttrib(el, 'width', el.width), dom.getAttrib(el, 'height', el.height));
        }

        // was the image resized?
        if (ed.lastDimensions[elId].width != dom.getAttrib(el, 'width', el.width) || ed.lastDimensions[elId].height != dom.getAttrib(el, 'height', el.height)) {
                // call "image resized" callback (if set)
            if (ed.getParam('advimagescale_resize_callback')) {
                ed.getParam('advimagescale_resize_callback')(ed, el);
            }
        }

        // remember "last dimensions" for next time
            ed.lastDimensions[elId] = { width: dom.getAttrib(el, 'width', el.width), height: dom.getAttrib(el, 'height', el.height) };
    }

    /**
     * Fixes IE7 and Gecko border width glitch
     *
     * Both "add" the border width to an image after the resize handles have been
     * dropped.  This reverses it by looking at the "previous" known size and comparing
     * to the current size.  If they don't match, then a resize has taken place and the browser
     * has (probably) messed it up.  So, we reverse it.  Note, this will probably need to be
     * wrapped in a conditional statement if/when each browser fixes this bug.
     */
    function fixImageBorderGlitch(ed, el) {
        var dom           = ed.dom;
        var elId          = dom.getAttrib(el, 'mce_advimageresize_id');        
        var currentWidth  = dom.getAttrib(el, 'width', el.width);
        var currentHeight = dom.getAttrib(el, 'height', el.height);
        var adjusted      = false;
        
        // if current dimensions do not match what we last saw, then a resize has taken place
        if (currentWidth != ed.lastDimensions[elId].width) {
            var adjustWidth = 0;

            // get computed border left/right widths
            adjustWidth += parseInt(dom.getStyle(el, 'borderLeftWidth', 'borderLeftWidth'));
            adjustWidth += parseInt(dom.getStyle(el, 'borderRightWidth', 'borderRightWidth'));
            
            // reset the width height to NOT include these amounts
            if (adjustWidth > 0) {
                dom.setAttrib(el, 'width', (currentWidth - adjustWidth));
                adjusted = true;
            }
        }
        if (currentHeight != ed.lastDimensions[elId].height) {
            var adjustHeight = 0;

            // get computed border top/bottom widths
            adjustHeight += parseInt(dom.getStyle(el, 'borderTopWidth', 'borderTopWidth'));
            adjustHeight += parseInt(dom.getStyle(el, 'borderBottomWidth', 'borderBottomWidth'));

            if (adjustHeight > 0) {
                dom.setAttrib(el, 'height', (currentHeight - adjustHeight));
                adjusted = true;
            }
        }
        if (adjusted && tinymce.isGecko) fixGeckoHandles(ed);
    }

    /**
     * Fix gecko resize handles glitch
     */
    function fixGeckoHandles(ed) {
        ed.execCommand('mceRepaint', false);
    }

    /**
     * Set image dimensions on into a uri as querystring params
     */
    function appendToUri(ed, el, w, h) {
        var dom  = ed.dom;
        var uri  = dom.getAttrib(el, 'src');
        var wKey = ed.getParam('advimagescale_url_width_key', 'w');
        uri      = setQueryParam(uri, wKey, w);
        var hKey = ed.getParam('advimagescale_url_height_key', 'h');
        uri      = setQueryParam(uri, hKey, h);

        // no need to continue if URL didn't change
        if (uri == dom.getAttrib(el, 'src')) {
            return;
        }
            
        // trigger image loading callback (if set)
        if (ed.getParam('advimagescale_loading_callback')) {
            // call loading callback
            ed.getParam('advimagescale_loading_callback')(el);
        }
        // hook image load(ed) callback (if set)
        if (ed.getParam('advimagescale_loaded_callback')) {
            // hook load event on the image tag to call the loaded callback
            tinymce.dom.Event.add(el, 'load', imageLoadedCallback, {el: el, ed: ed});
        }

        // set new src
        dom.setAttrib(el, 'src', uri);
    }
    
    /**
     * Callback event when an image is (re)loaded
     * @param {object} e Event (use e.target or this.el to access element, this.ed to access editor instance)
     */
    function imageLoadedCallback(e) {
        var el       = this.el; // image element
        var ed       = this.ed; // editor
        var callback = ed.getParam('advimagescale_loaded_callback'); // user specified callback

        // call callback, pass img as param
        callback(el);
        
        // remove callback event
        tinymce.dom.Event.remove(el, 'load', imageLoadedCallback);
    }

    /**
      * Sets URL querystring parameters by appending or replacing existing params of same name
     */
    function setQueryParam(uri, key, value) {
        if (!uri.match(/\?/)) uri += '?';
        if (!uri.match(new RegExp('([\?&])' + key + '='))) {
            if (!uri.match(/[&\?]$/)) uri += '&';
            uri += key + '=' + escape(value);
        } else {
                uri = uri.replace(new RegExp('([\?\&])' + key + '=[^&]*'), '$1' + key + '=' + escape(value));
        }
        return uri;
    }

    /**
     * Returns w/h that maintain aspect ratio
     */
    function maintainAspect(ed, el, w, h) {
        var elId = ed.dom.getAttrib(el, 'mce_advimageresize_id');

        // calculate aspect ratio of original so we can maintain it
            var ratio = ed.originalDimensions[elId].width / ed.originalDimensions[elId].height;
    
        // decide which dimension changed more (percentage),  because that's the
            // one we'll respect (the other we'll adjust to keep aspect ratio)
        var lastW  = ed.lastDimensions[elId].width;
        var lastH  = ed.lastDimensions[elId].height;
        var deltaW = Math.abs(lastW - w);       // absolute
        var deltaH = Math.abs(lastH - h);       // absolute
        var pctW   = Math.abs(deltaW / lastW);  // percentage
        var pctH   = Math.abs(deltaH / lastH);  // percentage

        if (deltaW || deltaH) {
            if (pctW > pctH) {
                // width changed more - use that as the locked point and adjust height
                return { width: w, height: Math.round(w / ratio) };
            } else {
                // height changed more - use that as the locked point and adjust width
                return { width: Math.round(h * ratio), height: h };
            }
        }
        
        // nothing changed
        return { width: w, height: h };
    }

    /**
     * Enforce min/max boundaries
     *
     * Returns true if an adjustment was made
     */
    function checkBoundaries(ed, el, w, h) {

        var elId           = ed.dom.getAttrib(el, 'mce_advimageresize_id');
        var maxW           = ed.getParam('advimagescale_max_width');
        var maxH           = ed.getParam('advimagescale_max_height');
        var minW           = ed.getParam('advimagescale_min_width');
        var minH           = ed.getParam('advimagescale_min_height');
        var maintainAspect = ed.getParam('advimagescale_maintain_aspect_ratio', true);
        var oW             = ed.originalDimensions[elId].width;
        var oH             = ed.originalDimensions[elId].height;
        var ratio          = oW/oH;

        // max
        if (maxW && w > maxW) {
            w = maxW;
            h = maintainAspect ? Math.round(w / ratio) : h;
                }
                if (maxH && h > maxH) {
                    h = maxH;
            w = maintainAspect ? Math.round(h * ratio) : w;
                }

                // min
                if (minW && w < minW) {
                    w = minW;
                    h = maintainAspect ? Math.round(w / ratio) : h;
                }
                if (minH && h < minH) {
                    h = minH;
                    w = maintainAspect ? Math.round(h * ratio) : h;
                }

        return { width: w, height:h };
    }

})();
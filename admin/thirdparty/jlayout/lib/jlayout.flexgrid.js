/**
 * @preserve jLayout Flex Grid Layout - JavaScript Layout Algorithms v0.4
 * Based on: http://www.javaworld.com/javaworld/javatips/jw-javatip121.html
 *
 * Licensed under the new BSD License.
 * Copyright 2008-2009, Bram Stein
 * All rights reserved.
 */
/*global jLayout:true */
(function () {
	jLayout = (typeof jLayout === 'undefined') ? {} : jLayout;

	// The flex grid has a dependency on the grid layout, so please make
	// sure you include the grid layout manager before the flex grid
	// layout manager.
	if (typeof jLayout.grid !== 'undefined') {
		jLayout.flexGrid = function (spec) {
			var my = {},
				that = this.grid(spec, my);

			function zeroArray(a, l) {
				var i = 0;
				for (; i < l; i += 1) {
					a[i] = 0;
				}
				return a;
			}

			function typeLayout(type) {
				return function (container) {
					var i = 0, r = 0, c = 0, nw = 0, nh = 0,
						w = zeroArray([], my.columns),
						h = zeroArray([], my.rows),
						type_size,
						insets = container.insets();
			
					for (i = 0; i < my.items.length; i += 1) {
						r = Math.floor(i / my.columns);
						c = i % my.columns;
						type_size = my.items[i][type + 'Size']();
						if (w[c] < type_size.width) {
							w[c] = type_size.width;
						}
						if (h[r] < type_size.height) {
							h[r] = type_size.height;
						}
					}
					for (i = 0; i < my.columns; i += 1) {
						nw += w[i];
					}
					for (i = 0; i < my.rows; i += 1) {
						nh += h[i];
					}
					return {
						width: insets.left + insets.right + nw + (my.columns - 1) * my.hgap,
						height: insets.top + insets.bottom + nh + (my.rows - 1) * my.vgap
					};
				};
			}

			that.preferred = typeLayout('preferred');
			that.minimum = typeLayout('minimum');
			that.maximum = typeLayout('maximum');

			that.layout = function (container) {
				var i = 0, c = 0, r = 0,
					pd = that.preferred(container),
					sw = container.bounds().width / pd.width,
					sh = container.bounds().height / pd.height,
					w = zeroArray([], my.columns),
					h = zeroArray([], my.rows),
					insets = container.insets(),
					x = insets.left,
					y = insets.top,
					d;

				for (i = 0; i < my.items.length; i += 1) {
					r = Math.floor(i / my.columns);
					c = i % my.columns;
					d = my.items[i].preferredSize();
					d.width = sw * d.width;
					d.height = sh * d.height;

					if (w[c] < d.width) {
						w[c] = d.width;
					}
					if (h[r] < d.height) {
						h[r] = d.height;
					}
				}

				for (c = 0; c < my.columns; c += 1) {
					for (r = 0, y = insets.top; r < my.rows; r += 1) {
						i = r * my.columns + c;
						if (i < my.items.length) {
							my.items[i].bounds({'x': x, 'y': y, 'width': w[c], 'height': h[r]});
							my.items[i].doLayout();
						}
						y += h[r] + my.vgap;
					}
					x += w[c] + my.hgap;
				}
				return container;
			};
			return that;
		};
	}
}());

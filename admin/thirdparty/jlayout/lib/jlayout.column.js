/**
 * @preserve jLayout Column Layout - JavaScript Layout Algorithms v0.1
 *
 * Licensed under the new BSD License.
 * Copyright 2008-2009, Bram Stein
 * All rights reserved.
 */
/*global jLayout */
/*
(function () {
	jLayout = typeof jLayout === 'undefined' ? {} : jLayout;

	jLayout.column = function (options) {	
		var that = {},
			my = {};

		my.hgap = options.hgap || 0;
		my.vgap = options.vgap || 0;
		my.columns = options.columns || 2;
		my.items = options.items || [];
		my.maxHeight = options.maxHeight || -1;

		that.items = function () {
			var r = [];
			Array.prototype.push.apply(r, my.items);
			return r;
		};

		that.layout = function (container) {
			var i = 0, j = 1,
				insets = container.insets(),
				x = insets.left,
				y = insets.top,
				rows = 0,
				width = (container.bounds().width - (insets.left + insets.right) - (my.columns - 1) * my.hgap) / my.columns,
				// TODO: if maxHeight is not available the height should be the height that divides the content equally over all columns.
				height = my.maxHeight,
				itemSize;

	//		container.bounds({'height': height * 4});

			console.log(height);

			for (; i < my.items.length; i += 1) {
				my.items[i].bounds({'width': width});
			}
			for (i = 0; i < my.items.length; i += 1) {
				itemSize = my.items[i].preferredSize();

				if (y + itemSize.height + my.hgap > height) {
					if (j === my.columns) {
						rows += 1;
						y = insets.top + my.hgap + height * rows;
						x = insets.left;
						j = 1;
						
					} else {
						y = insets.top + (rows * height);
						x += width + my.vgap;
						j += 1;
					}
				} 
				my.items[i].bounds({'x': x, 'y': y});
				y += itemSize.height + my.hgap;
				
				my.items[i].doLayout();
			}			

			//console.log(width);
			return container;
		};

		function typeLayout(type) {
			return function (container) {
				return {
					width: 800,
					height: 600
				};
			};
		}

		that.preferred = typeLayout('preferred');
		that.minimum = typeLayout('minimum');
		that.maximum = typeLayout('maximum');	
	
		return that;
	};
})();*/

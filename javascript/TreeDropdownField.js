(function($) {
	$.entwine('ss', function($){
		
		/**
		 * @todo Locale support
		 * @todo Multiselect
		 * @todo Search
		 */
		$('.TreeDropdownField').entwine({
			onmatch: function() {
				this.append('<div class="panel"><div class="tree-holder"></div></div>');
			},
			getPanel: function() {
				return this.find('.panel');
			},
			openPanel: function() {
				var panel = this.getPanel();
				panel.show();
				if(!panel.find('li').length) this.loadTree();
			},
			closePanel: function() {
				this.getPanel().hide();
			},
			togglePanel: function() {
				this[this.getPanel().is(':visible') ? 'closePanel' : 'openPanel']();
			},
			setTitle: function(title) {
				this.find('.title').text(title);
			},
			getTitle: function() {
				return this.find('.title').text();
			},
			setValue: function(val) {
				this.find(':input').val(val);
			},
			getValue: function() {
				return this.find(':input').val();
			},
			loadTree: function() {
				var self = this, treeHolder = $(this.getPanel()).find('.tree-holder');
				this.addClass('loading');
				treeHolder.load(this.attr('href'), {}, function(html, status, xhr) {
					if(status == 'success') {
						$(this)
							.bind('loaded.jstree', function(e, data) {
								var val = self.getValue();
								if(val) data.inst.select_node(treeHolder.find('*[data-id=' + val + ']'));
							})
							.jstree(self.getTreeConfig())
							.bind('select_node.jstree', function(e, data) {
								var node = data.rslt.obj;
								self.setValue($(node).data('id'));
								self.setTitle(data.inst.get_text(node));
								self.closePanel();
							});
					}
					
					self.removeClass('loading');
				});
			},
			getTreeConfig: function() {
				return {
					'core': {
						'initially_open': ['record-0'],
						'animation': 0
					},
					'html_data': {
						// TODO Hack to avoid ajax load on init, see http://code.google.com/p/jstree/issues/detail?id=911
						'data': this.getPanel().find('.tree-holder').html(),
						'ajax': {
							'url': this.attr('href'),
							'data': function(node) {
								return { ID : $(node).data("id") ? $(node).data("id") : 0 , ajax: 1};
							}
						}
					},
					'ui': {
						"select_limit" : 1,
						'initially_select': [this.getPanel().find('.current').attr('id')]
					},
					'themes': {
						'theme': 'apple'
					},
					'plugins': ['html_data', 'ui', 'themes']
				};
			}
		});
		$('.TreeDropdownField *').entwine({
			getField: function() {
				return this.parents('.TreeDropdownField:first');
			}
		});
		$('.TreeDropdownField .editLink').entwine({
			onclick: function(e) {
				this.getField().togglePanel();
			}
		});
	});
}(jQuery));
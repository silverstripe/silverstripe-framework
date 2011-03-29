/**
 * File: ModelAdmin.History.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: .ModelAdmin
		 * 
		 * A simple ajax browser history implementation tailored towards
		 * navigating through search results and different forms loaded into
		 * the ModelAdmin right panels. The logic listens to search and form loading
		 * events, keeps track of the loaded URLs, and will display graphical back/forward
		 * buttons where appropriate. A search action will cause the history to be reset.
		 * 
		 * Note: The logic does not replay save operations or hook into any form actions.
		 * 
		 * Available Events:
		 * - historyAdd
		 * - historyStart
		 * - historyGoFoward
		 * - historyGoBack
		 * 
		 * Todo:
		 *  Switch tab state when re-displaying search forms
		 *  Reload search parameters into forms
		 */
		$('.ModelAdmin').entwine({
			
			/**
			 * Variable: History
			 */
			History: [],
			
			/**
			 * Variable: Future
			 */
			Future: [],
		
			onmatch: function() {
				var self = this;
			
				this._super();
			
				// generate markup
				this.find('#right').prepend(
					'<div class="historyNav">' 
					+ '<a href="#" class="back">&lt; ' + ss.i18n._t('ModelAdmin.HISTORYBACK', 'back') + '</a>'
					+ '<a href="#" class="forward">' + ss.i18n._t('ModelAdmin.HISTORYFORWARD', 'forward') + ' &gt;</a>'
					+ '</div>'
				).find('.back,.forward').hide();
			
				this.find('.historyNav .back').live('click', function() {
					self.goBack();
					return false;
				});

				this.find('.historyNav .forward').live('click', function() {
					self.goForward();
					return false;
				});
			},
		
			/**
			 * Function: redraw
			 */
			redraw: function() {
				this.find('.historyNav .forward').toggle(Boolean(this.getFuture().length > 0));
				this.find('.historyNav .back').toggle(Boolean(this.getHistory().length > 1));
			},
		
			/**
			 * Function: startHistory
			 * 
			 * Parameters:
			 *  (String) url - ...
			 *  (Object) data - ...
			 */
			startHistory: function(url, data) {
				this.trigger('historyStart', {url: url, data: data});
			
				this.setHistory([]);
				this.addHistory(url, data);
			},

			/**
			 * Add an item to the history, to be accessed by goBack and goForward
			 */
			addHistory: function(url, data) {
				this.trigger('historyAdd', {url: url, data: data});
			
				// Combine data into URL
				if(data) {
					if(url.indexOf('?') == -1) url += '?' + $.param(data);
					else url += '&' + $.param(data);
				}
				// Add to history 
				this.getHistory().push(url);
				// Reset future
				this.setFuture([]);
			
				this.redraw();
			},
			
			/**
			 * Function: goBack
			 */
			goBack: function() {
				if(this.getHistory() && this.getHistory().length) {
					if(this.getFuture() == null) this.setFuture([]);

					var currentPage = this.getHistory().pop();
					var previousPage = this.getHistory()[this.getHistory().length-1];

					this.getFuture().push(currentPage);
				
					this.trigger('historyGoBack', {url:previousPage});
				
					// load new location
					$('#Form_EditForm').loadForm(previousPage);
				
					this.redraw();
				}
			},

			/**
			 * Function: goForward
			 */
			goForward: function() {
				if(this.getFuture() && this.getFuture().length) {
					if(this.getFuture() == null) this.setFuture([]);

					var nextPage = this.getFuture().pop();

					this.getHistory().push(nextPage);
				
					this.trigger('historyGoForward', {url:nextPage});
				
					// load new location
					$('#Form_EditForm').loadForm(nextPage);
				
					this.redraw();
				}
			}
		});
	
		/**
		 * Class: #SearchForm_holder form
		 * 
		 * A search action will cause the history to be reset.
		 */
		$('#SearchForm_holder form').entwine({
			onmatch: function() {
				var self = this;
				this.bind('beforeSubmit', function(e) {
					$('.ModelAdmin').startHistory(
						self.attr('action'), 
						self.serializeArray()
					);
				});
				
				this._super();
			}
		});
	
		/**
		 * Class: form[name=Form_ResultsForm] tbody td a
		 * 
		 * We have to apply this to the result table buttons instead of the
		 * more generic form loading.
		 */
		$('form[name=Form_ResultsForm] tbody td a').entwine({
			onclick: function(e) {
				$('.ModelAdmin').addHistory(this.attr('href'));
			}
		});
		
	});
})(jQuery);
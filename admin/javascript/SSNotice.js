/**
* File: SSNotice.js
*
* Replace the old statusMessage/errorMessage
* with a custom library: ss.notice
*
* Allows for concistent notices/message display throughout the CMS
* via simple JavaScript function calls:
* - ss.notice.message(text, [options]);
* - ss.notice.success(text, [options]);
* - ss.notice.warning(text, [details], [options]);
* - ss.notice.error(text, [details], [options]);
**/
(function($, root) {

	// local working version of the library object
	var lib = {};

	/**
	 * Library settings
	 */
	lib.settings = {
		parentSelector: '.cms-content-fields',
		$notice: $('<p class="message"/>'),
		$details: $('<span class="error-details">'+ss.i18n._t('SSNotice.DETAILS', 'Details')+'<span class="content"/></span>'),
		timeOut: 10000
	};

	/**
	 * Create and display notice
	 *
	 * @param object options Options hash
	 */
	lib.show = function(options)
	{
		var settings = this.settings,
				$notice  = settings.$notice.clone(),
				$details = settings.$details.clone()
				;

		// notice text
		options.text = $('<div/>').text(options.text).html(); // Escape HTML entities in text

		if ( options.prefix )
		{
			options.text = '<strong>' + options.prefix + ':</strong> ' + options.text;
		}

		options.type = options.type || 'notice'; // default type = notice
		$notice.html(options.text).addClass(options.type);

		// handle errors/warnings details
		if ( options.details )
		{
			// quick-format details if requested e.g. for PHP debug message
			if ( options.formatDetails )
			{
				options.details = options.details.replace(/\n/g, '<br/>').replace(/\s/g, '&nbsp;');
			}

			// add details to .content span
			$details.find('span.content').append(options.details);
			$notice.append($details); // add details to notice
		}

		// disable auto-remove
		if ( options.static )
		{
			$notice.addClass('static');
		}

		// add notice to DOM
		$(settings.parentSelector).prepend($notice);
	};

	// Shorthand helpers for $.SSNotice.show()

	/**
	 * Displays a normal notice
	 *
	 * @param string text The text to display
	 * @param object options Optional options hash
	 */
	lib.message = function(text, options)
	{
		this.show($.extend({
			text: text
		}, options));
	};

	/**
	 * Displays a success notice
	 *
	 * @param string text The text to display
	 * @param object options Optional options hash
	 */
	lib.success = function(text, options)
	{
		this.show($.extend({
			text: text,
			type: 'good'
		}, options));
	};

	/**
	 * Displays a warning notice
	 *
	 * @param string text The text to display
	 * @param object options Optional options hash
	 */
	lib.warning = function(text, details, options)
	{
		this.show($.extend({
			text:    text,
			prefix:  ss.i18n._t('SSNotice.WARNING', 'Warning'),
			details: details,
			type:    'warning',
			static:  true
		}, options));
	};

	/**
	 * Displays an error notice
	 *
	 * @param string text The text to display
	 * @param object options Optional options hash
	 */
	lib.error = function(text, details, options)
	{
		this.show($.extend({
			text:    text,
			prefix:  ss.i18n._t('SSNotice.ERROR', 'Error'),
			details: details,
			type:    'error',
			static:  true
		}, options));
	};

	// save on ss namespace
	root.ss = root.ss || {};
	root.ss.notice = lib;

	// catch any existing notice e.g. from PHP
	//lib.init();

	// catch any new/existing notice and handles behaviours
	$.entwine('ss.notice', function($)
	{
		$('p.message').entwine({

			onmatch: function(){
				if ( !this.hasClass('static') )
				{
					this._setTimeOut();
				}
				this._setupBehaviour();
			},
			onunmatch: function(){},

			/**
			 * Setup auto-remove timeOut
			 */
			_setTimeOut: function()
			{
				//this.data('timeOut', setTimeout(this._hide, this.settings.timeOut, $notice));
				this.data('timeOut', setTimeout($.proxy(this._hide, this), ss.notice.settings.timeOut));
			},

			/**
			 * Handles notices removal
			 */
			_hide: function()
			{
				this.slideUp().promise().done(function(){
					this.empty().remove();
				});
			},

			/**
			 * Setup notice's behaviours e.g. showing details
			 */
			_setupBehaviour: function()
			{
				this.find('span.error-details').on('click', function(e){
					$(e.target).parents('p.message')._showDetails();
				});
			},

			/**
			 * Display the notice's details in a pop-up
			 */
			_showDetails: function()
			{
				var details = this.find('span.error-details span.content').clone(),
						popUp   = (ss.notice.popUp) ? ss.notice.popUp : window.open('','SilverStripeErrorPopUp');

				popUp.document.title = 'SilverStripe Error';
				$(popUp.document.body).css({'fontFamily': 'monospace'}).html(details.html());
				popUp.focus();
			}

		});
	});

}(jQuery, window));
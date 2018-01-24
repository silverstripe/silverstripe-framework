/**
 * File: LeftAndMain.Ping.js
 */
(function($) {
	// TODO: Coping with an apparent bug in entwine namespacing! Normally $('.cms-container').entwine('ss') would still work even from within the 'ss.ping' namespace.
	// TODO: Best solution for now at least may be to tweak the 'ss' namespace to just 'ss'.
	var containerScope;
	$.entwine('ss', function($){
		containerScope = $('.cms-container');
	});

	$.entwine('ss.ping', function($){

		$('.cms-container').entwine(/** @lends ss.Form_EditForm */{
			/**
			 * Variable: PingIntervalSeconds
			 * (Number) Interval in which /Security/ping will be checked for a valid login session.
			 */
			PingIntervalSeconds: 5*60,

			onadd: function() {
				this._setupPinging();
				this._super();
			},

			/**
			 * Function: _setupPinging
			 *
			 * This function is called by prototype when it receives notification that the user was logged out.
			 * It uses /Security/ping for this purpose, which should return '1' if a valid user session exists.
			 * It opens the login form in a new window if the URL is either unreachable (doesn't return HTTP 200) or doesn't return the expected '1'.
			 */
			_setupPinging: function() {
				var loginWindow;	// Reference to pop-up window generated if/when ping fails.
				var dialog;			// Reference to message dialog, if one is ever opened.

				var onSessionLost = function(xmlhttp, status) {
					// See if we're getting a response that contains a request to re-authenticate. If so, do that now.
					if (xmlhttp.getResponseHeader('X-Reauthenticate')) {
						//TODO: Change to the following line when entwine bug is found/fixed.
						// $('.cms-container').entwine('ss').showLoginDialog();
						containerScope.showLoginDialog();

					} else if(xmlhttp.responseText !== "1") {
						// In this case, the server may not have even responded at all. To be safe, open a new window and
						// show a default message requesting user to login, in case the login window is already open or
						// opens successfully. This is necessary so we don't attempt to load a login iframe to a down
						// server (better to do that in a new window instead).
						var message = 'Please log in and then try again.';

						if (!loginWindow || (loginWindow && loginWindow.closed)) {
							// Window hasn't yet been opened (or has been closed but must be reopened again).
							loginWindow = window.open('Security/login');
							if(!loginWindow) message = 'Please enable pop-ups for this site.';
						}

						// Render message dialog now.
						// TODO: This generic dialog code should be abstracted so it can be reused throughout the system in place of 90's style alert() dialogs.
						dialog = $("#message-dialog");
						window.testDialog = dialog;
						if (dialog.length == 0) {
							dialog = $('<div id="message-dialog" align="center"></div>');
							$('body').append(dialog);
						}
						dialog.html('<h4>' + message + '</h4>');

						dialog.ssdialog({
							autoOpen: true,
							minWidth: 450,
							maxWidth: 450,
							minHeight: 0,
							maxHeight: 0,
							closeOnEscape: true
						});

					} else {
						if (loginWindow || dialog) {
							// Close login window and warning dialog now that they are no longer necessary.
							if (loginWindow) loginWindow.close();
							if (dialog) dialog.remove();
							loginWindow = dialog = null;

							// However, we still want to confirm that the user still has a valid session (in case it timed
							// out server-side). If this fails, we'll detect the presence of X-Reauthenticate and trigger
							// the display of the standard login prompt within the page.
							$.ajax({
								url: 'Security/ping',
								global: false,
								type: 'POST',
								complete: onSessionLost
							});
						}
					}
				};

				// setup pinging for login expiry
				setInterval(function() {
					$.ajax({
						url: 'Security/ping',
						global: false,
						type: 'POST',
						complete: onSessionLost
					});
				}, this.getPingIntervalSeconds() * 1000);
			}
		});
	});
}(jQuery));

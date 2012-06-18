/**
 * File: LeftAndMain.Ping.js
 */
(function($) {
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
			 * It redirects back to the login form if the URL is either unreachable, or returns '0'.
			 */
			_setupPinging: function() {
				var onSessionLost = function(xmlhttp, status) {
					if(xmlhttp.status > 400 || xmlhttp.responseText == 0) {
						// TODO will pile up additional alerts when left unattended
						if(window.open('Security/login')) {
							alert('Please log in and then try again');
						} else {
							alert('Please enable pop-ups for this site');
						}
					}
				};

				// setup pinging for login expiry
				setInterval(function() {
					$.ajax({
						url: 'admin/security/ping',
						global: false,
						complete: onSessionLost
					});
				}, this.getPingIntervalSeconds() * 1000);
			}
		});
	});
}(jQuery));

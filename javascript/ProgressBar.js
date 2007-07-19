/**
 * Create a progress bar that can be updated clientside
 *
 */
ProgressBar = Class.create();
ProgressBar.applyTo('div.ProgressBar');
ProgressBar.prototype = {
	initialize: function() {
		this.statusText = this.getElementsByTagName('p')[0];
		this.progressBar = this.getElementsByTagName('div')[0].getElementsByTagName('div')[0];
		this.progress = 0;
		this.startTime = 0;
		this.lastProgressUpdate = 0;
		this.defaultText = this.statusText.innerHTML;
		this.defaultDisplay = this.style.display;
	},
	
	setProgress: function( progress ) {
		this.progress = progress;
		this.progressBar.style.width = '' + parseInt( progress ) + '%';
		this.progressBar.width = '' + parseInt( progress ) + '%';
		var now = new Date();
		this.lastProgressUpdate = now.getTime();
	},
	
	setText: function( text ) {
		this.statusText.innerHTML = text;
	},
	
	reset: function() {
		this.setProgress(0);
		this.setText( this.defaultText );
		this.style.display = this.defaultDisplay;
	},
	
	estimateTime: function() {
		var time = ( this.lastProgressUpdate - this.startTime ) * ( ( 100 - this.progress ) / this.progress );
		
		if( time < 60000 )
			return Math.round( time / 1000 ) + ' seconds';
		
		if( time < 3600000 )
			return Math.round( time / 60000 ) + ' minutes';
		
		return Math.round( time / 3600000 ) + ' hours';
	},
	
	start: function() {
		var now = new Date();
		this.startTime = now.getTime();
		this.style.display = 'block'; 
	}
}
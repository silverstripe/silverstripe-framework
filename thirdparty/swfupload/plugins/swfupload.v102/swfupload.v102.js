/*
	SWFUpload v1.0.2 Plug-in
	
	This plug in creates API compatibility with SWFUpload v1.0.2.  Many SWFUpload v1.0.2 behaviors are emulated as well.
*/

var SWFUpload;
if (typeof(SWFUpload) === "function") {
	SWFUpload.v102 = {};
	
	SWFUpload.prototype.initSWFUpload = function (init_settings) {
		try {
			this.customSettings = {};	// A container where developers can place their own settings associated with this instance.
			this.settings = {};
			this.eventQueue = [];
			this.movieName = "SWFUpload_" + SWFUpload.movieCount++;
			this.movieElement = null;

			// Setup global control tracking
			SWFUpload.instances[this.movieName] = this;

			// Load the settings.  Load the Flash movie.
			this.initSettings(init_settings);
			this.loadFlash();

			this.displayDebugInfo();

		} catch (ex2) {
			this.debug(ex2);
		}	
	};
	
	SWFUpload.prototype.initSettings = function (init_settings) {
		// Store v1.0.2 settings
		this.customSettings["target"] 					= this.retrieveSetting(init_settings["target"], "");
		this.customSettings["create_ui"] 				= this.retrieveSetting(init_settings["create_ui"], false);
		this.customSettings["browse_link_class"] 		= this.retrieveSetting(init_settings["browse_link_class"], "SWFBrowseLink");
		this.customSettings["upload_link_class"] 		= this.retrieveSetting(init_settings["upload_link_class"], "SWFUploadLink");
		this.customSettings["browse_link_innerhtml"] 	= this.retrieveSetting(init_settings["browse_link_innerhtml"], "<span>Browse...</span>");
		this.customSettings["upload_link_innerhtml"] 	= this.retrieveSetting(init_settings["upload_link_innerhtml"], "<span>Upload</span>");
		this.customSettings["auto_upload"] 				= this.retrieveSetting(init_settings["auto_upload"], false);
		
		// Store v1.0.2 events
		this.customSettings["upload_file_queued_callback"] 		= this.retrieveSetting(init_settings["upload_file_queued_callback"], null);
		this.customSettings["upload_file_start_callback"] 		= this.retrieveSetting(init_settings["upload_file_start_callback"], null);
		this.customSettings["upload_file_complete_callback"] 	= this.retrieveSetting(init_settings["upload_file_complete_callback"], null);
		this.customSettings["upload_queue_complete_callback"] 	= this.retrieveSetting(init_settings["upload_queue_complete_callback"], null);
		this.customSettings["upload_progress_callback"] 		= this.retrieveSetting(init_settings["upload_progress_callback"], null);
		this.customSettings["upload_dialog_cancel_callback"] 	= this.retrieveSetting(init_settings["upload_dialog_cancel_callback"], null);
		this.customSettings["upload_file_error_callback"] 		= this.retrieveSetting(init_settings["upload_file_error_callback"], null);
		this.customSettings["upload_file_cancel_callback"] 		= this.retrieveSetting(init_settings["upload_file_cancel_callback"], null);
		this.customSettings["upload_queue_cancel_callback"] 	= this.retrieveSetting(init_settings["upload_queue_cancel_callback"], null);
		this.customSettings["queue_cancelled_flag"]				= false;

		// Upload backend settings
		this.addSetting("upload_url",		 		init_settings["upload_script"],		  			"");
		this.addSetting("file_post_name",	 		"Filedata");
		this.addSetting("post_params",		 		{});

		// File Settings
		this.addSetting("file_types",			  	init_settings["allowed_filetypes"],				"*.*");
		this.addSetting("file_types_description", 	init_settings["allowed_filetypes_description"], "All Files");
		this.addSetting("file_size_limit",		  	init_settings["allowed_filesize"],				"1024");
		this.addSetting("file_upload_limit",	  	"0");
		this.addSetting("file_queue_limit",		  	"0");

		// Flash Settings
		this.addSetting("flash_url",		  		init_settings["flash_path"],					"swfupload.swf");
		this.addSetting("flash_width",		  		init_settings["flash_width"],					"1px");
		this.addSetting("flash_height",		  		init_settings["flash_height"],					"1px");
		this.addSetting("flash_color",		  		init_settings["flash_color"],					"#000000");

		// Debug Settings
		this.addSetting("debug_enabled", init_settings.debug,  false);

		// Event Handlers
		this.flashReady_handler         = SWFUpload.flashReady;	// This is a non-overrideable event handler

		this.swfUploadLoaded_handler    = SWFUpload.v102.swfUploadLoaded;
		this.fileDialogStart_handler	= SWFUpload.fileDialogStart;
		this.fileQueued_handler			= SWFUpload.v102.fileQueued;
		this.fileQueueError_handler		= SWFUpload.v102.uploadError;
		this.fileDialogComplete_handler	= SWFUpload.v102.fileDialogComplete;
		
		this.uploadStart_handler		= SWFUpload.v102.uploadStart;
		this.uploadProgress_handler		= SWFUpload.v102.uploadProgress;
		this.uploadError_handler		= SWFUpload.v102.uploadError;
		this.uploadSuccess_handler		= SWFUpload.v102.uploadSuccess;
		this.uploadComplete_handler		= SWFUpload.v102.uploadComplete;

		this.debug_handler				= SWFUpload.v102.debug;
		
		// Hook up the v1.0.2 methods
		this.browse = SWFUpload.v102.browse;
		this.upload = SWFUpload.v102.upload;
		this.cancelFile = SWFUpload.v102.cancelFile;
		this.cancelQueue = SWFUpload.v102.cancelQueue;
		this.debugSettings = SWFUpload.v102.debugSettings;
	}

	// Emulate the v1.0.2 events
	SWFUpload.v102.swfUploadLoaded = function() {
		try {
			var target_id = this.customSettings["target"];
			if(target_id !== "" && target_id !== "fileinputs") {
				var self = this;
				var target = document.getElementById(target_id);
				
				if (target != null) {
					// Create the link for uploading
					var browselink = document.createElement("a");
					browselink.className = this.customSettings["browse_link_class"];
					browselink.id = this.movieName + "BrowseBtn";
					browselink.href = "javascript:void(0);";
					browselink.onclick = function() { self.browse(); return false; }
					browselink.innerHTML = this.customSettings["browse_link_innerhtml"];
				
					target.innerHTML = "";
					target.appendChild(browselink);
						
					// Add upload btn if auto upload not used
					if(this.customSettings["auto_upload"] === false) {

						// Create the link for uploading
						var uploadlink = document.createElement("a");
						uploadlink.className = this.customSettings["upload_link_class"];
						uploadlink.id = this.movieName + "UploadBtn";
						uploadlink.href = "#";
						uploadlink.onclick = function() { self.upload(); return false; }
						uploadlink.innerHTML = this.customSettings["upload_link_innerhtml"];
						target.appendChild(uploadlink);
					}
				}
			}
		}
		catch (ex) {
			this.debug("Exception in swfUploadLoaded");
			this.debug(ex);
		}
	}

	SWFUpload.v102.fileQueued = function(file) {
		var stats = this.getStats();
		var total_files = stats.successful_uploads + stats.upload_errors + stats.files_queued;

		var v102fileQueued = this.customSettings["upload_file_queued_callback"];
		if (typeof(v102fileQueued) === "function")  {
			v102fileQueued.call(this, file, total_files);
		}
	}
	
	SWFUpload.v102.fileDialogComplete = function(num_selected) {
		if (!!this.customSettings["auto_upload"]) {
			this.startUpload();
		}
	};
	
	SWFUpload.v102.uploadStart = function (file) {
		var callback = this.customSettings["upload_file_start_callback"];
		var stats = this.getStats();
		var current_file_number = stats.successful_uploads + stats.upload_errors + 1;
		var total_files = stats.successful_uploads + stats.upload_errors + stats.files_queued;
		if (typeof(callback) === "function") {
			callback.call(this, file, current_file_number, total_files);
		}
		
		return true;
	};
	
	SWFUpload.v102.uploadProgress = function (file, bytes_complete, bytes_total) {
		var callback = this.customSettings["upload_progress_callback"];
		if (typeof(callback) === "function") {
			callback.call(this, file, bytes_complete, bytes_total);
		}
	};
	
	SWFUpload.v102.uploadSuccess = function (file, server_data) {
		var callback = this.customSettings["upload_file_complete_callback"];
		if (typeof(callback) === "function") {
			callback.call(this, file, server_data);
		}
	};
	
	SWFUpload.v102.uploadComplete = function (file) {
		var stats = this.getStats();
		
		if (stats.files_queued > 0 && !this.customSettings["queue_cancelled_flag"]) {
			// Automatically start the next upload (if the queue wasn't cancelled)
			this.startUpload();
		} else if (stats.files_queued === 0 && !this.customSettings["queue_cancelled_flag"]) {
			// Call Queue Complete if there are no more files queued and the queue wasn't cancelled
			var callback = this.customSettings["upload_queue_complete_callback"];
			if (typeof(callback) === "function") {
				callback.call(this, file);
			}
		} else {
			// Don't do anything. Remove the queue cancelled flag (if the queue was cancelled it will be set again)
			this.customSettings["queue_cancelled_flag"] = false;
		}
	}
	
	
	SWFUpload.v102.uploadError = function (file, error_code, msg) {
		var translated_error_code = SWFUpload.v102.translateErrorCode(error_code);
		switch (error_code) {
			case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
				var stats = this.getStats();
				var total_files = stats.successful_uploads + stats.upload_errors + stats.files_queued;
				var callback = this.customSettings["upload_file_cancel_callback"];
				if (typeof(callback) === "function") {
					callback.call(this, file, total_files);
				}
				break;
			defaut:
				var error_callback = this.customSettings["upload_file_error_callback"];
				if (error_callback === null || typeof(error_callback) !== "function") {
					SWFUpload.v102.defaultHandleErrors.call(this, translated_error_code, file, msg);
				} else {
					error_callback.call(this, translated_error_code, file, msg);
				}
		}		
	};
	
	SWFUpload.v102.translateErrorCode = function (error_code) {
		var translated_error_code = 0;
		switch (error_code) {
			case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
				translated_error_code = -40;
				break;
			case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
				translated_error_code = -50;
				break;
			case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
				translated_error_code = -30;
				break;
			case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
				translated_error_code = -30;
				break;
			case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
				translated_error_code = -10;
				break;
			case SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL:
				translated_error_code = -20;
				break;
			case SWFUpload.UPLOAD_ERROR.IO_ERROR:
				translated_error_code = -30;
				break;
			case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
				translated_error_code = -40;
				break;
			case SWFUpload.UPLOAD_ERROR.SPECIFIED_FILE_ID_NOT_FOUND:
				translated_error_code = -30;
				break;
			case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
				translated_error_code = -30;
				break;
			case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
				translated_error_code = -10;
				// FIX ME - call the upload_cancelled_callback
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
				translated_error_code = -30;
				break;
		}
		
		return translated_error_code;
	}
	
	// Default error handling.
	SWFUpload.v102.defaultHandleErrors = function(errcode, file, msg) {
		
		switch(errcode) {
			
			case -10:	// HTTP error
				alert("Error Code: HTTP Error, File name: " + file.name + ", Message: " + msg);
				break;
			
			case -20:	// No upload script specified
				alert("Error Code: No upload script, File name: " + file.name + ", Message: " + msg);
				break;
			
			case -30:	// IOError
				alert("Error Code: IO Error, File name: " + file.name + ", Message: " + msg);
				break;
			
			case -40:	// Security error
				alert("Error Code: Security Error, File name: " + file.name + ", Message: " + msg);
				break;

			case -50:	// Filesize too big
				alert("Error Code: Filesize exceeds limit, File name: " + file.name + ", File size: " + file.size + ", Message: " + msg);
				break;
			default:
				alert("Error Code: " + errcode + ". File name: " + file.name + ", Message: " + msg);
		}
		
	};
	
	SWFUpload.v102.debug = function (message) {
		if (this.getSetting("debug_enabled")) {
			if (window.console) {
				window.console.log(message);
			} else {
				alert(message);
			}
		}
	};
	
	
	// Emulate the v1.0.2 function calls
	SWFUpload.v102.browse = function() {
		this.selectFiles();
	};
	SWFUpload.v102.upload = function () {
		this.startUpload();
	};
	SWFUpload.v102.cancelFile = function (file_id) {
		this.cancelUpload(file_id);
	};
	SWFUpload.v102.cancelQueue = function () {
		var stats = this.getStats();
		while (stats["files_queued"] > 0) {
			this.customSettings["queue_cancelled_flag"] = true;
			this.cancelUpload();
			stats = this.getStats();
		}
		
		if (status.in_progress === 0) {
			this.customSettings["queue_cancelled_flag"] = false;
		}
	};
}
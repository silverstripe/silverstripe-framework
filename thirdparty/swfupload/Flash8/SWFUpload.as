/*
* Todo:
* In SWFUpload v3 the file_queue and file_index should be merged. This probably means we'll remove FileItem.id and just
* use indexes everywhere.
* */

import flash.net.FileReferenceList;
import flash.net.FileReference;
import flash.external.ExternalInterface;

import FileItem;
import ExternalCall;
import Delegate;

class SWFUpload {
	// Cause SWFUpload to start as soon as the movie starts
	public static function main():Void
	{
		// Code to attempt to fix "flash script running slowly" error messages
		var counter:Number = 0;
		_root.onEnterFrame = function () {
			if (++counter > 100) counter = 0;
		};

		// Start SWFUpload
		var SWFUpload:SWFUpload = new SWFUpload();
	}
	
	private var build_number:String = "SWFUPLOAD 2.0.1 FP8 2007-12-05 0001";
	
	// State tracking variables
	private var fileBrowserMany:FileReferenceList = new FileReferenceList();
	private var fileBrowserOne:FileReference = null;	// This isn't set because it can't be reused like the FileReferenceList. It gets setup in the SelectFile method

	private var file_queue:Array = new Array();		// holds a list of all items that are to be uploaded.
	private var current_file_item:FileItem = null;	// the item that is currently being uploaded.
	
	private var file_index:Array = new Array();

	private var successful_uploads:Number = 0;		// Tracks the uploads that have been completed
	private var queue_errors:Number = 0;			// Tracks files rejected during queueing
	private var upload_errors:Number = 0;			// Tracks files that fail upload
	private var upload_cancelled:Number = 0;		// Tracks number of cancelled files
	private var queued_uploads:Number = 0;			// Tracks the FileItems that are waiting to be uploaded.
	
	private var valid_file_extensions:Array = new Array();// Holds the parsed valid extensions.
	
	private var file_reference_listener:Object;
	
	// Callbacks
	private var flashReady_Callback:String;
	private var fileDialogStart_Callback:String;
	private var fileQueued_Callback:String;
	private var fileQueueError_Callback:String;
	private var fileDialogComplete_Callback:String;
	
	private var uploadStart_Callback:String;
	private var uploadProgress_Callback:String;
	private var uploadError_Callback:String;
	private var uploadSuccess_Callback:String;

	private var uploadComplete_Callback:String;
	
	private var debug_Callback:String;
	
	// Values passed in from the HTML
	private var movieName:String;
	private var uploadURL:String;
	private var filePostName:String;
	private var uploadPostObject:Object;
	private var fileTypes:String;
	private var fileTypesDescription:String;
	private var fileSizeLimit:Number;
	private var fileUploadLimit:Number = 0;
	private var fileQueueLimit:Number = 0;
	private var debugEnabled:Boolean;

	// Error code "constants"
	// Size check constants
	private var SIZE_TOO_BIG:Number		= 1;
	private var SIZE_ZERO_BYTE:Number	= -1;
	private var SIZE_OK:Number			= 0;
	
	// Queue errors
	private var ERROR_CODE_QUEUE_LIMIT_EXCEEDED:Number 			= -100;
	private var ERROR_CODE_FILE_EXCEEDS_SIZE_LIMIT:Number 		= -110;
	private var ERROR_CODE_ZERO_BYTE_FILE:Number 				= -120;
	private var ERROR_CODE_INVALID_FILETYPE:Number          	= -130;

	// Upload Errors
	private var ERROR_CODE_HTTP_ERROR:Number 					= -200;
	private var ERROR_CODE_MISSING_UPLOAD_URL:Number        	= -210;
	private var ERROR_CODE_IO_ERROR:Number 						= -220;
	private var ERROR_CODE_SECURITY_ERROR:Number 				= -230;
	private var ERROR_CODE_UPLOAD_LIMIT_EXCEEDED:Number			= -240;
	private var ERROR_CODE_UPLOAD_FAILED:Number 				= -250;
	private var ERROR_CODE_SPECIFIED_FILE_ID_NOT_FOUND:Number 	= -260;
	private var ERROR_CODE_FILE_VALIDATION_FAILED:Number		= -270;
	private var ERROR_CODE_FILE_CANCELLED:Number				= -280;
	private var ERROR_CODE_UPLOAD_STOPPED:Number				= -290;

	public function SWFUpload() {
		System.security.allowDomain("*");	// Allow uploading to any domain

		// Setup file FileReferenceList events
		var fbmListener:Object = {
			onSelect : Delegate.create(this, this.Select_Many_Handler),
			onCancel : Delegate.create(this, this.DialogCancelled_Handler)
		}
		this.fileBrowserMany.addListener(fbmListener);

		// Setup the events listner
		this.file_reference_listener = {
			onProgress : Delegate.create(this, this.FileProgress_Handler),
			onIOError  : Delegate.create(this, this.IOError_Handler),
			onSecurityError : Delegate.create(this, this.SecurityError_Handler),
			onHTTPError : Delegate.create(this, this.HTTPError_Handler),
			onComplete : Delegate.create(this, this.FileComplete_Handler)
		};
		
		// Get the move name
		this.movieName = _root.movieName;

		// **Configure the callbacks**
		// The JavaScript tracks all the instances of SWFUpload on a page.  We can access the instance
		// associated with this SWF file using the movieName.  Each callback is accessible by making
		// a call directly to it on our instance.  There is no error handling for undefined callback functions.
		// A developer would have to deliberately remove the default functions,set the variable to null, or remove
		// it from the init function.
		this.flashReady_Callback         = "SWFUpload.instances[\"" + this.movieName + "\"].flashReady";
		this.fileDialogStart_Callback    = "SWFUpload.instances[\"" + this.movieName + "\"].fileDialogStart";
		this.fileQueued_Callback         = "SWFUpload.instances[\"" + this.movieName + "\"].fileQueued";
		this.fileQueueError_Callback     = "SWFUpload.instances[\"" + this.movieName + "\"].fileQueueError";
		this.fileDialogComplete_Callback = "SWFUpload.instances[\"" + this.movieName + "\"].fileDialogComplete";

		this.uploadStart_Callback        = "SWFUpload.instances[\"" + this.movieName + "\"].uploadStart";
		this.uploadProgress_Callback     = "SWFUpload.instances[\"" + this.movieName + "\"].uploadProgress";
		this.uploadError_Callback        = "SWFUpload.instances[\"" + this.movieName + "\"].uploadError";
		this.uploadSuccess_Callback      = "SWFUpload.instances[\"" + this.movieName + "\"].uploadSuccess";

		this.uploadComplete_Callback       = "SWFUpload.instances[\"" + this.movieName + "\"].uploadComplete";

		this.debug_Callback              = "SWFUpload.instances[\"" + this.movieName + "\"].debug";

		// Get the Flash Vars
		this.uploadURL = _root.uploadURL;
		this.filePostName = _root.filePostName;
		this.fileTypes = _root.fileTypes;
		this.fileTypesDescription = _root.fileTypesDescription + " (" + this.fileTypes + ")";
		this.loadPostParams(_root.params);

		
		if (!this.filePostName) {
			this.filePostName = "Filedata";
		}
		if (!this.fileTypes) {
			this.fileTypes = "*.*";
		}
		if (!this.fileTypesDescription) {
			this.fileTypesDescription = "All Files";
		}
		
		this.LoadFileExensions(this.fileTypes);
		
		try {
			this.debugEnabled = _root.debugEnabled == "true" ? true : false;
		} catch (ex:Object) {
			this.debugEnabled = false;
		}

		try {
			this.fileSizeLimit = Number(_root.fileSizeLimit);
			if (this.fileSizeLimit < 0) this.fileSizeLimit = 0;
		} catch (ex:Object) {
			this.fileSizeLimit = 0;
		}

		try {
			this.fileUploadLimit = Number(_root.fileUploadLimit);
			if (this.fileUploadLimit < 0) this.fileUploadLimit = 0;
		} catch (ex:Object) {
			this.fileUploadLimit = 0;
		}

		try {
			this.fileQueueLimit = Number(_root.fileQueueLimit);
			if (this.fileQueueLimit < 0) this.fileQueueLimit = 0;
		} catch (ex:Object) {
			this.fileQueueLimit = 0;
		}

		// Set the queue limit to match the upload limit when the queue limit is bigger than the upload limit
		if (this.fileQueueLimit > this.fileUploadLimit && this.fileUploadLimit != 0) this.fileQueueLimit = this.fileUploadLimit;
		// The the queue limit is unlimited and the upload limit is not then set the queue limit to the upload limit
		if (this.fileQueueLimit == 0 && this.fileUploadLimit != 0) this.fileQueueLimit = this.fileUploadLimit;

		try {
			ExternalInterface.addCallback("SelectFile", this, this.SelectFile);
			ExternalInterface.addCallback("SelectFiles", this, this.SelectFiles);
			ExternalInterface.addCallback("StartUpload", this, this.StartUpload);
			ExternalInterface.addCallback("ReturnUploadStart", this, this.ReturnUploadStart);
			ExternalInterface.addCallback("StopUpload", this, this.StopUpload);
			ExternalInterface.addCallback("CancelUpload", this, this.CancelUpload);
			
			ExternalInterface.addCallback("GetStats", this, this.GetStats);
			ExternalInterface.addCallback("SetStats", this, this.SetStats);
			ExternalInterface.addCallback("GetFile", this, this.GetFile);
			ExternalInterface.addCallback("GetFileByIndex", this, this.GetFileByIndex);
			
			ExternalInterface.addCallback("AddFileParam", this, this.AddFileParam);
			ExternalInterface.addCallback("RemoveFileParam", this, this.RemoveFileParam);

			ExternalInterface.addCallback("SetUploadURL", this, this.SetUploadURL);
			ExternalInterface.addCallback("SetPostParams", this, this.SetPostParams);
			ExternalInterface.addCallback("SetFileTypes", this, this.SetFileTypes);
			ExternalInterface.addCallback("SetFileSizeLimit", this, this.SetFileSizeLimit);
			ExternalInterface.addCallback("SetFileUploadLimit", this, this.SetFileUploadLimit);
			ExternalInterface.addCallback("SetFileQueueLimit", this, this.SetFileQueueLimit);
			ExternalInterface.addCallback("SetFilePostName", this, this.SetFilePostName);
			ExternalInterface.addCallback("SetDebugEnabled", this, this.SetDebugEnabled);
		} catch (ex:Error) {
			this.Debug("Callbacks where not set.");
		}
		
		this.Debug("SWFUpload Init Complete");
		this.PrintDebugInfo();

		// Do some feature detection
		if (flash.net.FileReferenceList && flash.net.FileReference && flash.external.ExternalInterface && flash.external.ExternalInterface.available) {
			ExternalCall.Simple(this.flashReady_Callback);
		} else {
			this.Debug("Feature Detection Failed");
		}
	}

	/* *****************************************
	* FileReference Event Handlers
	* *************************************** */
	private function DialogCancelled_Handler():Void {
		this.Debug("Event: fileDialogComplete: File Dialog window cancelled.");
		ExternalCall.FileDialogComplete(this.fileDialogComplete_Callback, 0);
	}

	private function FileProgress_Handler(file:FileReference, bytesLoaded:Number, bytesTotal:Number):Void {
		this.Debug("Event: uploadProgress: File ID: " + this.current_file_item.id + ". Bytes: " + bytesLoaded + ". Total: " + bytesTotal);
		ExternalCall.UploadProgress(this.uploadProgress_Callback, this.current_file_item.ToJavaScriptObject(), bytesLoaded, bytesTotal);
	}

	private function FileComplete_Handler():Void {
		this.successful_uploads++;
		this.current_file_item.file_status = FileItem.FILE_STATUS_SUCCESS;

		this.Debug("Event: uploadSuccess: File ID: " + this.current_file_item.id + " Data: n/a");
		ExternalCall.UploadSuccess(this.uploadSuccess_Callback, this.current_file_item.ToJavaScriptObject());

		this.UploadComplete();
		
	}

	private function HTTPError_Handler(file:FileReference, httpError:Number):Void {
		this.upload_errors++;
		this.current_file_item.file_status = FileItem.FILE_STATUS_ERROR;

		this.Debug("Event: uploadError: HTTP ERROR : File ID: " + this.current_file_item.id + ". HTTP Status: " + httpError + ".");
		ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_HTTP_ERROR, this.current_file_item.ToJavaScriptObject(), httpError.toString());
		this.UploadComplete();
	}
	
	// Note: Flash Player does not support Uploads that require authentication. Attempting this will trigger an
	// IO Error or it will prompt for a username and password and the crash the browser (FireFox/Opera)
	private function IOError_Handler(file:FileReference):Void {
		this.upload_errors++;
		this.current_file_item.file_status = FileItem.FILE_STATUS_ERROR;

		if(!this.uploadURL.length) {
			this.Debug("Event: uploadError : IO Error : File ID: " + this.current_file_item.id + ". Upload URL string is empty.");
			ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_MISSING_UPLOAD_URL, this.current_file_item.ToJavaScriptObject(), "IO Error");
		} else {
			this.Debug("Event: uploadError : IO Error : File ID: " + this.current_file_item.id + ". IO Error.");
			ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_IO_ERROR, this.current_file_item.ToJavaScriptObject(), "IO Error");
		}

		this.UploadComplete();
	}

	private function SecurityError_Handler(file:FileReference, errorString:String):Void {
		this.upload_errors++;
		this.current_file_item.file_status = FileItem.FILE_STATUS_ERROR;

		this.Debug("Event: uploadError : Security Error : File Number: " + this.current_file_item.id + ". Error:" + errorString);
		ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_SECURITY_ERROR, this.current_file_item.ToJavaScriptObject(), errorString);

		this.UploadComplete();
	}

	private function Select_Many_Handler(frl:FileReferenceList):Void {
		this.Select_Handler(frl.fileList);
	}
	private function Select_One_Handler(file:FileReference):Void {
		var fileArray:Array = new Array(1);
		fileArray[0] = file;
		this.Select_Handler(fileArray);
	}
	
	private function Select_Handler(file_reference_list:Array):Void {
		this.Debug("Select Handler: Files Selected from Dialog. Processing file list");
		this.Debug("Type: " + typeof(this.fileQueueLimit) + " Value:" + this.fileQueueLimit);
		// Determine how many queue slots are remaining (check the unlimited (0) settings, successful uploads and queued uploads)
		var queue_slots_remaining:Number = 0;
		if (this.fileUploadLimit == 0) {
			queue_slots_remaining = (this.fileQueueLimit == 0) ? file_reference_list.length : (this.fileQueueLimit - this.queued_uploads);	// If unlimited queue make the allowed size match however many files were selected.
		} else {
			var remaining_uploads:Number = this.fileUploadLimit - this.successful_uploads - this.queued_uploads;
			if (remaining_uploads < 0) remaining_uploads = 0;
			if (this.fileQueueLimit == 0 || this.fileQueueLimit >= remaining_uploads) {
				queue_slots_remaining = remaining_uploads;
			} else if (this.fileQueueLimit < remaining_uploads) {
				queue_slots_remaining = this.fileQueueLimit;
			}
		}
		
		// Check if the number of files selected is greater than the number allowed to queue up.
		if (queue_slots_remaining < file_reference_list.length) {
			this.Debug("Event: fileQueueError : Selected Files (" + file_reference_list.length + ") exceeds remaining Queue size (" + queue_slots_remaining + ").");
			ExternalCall.FileQueueError(this.fileQueueError_Callback, this.ERROR_CODE_QUEUE_LIMIT_EXCEEDED, null, queue_slots_remaining.toString());
		} else {
			// Process each selected file
			for (var i:Number = 0; i < file_reference_list.length; i++) {
				var file_item:FileItem = new FileItem(file_reference_list[i], this.movieName);
				
				// Add the file to the index
				file_item.index = this.file_index.length;
				this.file_index[file_item.index] = file_item;

				// The the file to see if it is acceptable
				var size_result:Number = this.CheckFileSize(file_item);
				var is_valid_filetype:Boolean = this.CheckFileType(file_item);
				
				if(size_result == this.SIZE_OK && is_valid_filetype) {
					this.Debug("Event: fileQueued : File ID: " + file_item.id);
					this.file_queue.push(file_item);
					this.queued_uploads++;
					ExternalCall.FileQueued(this.fileQueued_Callback, file_item.ToJavaScriptObject());
				}
				else if (!is_valid_filetype) {
					file_item.file_reference = null; 	// Cleanup the object
					this.Debug("Event: fileQueueError : File not of a valid type.");
					this.queue_errors++;
					ExternalCall.FileQueueError(this.fileQueueError_Callback, this.ERROR_CODE_INVALID_FILETYPE, file_item.ToJavaScriptObject(), "File is not an allowed file type.");
				}
				else if (size_result == this.SIZE_TOO_BIG) {
					file_item.file_reference = null; 	// Cleanup the object
					this.Debug("Event: fileQueueError : File exceeds size limit.");
					this.queue_errors++;
					ExternalCall.FileQueueError(this.fileQueueError_Callback, this.ERROR_CODE_FILE_EXCEEDS_SIZE_LIMIT, file_item.ToJavaScriptObject(), "File size exceeds allowed limit.");
				}
				else if (size_result == this.SIZE_ZERO_BYTE) {
					file_item.file_reference = null; 	// Cleanup the object
					this.Debug("Event: fileQueueError : File is zero bytes.");
					this.queue_errors++;
					ExternalCall.FileQueueError(this.fileQueueError_Callback, this.ERROR_CODE_ZERO_BYTE_FILE, file_item.ToJavaScriptObject(), "File is zero bytes and cannot be uploaded.");
				}
				else {
					file_item.file_reference = null; 	// Cleanup the object
					this.Debug("Entered an unexpected state checking the file in Select_Handler");
				}
			}
		}
		
		this.Debug("Event: fileDialogComplete : Finished adding files");
		ExternalCall.FileDialogComplete(this.fileDialogComplete_Callback, file_reference_list.length);
	}

	
	/* ****************************************************************
		Externally exposed functions
	****************************************************************** */
	// Opens a file browser dialog that allows one file to be selected.
	private function SelectFile():Void  {
		this.fileBrowserOne = new FileReference();
		var fbo_listener:Object = {
			onSelect : Delegate.create(this, this.Select_One_Handler),
			onCancel : Delegate.create(this, this.DialogCancelled_Handler)
		}
		this.fileBrowserOne.addListener(fbo_listener);

		// Default file type settings
		var allowed_file_types:String = "*.*";
		var allowed_file_types_description:String = "All Files";

		// Get the instance settings
		if (this.fileTypes.length > 0) allowed_file_types = this.fileTypes;
		if (this.fileTypesDescription.length > 0)  allowed_file_types_description = this.fileTypesDescription;

		this.Debug("Event: fileDialogStart : Browsing files. Single Select. Allowed file types: " + allowed_file_types);
		ExternalCall.Simple(this.fileDialogStart_Callback);

		this.fileBrowserOne.browse([{ description : allowed_file_types_description, extension : allowed_file_types }]);

	}
	
	// Opens a file browser dialog that allows multiple files to be selected.
	private function SelectFiles():Void {
		var allowed_file_types:String = "*.*";
		var allowed_file_types_description:String = "All Files";
		if (this.fileTypes.length > 0) allowed_file_types = this.fileTypes;
		if (this.fileTypesDescription.length > 0)  allowed_file_types_description = this.fileTypesDescription;

		this.Debug("Event: fileDialogStart : Browsing files. Multi Select. Allowed file types: " + allowed_file_types);
		ExternalCall.Simple(this.fileDialogStart_Callback);
		this.fileBrowserMany.browse([{ description : allowed_file_types_description, extension : allowed_file_types }]);
	}


	// Starts uploading.  Checks to see if a file is currently uploading and, if not, starts the upload.
	private function StartUpload(file_id:String):Void {
		if (file_id == undefined) file_id = "";
		
		if (this.current_file_item == null) {
			this.Debug("StartUpload(): Starting Upload: " + (file_id ?  "File ID:" + file_id : "First file in queue"));
			this.StartFile(file_id);
		} else {
			this.Debug("StartUpload(): Upload run already in progress");
		}
	}

	// Cancel the current upload and stops.  Doesn't advance the upload pointer. The current file is requeued at the beginning.
	private function StopUpload():Void {
		if (this.current_file_item != null) {
			// Cancel the upload and re-queue the FileItem
			this.current_file_item.file_reference.cancel();

			this.current_file_item.file_status = FileItem.FILE_STATUS_QUEUED;
			
			// Remove the event handlers
			this.current_file_item.file_reference.removeListener(this.file_reference_listener);

			this.file_queue.unshift(this.current_file_item);
			var js_object:Object = this.current_file_item.ToJavaScriptObject();
			this.current_file_item = null;
			
			ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_UPLOAD_STOPPED, js_object, "Upload Stopped");
			ExternalCall.UploadComplete(this.uploadComplete_Callback, js_object);
			this.Debug("StopUpload(): upload stopped.");
		} else {
			this.Debug("StopUpload(): Upload run not in progress");
		}
	}

	/* Cancels the upload specified by file_id
	 * If the file is currently uploading it is cancelled and the uploadComplete
	 * event gets called.
	 * If the file is not currently uploading then only the uploadCancelled event is fired.
	 * */
	private function CancelUpload(file_id:String):Void {
		var file_item:FileItem = null;
		if (file_id == undefined) file_id = "";
		
		// Check the current file item
		if (this.current_file_item != null && (this.current_file_item.id == file_id || !file_id)) {
				this.current_file_item.file_reference.cancel();
				this.current_file_item.file_status = FileItem.FILE_STATUS_CANCELLED;
				this.upload_cancelled++;
				
				this.Debug("Event: fileCancelled: File ID: " + this.current_file_item.id + ". Cancelling current upload");
				ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_FILE_CANCELLED, this.current_file_item.ToJavaScriptObject(), "File Upload Cancelled.");

				this.UploadComplete(); // <-- this advanced the upload to the next file
		} else if (file_id) {
				// Find the file in the queue
				var file_index:Number = this.FindIndexInFileQueue(file_id);
				if (file_index >= 0) {
					// Remove the file from the queue
					file_item = FileItem(this.file_queue[file_index]);
					file_item.file_status = FileItem.FILE_STATUS_CANCELLED;
					this.file_queue[file_index] = null;
					this.queued_uploads--;
					this.upload_cancelled++;
					

					// Cancel the file (just for good measure) and make the callback
					file_item.file_reference.cancel();
					file_item.file_reference.removeListener(this.file_reference_listener);
					file_item.file_reference = null;

					this.Debug("Event: uploadError : " + file_item.id + ". Cancelling queued upload");
					this.Debug("Event: uploadError : " + file_item.id + ". Cancelling queued upload");
					ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_FILE_CANCELLED, file_item.ToJavaScriptObject(), "File Cancelled");

					// Get rid of the file object
					file_item = null;
				}
		} else {
			// Get the first file and cancel it
			while (this.file_queue.length > 0 && file_item == null) {
				// Check that File Reference is valid (if not make sure it's deleted and get the next one on the next loop)
				file_item = FileItem(this.file_queue.shift());	// Cast back to a FileItem
				if (typeof(file_item) == "undefined") {
					file_item = null;
					continue;
				}
			}
			
			if (file_item != null) {
				file_item.file_status = FileItem.FILE_STATUS_CANCELLED;
				this.queued_uploads--;
				this.upload_cancelled++;
				

				// Cancel the file (just for good measure) and make the callback
				file_item.file_reference.cancel();
				file_item.file_reference.removeListener(this.file_reference_listener);
				file_item.file_reference = null;

				this.Debug("Event: uploadError : " + file_item.id + ". Cancelling queued upload");
				
				ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_FILE_CANCELLED, file_item.ToJavaScriptObject(), "File Cancelled");

				// Get rid of the file object
				file_item = null;
			}
			
		}

	}
	
	private function GetStats():Object {
		return {
			in_progress : this.current_file_item == null ? 0 : 1,
			files_queued : this.queued_uploads,
			successful_uploads : this.successful_uploads,
			upload_errors : this.upload_errors,
			upload_cancelled : this.upload_cancelled,
			queue_errors : this.queue_errors
		};
	}
	private function SetStats(stats:Object):Void {
		this.successful_uploads = (typeof(stats["successful_uploads"]) === "Number") ? stats["successful_uploads"] : this.successful_uploads;
		this.upload_errors = (typeof(stats["upload_errors"]) === "Number") ? stats["upload_errors"] : this.upload_errors;
		this.upload_cancelled = (typeof(stats["upload_cancelled"]) === "Number") ? stats["upload_cancelled"] : this.upload_cancelled;
		this.queue_errors = (typeof(stats["queue_errors"]) === "Number") ? stats["queue_errors"] : this.queue_errors;
	}

	private function GetFile(file_id:String):Object {
		var file:FileItem = null;
		var file_index:Number = this.FindIndexInFileQueue(file_id);
		if (file_index >= 0) {
			file = this.file_queue[file_index];
		} else {
			if (this.current_file_item != null) {
				file = this.current_file_item;
			} else {
				for (var i:Number = 0; i < this.file_queue.length; i++) {
					file = this.file_queue[i];
					if (file != null) break;
				}
			}
		}
		
		if (file == null) {
			return null;
		} else {
			return file.ToJavaScriptObject();
		}
		
	}
	private function GetFileByIndex(index:Number):Object {
		if (index < 0 || index > this.file_index.length - 1) {
			return null;
		} else {
			return this.file_index[index].ToJavaScriptObject();
		}
	}
	
	private function AddFileParam(file_id:String, name:String, value:String):Boolean {
		var file_index:Number = this.FindIndexInFileQueue(file_id);
		if (file_index >= 0) {
			var file_item:FileItem = FileItem(this.file_queue[file_index]);
			
			file_item.AddParam(name, value);
			return true;
		} else {
			return false;
		}
	}
	private function RemoveFileParam(file_id:String, name:String):Boolean {
		var file_index:Number = this.FindIndexInFileQueue(file_id);
		if (file_index >= 0) {
			var file_item:FileItem = FileItem(this.file_queue[file_index]);
			file_item.RemoveParam(name);
			return true;
		} else {
			return false;
		}
	}
	
	private function SetUploadURL(url:String):Void {
		if (typeof(url) !== "undefined" && url !== "") {
			this.uploadURL = url;
		}
	}
	
	private function SetPostParams(post_object:Object):Void {
		if (typeof(post_object) !== "undefined" && post_object !== null) {
			this.uploadPostObject = post_object;
		}
	}
	
	private function SetFileTypes(types:String, description:String):Void {
		this.fileTypes = types;
		this.fileTypesDescription = description;
		
		this.LoadFileExensions(this.fileTypes);
	}

	private function SetFileSizeLimit(bytes:Number):Void {
		if (bytes < 0) bytes = 0;
		this.fileSizeLimit = bytes;
	}
	
	private function SetFileUploadLimit(file_upload_limit:Number):Void {
		if (file_upload_limit < 0) file_upload_limit = 0;
		this.fileUploadLimit = file_upload_limit;
	}
	
	private function SetFileQueueLimit(file_queue_limit:Number):Void {
		if (file_queue_limit < 0) file_queue_limit = 0;
		this.fileQueueLimit = file_queue_limit;
	}
	
	private function SetFilePostName(file_post_name:String):Void {
		if (file_post_name != "") {
			this.filePostName = file_post_name;
		}
	}
	
	private function SetDebugEnabled(debug_enabled:Boolean):Void {
		this.debugEnabled = debug_enabled;
	}
	
	/* *************************************************************
		File processing and handling functions
	*************************************************************** */
	//
	private function StartFile(file_id:String):Void {
		if (file_id == undefined) file_id = "";
		
		// Only upload a file uploads are being processed.
		//   startFile could be called by a file cancellation even when we aren't currently uploading
		if (this.current_file_item != null) {
			this.Debug("StartFile(): Upload already in progress. Not starting another upload.");
		}

		this.Debug("StartFile: " + (file_id ? "File ID: " + file_id : "First file in queue"));

		// Check the upload limit
		if (this.successful_uploads >= this.fileUploadLimit && this.fileUploadLimit != 0) {
			this.Debug("Event: uploadError : Upload limit reached. No more files can be uploaded.");
			ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_UPLOAD_LIMIT_EXCEEDED, null, "The upload limit has been reached.");
			this.current_file_item = null;
			return;
		}
		
		// Get the next file to upload
		if (!file_id) {
			while (this.file_queue.length > 0 && this.current_file_item == null) {
				// Check that File Reference is valid (if not make sure it's deleted and get the next one on the next loop)
				this.current_file_item = FileItem(this.file_queue.shift());	// Cast back to a FileItem
				if (typeof(this.current_file_item) == "undefined") {
					this.current_file_item = null;
					continue;
				}
			}
		} else {
			var file_index:Number = this.FindIndexInFileQueue(file_id);
			if (file_index >= 0) {
				// Set the file as the current upload and remove it from the queue
				this.current_file_item = FileItem(this.file_queue[file_index]);
				this.file_queue[file_index] = null;
			} else {
				this.Debug("Event: uploadError : File ID not found in queue: " + file_id);
				ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_SPECIFIED_FILE_ID_NOT_FOUND, null, "File ID not queued.");
			}
		}


		// Start the upload if we found an item to upload
		if (this.current_file_item != null) {
			// Begin the upload
			this.Debug("Event: uploadStart : File ID: " + this.current_file_item.id);
			ExternalCall.UploadStart(this.uploadStart_Callback, this.current_file_item.ToJavaScriptObject());
		
		}
		// Otherwise we've would have looped through all the FileItems. This means the queue is empty)
		else {
			this.Debug("startFile(): No File Reference found.  There are no files left to upload.\nstartFile(): Ending upload run.");
		}
	}

	// This starts the upload when the user returns TRUE from the uploadStart event.  Rather than just have the value returned from
	// the function we do a return function call so we can use the setTimeout work-around for Flash/JS circular calls.
	private function ReturnUploadStart(start_upload:Boolean):Void {
		if (this.current_file_item == null) {
			this.Debug("ReturnUploadStart called but file was no longer queued. This is okay if the file was stopped or cancelled.");
			return;
		}
		
		if (start_upload) {
			try {
				// Set the event handlers
				this.current_file_item.file_reference.addListener(this.file_reference_listener);
				
				// Upload the file
				var url:String = this.BuildRequest();
				
				this.Debug("startFile(): File Reference found. File accepted by startUpload event.  Starting upload to " + this.uploadURL + " for File ID: " + this.current_file_item.id);
				this.current_file_item.file_reference.upload(url, this.filePostName, false);
			} catch (ex:Error) {
				this.upload_errors++;
				this.current_file_item.file_status = FileItem.FILE_STATUS_ERROR;
				var message:String = ex.name + "\n" + ex.message;
				this.Debug("Event: uploadError(): Unhandled exception: " + message);
				ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_UPLOAD_FAILED, this.current_file_item.ToJavaScriptObject(), message);
				
				this.UploadComplete();
			}
			this.current_file_item.file_status = FileItem.FILE_STATUS_IN_PROGRESS;

		} else {
			this.Debug("Event: uploadError : Call to uploadStart returned false. Not uploading file.");
			
			// Remove the event handlers
			this.current_file_item.file_reference.removeListener(this.file_reference_listener);

			// Re-queue the FileItem
			this.current_file_item.file_status = FileItem.FILE_STATUS_QUEUED;
			var js_object:Object = this.current_file_item.ToJavaScriptObject();
			this.file_queue.unshift(this.current_file_item);
			this.current_file_item = null;
			
			ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_FILE_VALIDATION_FAILED, js_object, "Call to uploadStart return false. Not uploading file.");
			ExternalCall.UploadComplete(this.uploadComplete_Callback, js_object);
			this.Debug("startFile(): upload rejected by startUpload event. File re-queued.");
		}
	}

	// Completes the file upload by deleting it's reference, advancing the pointer.
	// Once this event files a new upload can be started.
	private function UploadComplete():Void {
		var jsFileObj:Object = this.current_file_item.ToJavaScriptObject();
		
		this.current_file_item.file_reference.removeListener(this.file_reference_listener);
		this.current_file_item.file_reference = null;
		
		this.current_file_item = null;
		this.queued_uploads--;

		this.Debug("Event: uploadComplete : Upload cycle complete.");
		ExternalCall.UploadComplete(this.uploadComplete_Callback, jsFileObj);
	}


	/* *************************************************************
		Utility Functions
	*************************************************************** */
	// Check the size of the file against the allowed file size.
	private function CheckFileSize(file_item:FileItem):Number {
		if (file_item.file_reference.size == 0) {
			return this.SIZE_ZERO_BYTE;
		} else if (this.fileSizeLimit != 0 && file_item.file_reference.size > (this.fileSizeLimit * 1000)) {
			return this.SIZE_TOO_BIG;
		} else {
			return this.SIZE_OK;
		}
	}
	
	private function CheckFileType(file_item:FileItem):Boolean {
		// If no extensions are defined then a *.* was passed and the check is unnecessary
		if (this.valid_file_extensions.length == 0) {
			return true;
		}
		
		var fileRef:FileReference = file_item.file_reference;
		var last_dot_index:Number = fileRef.name.lastIndexOf(".");
		var extension:String = "";
		if (last_dot_index >= 0) {
			extension = fileRef.name.substr(last_dot_index + 1).toLowerCase();
		}
		
		var is_valid_filetype:Boolean = false;
		for (var i:Number=0; i < this.valid_file_extensions.length; i++) {
			if (String(this.valid_file_extensions[i]) == extension) {
				is_valid_filetype = true;
				break;
			}
		}
		
		return is_valid_filetype;
	}

	private function BuildRequest():String {
		// Create the request object
		var file_post:Object = this.current_file_item.GetPostObject();
		var key:String;

		var url:String = this.uploadURL;
		
		var pairs:Array = new Array();
		for (key in this.uploadPostObject) {
			this.Debug("Global URL Item: " + key + "=" + this.uploadPostObject[key]);
			if (this.uploadPostObject.hasOwnProperty(key)) {
				pairs.push(key + "=" + this.uploadPostObject[key]);
			}
		}

		for (key in file_post) {
			this.Debug("File Post Item: " + key + "=" + this.uploadPostObject[key]);
			if (file_post.hasOwnProperty(key)) {
				pairs.push(escape(key) + "=" + escape(file_post[key]));
			}
		}
		
		url = this.uploadURL  + (this.uploadURL.indexOf("?") > -1 ? "&" : "?") + pairs.join("&");
			
		return url;
	}
	
	private function Debug(msg:String):Void {
		if (this.debugEnabled) {
			var lines:Array = msg.split("\n");
			for (var i:Number=0; i < lines.length; i++) {
				lines[i] = "SWF DEBUG: " + lines[i];
			}
			try {
				ExternalCall.Debug(this.debug_Callback, lines.join("\n"));
			} catch (ex:Error) {
				// pretend nothing happened
			}
		}
	}

	private function PrintDebugInfo():Void {
		var debug_info:String = "\n----- SWF DEBUG OUTPUT ----\n"
			+ "Build Number:           " + this.build_number + "\n"
			+ "movieName:              " + this.movieName + "\n"
			+ "Upload URL:             " + this.uploadURL + "\n"
			+ "File Types String:      " + this.fileTypes + "\n"
			+ "Parsed File Types:      " + this.valid_file_extensions.toString() + "\n"
			+ "File Types Description: " + this.fileTypesDescription + "\n"
			+ "File Size Limit:        " + this.fileSizeLimit + "\n"
			+ "File Upload Limit:      " + this.fileUploadLimit + "\n"
			+ "File Queue Limit:       " + this.fileQueueLimit + "\n"
			+ "Post Params:\n";
		for (var key:String in this.uploadPostObject) {
			debug_info += "                        " + key + "=" + this.uploadPostObject[key] + "\n";
		}
		debug_info += "----- END SWF DEBUG OUTPUT ----\n";

		this.Debug(debug_info);
	}

	private function FindIndexInFileQueue(file_id:String):Number {
		for (var i:Number = 0; i<this.file_queue.length; i++) {
			var item:FileItem = this.file_queue[i];
			if (item != null && item.id == file_id) return i;
		}

		return -1;
	}
	
	// Parse the file extensions in to an array so we can validate them agains
	// the files selected later.
	private function LoadFileExensions(filetypes:String):Void {
		var extensions:Array = filetypes.split(";");
		this.valid_file_extensions = new Array();

		for (var i:Number=0; i < extensions.length; i++) {
			var extension:String = String(extensions[i]);
			var dot_index:Number = extension.lastIndexOf(".");
			
			if (dot_index >= 0) {
				extension = extension.substr(dot_index + 1).toLowerCase();
			} else {
				extension = extension.toLowerCase();
			}
			
			// If one of the extensions is * then we allow all files
			if (extension == "*") {
				this.valid_file_extensions = new Array();
				break;
			}
			
			this.valid_file_extensions.push(extension);
		}
	}
	
	private function loadPostParams(param_string:String):Void {
		var post_object:Object = {};

		if (param_string != null) {
			var name_value_pairs:Array = param_string.split("&");
			
			for (var i:Number = 0; i < name_value_pairs.length; i++) {
				var name_value:String = String(name_value_pairs[i]);
				var index_of_equals:Number = name_value.indexOf("=");
				if (index_of_equals > 0) {
					post_object[unescape(name_value.substring(0, index_of_equals))] = unescape(name_value.substr(index_of_equals + 1));
				}
			}
		}
		this.uploadPostObject = post_object;
	}

}
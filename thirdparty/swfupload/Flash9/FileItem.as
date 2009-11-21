package {
	import flash.net.FileReference;

	internal class FileItem
	{
		private static var file_id_sequence:Number = 0;		// tracks the file id sequence

		private var postObject:Object;
		public var file_reference:FileReference;
		public var id:String;
		public var index:Number = -1;
		public var file_status:int = 0;
		private var js_object:Object;
		
		public static var FILE_STATUS_QUEUED:int		= -1;
		public static var FILE_STATUS_IN_PROGRESS:int	= -2;
		public static var FILE_STATUS_ERROR:int			= -3;
		public static var FILE_STATUS_SUCCESS:int		= -4;
		public static var FILE_STATUS_CANCELLED:int		= -5;
		public static var FILE_STATUS_NEW:int			= -6;	// This file status should never be sent to JavaScript
		
		public function FileItem(file_reference:FileReference, control_id:String)
		{
			this.postObject = {};
			this.file_reference = file_reference;
			this.id = control_id + "_" + (FileItem.file_id_sequence++);
			this.file_status = FileItem.FILE_STATUS_NEW;
			
			this.js_object = {
				id: this.id,
				index: this.index,
				post: this.GetPostObject()
			};
			
			// Cleanly attempt to retrieve the FileReference info
			// this can fail and so is wrapped in try..catch
			try {
				this.js_object.name = this.file_reference.name;
				this.js_object.size = this.file_reference.size;
				this.js_object.type = this.file_reference.type;
				this.js_object.creationdate = this.file_reference.creationDate;
				this.js_object.modificationdate = this.file_reference.modificationDate;
			} catch (ex:Error) {
				this.file_status = FileItem.FILE_STATUS_ERROR;
			}
			
			this.js_object.filestatus = this.file_status;
		}
		
		public function AddParam(name:String, value:String):void {
			this.postObject[name] = value;
		}
		
		public function RemoveParam(name:String):void {
			delete this.postObject[name];
		}
		
		public function GetPostObject():Object {
			return this.postObject;
		}
		
		// Create the simply file object that is passed to the browser
		public function ToJavaScriptObject():Object {
			this.js_object.file_status = this.file_status;
			this.js_object.post = this.GetPostObject();
		
			return this.js_object;
		}
		
		public function toString():String {
			return "FileItem - ID: " + this.id;
		}
	}
}
<?php
/**
 * Daily task to send queued email.
 * @package sapphire
 * @subpackage email
 */
class QueuedEmailDispatchTask extends DailyTask {
	
	public function process() {
		if(ini_get("safe_mode") != "1") {
			set_time_limit(0);
		}
		
		echo "SENDING QUEUED EMAILS\n";
		
		$queued = DataObject::get('QueuedEmail', "`Send` < NOW()");
		
		if( !$queued )
			return;
			
		foreach( $queued as $data ) {
			
			if( !$data->canSendEmail() )
				continue;
			
			$data->send();
			echo 'Sent to: ' . $data->To()->Email . "\n";
			
			$data->delete();
		}
	}
	
}
?>
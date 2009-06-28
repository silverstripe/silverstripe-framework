<?php
/**
 * Daily task to send queued email.
 * @package sapphire
 * @subpackage email
 */
class QueuedEmailDispatchTask extends DailyTask {
	
	public function process() {
		increase_time_limit_to();
		
		echo "SENDING QUEUED EMAILS\n";
		
		$queued = DataObject::get('QueuedEmail', "\"Send\" < " . DB::getConn()->now());
		
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
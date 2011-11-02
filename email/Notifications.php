<?php
/**
 * The Notifications class allows you to create email notifications for various events.
 * It lets your scripts generate a number of notifications, and delay sending of the emails until
 * the end of execution, so that multiple notifications can collated together
 * @package cms
 */
class Notifications extends Object {
	protected static $events = array();
	
	/**
	 * Raise an event that requires notification.
	 * @param eventType A string used to identify different event types.  You can refer back to the events
	 * raised by this eventType.
	 * @param item An object related to the notification, such as a database record.
	 * @param notifyMemberID A person to notify via email about the event.  Events won't be notified by
	 * email until you call {@link notifyByEmail()}
	 */
	static function event($eventType, $item, $notifyMemberID) {
		Notifications::$events[$eventType][$notifyMemberID][] = $item;
	}
	
	/**
	 * Notify the appropriate parties about all instances of this event, by email.
	 * @param eventType A string, this should match the eventType passed to {@link event()}
	 * @param emailTemplateClass The class-name of the email template to use.
	 */
	
	static function notifyByEmail($eventType, $emailTemplateClass) {
		$count = 0;
		if(class_exists($emailTemplateClass)) {
			foreach(Notifications::$events[$eventType] as $memberID => $items) {
				if($memberID) {
					$email = new $emailTemplateClass();
					$email->populateTemplate(new ArrayData(array(
						"Recipient" => DataObject::get_by_id("Member", $memberID),
						"BrokenPages" => new DataObjectSet($items),			
					)));
					$email->debug();
					$email->send();
					$count++;
				}
			}
		}
		return $count;
	}
	
	/**
	 * Get all the items that were passed with this event type.
	 * @param eventType A string, this should match the eventType passed to {@link event()}
	 */
	static function getItems($eventType) {
		$allItems = array();
		if(isset(Notifications::$events[$eventType])) {
			foreach(Notifications::$events[$eventType] as $memberID => $items) {
				$allItems = array_merge($allItems, (array)$items);
			}
		}
		return $allItems;
	}

}

?>
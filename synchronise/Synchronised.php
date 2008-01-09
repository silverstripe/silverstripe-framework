<?php

/**
 * @package sapphire
 * @subpackage synchronisation
 */

/**
 * Functions to provide synchronisation between two Silverstripe implementations. This allows the same entry to have two different
 * IDs on each installation
 * @package sapphire
 * @subpackage synchronisation
 */
class Synchronised extends DataObjectDecorator {

	// URLs to send and receive updates to
	static $receiveURL;
	static $sendURL;
	
	/**
	 * Make any required changes to the database schema
	 * 
	 * Synchronised data objects store the time they were last updated, their foreign ID, their local ID and their class
	 */
	function augmentDatabase() {
		DB::requireTable( $this->owner->class . '_sync', array(
			'LocalID' => 'Int',
			'ForeignID' => 'Int',
			'NeedsUpdate' => 'Boolean'
			/*'ClassName' => 'Varchar'*/
		), array(
			'LocalID' => 'unique (LocalID)',
			'ForeignID' => true,
			'NeedsUpdate' => true
			/*'ClassName' => true*/
		));
	}
	
	// Don't need to do anything
	function augmentSQL(SQLQuery &$query) {
		
	}
	
	/**
	 * Update the necessary tables when a record is updated
	 */
	function augmentWrite(&$manipulation) {
		// TODO Changed by ischommer, "replace" is not a valid command?!
		return true;
		
		$data['fields']['LocalID'] = $this->owner->ID;
		$data['fields']['NeedsUpdate'] = 1;
		$data['where'] = "`LocalID`=" . $this->owner->ID;
		$data['command'] = 'replace';
		
		$manipulation[$this->owner->class . '_sync'] = $data;
	}
	
	/**
	 * Initiate an update with the receiving URL. This will retrieve all objects that have been updated since their last 
	 * synch and send it to the receiving URL. The initiating installation has priority.
	 */
	static function update() {
		
		$objects = self::getUpdatedObjects();
		
		$xml = <<<XML
<?xml version="1.0" standalone="yes" ?>
<synchronise>
XML;
		
		foreach( array_keys( $objects ) as $class )
			if( $objects[$class] )
				foreach( $objects[$class] as $object ) {
					$xml .= $object->serialiseAsXML();
					$objSent++;	
				}
		
		$xml .= "</synchronise>";
		
		preg_match( '/([^\/]+)(.*)[?]?(?:.*)$/', self::$sendURL, $urlParts );
		
		$host = $urlParts[1];
		$path = $urlParts[2];
		$query = $urlParts[3];
		
		$response = HTTP::sendPostRequest( $host, $path . '/Synchronise/receive', $xml, 'synchronise' );
		
		list( $header, $response ) = explode( "\r\n\r\n", $response, 2 );
 		
		Debug::show( $response );
		
		$element = new SimpleXMLElement( $response );
		
		if( $element->mapping ) {
			foreach( $element->mapping as $mapping )
				self::updateMapping( $mapping );
		}
		
		echo "Sent $objSent objects";
	}
	
	static function map() {
		$data = file_get_contents( $_FILES['synchronise']['tmp_name'] );
			
		$element = new SimpleXMLElement( $data );
		
		if( $element->mapping ) {
			foreach( $element->mapping as $mapping )
				self::updateMapping( $mapping );
		}
	}
	
	static function updateMapping( $element ) {
		$tableName = ((string)$element['classname']) . '_sync';
		$foreignID = (string)$element['id'];
		$localID = (string)$element['foreignid'];
		
		DB::query("UPDATE `$tableName` SET `ForeignID` = $foreignID, `NeedsUpdate` = 0 WHERE `LocalID` = $localID");
	}
	
	static $receivedObjects;
	static $waitingForObject;
	
	/**
	 * Receive updated objects and respond with the foreign mappings for these objects
	 */
	static function receive() {
		
		// get the data from the files array
		$data = $_REQUEST['synchronise'];
		
		$xml = new SimpleXMLElement( trim( $data ) );
		
		echo '<?xml version="1.0" standalone="yes" ?><synchronise>';
		
		if( $xml->object ) {
			$mappings = array();
			
			foreach( $xml->object as $object ) {
				list( $localID, $foreignID, $className ) = self::receiveObject( $object );
				$mappings[$className][$foreignID] = $localID;
			}
			
			self::updateManyManyRelations();
			
			if( count( $mappings ) > 0 ) {
				
				
				foreach( $mappings as $className => $array )
					foreach( $array as $foreignID => $localID )
						echo '<mapping classname="' . Convert::raw2att( $className ) . '" id="' . ((string)$localID) . '" foreignid="' . ((string)$foreignID) . '"/>';
			}
		}
		
		echo '</synchronise>';
	}
	
	/**
	 * Initiates a request to receive all updated objects from the server
	 */
	static function get() {
		
		preg_match( '/([^\/]+)(.*)[?]?(?:.*)$/', self::$sendURL, $urlParts );
		
		$host = $urlParts[1];
		$path = $urlParts[2];
		$query = $urlParts[3];
		
		
		$response = HTTP::sendRequest( $host, $path . '/Synchronise/send', $query );
		
		$element = new SimpleXMLElement( $response );
		
		$mappings = array();
		
		$xml = '<?xml version="1.0" standalone="yes" ?><synchronise>';
		
		if( $element->object ) {
			
			
			foreach( $element->object as $object ) {
				list( $localID, $foreignID, $className ) = self::receiveObject( $object );
					$xml .= '<mapping class="' . Convert::raw2att( $className ) . '" id="' . ((string)$localID) . '" foreignid="' . ((string)$foreignID) . '"/>';
			}
		}
		
		$xml .= '</synchronise>';
		
		HTTP::sendPostRequest( $host, $path . '/Synchronise/map', $xml, 'synchronise' );
	}
	
	static function send() {
		
	}
	
	/**
	 * Write the owner object to XML so that it can be sent to a client installation
	 */
	function serialiseAsXML() {
		
		if( !is_a( $this->owner, 'DataObject' ) )
			return '';
		
		$xml = '<object id="' . $this->owner->ID . '" classname="' . Convert::raw2att( $this->owner->class ) . '">';

		if( is_array( $this->owner->db() ) ) {
			foreach( $this->owner->db() as $field => $type )
				if( $fieldObj = $this->owner->obj($field) )
					$xml .= '<db name="' . Convert::raw2att( $field ) . '">' . $fieldObj->XML() . '</db>';
		}
		
		if( is_array( $this->owner->has_one() ) ) {
			foreach( $this->owner->has_one() as $field => $type ) {
				$idField = $field . 'ID';
				$xml .= '<hasOne name="' . Convert::raw2att( $field ) . '">' . ( (string)$this->owner->$idField ) . '</hasOne>';
			}
		}
		
		// If we need to do anything for a has_many, do it here
		if( is_array( $this->owner->has_many() ) ) {
			
		}
		
		if( is_array( $this->owner->many_many() ) ) {
			foreach( $this->owner->many_many() as $field => $type ) {
				if( $componentSet = $this->owner->$field() ) {
					
					$ids = $componentSet->getIdList();
					
					if( $ids )
						foreach( $ids as $id )
							$xml .= '<manyMany name="' . Convert::raw2att( $field ) . '">' . ( (string)$id ) . '</manyMany>';
				}
			}
		}
		
		$xml .= '</object>';
		
		return $xml;
	}
	
	static $recvManyManyRelations;
	
	/**
	 * Receive an XML fragment and update the database
	 */
	static function receiveObject( $element ) {
		
		if( $element->getName() !== 'object' )
			return;
		
		
		
		$className = (string)$element['classname'];
		$foreignID = (string)$element['id'];
		
		// retrieve the local object
		if( $foreignID ) {
			$objects = DataObject::get( $className, "`{$className}_sync`.`ForeignID`=$foreignID", "", "LEFT JOIN `{$className}_sync` ON `LocalID` = `$className`.`ID`", 1 );
			
			if( $objects ) 
				$object = $objects->First();
		}

		if( !$object ) {
			$object = new $className();
			$object->write();
	
			DB::query("UPDATE `{$className}_sync` SET `ForeignID` = $foreignID, `NeedsUpdate` = 0 WHERE `LocalID` = " . $object->ID );
		}

		
		ob_start();

		// Update the fields
		if( $dbFields = $element->xpath('db') )
			foreach( $dbFields as $dbElement ) {
				$fieldName = ((string)$dbElement['name']);
				$object->$fieldName = (string)$dbElement;
				var_export((string)$dbElement); 
			} 

		// Update the fields
		if( $hasOneElements = $element->xpath('hasOne') )
			foreach( $hasOneElements as $dbElement ) {
				$fieldName = ((string)$dbElement['name']) . 'ID';
				$value = (string)$dbElement;

				// IDs will not necessarily match on each installation. We need to wait until we've received the object that this field
				// references
				if( $recvObj = self::$receivedObjects[$value] ) {
					$object->$fieldName = $recvObj->ID;
				} else {
					self::$waitingForObject[$value][] = array(
						'object' => $object,
						'srcField' => 'ID',
						'destField' => $fieldName
					);
				}
			}

		// Update the fields
		if( $manyManyElements = $element->xpath('/manyMany') ) {

			$manyMany = array();

			foreach( $manyManyElements as $dbElement ) {
				$fieldName = $dbElement['name'];
				$manyMany[$fieldName][] = (string)$dbElement;
			}

			foreach( $manyMany as $relation => $ids )
				self::$recvManyManyRelations[] = array(
					'object' => $object,
					'relation' => $relation,
					'ids' => $ids
				);

				// $object->$relation()->setByIDList( $ids );
		}

		$dbg = ob_get_contents();
		ob_end_clean();

		self::$receivedObjects[$foreignID] = $object;

		// Now that this object has been received, any objects waiting on it can be updated
		if( self::$waitingForObject[$foreignID] ) {
			foreach( self::$waitingForObject[$foreignID] as $waiting ) {			
				$waitingObj = $waiting['object'];
				$waitField = $waiting['destField'];
				$srcField = $waiting['srcField'];

				$waitingObj->$waitField = $object->$srcField;
				$waitingObj->write();
			}

			unset( self::$waitingForObject[$foreignID] );
		}

		$object->write();

		// DB::query("UPDATE `{$className}_sync` SET `LastSync` = NOW() WHERE `LocalID` = " . $object->ID );

		return array( $object->ID, $foreignID, $className ); 
	}
	
	/**
	 * Local many-many relations will not map to foreign many-many relations. All ids must be updated and this should be done
	 * after all objects have been processed
	 */
	static function updateManyManyRelations() {
		
		if( !self::$recvManyManyRelations )
			return;

		foreach( self::$recvManyManyRelations as $relation ) {
			$object = $relation['object'];
			$relName = $relation['relation'];
			
			$localIDs = array();

			foreach( $relation['ids'] as $foreignID )
				$localIDs[] = self::$receivedObjects[$foreignID]->ID;

			$object->$relName()->setByIdList( $localIDs );
		}

	}

	/**
	 * Retrieve all objects that have been updated since the last synchronisation
	 */
	static function getUpdatedObjects() {
		
		// Find all classes that can be synched
		$allClasses = ClassInfo::subclassesFor('DataObject');
		
		foreach( $allClasses as $class ) {
			
			$instance = singleton($class);
			if( is_array( $instance->stat('extensions') ) && in_array( 'Synchronised', $instance->stat('extensions') ) )
				$synchronised[$class] = true;
		}
		
		// get all updated objects
		foreach( array_keys( $synchronised ) as $class )
			$synchronised[$class] = DataObject::get($class, /*"`{$class}_sync`.`NeedsUpdate` = 1"*/ "", "", "LEFT JOIN `{$class}_sync` ON `LocalID` = `$class`.`ID`");
		
		return $synchronised;
	}
}
?>
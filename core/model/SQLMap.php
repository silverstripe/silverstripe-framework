<?php
/**
 * This is a class used to represent key->value pairs generated from database queries.
 * The query isn't actually executed until you need it.
 * 
 * @package sapphire
 * @subpackage model
 */
class SQLMap extends Object implements IteratorAggregate {
	/**
	 * The query used to generate the map.
	 * @var SQLQuery
	 */
	protected $query;
	protected $keyField, $titleField;
	
	/**
	 * Construct a SQLMap.
	 * @param SQLQuery $query The query to generate this map. THis isn't executed until it's needed.
	 */
	public function __construct(SQLQuery $query, $keyField = "ID", $titleField = "Title") {
		if(!$query) {
			user_error('SQLMap constructed with null query.', E_USER_ERROR);
		}
		
		$this->query = $query;
		$this->keyField = $keyField;
		$this->titleField = $titleField;
		
		parent::__construct();
	}
	
	/**
	 * Get the name of an item.
	 * @param string|int $id The id of the item.
	 * @return string
	 */
	public function getItem($id) {
		if($id) {
			$baseTable = reset($this->query->from);
			$this->query->where[] = "$baseTable.\"ID\" = $id";
			$record = $this->query->execute()->first();
			if($record) {
				$className = $record['ClassName'];
				$obj = new $className($record);
				return $obj->Title;
			}
		}
	}
	
	public function getIterator() {
		$this->genItems();
		return new SQLMap_Iterator($this->items->getIterator(), $this->keyField, $this->titleField);
	}
	
	/**
	 * Get the items in this class.
	 * @return DataObjectSet
	 */
	public function getItems() {
		$this->genItems();
		return $this->items;
	}
	
	/**
	 * Generate the items in this map. This is used by
	 * getItems() if the items have not been generated already.
	 */
	protected function genItems() {
		if(!isset($this->items)) {
			$this->items = new DataObjectSet();
			$items = $this->query->execute();	
			
			foreach($items as $item) {
				$className = isset($item['RecordClassName'])  ? $item['RecordClassName'] :  $item['ClassName'];

				if(!$className) {
					user_error('SQLMap query could not retrieve className', E_USER_ERROR);
				}
				
				$this->items->push(new $className($item));
			}
		}
	}
}

/**
 * @package sapphire
 * @subpackage model
 */
class SQLMap_Iterator extends Object implements Iterator {
	protected $items;
	protected $keyField, $titleField;
	
	function __construct(Iterator $items, $keyField, $titleField) {
		$this->items = $items;
		$this->keyField = $keyField;
		$this->titleField = $titleField;
	}

	
	/*
	 * Iterator functions - necessary for foreach to work
	 */
	public function rewind() {
		return $this->items->rewind() ? $this->items->rewind()->{$this->titleField} : null;
	}
	
	public function current() {
		return $this->items->current()->{$this->titleField};
	}
	
	public function key() {
		return $this->items->current()->{$this->keyField};
	}
	
	public function next() {
		$next = $this->items->next();
		return isset($next->{$this->titleField}) ? $next->{$this->titleField} : null;
	}
	
	public function valid() {
	 	return $this->items->valid();
	}
}

?>
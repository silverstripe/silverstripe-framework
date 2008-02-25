<?php

/**
 * This is a class used to represent key->value pairs generated from database queries.
 * The query isn't actually executed until you need it.
 */
class SQLMap extends Object implements Iterator {
	/**
	 * The query used to generate the map.
	 * @var SQLQuery
	 */
	protected $query;
	
	/**
	 * Construct a SQLMap.
	 * @param SQLQuery $query The query to generate this map. THis isn't executed until it's needed.
	 */
	public function __construct(SQLQuery $query) {
		if(!$query) {
			user_error('SQLMap constructed with null query.', E_USER_ERROR);
		}
		
		$this->query = $query;
	}
	
	/**
	 * Get the name of an item.
	 * @param string|int $id The id of the item.
	 * @return string
	 */
	public function getItem($id) {
		if($id) {
			$baseTable = reset($this->query->from);
			$this->query->where[] = "$baseTable.ID = $id";
			$record = $this->query->execute()->first();
			if($record) {
				$className = $record['ClassName'];
				$obj = new $className($record);
				return $obj->Title;
			}
		}
	}
	
	/*
	 * Iterator functions - necessary for foreach to work
	 */
	public function rewind() {
		$this->genItems();
		return $this->items->rewind() ? $this->items->rewind()->Title : null;
	}
	
	public function current() {
		$this->genItems();
		return $this->items->current()->Title;
	}
	
	public function key() {
		$this->genItems();
		return $this->items->current()->ID;
	}
	
	public function next() {
		$this->genItems();
		$next = $this->items->next();
		return isset($next->Title) ? $next->Title : null;
	}
	
	public function valid() {
		$this->genItems();
	 	return $this->items->valid();
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

?>
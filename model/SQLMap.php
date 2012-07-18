<?php
/**
 * This is a class used to represent key->value pairs generated from database queries.
 * The query isn't actually executed until you need it.
 * 
 * @package framework
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
		Deprecation::notice('3.0', 'Use SS_Map or DataList::map() instead.', Deprecation::SCOPE_CLASS);
		
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
			$where = "$baseTable.\"ID\" = $id";
			$this->query->where[sha1($where)] = $where;
			$record = $this->query->execute()->first();
			unset($this->query->where[sha1($where)]);
			if($record) {
				$className = $record['ClassName'];
				$obj = new $className($record);
				return $obj->Title;
			}
		}
	}
	
	public function getIterator() {
		$this->genItems();
		return new SS_Map_Iterator($this->items->getIterator(), $this->keyField, $this->titleField);
	}
	
	/**
	 * Get the items in this class.
	 * @return SS_List
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
			$this->items = new ArrayList();
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

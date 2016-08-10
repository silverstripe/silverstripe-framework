<?php

namespace SilverStripe\Admin;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;

class LeftAndMainMarkingFilter {

	/**
	 * @var array Request params (unsanitized)
	 */
	protected $params = array();

	/**
	 * @param array $params Request params (unsanitized)
	 */
	public function __construct($params = null) {
		$this->ids = array();
		$this->expanded = array();
		$parents = array();

		$q = $this->getQuery($params);
		$res = $q->execute();
		if (!$res) {
			return;
		}

		// And keep a record of parents we don't need to get parents
		// of themselves, as well as IDs to mark
		foreach($res as $row) {
			if ($row['ParentID']) $parents[$row['ParentID']] = true;
			$this->ids[$row['ID']] = true;
		}

		// We need to recurse up the tree,
		// finding ParentIDs for each ID until we run out of parents
		while (!empty($parents)) {
			$parentsClause = DB::placeholders($parents);
			$res = DB::prepared_query(
				"SELECT \"ParentID\", \"ID\" FROM \"SiteTree\" WHERE \"ID\" in ($parentsClause)",
				array_keys($parents)
			);
			$parents = array();

			foreach($res as $row) {
				if ($row['ParentID']) $parents[$row['ParentID']] = true;
				$this->ids[$row['ID']] = true;
				$this->expanded[$row['ID']] = true;
			}
		}
	}

	protected function getQuery($params) {
		$where = array();

		if(isset($params['ID'])) unset($params['ID']);
		if($treeClass = static::config()->tree_class) foreach($params as $name => $val) {
			// Partial string match against a variety of fields
			if(!empty($val) && singleton($treeClass)->hasDatabaseField($name)) {
				$predicate = sprintf('"%s" LIKE ?', $name);
				$where[$predicate] = "%$val%";
			}
		}

		return new SQLSelect(
			array("ParentID", "ID"),
			'SilverStripe\\CMS\\Model\\SiteTree',
			$where
		);
	}

	public function mark($node) {
		$id = $node->ID;
		if(array_key_exists((int) $id, $this->expanded)) {
			$node->markOpened();
		}
		return array_key_exists((int) $id, $this->ids) ? $this->ids[$id] : false;
	}
}

<?php

namespace SilverStripe\Core\Config;

use Exception;
use IteratorAggregate;

/**
 * A Directed Acyclic Graph - used for doing topological sorts on dependencies, such as the before/after conditions
 * in config yaml fragments
 */
class DAG implements IteratorAggregate
{
	/**
	 * The nodes/vertices in the graph. Should be a numeric sequence of items (no string keys, no gaps).
	 * @var array|null
	 */
	protected $data;

	/**
	 * The edges in the graph, in $to_idx => [$from_idx1, $from_idx2, ...] format
	 * @var array
	 */
	protected $dag;

	public function __construct($data = null)
	{
		$data = $data ? array_values($data) : array();

		$this->data = $data;
		$this->dag = array_fill_keys(array_keys($data), array());
	}

	/**
	 * Add another node/vertex
	 * @param mixed $item The item to add to the graph
	 */
	public function additem($item)
	{
		$this->data[] = $item;
		$this->dag[] = array();
	}

	/**
	 * Add an edge from one vertex to another.
	 *
	 * When passing actual nodes (as opposed to indexes), uses array_search with strict = true to find
	 *
	 * @param int $from The index in $data of the node/vertex, or the node/vertex
	 * itself, that the edge goes from
	 * @param int $to The index in $data of the node/vertex, or the node/vertex
	 * itself, that the edge goes to
	 * @throws Exception
	 */
	public function addedge($from, $to)
	{
		$i = is_numeric($from) ? $from : array_search($from, $this->data, true);
		$j = is_numeric($to) ? $to : array_search($to, $this->data, true);

		if ($i === false) {
			throw new Exception("Couldnt find 'from' item in data when adding edge to DAG");
		}
		if ($j === false) {
			throw new Exception("Couldnt find 'to' item in data when adding edge to DAG");
		}

		if (!isset($this->dag[$j])) {
			$this->dag[$j] = array();
		}
		$this->dag[$j][] = $i;
	}

	/**
	 * Sort graph so that each node (a) comes before any nodes (b) where an edge exists from a to b
	 * @return array - The nodes
	 * @throws Exception - If the graph is cyclic (and so can't be sorted)
	 */
	public function sort()
	{
		$data = $this->data;
		$dag = $this->dag;
		$sorted = array();

		while (true) {
			$withedges = array_filter($dag, 'count');
			$starts = array_diff_key($dag, $withedges);

			if (!count($starts)) {
				break;
			}

			foreach ($starts as $i => $foo) {
				$sorted[] = $data[$i];
			}

			foreach ($withedges as $j => $deps) {
				$withedges[$j] = array_diff($withedges[$j], array_keys($starts));
			}

			$dag = $withedges;
		}

		if ($dag) {
			$remainder = new DAG($data);
			$remainder->dag = $dag;
			throw new DAG_CyclicException("DAG has cyclic requirements", $remainder);
		}
		return $sorted;
	}

	public function getIterator()
	{
		return new DAG_Iterator($this->data, $this->dag);
	}
}

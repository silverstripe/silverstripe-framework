<?php

namespace SilverStripe\Core\Config;

use Iterator;

class DAG_Iterator implements Iterator
{

	protected $data;
	protected $dag;

	protected $dagkeys;
	protected $i;

	public function __construct($data, $dag)
	{
		$this->data = $data;
		$this->dag = $dag;
		$this->rewind();
	}

	public function key()
	{
		return $this->i;
	}

	public function current()
	{
		$res = array();

		$res['from'] = $this->data[$this->i];

		$res['to'] = array();
		foreach ($this->dag[$this->i] as $to) {
			$res['to'][] = $this->data[$to];
		}

		return $res;
	}

	public function next()
	{
		$this->i = array_shift($this->dagkeys);
	}

	public function rewind()
	{
		$this->dagkeys = array_keys($this->dag);
		$this->next();
	}

	public function valid()
	{
		return $this->i !== null;
	}
}

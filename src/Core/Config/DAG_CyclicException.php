<?php

namespace SilverStripe\Core\Config;

use Exception;

/**
 * Exception thrown when the {@link SilverStripe\Core\Config\DAG} class is unable to resolve sorting the DAG due
 * to cyclic dependencies.
 */
class DAG_CyclicException extends Exception
{

	public $dag;

	/**
	 * @param string $message The Exception message
	 * @param DAG $dag The remainder of the Directed Acyclic Graph (DAG) after the last successful sort
	 */
	public function __construct($message, $dag)
	{
		$this->dag = $dag;
		parent::__construct($message);
	}

}

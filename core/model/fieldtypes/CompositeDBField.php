<?php
/**
 * Apply this interface to any {@link DBField} that doesn't have a 1-1 mapping with a database field.
 * This includes multi-value fields and transformed fields
 *
 * @todo Unittests for loading and saving composite values (see GIS module for existing similiar unittests)
 *
 * @package sapphire
 * @subpackage model
 */
interface CompositeDBField {
	
}
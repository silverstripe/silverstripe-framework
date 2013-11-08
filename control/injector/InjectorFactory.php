<?php
/**
 * A factory which is used by the {@link Injector} to create new instances.
 *
 * Each injector has a default factory, but services can also specify a custom
 * factory using the `factory` parameter.
 *
 * @package framework
 * @subpackage injector
 */
interface InjectorFactory {

	/**
	 * Creates a new instance of a component and returns it.
	 *
	 * @param string $component The component name to create, usually a class name.
	 * @param array $parameters The parameters to use to create the object.
	 * @return object
	 */
	public function create($component, $parameters = array());

}

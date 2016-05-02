<?php
/**
 * Implements a single tab in a {@link TabSet}.
 *
 * Here is a simple implementation of a Tab. Obviously, you can include as much fields
 * inside as you want. A tab can contain other tabs as well.
 *
 * <code>
 * new Tab(
 * 	$title='Tab one',
 * 	new HeaderField("A header"),
 * 	new LiteralField("Lipsum","Lorem ipsum dolor sit amet enim.")
 * )
 * </code>
 *
 * @package forms
 * @subpackage fields-structural
 */
class Tab extends CompositeField {
	protected $tabSet;

	/**
	 * @uses FormField::name_to_label()
	 *
	 * @param string $name Identifier of the tab, without characters like dots or spaces
	 * @param string $title Natural language title of the tab. If its left out,
	 *  the class uses {@link FormField::name_to_label()} to produce a title from the {@link $name} parameter.
	 * @param FormField All following parameters are inserted as children to this tab
	 */
	public function __construct($name) {
		$args = func_get_args();

		$name = array_shift($args);
		if(!is_string($name)) user_error('TabSet::__construct(): $name parameter to a valid string', E_USER_ERROR);
		$this->name = $name;

		$this->id = preg_replace('/[^0-9A-Za-z]+/', '', $name);

		// Legacy handling: only assume second parameter as title if its a string,
		// otherwise it might be a formfield instance
		if(isset($args[0]) && is_string($args[0])) {
			$title = array_shift($args);
		}
		$this->title = (isset($title)) ? $title : FormField::name_to_label($name);

		parent::__construct($args);
	}

	public function id() {
		return ($this->tabSet) ? $this->tabSet->id() . '_' . $this->id : $this->id;
	}

	public function Fields() {
		return $this->children;
	}

	public function setTabSet($val) {
		$this->tabSet = $val;
		return $this;
	}

	/**
	 * Returns the named field
	 */
	public function fieldByName($name) {
		foreach($this->children as $child) {
			if($name == $child->getName()) return $child;
		}
	}

	public function extraClass() {
		return implode(' ', (array)$this->extraClasses);
	}

	public function getAttributes() {
		return array_merge(
			$this->attributes,
			array(
				'id' => $this->id(),
				'class' => 'tab ' . $this->extraClass()
			)
		);
	}
}


<?php
/**
 * A utility class that finds any files matching a set of rules that are
 * present within a directory tree.
 *
 * Each file finder instance can have several options set on it:
 *   - name_regex (string): A regular expression that file basenames must match.
 *   - accept_callback (callback): A callback that is called to accept a file.
 *     If it returns false the item will be skipped. The callback is passed the
 *     basename, pathname and depth.
 *   - accept_dir_callback (callback): The same as accept_callback, but only
 *     called for directories.
 *   - accept_file_callback (callback): The same as accept_callback, but only
 *     called for files.
 *   - file_callback (callback): A callback that is called when a file i
 *     succesfully matched. It is passed the basename, pathname and depth.
 *   - dir_callback (callback): The same as file_callback, but called for
 *     directories.
 *   - ignore_files (array): An array of file names to skip.
 *   - ignore_dirs (array): An array of directory names to skip.
 *   - ignore_vcs (bool): Skip over commonly used VCS dirs (svn, git, hg, bzr).
 *     This is enabled by default. The names of VCS directories to skip over
 *     are defined in {@link SS_FileFInder::$vcs_dirs}.
 *   - max_depth (int): The maxmium depth to traverse down the folder tree,
 *     default to unlimited.
 *
 * @package framework
 * @subpackage filesystem
 */
class SS_FileFinder {

	/**
	 * @var array
	 */
	protected static $vcs_dirs = array(
		'.git', '.svn', '.hg', '.bzr'
	);

	/**
	 * The default options that are set on a new finder instance. Options not
	 * present in this array cannot be set.
	 *
	 * Any default_option statics defined on child classes are also taken into
	 * account.
	 *
	 * @var array
	 */
	protected static $default_options = array(
		'name_regex'           => null,
		'accept_callback'      => null,
		'accept_dir_callback'  => null,
		'accept_file_callback' => null,
		'file_callback'        => null,
		'dir_callback'         => null,
		'ignore_files'         => null,
		'ignore_dirs'          => null,
		'ignore_vcs'           => true,
		'min_depth'            => null,
		'max_depth'            => null
	);

	/**
	 * @var array
	 */
	protected $options;

	public function __construct() {
		$this->options = array();
		$class = get_class($this);

		// We build our options array ourselves, because possibly no class or config manifest exists at this point
		do {
			$this->options = array_merge(Object::static_lookup($class, 'default_options'), $this->options);
		}
		while ($class = get_parent_class($class));
	}

	/**
	 * Returns an option value set on this instance.
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function getOption($name) {
		if (!array_key_exists($name, $this->options)) {
			throw new InvalidArgumentException("The option $name doesn't exist.");
		}

		return $this->options[$name];
	}

	/**
	 * Set an option on this finder instance. See {@link SS_FileFinder} for the
	 * list of options available.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption($name, $value) {
		if (!array_key_exists($name, $this->options)) {
			throw new InvalidArgumentException("The option $name doesn't exist.");
		}

		$this->options[$name] = $value;
	}

	/**
	 * Sets several options at once.
	 *
	 * @param array $options
	 */
	public function setOptions(array $options) {
		foreach ($options as $k => $v) $this->setOption($k, $v);
	}

	/**
	 * Finds all files matching the options within a directory. The search is
	 * performed depth first.
	 *
	 * @param  string $base
	 * @return array
	 */
	public function find($base) {
		$paths = array(array(rtrim($base, '/'), 0));
		$found = array();

		$fileCallback = $this->getOption('file_callback');
		$dirCallback  = $this->getOption('dir_callback');

		while ($path = array_shift($paths)) {
			list($path, $depth) = $path;

			foreach (scandir($path) as $basename) {
				if ($basename == '.' || $basename == '..') {
					continue;
				}

				if (is_dir("$path/$basename")) {
					if (!$this->acceptDir($basename, "$path/$basename", $depth + 1)) {
						continue;
					}

					if ($dirCallback) {
						call_user_func(
							$dirCallback, $basename, "$path/$basename", $depth + 1
						);
					}

					$paths[] = array("$path/$basename", $depth + 1);
				} else {
					if (!$this->acceptFile($basename, "$path/$basename", $depth)) {
						continue;
					}

					if ($fileCallback) {
						call_user_func(
							$fileCallback, $basename, "$path/$basename", $depth
						);
					}

					$found[] = "$path/$basename";
				}
			}
		}

		return $found;
	}

	/**
	 * Returns TRUE if the directory should be traversed. This can be overloaded
	 * to customise functionality, or extended with callbacks.
	 *
	 * @return bool
	 */
	protected function acceptDir($basename, $pathname, $depth) {
		if ($this->getOption('ignore_vcs') && in_array($basename, self::$vcs_dirs)) {
			return false;
		}

		if ($ignore = $this->getOption('ignore_dirs')) {
			if (in_array($basename, $ignore)) return false;
		}

		if ($max = $this->getOption('max_depth')) {
			if ($depth > $max) return false;
		}

		if ($callback = $this->getOption('accept_callback')) {
			if (!call_user_func($callback, $basename, $pathname, $depth)) return false;
		}

		if ($callback = $this->getOption('accept_dir_callback')) {
			if (!call_user_func($callback, $basename, $pathname, $depth)) return false;
		}

		return true;
	}

	/**
	 * Returns TRUE if the file should be included in the results. This can be
	 * overloaded to customise functionality, or extended via callbacks.
	 *
	 * @return bool
	 */
	protected function acceptFile($basename, $pathname, $depth) {
		if ($regex = $this->getOption('name_regex')) {
			if (!preg_match($regex, $basename)) return false;
		}

		if ($ignore = $this->getOption('ignore_files')) {
			if (in_array($basename, $ignore)) return false;
		}

		if ($minDepth = $this->getOption('min_depth')) {
			if ($depth < $minDepth) return false;
		}

		if ($callback = $this->getOption('accept_callback')) {
			if (!call_user_func($callback, $basename, $pathname, $depth)) return false;
		}

		if ($callback = $this->getOption('accept_file_callback')) {
			if (!call_user_func($callback, $basename, $pathname, $depth)) return false;
		}

		return true;
	}

}

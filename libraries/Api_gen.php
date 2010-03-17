<?php

/**
 * Copyright (c) 2010  John Reese
 * Licensed under the MIT license.
 */

/**
 * Dynamic API abstraction layer for CodeIgniter projects.
 */
class Api_gen {

	private $api_root = "";

	/**
	 * Initialize the dynamic API layer.
	 * Called by CI's load->library() module.
	 *
	 * Accepted parameters:
	 *
	 *   api_root =>
	 *     choose where APIGen looks for controllers
	 *
	 *   doc_cache_root =>
	 *     where APIGen caches generated documentation
	 *
	 * @param array Parameters (optional)
	 */
	public function __construct($params=array())
	{
		$this->ci =& get_instance();
		$this->ci->load->helper("array");


		$this->api_root = element("api_root", $params);
		if (FALSE === $this->api_root)
		{
			log_message("error", "Api_gen not given 'api_root' parameter.");
		}
	}

	/**
	 * Dispatch an API call, given a data format, API version, module, and
	 * method name.  Module name is optional only when passing the "doc" format.
	 * Method name is optional for all data formats.
	 *
	 * Data formats allowed:  doc, json
	 *
	 * @param string Data format
	 * @param int API version
	 * @param string Module name
	 * @param string Method name
	 */
	public function dispatch($format, $version, $module=NULL, $method=NULL)
	{
		# transfer to the documentation generator
		if ($format == "doc")
		{
			return $this->documentation($version, $module, $method);
		}

		# execute the API call to the appropriate version, module, method
		$data = $this->_api_call($version, $module, $method);

		switch ($format)
		{
			case "json":
				$this->ci->output->set_header("Content-type: application/json");
				$data = json_encode($data);
				break;

			case "php":
				$this->ci->output->set_header("Content-type: text/plain");
				$data = serialize($data);
				break;

			default:
				show_error("Invalid data format");
		}

		echo $data;
	}

	/**
	 * Display the generated documentation for a given API version, module,
	 * and method name.  Module and method names are optional.
	 *
	 * @param int API version
	 * @param string Module name
	 * @param string Method name
	 */
	public function documentation($version, $module=NULL, $method=NULL)
	{
	}

	/**
	 * Load and call a given API version, module, and method, returning
	 * the value returned from the API method.
	 *
	 * @param int API version
	 * @param string Module name
	 * @param string Method name
	 * @return mixed API return value
	 */
	private function _api_call($version, $module, $method)
	{
		$object = $this->_load_api($version, $module);

		if ($object === NULL)
		{
			show_error("Invalid API module or version");
		}

		if ($method === NULL && method_exists($object, "_index"))
		{
			return $object->_index();
		}
		elseif ($method !== NULL && method_exists($object, $method))
		{
			return $object->$method();
		}

		show_error("Invalid API method");
	}

	/**
	 * Load a given API module version, returning the API object if loaded.
	 *
	 * @param int API version
	 * @param string Module name
	 * @return object API module
	 */
	private function _load_api($version, $module)
	{
		$dirname = "{$this->api_root}/v{$version}";
		$filename = "{$dirname}/{$module}.php";
		$classname = "Api_{$module}";

		if (!is_dir($dirname))
		{
			show_error("Invalid API version");
		}
		elseif (!is_file($filename))
		{
			show_error("Invalid API module");
		}

		require_once($filename);

		if (class_exists($classname))
		{
			return new $classname();
		}
		else
		{
			return NULL;
		}
	}

}


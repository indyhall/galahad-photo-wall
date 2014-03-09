<?php

namespace Galahad\PhotoWall\Api;

class Request
{
	protected $_data = array();

	public function __construct($data = array())
	{
		$this->_data = $data;
	}

	public function __get($key)
	{
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		}

		if (isset($_REQUEST[$key])) {
			return $_REQUEST[$key];
		}

		return null;
	}
}
<?php

namespace Galahad\PhotoWall\Api;

class PublicApi
{
	protected $_plugin;
	protected $_api;

	public function __construct(\Galahad\PhotoWall\Plugin $plugin, \Galahad\PhotoWall\Api $api)
	{
		$this->_plugin = $plugin;
		$this->_api = $api;

		// getPhotos
		\add_action('wp_ajax_' . $plugin->prefixKey('get_photos'), array($this, 'getPhotos'));
		\add_action('wp_ajax_nopriv_' . $plugin->prefixKey('get_photos'), array($this, 'getPhotos'));

		// setPhoto (priv'd only)
		\add_action('wp_ajax_' . $plugin->prefixKey('set_photo'), array($this, 'setPhoto'));
	}

	public function __call($name, $args)
	{
		$result = $this->_api->$name($_REQUEST);
		\wp_send_json($result);
	}
}
<?php

namespace Galahad\PhotoWall;

class Plugin
{
    const SLUG = 'galahad-photo-wall';
    const PREFIX = 'galahad_photo_wall_';

    protected $_bootstrapFile;
    protected $_pluginDir;
    protected $_baseName;

    /**
     * Constructor
     *
     * @param String $bootstrap The filename of the plugin's bootstrap file
     */
    public function __construct($bootstrap)
    {
        // Setup plugin
        $this->_bootstrapFile = $bootstrap;
        $this->_pluginDir = dirname($bootstrap);
        $this->_baseName = \plugin_basename($bootstrap);

        // Register hooks
        \register_activation_hook($bootstrap, array(self, 'activate'));
        \register_deactivation_hook($bootstrap, array(self, 'deactivate'));

        $this->doAction('pre_init');

        // Load components
        $api = new Api($this);
	    new Settings($this, $api);
        new Shortcode($this);
        new Template($this); // TODO: Conditionally load if a page is defined

        $this->doAction('post_init');
    }

	public function basename()
	{
		return \plugin_basename($this->_bootstrapFile);
	}

	public function translate($text)
	{
		return \translate($text, self::SLUG);
	}

    public function pathToFile($filename) {
        return $this->_pluginDir . DIRECTORY_SEPARATOR . $filename;
    }

    public function urlToFile($filename) {
        return \plugins_url($filename, $this->_bootstrapFile);
    }

    public function prefixKey($key)
    {
        return self::PREFIX . $key;
    }

	public function getOption($key, $default = false)
	{
		return \get_option($this->prefixKey($key), $default);
	}

	public function setOption($key, $value)
	{
		return \update_option($this->prefixKey($key), $value);
	}

    public function filter($tag, $value)
    {
        $args = func_get_args();
        $args[0] = $this->prefixKey($tag);
        return call_user_func_array('\apply_filters', $args);
    }

    public function addFilter($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        return \add_filter($this->prefixKey($tag), $callback, $priority, $accepted_args);
    }

    public function doAction($tag, $arg = '')
    {
        $args = func_get_args();
        $args[0] = $this->prefixKey($tag);
        return call_user_func_array('\do_action', $args);
    }

    /**
     * Activation hook
     */
    public static function activate()
    {
        // May want this down the road
    }

    /**
     * Deactivation hook
     */
    public static function deactivate()
    {
        // Also may need this
    }
}
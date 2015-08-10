<?php

namespace Galahad\PhotoWall;

class Shortcode
{
    protected $_plugin;
    protected $_includeScripts = false;

    /**
     * Constructor
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->_plugin = $plugin;
        \add_shortcode(Plugin::SLUG, array($this, 'render'));

        \add_action('wp_enqueue_scripts', array($this, 'registerScripts'));
        \add_action('wp_footer', array($this, 'renderScripts'));
    }

    /**
     * Render the photo wall's HTML
     *
     * @param array $atts Shortcode attributes
     * @return String
     */
    public function render($atts)
    {
	    $plugin = $this->_plugin;

        // Flag for scripts to be included in current request
        $this->_includeScripts = true;

        // Load defaults and shortcode attributes
        $defaults = array();
        $atts = \shortcode_atts($defaults, $atts);

        // Generate output
        $output = '<div id="' . $plugin->filter('outlet_id', $plugin->prefixKey('outlet')) . '"></div>';

        // Render filtered output
        return $this->_plugin->filter('output', $output);
    }

    /**
     * Register necessary Javascript
     */
    public function registerScripts()
    {
        $plugin = $this->_plugin;

	    // Bower components
	    $unveilFile = $plugin->urlToFile('bower_components/jquery-unveil/jquery.unveil.min.js');
	    \wp_register_script('jquery-unveil', $unveilFile, array('jquery'), '1.3.0', true);
	    $zoomerangFile = $plugin->urlToFile('bower_components/zoomerang/zoomerang.js');
	    \wp_register_script('zoomerang', $zoomerangFile, array(), '0.1.7', true);

	    // Main JS file
        $jsFile = $plugin->filter('js_file', $plugin->urlToFile('assets/main.js'));
        $config = $plugin->filter('js_config', array(
            'endpoint' => \admin_url('admin-ajax.php'),
            'action' => $plugin->prefixKey('get_photos'),
	        'outlet_id' => $plugin->filter('outlet_id', $plugin->prefixKey('outlet')),
	        'placeholder' => $plugin->filter('placeholder_url', $plugin->urlToFile('assets/placeholder.png'))
        ));

        \wp_register_script($plugin->prefixKey('main-js'), $jsFile, array('jquery', 'jquery-unveil', 'zoomerang'), '1.0.1', true);
        \wp_localize_script($plugin->prefixKey('main-js'), 'galahadPhotoWallConfig', $config);
    }

    /**
     * Render photo wall's Javascript
     */
    public function renderScripts()
    {
        if (!$this->_includeScripts) {
            return;
        }

        \wp_print_scripts($this->_plugin->prefixKey('main-js'));
    }
}
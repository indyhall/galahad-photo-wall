<?php
/*
Plugin Name: Galahad Photo Wall
Plugin URI: http://www.indyhall.org/
Description: Shows a wall of profile photos
Version: 0.4.2
Author: Chris Morrell
Author URI: http://cmorrell.com
License: GPL2
*/

require_once __DIR__ . '/vendor/autoload.php';

// Init the photo wall plugin after all plugins have loaded
add_action('plugins_loaded', function() {
    new \Galahad\PhotoWall\Plugin(__FILE__);
});

function galahad_photo_wall() {
    return do_shortcode('[' . \Galahad\PhotoWall\Plugin::SLUG . ']');
}
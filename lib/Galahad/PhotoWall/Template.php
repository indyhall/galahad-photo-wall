<?php

namespace Galahad\PhotoWall;

class Template
{
    protected $_plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->_plugin = $plugin;

        \add_filter('template_include', array($this, 'templateFilename'), 99);
        \add_filter('body_class', array($this, 'addBodyClass'));

        \wp_register_style(Plugin::SLUG, $plugin->filter('css_file', $plugin->urlToFile('assets/main.css')));
    }

    public function templateFilename($template)
    {
        $plugin = $this->_plugin;

        if ($this->_isPhotoWallPage()) {
            // Load galahad-photo-wall.php from the theme if it exists
            $themeTemplate = locate_template(array($plugin->filter('template_file_name', 'galahad-photo-wall.php')));
            if ('' !== $themeTemplate) {
                $template = $themeTemplate;
            } else {
                $template = $plugin->filter('template_file', $plugin->pathToFile('templates/photo-wall.php'));
                \wp_enqueue_style(Plugin::SLUG);
            }

            $plugin->doAction('init_page');
        }

        return $template;
    }

    public function addBodyClass($classes)
    {
        if ($this->_isPhotoWallPage()) {
            $classes[] = Plugin::SLUG;
        }

        return $classes;
    }

    protected function _isPhotoWallPage()
    {
        return \is_page($this->_plugin->getOption('page'));
    }
}
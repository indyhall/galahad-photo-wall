<?php

namespace Galahad\PhotoWall;

class Settings
{
	protected $_api;
	protected $_plugin;

	/**
	 * Constructor
	 *
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin, Api $api)
	{
		$this->_api = $api;
		$this->_plugin = $plugin;

		\add_action('admin_init', array($this, 'registerSettings'));
		\add_action('admin_menu', array($this, 'addMenuItems'));
		\add_filter('plugin_action_links_' . $plugin->basename(), array($this, 'addSettingsLink'));

		\add_action('admin_enqueue_scripts', function() use ($plugin) {
			$jsFile = $plugin->urlToFile('assets/admin.js');
			$cssFile = $plugin->urlToFile('assets/admin.css');
			\wp_register_script($plugin->prefixKey('admin-js'), $jsFile, array('jquery'), '1.0.1', true);
			\wp_register_style($plugin->prefixKey('admin-css'), $cssFile, array(), '1.0');
		});
	}

	public function registerSettings()
	{
		$plugin = $this->_plugin;

		\add_settings_section(
			$plugin->prefixKey('settings'),
			'Photo Wall Settings',
			function() {},
			$plugin->prefixKey('settings')
		);

		\add_settings_field(
			$plugin->prefixKey('page'),
			'Page to display wall',
			function() use ($plugin) {
				\wp_dropdown_pages(array(
					'echo' => true,
					'name' => $plugin->prefixKey('page'),
					'selected' => $plugin->getOption('page', 0),
					'show_option_none' => 'Please select a page'
				));
			},
			$plugin->prefixKey('settings'),
			$plugin->prefixKey('settings')
		);

		\add_settings_field(
			$plugin->prefixKey('roles'),
			'Roles to show on wall',
			function() use ($plugin) {
				global $wp_roles;
				$activeRoles = $plugin->getOption('roles');

				if (!isset($wp_roles)) {
					$wp_roles = new WP_Roles();
				}

				$name = $plugin->prefixKey('roles');
				$roles = $wp_roles->get_names();

				foreach ($roles as $key => $label) {
					$checked = (in_array($key, $activeRoles) ? ' checked' : '');
					printf('<p><label for="%1$s_%2$s"><input name="%1$s[]" id="%1$s_%2$s" type="checkbox" value="%2$s" %3$s>%4$s</label></p>',
						/*%1$s*/ $name,
						/*%2$s*/ $key,
						/*%3$s*/ $checked,
						/*%4$s*/ $label);
				}
			},
			$plugin->prefixKey('settings'),
			$plugin->prefixKey('settings')
		);

		\register_setting($plugin->prefixKey('settings'), $plugin->prefixKey('page'));
		\register_setting($plugin->prefixKey('settings'), $plugin->prefixKey('roles'));
	}

	public function addMenuItems()
	{
		$plugin = $this->_plugin;

		$photosSlug = $plugin->prefixKey('photos');
		$settingsSlug = $plugin->prefixKey('settings');

		$menuTitle = $plugin->translate('Photo Wall');

		$photosMenuTitle = $plugin->translate('Manage Photos');
		$photosPageTitle = $plugin->translate('Manage Photo Wall Photos');
		$settingsMenuTitle = $plugin->translate('Settings');
		$settingsPageTitle = $plugin->translate('Photo Wall Settings');

		$photosHookSuffix = \add_menu_page($photosPageTitle, $menuTitle, 'edit_users', $photosSlug, array($this, 'photosPage'), 'dashicons-id-alt');
		\add_submenu_page($photosSlug, $photosPageTitle, $photosMenuTitle, 'edit_users', $photosSlug, array($this, 'photosPage'));
		\add_submenu_page($photosSlug, $settingsPageTitle, $settingsMenuTitle, 'manage_options', $settingsSlug, array($this, 'settingsPage'));

		\add_action('admin_print_scripts-' . $photosHookSuffix, function() use ($plugin) {
			\wp_enqueue_script($plugin->prefixKey('admin-js'));
		});
		\add_action('admin_print_styles-' . $photosHookSuffix, function() use ($plugin) {
			\wp_enqueue_style($plugin->prefixKey('admin-css'));
		});
	}

	public function addSettingsLink($links)
	{
		$plugin = $this->_plugin;
		$page = $plugin->prefixKey('settings');
		$title = $plugin->translate('Settings');
		$links[] = sprintf('<a href="options-general.php?page=%s">%s</a>', $page, $title);
		return $links;
	}

	public function settingsPage()
	{
		$plugin = $this->_plugin;
		$greenscreenUrl = admin_url('admin-ajax.php') . '?action=' . $plugin->prefixKey('greenscreen');

		?>

		<div class="wrap">

			<?php if ('true' == $_REQUEST['settings-updated']): ?>
				<div class="updated"><?php \_e('Settings saved.'); ?></div>
			<?php endif; ?>

			<h2><?php echo $plugin->translate('Photo Wall Settings'); ?></h2>

			<form method="POST" action="options.php">
				<?php \settings_fields($plugin->prefixKey('settings')); ?>

				<p style="max-width: 700px;">This plugin was designed to be used with a custom template.  You can try using
					the <strong>[<?php echo Plugin::SLUG ?>]</strong> shortcode, but it will probably
					not work as expected.  For best results, create a file in your theme called
					<strong>galahad-photo-wall.php</strong> or hook into the
					<strong><?php echo $plugin->prefixKey('template_file'); ?></strong> filter.</p>

				<?php \do_settings_sections($plugin->prefixKey('settings')); ?>
				<?php \submit_button(); ?>
			</form>

			<h2><?php echo $plugin->translate('Greenscreen'); ?></h2>
			<p><?=$plugin->translate('Use the following URL for Greenscreen:')?></p>
			<p><input type="text" value="<?=$greenscreenUrl?>" onclick="this.select()" /></p>

		</div>

		<?php
	}

	public function photosPage()
	{
		wp_enqueue_media();

		$plugin = $this->_plugin;
		$table = new Settings\UserTable($plugin, $this->_api);
		$table->prepare_items();
		?>

		<div class="wrap">

			<h2><?php echo $plugin->translate('Manage Photo Wall Photos'); ?></h2>

			<?php $table->views(); ?>

			<form action="" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
				<input type="hidden" name="view" value="<?php echo $_REQUEST['view']; ?>" />
				<?php $table->search_box($plugin->translate('Search'), 'user'); ?>
				<?php $table->display(); ?>
			</form>

			<br class="clear" />

		</div>

		<?php
	}
}
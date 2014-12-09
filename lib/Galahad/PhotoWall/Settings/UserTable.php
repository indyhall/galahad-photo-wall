<?php

namespace Galahad\PhotoWall\Settings;

if (!class_exists('\WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class UserTable extends \WP_List_Table
{
	protected $_api;
	protected $_plugin;

	/**
	 * Constructor
	 *
	 * @param \Galahad\PhotoWall\Plugin $plugin
	 */
	public function __construct(\Galahad\PhotoWall\Plugin $plugin, \Galahad\PhotoWall\Api $api)
	{
		$this->_api = $api;
		$this->_plugin = $plugin;

		parent::__construct(array(
			'singular' => 'user',
			'plural' => 'users',
			'ajax' => false
		));
	}

	function column_default($item, $columnName){
		switch($columnName) {
			case 'ID':
				return $item->$columnName;
			case 'photo':
				return '...';
			default:
				return print_r($item, true); // Debugging
		}
	}

	function get_columns()
	{
		return array(
			'ID' => 'User ID',
			'display_name' => 'Name',
			'photo' => 'Photo',
			'actions' => ''
		);
	}

	function get_sortable_columns()
	{
		return array(
			'ID' => array('ID', false), // true = already sorted
			'display_name' => array('display_name', false)
		);
	}

	function get_views()
	{
		$plugin = $this->_plugin;

		$url = 'admin.php?page=' . $plugin->prefixKey('photos');
		$view = (isset($_REQUEST['view']) && !empty($_REQUEST['view']) ? $_REQUEST['view'] : false);

		$views = array();
		$views['all'] = sprintf('<a href="%1$s" %2$s>%3$s</a>',
			/*%1$s*/ $url,
			/*%2$s*/ ($view ? '' : 'class="current"'),
			/*%3$s*/ $plugin->translate('All'));

		$views['with_photos'] = sprintf('<a href="%1$s" %2$s>%3$s</a>',
			/*%1$s*/ $url . '&view=with_photos',
			/*%2$s*/ ('with_photos' == $view ? 'class="current"' : ''),
			/*%3$s*/ $plugin->translate('With Photos'));

		$views['without_photos'] = sprintf('<a href="%1$s" %2$s>%3$s</a>',
			/*%1$s*/ $url . '&view=without_photos',
			/*%2$s*/ ('without_photos' == $view ? 'class="current"' : ''),
			/*%3$s*/ $plugin->translate('Without Photos'));

		return $views;
	}

	function prepare_items()
	{
		global $wpdb;
		$blogId = \get_current_blog_id();

		// Setup columns
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Build meta query
		$metaQuery = array();

		// Roles
		$roles = $this->_plugin->getOption('roles', array());
		$metaQuery[] = array(
			'key' => $wpdb->get_blog_prefix($blogId) . 'capabilities',
			'value' => '"(' . implode('|', array_map('preg_quote', $roles)) . ')"',
			'compare' => 'REGEXP'
		);

		$paged = $this->get_pagenum();
		$users_per_page = $this->get_items_per_page('users_per_page');

		$queryArgs = array(
			'meta_query' => $metaQuery,
			'number' => $users_per_page,
			'offset' => ($paged - 1) * $users_per_page,
			'fields' => 'all_with_meta'
		);

		if (isset($_REQUEST['view'])) {
			switch ($_REQUEST['view']) {
				case 'with_photos':
					$queryArgs['meta_query'][] = array(
						'key' => $this->_plugin->prefixKey('photo'),
						'compare' => 'EXISTS'
					);
					break;

				case 'without_photos':
					$queryArgs['meta_query'][] = array(
						'key' => $this->_plugin->prefixKey('photo'),
						'compare' => 'NOT EXISTS'
					);
					break;
			}
		}

		if (isset($_REQUEST['orderby'])) {
			$queryArgs['orderby'] = $_REQUEST['orderby'];
		}

		if (isset($_REQUEST['order'])) {
			$queryArgs['order'] = $_REQUEST['order'];
		}

		if (isset($_REQUEST['s']) && '' !== $_REQUEST['s']) {
			$queryArgs['search'] = '*' . $_REQUEST['s'] . '*';
		}

		$wp_user_search = new \WP_User_Query($queryArgs);
		$this->items = $wp_user_search->get_results();

		$this->set_pagination_args( array(
			'total_items' => $wp_user_search->get_total(),
			'per_page' => $users_per_page,
		) );
	}

	function column_ID($item) {
		$id = $item->ID;
		echo '<a href="/wp-admin/user-edit.php?user_id=' . $id . '">' . $id . '</a>';
		echo ' <code>#member-' . $item->ID . '</code>';
	}

	function column_display_name($item)
	{
		$name = $item->display_name;
		if ($item->first_name) {
			$name = $item->first_name . ' ' . $item->last_name; // FIXME
		}

		echo htmlspecialchars($name);
	}

	function column_photo($item)
	{
		$plugin = $this->_plugin;
		echo '<div id="wall-photo-' . $item->ID . '" class="wall-photo-container wall-photo-' . $item->ID . '">';

		$photo = $this->_api->getPhoto(array(
			'user' => $item,
			'type' => 'html'
		));

		if (!$photo['success'] || !$photo['data']) {
			echo $plugin->translate('None.');
		} else {
			echo $photo['data'];
		}

		echo '</div>';
	}

	function column_actions($item)
	{
		$photoKey = $this->_plugin->prefixKey('photo');
		$attachmentId = $item->$photoKey;

		echo '<div class="' . ($attachmentId ? 'has-photo' : 'needs-photo') . '">';
		echo '<a href="#" data-user-id="' . $item->ID . '" id="add-photo-' . $item->ID . '" class="button-primary add-photo">Add Photo</a> ';
		echo '<a href="#" data-user-id="' . $item->ID . '" id="update-photo-' . $item->ID . '" class="button-secondary update-photo">Update Photo</a> ';
		echo '<a href="#" data-user-id="' . $item->ID . '" id="delete-photo-' . $item->ID . '" class="button-secondary delete-photo">Delete Photo</a>';
		echo '<span class="spinner"></span>';
		echo '</div>';
	}

	function no_items() {
		if (isset($_REQUEST['s'])) {
			echo $this->_plugin->translate('There are no users matching your search.');
		} else {
			echo $this->_plugin->translate('There are currently no users matching the <a href="admin.php?page=' .
				$this->_plugin->prefixKey('settings') . '">roles you\'ve configured</a>.');
		}
	}
}
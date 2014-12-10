<?php

namespace Galahad\PhotoWall;

class Api
{
	protected $_doingAjax = false;
	protected $_plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
	    $this->_plugin = $plugin;
	    new Api\PublicApi($plugin, $this); // Handles AJAX requests to API
    }

	public function getPhoto($req = array())
	{
		$plugin = $this->_plugin;
		$req = new Api\Request($req);

		$user = $req->user;
		if (!$user && ($userId = $req->user_id)) {
			$user = \get_userdata($userId);
		}

		if (!$user) {
			return $this->_apiError('No such user.');
		}

		$photoKey = $plugin->prefixKey('photo');
		$attachmentId = $user->$photoKey;

		if (!$attachmentId) {
			return $this->_apiResponse(false);
		}

		$type = $req->type;
		if (!$type) {
			$type = 'url';
		}

		switch ($type) {
			case 'html':
				$imageSize = $req->image_size;
				if (!$imageSize) {
					$imageSize = 'thumbnail';
				}
				$photo = \wp_get_attachment_image($attachmentId, $imageSize, false, array('class' => 'wall-photo'));
				break;

			default:
				$photo = \wp_get_attachment_url($attachmentId);
		}

		return $this->_apiResponse($photo);
	}

    public function getPhotos($req = array())
    {
	    global $wpdb;
	    $blogId = \get_current_blog_id();
	    $req = new Api\Request($req);

	    // Query users
	    $metaQuery = array();
	    $roles = $this->_plugin->getOption('roles', array());
	    $metaQuery[] = array(
		    'key' => $wpdb->get_blog_prefix($blogId) . 'capabilities',
		    'value' => '"(' . implode('|', array_map('preg_quote', $roles)) . ')"',
		    'compare' => 'REGEXP'
	    );

	    $queryArgs = array(
		    'meta_query' => $metaQuery,
		    'fields' => 'all_with_meta'
	    );

	    // Check for cache
	    $results = false;
	    $transientId = $this->_plugin->prefixKey('photos_cached_' . md5(json_encode($queryArgs)));
	    if (!$req->fresh && $cached = \get_transient($transientId)) {
		    $results = $cached;
	    }

	    if (!$results) {
		    $wp_user_search = new \WP_User_Query($queryArgs);
		    $users = $wp_user_search->get_results();

		    $results = array();
		    foreach ($users as $userId => $user) {
			    $row = array(
				    'ID' => $user->ID,
				    'display_name' => ($user->first_name ? $user->first_name . ' ' . $user->last_name : $user->display_name), // FIXME
				    'photo' => false
			    );

			    $photo = $this->getPhoto(array(
				    'user' => $user,
				    'type' => 'url'
			    ));
			    if ($photo['success'] && $photo['data']) {
				    $row['photo'] = $photo['data'];
			    }

			    if (!$row['photo'] && $req->with_photos) {
				    continue;
			    }

			    $results[] = $row;
		    }

		    // Store cache
		    \set_transient($transientId, $results, 3600); // Cache for 1 hour
		}


	    // Return
	    return $this->_apiResponse($results);
    }

	public function setPhoto($req = array())
	{
		// Check permissions
		if (!\current_user_can('edit_users')) {
			return $this->_apiError('You are not authorized to set user photos.');
		}

		// Load request
		$req = new Api\Request($req);

		// Get user ID
		$userId = $req->user_id;
		if (!$userId) {
			return $this->_apiError('The user_id parameter is required.');
		}

		// Get attachment ID
		$attachmentId = $req->attachment_id;
		if (!$attachmentId) {
			return $this->_apiError('The attachment_id parameter is required.');
		}

		// Load User object
		$user = \get_userdata($userId);
		if (!$user) {
			return $this->_apiError('A user with that ID does not exist.');
		}

		// Load attachment
		$attachmentId = (int) $attachmentId;
		$attachment = \get_post($attachmentId);
		if (!$attachment || 'attachment' !== $attachment->post_type) {
			return $this->_apiError('An attachment with that ID does not exist.');
		}

		// Load old photo & check
		$oldAttachmentId = get_user_meta($user->ID, $this->_plugin->prefixKey('photo'), true);
		if ($attachmentId == $oldAttachmentId) {
			// Photo already set
			$result = true;
		} else {
			// Set photo
			$result = \update_user_meta($user->ID, $this->_plugin->prefixKey('photo'), $attachment->ID);
		}

		if ($result) {
			return $this->_apiResponse(array(
				'thumbnail' => wp_get_attachment_thumb_url($attachmentId),
				'url' => wp_get_attachment_url($attachmentId)
			));
		}

		// Default to failure
		return $this->_apiError('Unable to attach photo to user.');
	}

	/**
	 * Either returns the API response or sends it to the client depending on context
	 *
	 * @param mixed $data Response data
	 * @param bool $success Whether the request should be considered "successful" - defaults to true
	 * @param string $message Message to send back with response (often error message)
	 * @return array|null Returns null if sent to client (wp_send_json die()'s)
	 */
	protected function _apiResponse($data, $success = true, $message = null)
	{
		$resp = array(
			'success' => $success,
			'data' => $data
		);

		// Set message (always on error)
		if ($message) {
			$resp['message'] = $this->_plugin->translate($message);
		} else if (!$success) {
			$resp['message'] = $this->_plugin->translate('Request failed.');
		}

		return $resp;
	}

	protected function _apiError($message = null)
	{
		return $this->_apiResponse(null, false, $message);
	}
}
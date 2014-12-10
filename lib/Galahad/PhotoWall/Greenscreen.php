<?php

namespace Galahad\PhotoWall;

class Greenscreen
{
	protected $_api;
	protected $_plugin;

	/**
	 * Constructor
	 *
	 * @param Plugin $plugin
	 * @param Api $api
	 */
	public function __construct(Plugin $plugin, Api $api)
	{
		$this->_api = $api;
		$this->_plugin = $plugin;

		\add_action('wp_ajax_' . $plugin->prefixKey('greenscreen'), array($this, 'showGreenscreen'));
		\add_action('wp_ajax_nopriv_' . $plugin->prefixKey('greenscreen'), array($this, 'showGreenscreen'));
	}

	public function showGreenscreen()
	{
		$api = $this->_api;
		$photos = $api->getPhotos(array('with_photos' => true)); // TODO: Handle $photos['success'] == false

		$autoplaySpeed = 10000; // 10 seconds
		if (isset($_REQUEST['autoplay_speed']) && ctype_digit($_REQUEST['autoplay_speed'])) {
			$autoplaySpeed = intval($_REQUEST['autoplay_speed']) * 1000;
		}

		// Output HTML
		?>

		<DOCTYPE html>
		<html>
			<head>
				<meta http-equiv="refresh" content="600" />
				<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/jquery.slick/1.3.15/slick.css" />
				<style type="text/css">
				*, *:before, *:after {
					box-sizing: border-box;
				}
				html, body {
					height: 100%;
					margin: 0;
					overflow: hidden;
				}
				#greenscreen {
					height: 100%;
					overflow: hidden;
				}
				.photo {
					position: relative;
				}
				.photo img {
					width: 100%;
					height: 100%;
				}
				.caption {
					font: normal 50px Garamond, serif;
					position: absolute;
					bottom: 0;
					right: 0;
					width: 100%;
					background: rgba(0, 0, 0, 0.4);
					color: #fff;
					text-align: right;
					margin: 0;
					padding: 15px;
					text-shadow: 2px 2px 10px #000;
				}
				</style>
			</head>
			<body>

				<div id="greenscreen">
					<? foreach ($photos['data'] as $member): ?>
						<div class="photo">
							<img data-lazy="<?=$member['photo']?>" />
							<h1 class="caption"><?=htmlspecialchars($member['display_name'])?></h1>
						</div>
					<? endforeach; ?>
				</div>

				<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
				<script type="text/javascript" src="//cdn.jsdelivr.net/jquery.slick/1.3.15/slick.min.js"></script>

				<script type="text/javascript">
				$('#greenscreen').slick({
					lazyLoad: 'ondemand',
					autoplay: true,
  					autoplaySpeed: <?=$autoplaySpeed?>
				});
				</script>

			</body>
		</html>

		<?php

		// Done
		exit;
	}
}
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
		$plugin = $this->_plugin;

		$photos = $api->getPhotos(array('with_photos' => true)); // TODO: Handle $photos['success'] == false

		$autoplaySpeed = 10000; // 10 seconds
		if (isset($_REQUEST['autoplay_speed']) && ctype_digit($_REQUEST['autoplay_speed'])) {
			$autoplaySpeed = intval($_REQUEST['autoplay_speed']) * 1000;
		}

		// X-Frame-Options Header
		if (function_exists('header_remove')) {
			header_remove('X-Frame-Options');
		}

		// Output HTML
		?>

		<DOCTYPE html>
		<html>
			<head>
				<meta http-equiv="refresh" content="600" />
				<link href="http://fonts.googleapis.com/css?family=Bitter" rel="stylesheet" type="text/css" />
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
				#logo {
					position: absolute;
					z-index: 10000;
					bottom: 15px;
					left: 15px;
					width: 200px;
					height: 200px;
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
					font-style: normal;
					font-family: "Bitter", serif;
					font-size: 60px;
					position: absolute;
					bottom: 60px;
					right: 0;
					width: 100%;
					background: rgba(0, 0, 0, 0.4);
					color: #fff;
					text-align: right;
					margin: 0;
					padding: 25px;
					text-shadow: 2px 2px 10px #000;
				}
				.caption small {
					color: rgba(255, 255, 255, 0.75);
					font-size: 45px;
					white-space: nowrap;
				}
				<?php 
				if ($customCss = $plugin->getOption('greenscreen_css')) {
					echo $customCss;
				}
				?>
				</style>
			</head>
			<body>

				<?php if (isset($_REQUEST['logo'])): ?>
					<img id="logo" src="<?php echo htmlspecialchars($_REQUEST['logo'])?>" />
				<?php endif; ?>

				<div id="greenscreen">
					<?php foreach ($photos['data'] as $member): ?>
						<div class="photo">
							<img data-lazy="<?php echo $member['photo'] ?>" />
							<h1 class="caption">
								<?php echo htmlspecialchars($member['display_name']) ?>
								<?php 
								if (isset($_REQUEST['subtext'])) {
									echo '<small>' . htmlspecialchars($_REQUEST['subtext']) . '</small>';
								}
								?>
							</h1>
						</div>
					<?php endforeach; ?>
				</div>

				<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
				<script type="text/javascript" src="//cdn.jsdelivr.net/jquery.slick/1.3.15/slick.min.js"></script>

				<script type="text/javascript">
				$('#greenscreen').slick({
					lazyLoad: 'progressive',
					autoplay: true,
  					autoplaySpeed: <?php echo $autoplaySpeed ?>
				});
				</script>

			</body>
		</html>

		<?php

		// Done
		exit;
	}
}
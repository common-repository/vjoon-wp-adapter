<?php
/**
 * Readme page for vjoon WP Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wrap">
	<div class="loader"></div>
	<div class="md">
	<?php
		$file = __DIR__ . '/../../README.md';
		echo esc_attr(
			Parsedown::instance()
			->setMarkupEscaped( false )
			->setUrlsLinked( true )
			->setBreaksEnabled( true )
			->text( vjoon\Adapter\Functions::get_file_content( $file ) )
		);
		?>
	</div>
</div>

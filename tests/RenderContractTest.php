<?php
/** Rendering contract tests. */

use PHPUnit\Framework\TestCase;

final class RenderContractTest extends TestCase {
	/** Name-only is a complete shell with no reserved optional regions. */
	public function test_name_only_has_one_heading_and_no_optional_markup() {
		$html = $this->render( array( 'name' => 'RAN Example' ) );

		$this->assertSame( 1, substr_count( $html, '<h1 ' ) );
		$this->assertStringContainsString( 'ran-admin-shell--name-only', $html );
		$this->assertStringContainsString( 'RAN Example', $html );
		$this->assertStringNotContainsString( '__strapline', $html );
		$this->assertStringNotContainsString( '__logo', $html );
		$this->assertStringNotContainsString( '__background', $html );
		$this->assertStringNotContainsString( '__navigation', $html );
		$this->assertStringNotContainsString( '__actions', $html );
		$this->assertStringNotContainsString( '__version', $html );
	}

	/** Optional values appear independently and unsafe copy is escaped. */
	public function test_passive_options_render_and_escape() {
		$html = $this->render(
			array(
				'name'       => '<Example>',
				'strapline'  => 'A shared vocabulary',
				'version'    => '1.2.3',
				'logo'       => array( 'url' => 'https://example.com/logo.png', 'width' => 128, 'height' => 128 ),
				'background' => array( 'url' => 'https://example.com/art.png', 'width' => 1600, 'height' => 400 ),
				'navigation' => array(
					array( 'label' => 'Current', 'url' => '/current', 'current' => true ),
					array( 'label' => 'Also current', 'url' => '/other', 'current' => true ),
				),
				'actions'    => array( array( 'label' => 'Support', 'url' => '/support' ) ),
			)
		);

		$this->assertStringContainsString( '&lt;Example&gt;', $html );
		$this->assertStringContainsString( '__strapline', $html );
		$this->assertStringContainsString( '__logo', $html );
		$this->assertStringContainsString( '__background', $html );
		$this->assertStringContainsString( '__navigation', $html );
		$this->assertStringContainsString( '__actions', $html );
		$this->assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
	}

	/** Invalid images and blank arrays normalize to absence. */
	public function test_invalid_optional_values_fail_closed() {
		$html = $this->render(
			array(
				'name'       => 'RAN Example',
				'strapline'  => ' ',
				'logo'       => array( 'url' => '/logo.png', 'width' => 0, 'height' => 10 ),
				'background' => array( 'url' => '', 'width' => 10, 'height' => 10 ),
				'navigation' => array(),
				'actions'    => array(),
			)
		);

		$this->assertStringNotContainsString( '__strapline', $html );
		$this->assertStringNotContainsString( '__logo', $html );
		$this->assertStringNotContainsString( '__background', $html );
		$this->assertStringNotContainsString( '__navigation', $html );
		$this->assertStringNotContainsString( '__actions', $html );
	}

	/** A missing required name renders nothing. */
	public function test_missing_name_renders_nothing() {
		$this->assertSame( '', $this->render( array( 'strapline' => 'No product' ) ) );
	}

	/** Render the symbol-free resource in a local scope. */
	private function render( array $configuration ) {
		$ran_admin_shell = $configuration;
		ob_start();
		include dirname( __DIR__ ) . '/resources/admin-shell.php';
		return (string) ob_get_clean();
	}
}

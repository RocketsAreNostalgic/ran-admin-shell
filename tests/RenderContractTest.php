<?php
/** Rendering contract tests. */

use PHPUnit\Framework\TestCase;

final class RenderContractTest extends TestCase {
	/** Name-only is a complete shell with no reserved optional regions. */
	public function test_name_only_has_one_heading_and_no_optional_markup(): void {
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
	public function test_passive_options_render_and_escape(): void {
		$html = $this->render(
			array(
				'name'       => '<Example>',
				'strapline'  => 'A shared vocabulary',
				'version'    => '1.2.3',
				'logo'       => array(
					'url'    => 'https://example.com/logo.png',
					'width'  => 128,
					'height' => 128,
				),
				'background' => array(
					'url'    => 'https://example.com/art.png',
					'width'  => 1600,
					'height' => 400,
				),
				'navigation' => array(
					array(
						'label'   => 'Current',
						'url'     => '/current',
						'current' => true,
					),
					array(
						'label'   => 'Also current',
						'url'     => '/other',
						'current' => true,
					),
				),
				'actions'    => array(
					array(
						'label' => 'Support',
						'url'   => '/support',
					),
				),
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
	public function test_invalid_optional_values_fail_closed(): void {
		$html = $this->render(
			array(
				'name'       => 'RAN Example',
				'strapline'  => ' ',
				'logo'       => array(
					'url'    => '/logo.png',
					'width'  => 0,
					'height' => 10,
				),
				'background' => array(
					'url'    => '',
					'width'  => 10,
					'height' => 10,
				),
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
	public function test_missing_name_renders_nothing(): void {
		$this->assertSame( '', $this->render( array( 'strapline' => 'No product' ) ) );
	}

	/** Breadcrumbs take precedence and the last item is always current text. */
	public function test_breadcrumbs_render_a_single_accessible_navigation_region(): void {
		$html            = $this->render(
			array(
				'name'              => 'Example',
				'breadcrumbs_label' => 'Where "now"',
				'breadcrumbs'       => array(
					array(
						'label'   => 'Parent <one>',
						'url'     => '/parent?one=1&two=2',
						'current' => true,
					),
					array(
						'label'   => 'Leaf & detail',
						'url'     => '/ignored',
						'current' => false,
					),
				),
				'navigation'        => array(
					array(
						'label' => 'Hidden destination',
						'url'   => '/hidden',
					),
				),
			)
		);
		$document        = new DOMDocument();
		$previous_errors = libxml_use_internal_errors( true );
		try {
			$loaded = $document->loadHTML( '<!doctype html><html><body>' . $html . '</body></html>' );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_errors );
		}
		$this->assertTrue( $loaded );
		$xpath = new DOMXPath( $document );
		$this->assertSame( 1.0, $xpath->evaluate( 'count(//h1)' ) );
		$this->assertSame( 1.0, $xpath->evaluate( 'count(//nav)' ) );
		$this->assertSame( 'Where "now"', $xpath->evaluate( 'string(//nav/@aria-label)' ) );
		$this->assertSame( 2.0, $xpath->evaluate( 'count(//nav/ol/li)' ) );
		$this->assertSame( 'Parent <one>', $xpath->evaluate( 'string(//nav/ol/li[1]/a)' ) );
		$this->assertSame( '/parent?one=1&two=2', $xpath->evaluate( 'string(//nav/ol/li[1]/a/@href)' ) );
		$this->assertSame( 'Leaf & detail', $xpath->evaluate( 'string(//*[@aria-current="page"])' ) );
		$this->assertSame( 1.0, $xpath->evaluate( 'count(//*[@aria-current="page"])' ) );
		$this->assertSame( 0.0, $xpath->evaluate( 'count(//nav/ol/li[2]/a)' ) );
		$this->assertSame( 1.0, $xpath->evaluate( 'count(//span[@class="ran-admin-shell__separator" and @aria-hidden="true"])' ) );
		$this->assertStringNotContainsString( 'Hidden destination', $html );
		$this->assertStringNotContainsString( '/ignored', $html );
	}

	public function test_single_breadcrumb_needs_no_url_and_uses_default_label(): void {
		$html = $this->render(
			array(
				'name'              => 'Example',
				'breadcrumbs_label' => ' ',
				'breadcrumbs'       => array( array( 'label' => 'Current' ) ),
			)
		);
		$this->assertStringContainsString( 'aria-label="Breadcrumbs"', $html );
		$this->assertStringContainsString( '<span aria-current="page">Current</span>', $html );
		$this->assertStringNotContainsString( '__separator', $html );
	}

	/** Any invalid member removes the whole trail and retains valid section navigation. */
	public function test_invalid_breadcrumbs_fall_back_to_navigation(): void {
		$invalid = array(
			null,
			'trail',
			array(),
			array( null ),
			array( array( 'label' => '' ) ),
			array( array( 'label' => array() ) ),
			array( array( 'label' => 'Parent' ), array( 'label' => 'Leaf' ) ),
			array(
				array(
					'label' => 'Parent',
					'url'   => ' ',
				),
				array( 'label' => 'Leaf' ),
			),
			array(
				array(
					'label' => 'Parent',
					'url'   => array(),
				),
				array( 'label' => 'Leaf' ),
			),
			array(
				array(
					'label' => 'Parent',
					'url'   => '/parent',
				),
				null,
			),
		);
		foreach ( $invalid as $trail ) {
			$html = $this->render(
				array(
					'name'             => 'Example',
					'breadcrumbs'      => $trail,
					'navigation_label' => ' ',
					'navigation'       => array(
						array(
							'label'   => 'Section',
							'url'     => '/section',
							'current' => true,
						),
					),
				)
			);
			$this->assertStringNotContainsString( '__breadcrumbs', $html );
			$this->assertStringContainsString( 'aria-label="Plugin sections"', $html );
			$this->assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		}
		$html = $this->render(
			array(
				'name'        => 'Example',
				'breadcrumbs' => array( null ),
			)
		);
		$this->assertStringNotContainsString( '<nav', $html );
	}

	/** Blocked ancestor URLs invalidate the trail with or without fallback navigation. */
	public function test_blocked_breadcrumb_urls_fall_back(): void {
		foreach ( array( 'javascript:alert(1)', 'data:text/html,test' ) as $url ) {
			$args = array(
				'name'        => 'Example',
				'breadcrumbs' => array(
					array(
						'label' => 'Parent',
						'url'   => $url,
					),
					array( 'label' => 'Leaf' ),
				),
			);
			$html = $this->render( $args );
			$this->assertStringNotContainsString( '<nav', $html );
			$args['navigation'] = array(
				array(
					'label' => 'Section',
					'url'   => '/section',
				),
			);
			$html               = $this->render( $args );
			$this->assertStringNotContainsString( '__breadcrumbs', $html );
			$this->assertStringContainsString( 'href="/section"', $html );
			$this->assertStringNotContainsString( 'href=""', $html );
		}
	}

	public function test_preview_keeps_quality_annotations_out_of_html(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Save controlled request state for restoration; this preview test performs no protected mutation.
		$previous_query = $_GET;
		$_GET           = array( 'case' => 'name-only' );
		ob_start();
		try {
			require dirname( __DIR__ ) . '/fixtures/index.php';
			$html = (string) ob_get_contents();
		} finally {
			ob_end_clean();
			$_GET = $previous_query;
		}
		$this->assertStringContainsString( '<title>RAN Admin Shell — name-only</title>', $html );
		$this->assertStringContainsString( 'ran-admin-shell--name-only', $html );
		$this->assertStringNotContainsString( 'phpcs:', $html );
	}

	/**
	 * Render the symbol-free resource in a local scope.
	 *
	 * @param array<string, mixed> $configuration Deliberately valid or malformed renderer input.
	 * @return string
	 */
	private function render( array $configuration ) {
		$ran_admin_shell = $configuration;
		ob_start();
		include dirname( __DIR__ ) . '/resources/admin-shell.php';
		return (string) ob_get_clean();
	}
}

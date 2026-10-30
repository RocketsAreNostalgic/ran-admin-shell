<?php
/** Verify the explicit composition independently of optional renderer inputs. */

use PHPUnit\Framework\TestCase;

final class NoticePlacementContractTest extends TestCase {
	public function test_each_composition_has_one_pre_shell_marker_and_contextual_feedback(): void {
		foreach ( array( 'global-only', 'owned-only', 'combined', 'empty', 'hidden', 'mixed', 'dismissed', 'css-hidden', 'updated', 'error' ) as $case ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve controlled query state around a read-only fixture include.
			$previous_query = $_GET;
			$_GET           = array( 'case' => $case );
			ob_start();
			try {
				require dirname( __DIR__ ) . '/fixtures/notice-placement.php';
				$html = (string) ob_get_contents();
			} finally {
				ob_end_clean();
				$_GET = $previous_query;
			}
			$document = new DOMDocument();
			$this->assertTrue( $document->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING ) );
			$xpath = new DOMXPath( $document );
			$this->assertSame( 1.0, $xpath->evaluate( 'count(//hr[@class="wp-header-end"])' ), $case );
			$this->assertSame( 1.0, $xpath->evaluate( 'count(//h1)' ), $case );
			$this->assertSame( 1.0, $xpath->evaluate( 'count(//div[@class="ran-admin-shell-notices"]/following-sibling::header)' ), $case );
			$this->assertSame( 1.0, $xpath->evaluate( 'count(//form//p[@id="form-feedback"])' ), $case );
			$this->assertSame( 1.0, $xpath->evaluate( 'count(//tr//div[@id="row-feedback"])' ), $case );
			$this->assertStringNotContainsString( 'phpcs:', $html );
			if ( in_array( $case, array( 'owned-only', 'combined', 'mixed' ), true ) ) {
				$this->assertSame( 1.0, $xpath->evaluate( 'count(//header/following-sibling::div//div[@id="owned-notice" and contains(@class,"inline")])' ), $case );
			}
			if ( in_array( $case, array( 'global-only', 'combined', 'mixed', 'updated', 'error' ), true ) ) {
				$this->assertSame( 1.0, $xpath->evaluate( 'count(//div[@id="global-notice"]/following-sibling::header)' ), $case );
			}
		}
	}
}

<?php
/** Browser fixture for the optional-first shell. */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$cases = require __DIR__ . '/cases.php';
$case  = isset( $_GET['case'] ) && is_string( $_GET['case'] ) && isset( $cases[ $_GET['case'] ] ) ? $_GET['case'] : 'name-only';
$ran_admin_shell = $cases[ $case ];
?><!doctype html>
<html lang="en"<?php echo 'rtl' === $case ? ' dir="rtl"' : ''; ?>>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>RAN Admin Shell — <?php echo esc_html( $case ); ?></title>
	<link rel="stylesheet" href="../resources/admin-shell.css" />
	<style>
		body { background: #f0f0f1; color: #1d2327; font: 14px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; }
		.fixture-toolbar { background: #1d2327; color: #fff; padding: 12px 24px; }
		.fixture-toolbar a { color: #fff; margin-inline-end: 12px; }
		.fixture-canvas { margin: 32px auto; max-width: 1120px; padding: 0 20px; }
	</style>
</head>
<body>
	<nav class="fixture-toolbar" aria-label="Fixture cases">
		<?php foreach ( array_keys( $cases ) as $case_name ) : ?>
			<a href="?case=<?php echo esc_attr( $case_name ); ?>"><?php echo esc_html( $case_name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<main class="fixture-canvas">
		<?php include dirname( __DIR__ ) . '/resources/admin-shell.php'; ?>
		<div class="postbox" style="background:#fff;border:1px solid #c3c4c7;padding:20px">Consumer-owned page content</div>
	</main>
</body>
</html>

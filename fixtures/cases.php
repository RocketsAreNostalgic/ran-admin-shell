<?php
/** Development fixture cases. */

$logo = 'data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#2c3338" d="M10 3h12l-2 13 5 8H7l5-8z"/><path fill="#3858e9" d="M11 26h10l-5 5z"/></svg>' );

return array(
	'booster-sections'    => array(
		'name'       => 'Booster',
		'strapline'  => 'Rockets Are Nostalgic',
		'logo'       => array(
			'url'    => $logo,
			'width'  => 32,
			'height' => 32,
		),
		'navigation' => array(
			array(
				'label'   => 'Overview',
				'url'     => '#content',
				'current' => true,
			),
			array(
				'label' => 'Settings',
				'url'   => '#content',
			),
			array(
				'label' => 'Troubleshooting',
				'url'   => '#content',
			),
		),
	),
	'breadcrumbs'         => array(
		'name'        => 'Booster',
		'strapline'   => 'Rockets Are Nostalgic',
		'breadcrumbs' => array(
			array(
				'label' => 'Overview',
				'url'   => '#content',
			),
			array(
				'label' => 'Settings',
				'url'   => '#content',
			),
			array(
				'label'   => 'Secure credential storage',
				'current' => true,
			),
		),
	),
	'long-navigation'     => array(
		'name'       => 'Booster',
		'navigation' => array(
			array(
				'label'   => 'Overview',
				'url'     => '#content',
				'current' => true,
			),
			array(
				'label' => 'An intentionally long navigation label',
				'url'   => '#content',
			),
			array(
				'label' => 'Diagnostics and troubleshooting',
				'url'   => '#content',
			),
			array(
				'label' => 'Additional settings and support',
				'url'   => '#content',
			),
		),
	),
	'name-only'           => array(
		'name' => 'RAN Duplicate Detector',
	),
	'turnstile'           => array(
		'name'      => 'RAN Turnstile for Jetpack Forms',
		'strapline' => 'Protect every Jetpack form on this site with Cloudflare Turnstile.',
	),
	'all-passive-options' => array(
		'name'             => 'RAN Example Plugin',
		'home_url'         => '#',
		'strapline'        => 'A shared visual vocabulary with consumer-owned content.',
		'version'          => '1.2.3',
		'navigation_label' => 'Example sections',
		'navigation'       => array(
			array(
				'label'   => 'Overview',
				'url'     => '#',
				'current' => true,
			),
			array(
				'label' => 'Settings',
				'url'   => '#',
			),
			array(
				'label' => 'Other',
				'url'   => '#',
			),
		),
		'actions'          => array(
			array(
				'label' => 'Documentation',
				'url'   => '#',
			),
			array(
				'label' => 'Support',
				'url'   => '#',
			),
		),
	),
	'blank-optionals'     => array(
		'name'       => 'RAN Heading Only',
		'strapline'  => '',
		'navigation' => array(),
		'actions'    => array(),
	),
	'invalid-optionals'   => array(
		'name'       => 'RAN Invalid Optional Inputs',
		'logo'       => array( 'url' => '/missing-size.png' ),
		'background' => array(
			'url'    => '',
			'width'  => 0,
			'height' => 0,
		),
	),
	'long-localized-copy' => array(
		'name'      => 'Rockets Are Nostalgic — A deliberately long localized plugin heading that must wrap without reserving empty columns',
		'strapline' => 'This deliberately lengthy localized supporting sentence proves that the masthead grows naturally rather than clipping copy or imposing a fixed height.',
	),
	'rtl'                 => array(
		'name'       => 'RAN Example in a right-to-left layout',
		'strapline'  => 'Logical properties keep the same component vocabulary in both directions.',
		'navigation' => array(
			array(
				'label'   => 'Overview',
				'url'     => '#content',
				'current' => true,
			),
			array(
				'label' => 'Settings and troubleshooting',
				'url'   => '#content',
			),
		),
	),
);

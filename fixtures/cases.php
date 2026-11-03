<?php
/** Development fixture cases. */

$logo = '/fixtures/nozzle.svg';

$cases = array(
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
	'many-actions'        => array(
		'name'    => 'RAN Example Plugin',
		'actions' => array(
			array(
				'label' => 'Documentation',
				'url'   => '#',
			),
			array(
				'label' => 'Support',
				'url'   => '#',
			),
			array(
				'label' => 'Settings',
				'url'   => '#',
			),
			array(
				'label' => 'An extraordinarily long action label that must wrap within the viewport',
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

$cases['classic-scrollbar'] = $cases['long-navigation'];

return $cases;

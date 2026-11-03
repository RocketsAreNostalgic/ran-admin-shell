# Compact shell v2

The v2 shell uses native PHP markup and public WordPress Design System tokens.
It ships the same two synchronized runtime resources as v1: PHP and CSS. There
is no React bundle, shared runtime, automatic enqueue or change to the
synchronization/provenance schema. Consumer pages can use WordPress UI alongside
the shell without replacing destination links with tab widgets.

## Identity and navigation

Only `name` is required. The existing `strapline` supplies the optional publisher
line; logo, version, actions and decorative background remain independent.

```php
$ran_admin_shell = array(
    'name'       => 'Booster',
    'strapline'  => 'Rockets Are Nostalgic',
    'navigation' => array(
        array( 'label' => 'Overview', 'url' => $overview_url, 'current' => true ),
        array( 'label' => 'Settings', 'url' => $settings_url ),
    ),
);
```

Use `navigation` for peer page destinations. The selected link is underlined
and has `aria-current="page"`; links retain their URLs and ordinary browser
navigation. These are not ARIA tabs.

For a hierarchical page, provide `breadcrumbs` instead:

```php
$ran_admin_shell['breadcrumbs_label'] = 'Breadcrumbs';
$ran_admin_shell['breadcrumbs'] = array(
    array( 'label' => 'Overview', 'url' => $overview_url ),
    array( 'label' => 'Settings', 'url' => $settings_url ),
    array( 'label' => 'Secure credential storage' ),
);
```

Every item needs a nonblank scalar label. Every ancestor needs a nonblank
scalar URL that remains nonempty after WordPress URL escaping. The final item
is the current location, rendered as text with
`aria-current="page"`; its URL and any `current` flags are ignored. Invalid
members invalidate the whole trail. A valid trail takes precedence over peer
navigation. Empty or invalid breadcrumbs fall back to valid `navigation`;
without either, the navigation region is absent. The consumer supplies
translated labels, authorized destinations and accurate hierarchy.

## Colour schemes

`color_scheme` accepts `light` or `inverse`. Light is the default; missing,
blank, unrecognised and non-string values retain the light presentation.

```php
$ran_admin_shell['color_scheme'] = 'inverse';
```

Light retains the WordPress design-system colour tokens and their light
fallbacks. Inverse supplies a shell-scoped navy surface with light foregrounds,
borders, hover states and focus indicators. It does not change the rest of the
WordPress dashboard or redefine public WordPress tokens. Typography, spacing,
navigation behaviour and optional-input structure remain the same in both.

The consumer supplies the appropriate `logo` asset for its chosen scheme.
Images are not automatically colour-inverted. The preview uses matching dark
and light fixture logos. Forced-colours mode uses system colours in either
scheme.

The preview's **Light** and **Inverse** links preserve the selected example.
For example, compare `/fixtures/?case=booster-sections&color_scheme=light` and
`/fixtures/?case=booster-sections&color_scheme=inverse`. These read-only preview
links do not store a plugin or WordPress preference.

## Tokens and WordPress UI

The stylesheet consumes public `--wpds-*` surface, foreground, stroke, spacing,
typography and focus tokens. The mapping was checked against the WordPress
[token reference](https://github.com/WordPress/gutenberg/blob/7c313d8b1a631dd7490b331410d8923a531269e5/packages/theme/docs/tokens.md).
It does not copy private component CSS or redefine WordPress tokens globally.
Literal fallbacks keep the distributed, unbuilt stylesheet usable without a
theme provider. The stylesheet's exception for
`plugin-wpds/no-token-fallback-values` is limited to that rule; token-name
validation and the remaining CSS checks stay active.

Consumers own style enqueueing on their exact screens. If WordPress registers
the `wp-theme` stylesheet, it can be used as a dependency. Check
`wp_style_is( 'wp-theme', 'registered' )` first: the presence of a newer npm
package does not establish an older installed WordPress runtime handle. Do not
inject a second document-wide token stylesheet into an existing admin screen.
Product styling remains consumer-owned and scoped to its pages.

This is native WordPress Design System integration. It does not load the React
`@wordpress/ui` package. Consumers adopting interactive WordPress UI components
own their pinned npm/runtime versions, build, theme provider, portals and
exclusive DOM lifecycle. The shell does not introduce an unused React adapter.

## Sticky layout

The default identity row has a 64px minimum height and a 32px logo. Section
navigation follows Jetpack AI's minimal treatment: a 48px row, regular 13px
labels, 16px gaps and a 2px label-width current indicator. Identity and navigation
use the same 24px horizontal inset. Breadcrumb links retain 44px targets.
Long content grows naturally, and long navigation scrolls
horizontally. The entire identity/navigation header sticks; no second header
or cloned heading is created.

With the WordPress `admin-bar` body class, the sticky offset is 32px on desktop
and 46px from 601px through 782px. At 600px and below the native toolbar scrolls
away, so the shell sticks at zero. Without that class the offset is zero.
At viewport heights of 400px or less, the shell stays in normal flow to keep
content reachable. Consumers with customized toolbar geometry must adapt the
offset in their exact-screen stylesheet.

Keep the shell as a direct page-level sibling before `.wrap`. An ancestor with
scrolling/clipping overflow can change the sticky containing block. Native
Help/Screen Options stay above the shell; keep their existing positioned
stacking level higher than the shell's level 10. The admin toolbar, menus and
consumer overlays retain their own higher layers.

Following content anchors and focused controls receive a default 192px scroll
margin. This includes room for a classic horizontal scrollbar at the responsive
46px toolbar breakpoint. For an unusually tall header, override
`--ran-admin-shell-scroll-margin` on the containing content scope and
verify focus and anchor destinations at the relevant viewport. This is a CSS
allowance, not runtime measurement of an arbitrary header.

The existing [notice placement](notice-placement.md) contract remains: global
notices and one native marker before the sticky header, owned inline page
notices below it, and form/row feedback beside its control. Partial/HTMX
responses do not render another header or marker. No shell JavaScript needs
mounting, disposal or reattachment after navigation.

## Version boundary and adoption

This is a breaking visual/resource change intended for v2. Preserve the existing
v1.0.0 tag. Booster, Turnstile and EmailOctopus remain pinned to exact v1 until
their own reviewed adoption. V2 development does not create a tag, release,
maintenance branch or consumer lock update. A v1 backport can establish a
maintenance branch when there is a concrete need; a moving branch is not an
immutable consumer pin.

Booster is the first intended v2 consumer under
[Booster #628](https://github.com/RocketsAreNostalgic/ran-booster/issues/628).
Adoption uses the existing dependency/lock, explicit sync, generated-resource
commit and immutable check workflow. The source package remains a development
dependency excluded from plugin archives.

## Preview and verification

Run `php -S 127.0.0.1:18736` from the repository and open
`/fixtures/?case=booster-sections` or `/fixtures/?case=breadcrumbs`, then use
the Light/Inverse links to compare the same example in both schemes.
The fixtures cover name-only, missing/invalid options, long labels, RTL and
notice composition. They are standalone previews, not an installed WordPress
acceptance claim.

Run the existing `composer check` and `pnpm check`, then the committed-HEAD
export/install/sync/removal proof included in the Composer suite. Consumer
adoption separately checks actual WordPress toolbar/sidebar and Help behaviour,
notice relocation, focus and anchors, mobile/RTL/zoom, overlays and repeated
enhanced navigation against the adopted immutable revision.

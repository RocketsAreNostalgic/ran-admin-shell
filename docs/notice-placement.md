# Opt-in notice placement

Ownership, audience and placement are separate decisions. A plugin-owned urgent
warning may legitimately appear on unrelated admin screens. The feature owns its
urgency, dismissal, snooze, acknowledgement, persistence, concurrency and operational
policy. The consumer owns exact-screen and capability checks, escaping, hooks,
explicit ownership, capture where necessary and contextual rendering. Admin Shell
supplies this composition and passive spacing only.

On an explicitly adopted full page, render this after WordPress has emitted its
ordinary global notice hooks, immediately before the synchronized shell:

```html
<div class="ran-admin-shell-notices">
	<hr class="wp-header-end">
</div>
```

Render the synchronized shell next (including its one page H1 and navigation),
then the consumer's `.wrap`, with owned page notices marked `.inline`. Keep form
and row feedback inline beside the controls or operation it describes. Partial
and HTMX responses render only their context, without another shell or marker.
The consumer may retain its existing queue for explicitly owned, escaped output.
Do not buffer, suppress or replay global hooks, call `admin_notices` again, or
infer ownership from callback names, severity, classes or plugin identity.

Remove the old post-shell `.wp-header-end`. Exactly one native marker must exist
on the full page. WordPress `common.js` moves ordinary `div.notice`, `div.updated`
and `div.error` elements, excluding `.inline` (and legacy `.below-h2`), after that
marker. Two markers can cause jQuery insertion to clone notices; a post-shell
marker routes unrelated notices below navigation. Server ordering alone does not
prove browser placement. Native dynamic notice producers also use this marker;
verify their actual insertion paths. The shell installs no JavaScript manager.

Before JavaScript, global notices emitted by WordPress precede the destination
and shell. After native relocation they are inside the destination, still before
the shell. Owned inline page notices start below navigation and stay there.
The opt-in CSS gives each global notice bottom separation, retaining native
horizontal margins in both positions. The destination establishes a formatting
context and hides the marker; empty, HTML-hidden, CSS-hidden and removed notices
therefore contribute no reserved spacing. It does not test visibility with
`:not([hidden])`, which would still count CSS-hidden notices. The pre-destination
selector uses `:has()`, supported by current WordPress-supported browsers.
The existing direct-sibling notice/header rule remains for unadopted consumers;
the destination breaks that adjacency deliberately. Consumers must retire any
local broad sibling spacing override that duplicates this adopted spacing, while
preserving its hidden/mixed-state regressions.

The destination must remain a direct child of `#wpbody-content`, immediately
before the shell. Do not put the shell inside `.wrap` or give the destination
`.wrap` margins. Preserve native Screen Options/Help and the consumer's existing
exact-screen stacking rules. Full-width shell offsets remain 20px desktop and
10px mobile. No renderer option, shared PHP symbol, synchronization schema,
runtime service, state or asset enqueue is added. Name-only and invalid/absent
optional renderer inputs retain their structure; adoption is consumer markup.

## Acceptance and immutable handoff

`fixtures/notice-placement.php` is a standalone full-page composition preview,
with simplified native styling and inert Help/Screen Options controls. It covers
global-only, owned-only, combined, empty, hidden, mixed, CSS-hidden and dismissed
states, including `notice`, `updated` and `error`. It is package fixture evidence,
not an installed WordPress or native-control acceptance claim.

Run the package's `composer check` and `pnpm check`, including render,
synchronization, immutable provenance and committed-export distribution proofs.
Record the actual opened PR's exact base/head and independent review. Identify
the reviewed immutable package revision before consumer adoption. Consumers use
their normal Composer dependency/lock, explicit sync, committed PHP/CSS/provenance
and immutable verification workflow; never edit generated resource copies.
No tag or publication is implicit. The package and development vendor tree stay
out of installable consumer archives.

Booster #560 owns the first installed pilot. Record exact source, resource
revision, artifact hash and installed byte readback. Exercise JavaScript on/off,
full native relocation and dynamic notices, desktop/mobile, relevant site/network
screens, global urgency, owned page output once, contextual form/row/fragment
feedback, empty/hidden/mixed/dismissed spacing, one marker/H1, native Help/Screen
Options and non-shell screens. Preserve feature policy and satellite warnings
when Core is unavailable. Report package-source, installed acceptance and eventual
release/deployment separately. Turnstile and EmailOctopus adopt independently
after this pilot; no consumer update or release follows from this package PR.

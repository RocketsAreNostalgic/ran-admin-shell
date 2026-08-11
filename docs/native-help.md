# Native contextual Help

Contextual Help belongs to each consumer because its copy, links, capabilities
and screen lifecycle are product-specific.

Capture the return value from `add_options_page()` (or the corresponding menu
function), then register tabs on `load-$hook_suffix`. Add a sidebar only when at
least one valid tab exists; WordPress does not expose the Help control for a
sidebar by itself. A consumer with no Help content registers no callback, tabs
or sidebar.

The shared shell never replaces WordPress Help or Screen Options.

For the native controls to appear over a full-width shell, render the shell as
a direct page-level sibling before opening the consumer's `.wrap`. The package
offsets WordPress's responsive `#wpcontent` start padding and uses
`overflow: clip` so the header background can continue beneath WordPress's
floating screen-meta controls. The logical offset supports both LTR and RTL.
The consumer's exact-screen stylesheet should give `#screen-meta-links` a
positioned stacking level above the shell. When Help opens, WordPress's panel
remains in normal flow above the shell.

# Native contextual Help

Contextual Help belongs to each consumer because its copy, links, capabilities
and screen lifecycle are product-specific.

Capture the return value from `add_options_page()` (or the corresponding menu
function), then register tabs on `load-$hook_suffix`. Add a sidebar only when at
least one valid tab exists; WordPress does not expose the Help control for a
sidebar by itself. A consumer with no Help content registers no callback, tabs
or sidebar.

The shared shell never replaces WordPress Help or Screen Options.

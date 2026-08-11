# Auxiliary drawers

The initial package accepts the valid zero-drawer state and ships no shared
drawer markup, CSS or JavaScript. Turnstile, the first production consumer, has
no genuine auxiliary-drawer requirement.

When a production consumer has a real read-only side/top drawer use case, add
the feature as an explicit package extension. That change must deliver zero,
one and multiple fixtures together and prove unique IDs, labelled controls,
keyboard operation, focus containment and return, Escape close, narrow/short
viewport behavior, RTL, reduced motion and forced-colour support.

An inline `details` element is a disclosure, not a drawer. Drawer descriptors
must not accept callbacks, forms, remote loaders, mutation authority or an
arbitrary whole-view HTML slot.

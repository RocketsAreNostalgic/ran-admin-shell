# RAN Admin Shell

Build-time admin-shell resources for Rockets Are Nostalgic WordPress plugins.
The package provides an optional-first visual vocabulary without a live
cross-plugin runtime dependency.

Only a non-empty `name` is required. `home_url`, `strapline`, `logo`,
`background`, `version`, `navigation` and `actions` are independently optional.
Blank or invalid optional values emit no feature markup and reserve no layout
space. Native contextual Help is consumer-owned.

Consumers install this as a development dependency, create
`ran-admin-shell.json`, then explicitly synchronize and verify committed
runtime files:

```sh
vendor/bin/ran-admin-shell sync --config=ran-admin-shell.json
vendor/bin/ran-admin-shell check --config=ran-admin-shell.json --immutable
```

The package and `vendor/` stay out of installed WordPress sites.

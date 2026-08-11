# RAN Admin Shell

This repository is a build-time Composer library for shared Rockets Are
Nostalgic WordPress admin-shell resources. It is not an activatable WordPress
plugin and must not register runtime hooks, globals, services or assets.

Only the product name is required by the rendering contract. Optional values
must disappear structurally when absent: no empty wrapper, control, grid track
or asset enqueue.

Consumers install this package as a development dependency, run the explicit
sync command, commit the synchronized PHP/CSS/provenance files and ship those
consumer-owned files. Consumers must not ship this package or Composer vendor
files in WordPress plugin archives.

Run `composer check` before handoff. Use Conventional Commits. Do not tag,
publish to Packagist or create a release without separate owner authorization.

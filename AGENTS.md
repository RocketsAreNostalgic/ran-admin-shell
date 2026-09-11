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

## External AI agent prohibition

Do not invoke, delegate work to, tag, enable, or otherwise use Blacksmith [code]smith,
`@codesmith-bot`, Blacksmith Autofix, Blacksmith CI Tuning, Blacksmith Testbox agents,
or any other Blacksmith AI/agent feature.

Blacksmith may be used only as infrastructure for ordinary GitHub Actions runners where
the repository workflow explicitly specifies a Blacksmith runner.

Do not click or trigger "Enable autofix", do not ask [code]smith to investigate or repair
CI, and do not call Blacksmith agent/MCP/CLI/API features that perform AI inference.

If CI fails, inspect GitHub Actions logs directly and diagnose/fix the failure yourself.

This prohibition is a cost-control requirement and must not be overridden by convenience,
CI failure, review comments, or suggestions from GitHub/Blacksmith UI.

# Releases

The initial setup uses an empty manifest. Release Please uses the full history
and proposes 1.0.0 as the first version for a later owner-approved release. Tags
use `vX.Y.Z`. Composer derives the version from the tag; there is no version
field in `composer.json` or the private quality package.

Use Conventional Commits to describe release impact. `feat` drives a minor
release; `fix` and `deps` drive a patch; a breaking change drives a major
release (use `feat!:` or a `BREAKING CHANGE:` footer). Review source, resource,
tool and runtime dependency effects for release significance. Use `feat`, `fix`,
`deps` or a supported breaking marker for a release-significant change. Hidden
types such as `chore`, `test` and `ci` are for work without user-facing changes.
Review the proposed version and changelog against the actual changes, including
the first release's history.

The [shared Profile A workflow][profile] runs after the Quality workflow
completes on `main` and qualifies that run before proposing a release. The
caller pins it to `289352e08cdf10b15d07c4e1c890f385afc3d3f5`. It dispatches
Quality for the bot-created candidate so that the pull request can receive the
required checks. Adding or merging this workflow enables that automation on
subsequent qualifying events; it does not itself grant release approval.

Before merging a release pull request, independently review its exact base and
head SHAs, resolve or disposition findings, and verify required checks for that
same tuple. The proposed merge method is squash merge, subject to the strict
Quality requirement without bypass. An owner must explicitly authorize merging
that specific pull request. After merge, main Quality must succeed before the
automation creates the tag and GitHub release; this is a separate decision from
preparing or reviewing the pull request.

The GitHub source archive is the package distribution input. Run the existing
`composer test` distribution proof against committed `HEAD`: it exports the
archive and installs it into an isolated Composer consumer, then checks sync,
immutable provenance and development dependency removal. This proof does not
publish to Packagist, deploy a site, or qualify a real consumer. Each consumer
must separately review its exact lock and provenance changes and verify its own
plugin archive before adoption. Preparing this automation does not update any
consumer.

[profile]: https://github.com/RocketsAreNostalgic/.github/blob/main/docs/release/RELEASE_PROFILE_A.md

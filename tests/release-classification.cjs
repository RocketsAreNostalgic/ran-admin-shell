const { execFileSync } = require('node:child_process');
const fs = require('node:fs');

function classify(base, head, paths) {
	const requirements = (value) => {
		const map = JSON.parse(value).require;
		if (!map || typeof map !== 'object' || Array.isArray(map)) {
			throw new Error('Composer require must be an object');
		}
		return JSON.stringify(
			Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
		);
	};
	return (
		requirements(base) !== requirements(head) ||
		paths.some(
			(path) =>
				path.startsWith('resources/') ||
				path === 'tools/SyncCommand.php' ||
				path === 'bin/ran-admin-shell'
		)
	);
}

function titleDrivesRelease(title, config) {
	const match = /^([a-z]+)(?:\([^\r\n()]+\))?(!)?: .+$/u.exec(title);
	if (!match) return false;
	return (
		Boolean(match[2]) ||
		config['changelog-sections'].some(
			(section) => section.type === match[1] && section.hidden === false
		)
	);
}

function checkPullRequest(event, config, git) {
	const { base, head, title } = event.pull_request;
	for (const sha of [base.sha, head.sha]) {
		if (!/^[0-9a-f]{40}$/u.test(sha))
			throw new Error('Invalid pull request SHA');
	}
	if (git(['rev-parse', 'HEAD']).trim() !== head.sha)
		throw new Error('Checkout does not match pull request head');
	const before = git(['show', `${base.sha}:composer.json`]);
	const after = git(['show', `${head.sha}:composer.json`]);
	const paths = git([
		'diff',
		'--no-renames',
		'--name-only',
		'-z',
		base.sha,
		head.sha,
		'--',
	])
		.split('\0')
		.filter(Boolean);
	if (classify(before, after, paths) && !titleDrivesRelease(title, config)) {
		throw new Error(
			'Release-significant changes require a visible release type or breaking marker in the squash title'
		);
	}
}

if (
	require.main === module &&
	process.env.GITHUB_EVENT_NAME === 'pull_request'
) {
	try {
		const event = JSON.parse(
			fs.readFileSync(process.env.GITHUB_EVENT_PATH, 'utf8')
		);
		const config = JSON.parse(
			fs.readFileSync('release-please-config.json', 'utf8')
		);
		checkPullRequest(event, config, (args) =>
			execFileSync('git', args, { encoding: 'utf8' })
		);
	} catch (error) {
		console.error(error.message);
		process.exitCode = 1;
	}
}

module.exports = { classify, titleDrivesRelease, checkPullRequest };

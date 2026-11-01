const assert = require('node:assert/strict');
const test = require('node:test');
const {
	classify,
	titleDrivesRelease,
	checkPullRequest,
} = require('./release-classification.cjs');
const config = require('../release-please-config.json');
const base = JSON.stringify({
	require: { php: '>=8.0' },
	'require-dev': { test: '1' },
});
const runtime = JSON.stringify({
	require: { php: '>=8.1' },
	'require-dev': { test: '1' },
});
const dev = JSON.stringify({
	require: { php: '>=8.0' },
	'require-dev': { test: '2' },
});

test('classifies runtime requirements and shipped files, not dev dependencies', () => {
	assert.equal(classify(base, runtime, []), true);
	assert.equal(classify(base, dev, []), false);
	assert.equal(classify(base, base, ['resources/admin-shell.css']), true);
	assert.equal(classify(base, base, ['tools/SyncCommand.php']), true);
	assert.equal(classify(base, base, ['bin/ran-admin-shell']), true);
	assert.throws(() => classify('{}', base, []));
});

test('visible types and breaking titles drive release', () => {
	for (const title of ['chore: update', 'refactor(core): change'])
		assert.equal(titleDrivesRelease(title, config), false);
	for (const title of [
		'deps: update',
		'feat!: change',
		'refactor(core)!: change',
	])
		assert.equal(titleDrivesRelease(title, config), true);
	assert.equal(titleDrivesRelease('not a conventional title', config), false);
});

test('checks current title and exact checked out pull request SHAs', () => {
	const baseSha = 'a'.repeat(40);
	const headSha = 'b'.repeat(40);
	const event = {
		pull_request: {
			base: { sha: baseSha },
			head: { sha: headSha },
			title: 'deps: update',
		},
	};
	const git = (args) =>
		args[0] === 'rev-parse'
			? headSha
			: args[0] === 'diff'
				? ''
				: args[1].startsWith(baseSha)
					? base
					: runtime;
	checkPullRequest(event, config, git);
	event.pull_request.title = 'chore: update';
	assert.throws(() => checkPullRequest(event, config, git));
	event.pull_request.head.sha = 'bad';
	assert.throws(() => checkPullRequest(event, config, git));
});

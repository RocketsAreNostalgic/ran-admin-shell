const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const yaml = require('js-yaml');

const read = (path) => fs.readFileSync(path, 'utf8');
const load = (path) => yaml.load(read(path));

function checkWorkflows(release, quality) {
	assert.equal(release.name, 'Release Please');
	assert.deepEqual(release.on.workflow_run, {
		workflows: ['Quality'],
		types: ['completed'],
		branches: ['main'],
	});
	assert.deepEqual(release.permissions, {});
	const job = release.jobs.release;
	assert.equal(
		job.uses,
		'RocketsAreNostalgic/.github/.github/workflows/release-profile-a.yml@289352e08cdf10b15d07c4e1c890f385afc3d3f5'
	);
	assert.deepEqual(job.permissions, {
		contents: 'write',
		issues: 'write',
		'pull-requests': 'write',
		actions: 'write',
	});
	assert.equal(
		job.with['expected-workflow-path'],
		'.github/workflows/quality.yml'
	);
	assert.equal(
		job.with['release-pr-head'],
		'release-please--branches--main--components--ran/admin-shell'
	);
	assert.equal(quality.name, 'Quality');
	assert.deepEqual(quality.on.pull_request.types, [
		'opened',
		'synchronize',
		'reopened',
		'edited',
	]);
	assert.ok(quality.jobs.quality.needs.includes('release-classification'));
	assert.match(
		quality.jobs.quality.steps[0].run,
		/test "\$RELEASE_CLASSIFICATION_RESULT" = success/
	);
	assert.equal(
		quality.jobs.quality.steps[0].env.RELEASE_CLASSIFICATION_RESULT,
		'${{ needs.release-classification.result }}'
	);
	assert.deepEqual(quality.on.push.branches, ['main']);
	assert.ok(Object.hasOwn(quality.on, 'workflow_dispatch'));
	const inputs = quality.on.workflow_dispatch?.inputs ?? {};
	assert.ok(Object.values(inputs).every((input) => input.required !== true));
	for (const permissions of [
		quality.permissions,
		...Object.values(quality.jobs).map((entry) => entry.permissions),
	]) {
		assert.notEqual(permissions, 'write-all');
		assert.ok(
			!permissions ||
				Object.values(permissions).every((value) => value !== 'write')
		);
	}
	assert.deepEqual(quality.permissions, { contents: 'read' });
}

const release = load('.github/workflows/release-please.yml');
const quality = load('.github/workflows/quality.yml');

test('release configuration targets the Composer library', () => {
	const config = JSON.parse(read('release-please-config.json'));
	const manifest = JSON.parse(read('.release-please-manifest.json'));
	const composer = JSON.parse(read('composer.json'));
	const npm = JSON.parse(read('package.json'));
	assert.equal(config['release-type'], 'php');
	assert.equal(config['include-component-in-tag'], false);
	assert.equal(config['include-v-in-tag'], true);
	assert.equal(config['changelog-path'], 'CHANGELOG.md');
	assert.equal(config.packages['.']['package-name'], composer.name);
	assert.equal(config.packages['.']['initial-version'], '1.0.0');
	assert.deepEqual(
		Object.keys(manifest).filter((key) => key !== '.'),
		[]
	);
	if (manifest['.'] !== undefined)
		assert.match(manifest['.'], /^\d+\.\d+\.\d+$/);
	assert.equal(composer.version, undefined);
	assert.equal(npm.version, undefined);
	const sections = Object.fromEntries(
		config['changelog-sections'].map(({ type, hidden }) => [type, hidden])
	);
	for (const type of ['feat', 'fix', 'perf', 'revert', 'deps'])
		assert.equal(sections[type], false);
	for (const type of [
		'docs',
		'style',
		'refactor',
		'test',
		'build',
		'ci',
		'chore',
	])
		assert.equal(sections[type], true);
});

test('Profile A qualifies main Quality and can dispatch candidate Quality', () => {
	checkWorkflows(release, quality);
});

test('workflow contract rejects unsafe or mismatched fixtures', () => {
	const cases = [
		(r, q) => {
			q.on.pull_request.types = ['opened'];
		},
		(r, q) => {
			q.jobs.quality.needs = ['baseline', 'frontend'];
		},
		(r, q) => {
			q.on.workflow_dispatch = { inputs: { token: { required: true } } };
		},
		(r, q) => {
			q.jobs.baseline.permissions = { contents: 'write' };
		},
		(r) => {
			r.name = 'Other';
		},
		(r) => {
			r.jobs.release.with['expected-workflow-path'] =
				'.github/workflows/other.yml';
		},
		(r) => {
			r.on.workflow_run.branches = ['other'];
		},
	];
	for (const mutate of cases) {
		const r = structuredClone(release);
		const q = structuredClone(quality);
		mutate(r, q);
		assert.throws(() => checkWorkflows(r, q));
	}
});

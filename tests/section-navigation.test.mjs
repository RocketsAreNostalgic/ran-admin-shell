import assert from 'node:assert/strict';
import test from 'node:test';
import { createSectionNavigation } from '../resources/section-navigation.js';

function fixture({ nested = false } = {}) {
	let current = 0;
	let nextFrame = 0;
	let resized;
	let disconnected = false;
	const frames = new Map(),
		styles = new Map(),
		classes = new Set(),
		events = new Map();
	const observed = new Set();
	const makeLinks = () =>
		[30, 90, 160].map((left, index) => ({
			getAttribute: () => (index === current ? 'page' : null),
			left,
			width: 50,
			getBoundingClientRect() {
				return { left, bottom: 66, width: this.width };
			},
			scrollIntoView(options) {
				this.scrolled = options;
			},
		}));
	let links = makeLinks();
	const nestedIndicator = { parentNode: links[0] };
	const makeIndicator = () => ({
		setAttribute() {},
		getBoundingClientRect: () => ({ left: 30, top: 64, width: 50 }),
		remove() {
			if (directIndicator === this) directIndicator = null;
		},
	});
	let directIndicator = nested ? null : makeIndicator();
	const view = {
		getComputedStyle: () => ({ getPropertyValue: () => '2px' }),
		requestAnimationFrame(callback) {
			frames.set(++nextFrame, callback);
			return nextFrame;
		},
		cancelAnimationFrame(id) {
			frames.delete(id);
		},
		ResizeObserver: class {
			constructor(callback) {
				resized = callback;
			}
			observe(target) {
				observed.add(target);
			}
			unobserve(target) {
				observed.delete(target);
			}
			disconnect() {
				disconnected = true;
				observed.clear();
			}
		},
	};
	const host = {
		matches: () => true,
		ownerDocument: { defaultView: view, createElement: makeIndicator },
		appendChild(child) {
			directIndicator = child;
		},
		isConnected: true,
		scrollLeft: 4,
		scrollTop: 0,
		clientLeft: 1,
		clientTop: 0,
		querySelectorAll: () => links,
		querySelector: (selector) =>
			selector.startsWith(':scope >')
				? directIndicator
				: nested
					? nestedIndicator
					: directIndicator,
		getBoundingClientRect: () => ({ left: 10, top: 30 }),
		addEventListener: (name, listener) => events.set(name, listener),
		removeEventListener: (name) => events.delete(name),
		style: {
			setProperty: (name, value) => styles.set(name, value),
			removeProperty: (name) => styles.delete(name),
		},
		classList: {
			add: (name) => classes.add(name),
			remove: (...names) => names.forEach((name) => classes.delete(name)),
			toggle: (name, on) =>
				on ? classes.add(name) : classes.delete(name),
		},
	};
	return {
		host,
		get links() {
			return links;
		},
		observed,
		nestedIndicator,
		get directIndicator() {
			return directIndicator;
		},
		replaceLinks() {
			links = makeLinks();
			directIndicator = null;
		},
		styles,
		classes,
		events,
		frames,
		select(index) {
			current = index;
		},
		resize(target = host) {
			if (observed.has(target)) resized();
		},
		paint() {
			const pending = [...frames.values()];
			frames.clear();
			pending.forEach((callback) => callback());
		},
		get disconnected() {
			return disconnected;
		},
	};
}

test('optional controller aligns the current native link and scrolls only focused links', () => {
	const f = fixture(),
		controller = createSectionNavigation(f.host);
	assert.equal(f.styles.get('--ran-section-indicator-x'), '23px');
	assert.equal(f.styles.get('--ran-section-indicator-top'), '34px');
	assert.equal(f.styles.get('--ran-section-indicator-width'), '50px');
	f.events.get('focusin')({ target: f.links[1] });
	assert.deepEqual(f.links[1].scrolled, {
		block: 'nearest',
		inline: 'nearest',
	});
	controller.destroy();
	assert.equal(f.disconnected, true);
	assert.equal(f.events.size, 0);
	assert.equal(f.styles.size, 0);
});

test('deferred update keeps the old endpoint through layout paint, including resize callbacks', () => {
	const f = fixture(),
		controller = createSectionNavigation(f.host);
	f.select(1);
	controller.update({ animate: true, afterPaint: true });
	f.resize();
	f.paint();
	f.resize();
	assert.equal(f.styles.get('--ran-section-indicator-x'), '23px');
	f.paint();
	assert.equal(f.styles.get('--ran-section-indicator-x'), '83px');
	assert.equal(f.classes.has('ran-section-navigation--moving'), true);
});

test('latest committed update wins and cleanup prevents queued or observer writes', () => {
	const f = fixture(),
		controller = createSectionNavigation(f.host);
	f.select(1);
	controller.update({ animate: true, afterPaint: true });
	f.paint();
	f.select(2);
	controller.update({ animate: true, afterPaint: true });
	assert.equal(f.frames.size, 1);
	f.paint();
	f.paint();
	assert.equal(f.styles.get('--ran-section-indicator-x'), '153px');
	controller.update({ animate: true, afterPaint: true });
	f.paint();
	controller.destroy();
	f.paint();
	f.resize();
	controller.update();
	assert.equal(f.frames.size, 0);
	assert.equal(f.styles.size, 0);
	assert.equal(f.classes.size, 0);
});

test('opaque handoff retains rendered position across a new host before animating accepted current', () => {
	const old = fixture(),
		previous = createSectionNavigation(old.host);
	const snapshot = previous.snapshot();
	previous.destroy();
	const next = fixture();
	next.host.getBoundingClientRect = () => ({ left: 20, top: 30 });
	next.select(2);
	const controller = createSectionNavigation(next.host);
	controller.transfer(snapshot);
	assert.equal(next.styles.get('--ran-section-indicator-x'), '13px');
	next.resize();
	next.paint();
	assert.equal(next.styles.get('--ran-section-indicator-x'), '13px');
	next.paint();
	assert.equal(next.styles.get('--ran-section-indicator-x'), '143px');
	assert.equal(next.classes.has('ran-section-navigation--moving'), true);
});

test('PHP nested fallback remains owned by its link while enhancement uses the host', () => {
	const f = fixture({ nested: true });
	const parent = f.nestedIndicator.parentNode;
	const controller = createSectionNavigation(f.host);
	assert.ok(
		f.directIndicator,
		'enhanced indicator must be a direct host child'
	);
	assert.equal(f.nestedIndicator.parentNode, parent);
	f.select(1);
	controller.update();
	assert.equal(f.styles.get('--ran-section-indicator-x'), '83px');
	controller.destroy();
	assert.equal(f.directIndicator, null);
	assert.equal(f.nestedIndicator.parentNode, parent);
});

test('committed link replacements reconcile resize targets and recreate the host indicator', () => {
	const f = fixture();
	const controller = createSectionNavigation(f.host);
	const old = [...f.links];
	f.replaceLinks();
	f.select(1);
	controller.update({ animate: true, afterPaint: true });
	old.forEach((link) => assert.equal(f.observed.has(link), false));
	f.links.forEach((link) => assert.equal(f.observed.has(link), true));
	assert.ok(
		f.directIndicator,
		'replacement content must retain an enhanced indicator'
	);
	f.paint();
	f.paint();
	f.links[1].width = 95;
	f.resize(f.links[1]);
	assert.equal(f.styles.get('--ran-section-indicator-width'), '95px');
	controller.destroy();
	assert.equal(f.observed.size, 0);
});

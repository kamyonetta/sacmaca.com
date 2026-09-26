const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Element {
  constructor() { this.events = {}; this.attrs = {}; this.value = '0.7'; this.textContent = ''; }
  addEventListener(name, fn) { (this.events[name] ||= []).push(fn); }
  emit(name) { for (const fn of this.events[name] || []) fn(); }
  setAttribute(name, value) { this.attrs[name] = value; }
}
async function run() {
  const ids = ['audio-player', 'play-pause', 'volume-slider', 'seek-slider', 'player-status', 'prev', 'next', 'track-name', 'elapsed', 'duration'];
  const elements = Object.fromEntries(ids.map(id => [id, new Element()]));
  const audio = elements['audio-player'];
  Object.assign(audio, { paused: true, ended: false, duration: 120, currentTime: 0,
    play() { if (this.reject) return Promise.reject(new Error('blocked')); this.paused = false; this.emit('play'); return Promise.resolve(); },
    pause() { this.paused = true; this.emit('pause'); }, load() { this.currentTime = 0; } });
  vm.runInNewContext(fs.readFileSync('mp3-player/script.js', 'utf8'), { document: { getElementById: id => elements[id] } });
  assert.equal(audio.volume, 0.7);
  assert.equal(audio.paused, true, 'No autoplay on initial load');
  elements['play-pause'].emit('click'); await new Promise(setImmediate);
  assert.equal(elements['play-pause'].attrs['aria-label'], 'Pause');
  elements['play-pause'].emit('click');
  assert.equal(elements['play-pause'].attrs['aria-label'], 'Play');
  elements.next.emit('click'); await new Promise(setImmediate);
  assert.equal(audio.src, 'music/song2.mp3');
  elements.next.emit('click'); await new Promise(setImmediate);
  assert.equal(audio.src, 'music/song1.mp3', 'Wrap without a missing third track');
  elements.prev.emit('click'); await new Promise(setImmediate);
  assert.equal(audio.src, 'music/song2.mp3');
  audio.emit('ended'); await new Promise(setImmediate);
  assert.equal(audio.src, 'music/song1.mp3');
  elements['volume-slider'].value = '0.25'; elements['volume-slider'].emit('input');
  assert.equal(audio.volume, 0.25);
  elements['seek-slider'].value = '50'; elements['seek-slider'].emit('input');
  assert.equal(audio.currentTime, 60);
  audio.emit('timeupdate'); assert.equal(elements.elapsed.textContent, '1:00');
  audio.pause(); audio.reject = true;
  elements['play-pause'].emit('click'); await new Promise(setImmediate);
  assert.equal(elements['play-pause'].attrs['aria-label'], 'Play');
  assert.match(elements['player-status'].textContent, /Unable/);
  audio.emit('error'); assert.match(elements['player-status'].textContent, /unavailable/);
  const button = new Element();
  button.replaceChildren = child => { button.textContent = child; };
  let dimmed;
  vm.runInNewContext(fs.readFileSync('wp-content/themes/astra-child/salon.js', 'utf8'), {
    document: { querySelector: () => button, createTextNode: x => x, body: { classList: { toggle: (_, value) => { dimmed = value; } } } },
    localStorage: { getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); } }
  });
  assert.equal(dimmed, false); button.emit('click'); assert.equal(dimmed, true);
  assert.equal(button.attrs['aria-pressed'], 'true');
  button.emit('click'); assert.equal(dimmed, false);
  for (const name of ['header.php', 'front-page.php', 'footer.php']) {
    const template = fs.readFileSync(`wp-content/themes/astra-child/${name}`, 'utf8');
    assert.doesNotMatch(template, /krmf|calendar/i, 'No calendar app exposure in homepage templates');
  }
  console.log('Passed: playback, pause, next/previous/wrap, ended, volume, seek, failures, storage-blocked lights, and KRMF exclusion.');
}
run().catch(error => { console.error(error); process.exitCode = 1; });

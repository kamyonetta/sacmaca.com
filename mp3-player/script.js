(() => {
  'use strict';
  const audio = document.getElementById('audio-player');
  const play = document.getElementById('play-pause');
  const volume = document.getElementById('volume-slider');
  const seek = document.getElementById('seek-slider');
  const status = document.getElementById('player-status');
  const songs = ['music/song1.mp3', 'music/song2.mp3'];
  let index = 0;
  let request = 0;
  const time = seconds => Number.isFinite(seconds) ? `${Math.floor(seconds / 60)}:${String(Math.floor(seconds % 60)).padStart(2, '0')}` : '0:00';
  function sync() {
    const playing = !audio.paused && !audio.ended;
    play.textContent = playing ? 'Ⅱ' : '▶';
    play.setAttribute('aria-label', playing ? 'Pause' : 'Play');
    play.setAttribute('aria-pressed', String(playing));
  }
  function progress() {
    const ready = Number.isFinite(audio.duration) && audio.duration > 0;
    seek.disabled = !ready;
    seek.value = ready ? String(audio.currentTime / audio.duration * 100) : '0';
    document.getElementById('elapsed').textContent = time(audio.currentTime);
    document.getElementById('duration').textContent = time(audio.duration);
  }
  async function start() {
    const current = ++request;
    status.textContent = 'Loading…';
    try {
      await audio.play();
      if (current === request) { status.textContent = 'Now playing'; sync(); }
    } catch (_) {
      if (current === request) { status.textContent = 'Unable to play. Try again.'; sync(); }
    }
  }
  function changeTrack(direction) {
    ++request;
    audio.pause();
    index = (index + direction + songs.length) % songs.length;
    audio.src = songs[index];
    audio.load();
    document.getElementById('track-name').textContent = `Track ${String(index + 1).padStart(2, '0')} / 02`;
    progress();
    start();
  }
  play.addEventListener('click', () => {
    if (audio.paused) start();
    else { ++request; audio.pause(); status.textContent = 'Paused'; }
  });
  document.getElementById('prev').addEventListener('click', () => changeTrack(-1));
  document.getElementById('next').addEventListener('click', () => changeTrack(1));
  audio.addEventListener('ended', () => changeTrack(1));
  audio.addEventListener('play', sync);
  audio.addEventListener('pause', sync);
  audio.addEventListener('error', () => { status.textContent = 'Track unavailable. Try next.'; sync(); });
  audio.addEventListener('timeupdate', progress);
  audio.addEventListener('loadedmetadata', progress);
  volume.addEventListener('input', () => { audio.volume = Number(volume.value); });
  seek.addEventListener('input', () => {
    if (Number.isFinite(audio.duration) && audio.duration > 0) audio.currentTime = Number(seek.value) / 100 * audio.duration;
  });
  audio.volume = Number(volume.value);
  sync();
})();

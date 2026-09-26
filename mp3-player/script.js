const audioPlayer = document.getElementById("audio-player");
const playPauseBtn = document.getElementById("play-pause");
const prevBtn = document.getElementById("prev");
const nextBtn = document.getElementById("next");
const volumeSlider = document.getElementById("volume-slider"); // Get the volume slider

let currentIndex = 0; // Start with the first song
const songs = [
    "music/song1.mp3",
    "music/song2.mp3",
    "music/song3.mp3"
];

function loadSong(index) {
    audioPlayer.src = songs[index];  // Load new song
    audioPlayer.load();  // Reload the audio source
    playPauseBtn.textContent = "▶";  // Ensure Play button is shown on load
}

// Play or Pause functionality
playPauseBtn.addEventListener("click", () => {
    if (audioPlayer.paused) {
        audioPlayer.play().catch(error => console.log("Autoplay blocked:", error));
        playPauseBtn.textContent = "⏸";  // Show pause button
    } else {
        audioPlayer.pause();
        playPauseBtn.textContent = "▶";  // Show play button
    }
});

// Next Track
nextBtn.addEventListener("click", () => {
    currentIndex = (currentIndex + 1) % songs.length;  // Move to next song
    loadSong(currentIndex);
    audioPlayer.play();
    playPauseBtn.textContent = "⏸";
});

// Previous Track
prevBtn.addEventListener("click", () => {
    currentIndex = (currentIndex - 1 + songs.length) % songs.length;  // Move to previous song
    loadSong(currentIndex);
    audioPlayer.play();
    playPauseBtn.textContent = "⏸";
});

// Auto-play next song when current song ends
audioPlayer.addEventListener("ended", () => {
    currentIndex = (currentIndex + 1) % songs.length;
    loadSong(currentIndex);
    audioPlayer.play();
});

// ✅ Fix: Volume Slider Functionality
volumeSlider.addEventListener("input", () => {
    audioPlayer.volume = volumeSlider.value;  // Adjust volume based on slider
});

// Set initial volume to 100%
audioPlayer.volume = 1;
volumeSlider.value = 1;

// Load the first song on page load without autoplay
window.addEventListener("DOMContentLoaded", () => {
    loadSong(currentIndex);
});

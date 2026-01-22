function updateClock() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const timeString = `${hours}<span class="clock-separator">:</span>${minutes}`;


    const clockElement = document.getElementById('current-time');
    if (clockElement) {
        clockElement.innerHTML = timeString;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    updateClock();
    setInterval(updateClock, 1000);
});
function updateClock() {
    const now = new Date();
    const hStr = now.getHours().toString().padStart(2, '0');
    const mStr = now.getMinutes().toString().padStart(2, '0');

    const formatDigits = (str) => {
        return str.split('').map(char => {
            const className = char === '1' ? 'digit-one' : '';
            return `<span class="${className}">${char}</span>`;
        }).join('');
    };

    const hHtml = formatDigits(hStr);
    const mHtml = formatDigits(mStr);

    const timeString = `${hHtml}<span class="clock-separator">:</span>${mHtml}`;


    const clockElement = document.getElementById('current-time');
    if (clockElement) {
        clockElement.innerHTML = timeString;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    updateClock();
    setInterval(updateClock, 1000);
});
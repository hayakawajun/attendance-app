const trigger = document.getElementById('month__picker--trigger');
const picker = document.getElementById('month__picker');

trigger.addEventListener('click', () => {
    try {
        picker.showPicker();
    } catch (error) {
        picker.click();
    }
});

picker.addEventListener('change', (e) => {
    const baseUrl = e.target.dataset.url;
    const val = e.target.value;
    if (!val || !baseUrl) return;
    const [year, month] = val.split('-');
    window.location.href = `${baseUrl}/${year}/${month}`;
});
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
    const val = e.target.value;
    if (!val) return;
    const [year, month] = val.split('-');
    window.location.href = `/attendance/list/${year}/${month}`;
});
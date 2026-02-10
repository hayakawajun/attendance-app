const trigger = document.getElementById('date__picker--trigger');
const picker = document.getElementById('date__picker');

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
    const [year, month, day] = val.split('-');
    window.location.href = `/admin/attendance/list/${year}/${month}/${day}`;
});
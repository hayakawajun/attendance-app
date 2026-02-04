document.querySelectorAll('.js__input-time--helper').forEach(input => {

    input.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, '');

        if (value.length >= 1) {
            if (value.length === 1 && parseInt(value, 10) > 2) value = '';
            else if (value.length >= 2 && parseInt(value.substring(0, 2), 10) > 23) value = value.substring(0, 1);
        }

        if (value.length >= 3) {
            if (value.length === 3 && parseInt(value.substring(2, 3), 10) > 5) value = value.substring(0, 2);
            else if (value.length >= 4 && parseInt(value.substring(2, 4), 10) > 59) value = value.substring(0, 3);
        }

        if (value.length > 2) {
            value = value.substring(0, 2) + ':' + value.substring(2, 4);
        }
        e.target.value = value;
    });

    input.addEventListener('blur', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length === 3) {
            val = '0' + val;
            e.target.value = val.substring(0, 2) + ':' + val.substring(2, 4);
        }
    });
});
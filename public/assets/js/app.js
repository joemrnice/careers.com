document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#primary-navigation');
    if (menuButton && navigation) {
        menuButton.addEventListener('click', () => {
            const expanded = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!expanded));
            navigation.classList.toggle('open', !expanded);
        });
    }

    const search = document.querySelector('#career-search');
    const cards = document.querySelectorAll('.searchable .card');
    if (search && cards.length) {
        search.addEventListener('input', () => {
            const term = search.value.trim().toLowerCase();
            cards.forEach((card) => {
                card.hidden = term !== '' && !card.dataset.search.includes(term);
            });
        });
    }

    const compareForm = document.querySelector('.compare-form');
    const compareOptions = compareForm ? compareForm.querySelectorAll('input[name="ids[]"]') : [];
    if (compareOptions.length) {
        compareOptions.forEach((option) => {
            option.addEventListener('change', () => {
                const selected = compareForm.querySelectorAll('input[name="ids[]"]:checked');
                if (selected.length >= 3) {
                    compareOptions.forEach((item) => {
                        item.disabled = !item.checked;
                    });
                } else {
                    compareOptions.forEach((item) => {
                        item.disabled = false;
                    });
                }
            });
        });
    }
});

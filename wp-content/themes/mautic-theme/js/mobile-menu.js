document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const menu = document.querySelector('#mobile-menu');
    const closeMenuButton = document.querySelector('.menu-close');
    const parentLinks = document.querySelectorAll('.mobile-menu-list .menu-item-has-children > a');

    if (menuToggle && menu) {
        menuToggle.addEventListener('click', () => {
            const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', !isExpanded);
            menu.setAttribute('aria-hidden', isExpanded);
            menu.classList.toggle('open', !isExpanded);
        });
    }

    if (closeMenuButton) {
        closeMenuButton.addEventListener('click', () => {
            menuToggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            menu.classList.remove('open');
        });
    }

    parentLinks.forEach((link) => {
        const submenu = link.nextElementSibling;

        if (submenu && submenu.classList.contains('sub-menu')) {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                const isExpanded = link.getAttribute('aria-expanded') === 'true';
                link.setAttribute('aria-expanded', !isExpanded);
                submenu.classList.toggle('open', !isExpanded);

                if (!isExpanded) {
                    submenu.style.maxHeight = `${submenu.scrollHeight}px`;
                } else {
                    submenu.style.maxHeight = null;
                }
            });
        }
    });
});

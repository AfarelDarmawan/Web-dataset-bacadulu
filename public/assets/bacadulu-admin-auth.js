(() => {
    const shell = document.querySelector('.admin-auth-shell');
    if (!shell) return;

    const passwordButton = shell.querySelector('[data-admin-password-toggle]');

    passwordButton?.addEventListener('click', () => {
        const input = document.getElementById(
            passwordButton.getAttribute('aria-controls')
        );

        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        passwordButton.textContent = show ? 'Sembunyikan' : 'Lihat';
        passwordButton.setAttribute('aria-pressed', String(show));
    });

    shell.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('[data-dismissible]')?.remove();
        });
    });

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (!window.gsap || reducedMotion) return;

    const gsap = window.gsap;
    const heading = shell.querySelector('.admin-login-heading');
    const fields = shell.querySelectorAll(
        '.admin-login-field, .admin-login-submit'
    );
    const context = shell.querySelector('.admin-auth-context__body');

    gsap.timeline({ defaults: { ease: 'power2.out' } })
        .from(context, {
            opacity: 0,
            x: -14,
            duration: .55
        })
        .from(heading, {
            opacity: 0,
            y: 12,
            duration: .48
        }, '-=.32')
        .from(fields, {
            opacity: 0,
            y: 10,
            duration: .35,
            stagger: .07
        }, '-=.24');
})();
/* Researcher-only interactions; requires no inline scripts. */
document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-profile-page], [data-edit-profile-page]');
    if (!page) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const gsap = window.gsap;

    // Keep initials available if a stored photo URL no longer resolves.
    const photo = page.querySelector('[data-profile-avatar-image]');
    const fallback = page.querySelector('[data-profile-avatar-fallback]');
    if (photo && fallback) {
        const showFallback = () => {
            photo.hidden = true;
            fallback.hidden = false;
        };
        photo.addEventListener('error', showFallback);
        if (photo.complete && photo.naturalWidth === 0) showFallback();
    }

    const reveals = page.querySelectorAll('[data-profile-reveal]');
    if (gsap && !reduceMotion) {
        gsap.fromTo(reveals, { autoAlpha: 0, y: 16 }, {
            autoAlpha: 1,
            y: 0,
            duration: .56,
            stagger: .09,
            ease: 'power2.out',
            clearProps: 'opacity,visibility,transform',
        });
    }

    const input = page.querySelector('[data-profile-avatar-input]');
    const preview = page.querySelector('[data-avatar-preview]');
    if (!input || !preview) return;

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) return;

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            const initials = fallback?.textContent?.trim() || preview.querySelector('span')?.textContent?.trim() || 'P';
            const image = document.createElement('img');
            image.alt = 'Pratinjau foto profil';
            image.addEventListener('error', () => {
                image.hidden = true;
                preview.querySelector('[data-profile-avatar-fallback]')?.removeAttribute('hidden');
            });

            const backup = document.createElement('span');
            backup.textContent = initials;
            backup.hidden = true;
            backup.dataset.profileAvatarFallback = '';
            preview.replaceChildren(image, backup);
            image.src = String(reader.result);

            if (gsap && !reduceMotion) {
                gsap.fromTo(preview, { scale: .94, autoAlpha: .7 }, {
                    scale: 1,
                    autoAlpha: 1,
                    duration: .38,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform',
                });
            }
        });
        reader.readAsDataURL(file);
    });
});

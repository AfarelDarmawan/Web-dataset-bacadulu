/* Motion for the public home page. GSAP is loaded locally by layouts/public.blade.php. */
(() => {
    const init = () => {
        const home = document.querySelector('[data-home-page]');
        if (!home) return;

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const gsap = window.gsap;
        home.dataset.motionRuntime = reduced ? 'reduced' : gsap ? 'gsap' : 'fallback';

        const hero = home.querySelectorAll('.home-kicker, .home-hero h1, .home-hero__lead, .home-search, .home-hero__hint');
        const paths = home.querySelectorAll('.home-paths__header, .home-path, .home-paths__foot');
        const sections = [...home.querySelectorAll('[data-home-reveal]')]
            .filter((item) => !item.closest('.home-hero'));

        const reveal = (items) => {
            if (!items.length || reduced) return;
            if (gsap) {
                gsap.fromTo(items, { autoAlpha: 0, y: 18 }, {
                    autoAlpha: 1, y: 0, duration: .65, stagger: .1,
                    ease: 'power2.out', clearProps: 'opacity,visibility,transform',
                });
            } else if (Element.prototype.animate) {
                items.forEach((item, index) => item.animate(
                    [{ opacity: 0, transform: 'translateY(18px)' }, { opacity: 1, transform: 'translateY(0)' }],
                    { duration: 600, delay: index * 90, easing: 'ease-out' }
                ));
            }
        };

        if (!reduced) {
            if (gsap) {
                gsap.timeline({ defaults: { ease: 'power2.out' } })
                    .fromTo(hero, { autoAlpha: 0, y: 24 }, {
                        autoAlpha: 1, y: 0, duration: .72, stagger: .13,
                        clearProps: 'opacity,visibility,transform',
                    })
                    .fromTo(paths, { autoAlpha: 0, y: 20 }, {
                        autoAlpha: 1, y: 0, duration: .55, stagger: .13,
                        clearProps: 'opacity,visibility,transform',
                    }, '-=.45');
            } else {
                reveal([...hero, ...paths]);
            }

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries.filter((entry) => entry.isIntersecting)
                        .map((entry) => entry.target);
                    visible.forEach((item) => observer.unobserve(item));
                    reveal(visible);
                }, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
                sections.forEach((item) => observer.observe(item));
            } else {
                reveal(sections);
            }
        }

        const counters = home.querySelectorAll('[data-home-counter]');
        if (!reduced && gsap && 'IntersectionObserver' in window) {
            const formatter = new Intl.NumberFormat('id-ID');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    observer.unobserve(entry.target);
                    const total = Number(entry.target.dataset.homeCounter);
                    if (!Number.isSafeInteger(total) || total <= 0) return;
                    const state = { value: 0 };
                    gsap.to(state, {
                        value: total, duration: 1, ease: 'power2.out',
                        onUpdate: () => { entry.target.textContent = formatter.format(Math.round(state.value)); },
                        onComplete: () => { entry.target.textContent = formatter.format(total); },
                    });
                });
            }, { threshold: .2 });
            counters.forEach((item) => observer.observe(item));
        }

        const search = home.querySelector('#home-query');
        document.addEventListener('keydown', (event) => {
            if (event.key !== '/' || event.altKey || event.ctrlKey || event.metaKey) return;
            if (document.activeElement?.matches('input, textarea, select, [contenteditable="true"]')) return;
            event.preventDefault();
            search?.focus();
        });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();

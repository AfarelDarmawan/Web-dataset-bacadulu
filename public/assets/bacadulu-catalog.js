(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const page = document.querySelector('[data-catalog-page]');

        if (!page) {
            return;
        }

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const gsap = window.gsap;

        const heroCopy = page.querySelector('[data-catalog-hero-copy]');
        const scene = page.querySelector('[data-catalog-scene]');
        const scopeCards = [...page.querySelectorAll('.catalog-scope-card')];
        const resultCount = page.querySelector('.catalog-results__count');
        const filterPanel = page.querySelector('[data-filter-panel]');
        const filterToggle = page.querySelector('[data-filter-toggle]');
        const quickSearchForm = page.querySelector('.catalog-quick-search');
        const quickSearchInput = page.querySelector('#catalog-main-search');

        quickSearchForm?.addEventListener('submit', (event) => {
            const query = quickSearchInput?.value.trim() ?? '';

            if (!query) {
                event.preventDefault();
                quickSearchInput?.focus();
                return;
            }

            if (quickSearchInput) {
                quickSearchInput.value = query;
            }
        });

        const revealScopeCards = () => {
            if (!scopeCards.length) {
                return;
            }

            if (reduceMotion || !gsap) {
                scopeCards.forEach((card) => {
                    card.style.opacity = '1';
                    card.style.transform = 'none';
                });
                return;
            }

            gsap.set(scopeCards, {
                autoAlpha: 0,
                y: 22
            });

            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                observer.disconnect();

                gsap.to(scopeCards, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.58,
                    stagger: 0.09,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform'
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -8% 0px'
            });

            observer.observe(scopeCards[0]);
        };

        const animateResultCount = () => {
            if (!resultCount || resultCount.dataset.countAnimated === 'true') {
                return;
            }

            const target = Number.parseInt(resultCount.textContent.replace(/[^0-9]/g, ''), 10);

            if (!Number.isFinite(target) || reduceMotion || !gsap) {
                return;
            }

            resultCount.dataset.countAnimated = 'true';

            const counter = { value: 0 };
            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                observer.disconnect();

                gsap.to(counter, {
                    value: target,
                    duration: Math.min(1.35, .72 + (target / 160)),
                    ease: 'power2.out',
                    onUpdate: () => {
                        resultCount.textContent = Math.round(counter.value).toLocaleString('id-ID');
                    },
                    onComplete: () => {
                        resultCount.textContent = target.toLocaleString('id-ID');
                    }
                });
            }, {
                threshold: 0.5
            });

            observer.observe(resultCount);
        };

        const animateFilterOpen = () => {
            if (!filterPanel || !gsap || reduceMotion || !filterPanel.classList.contains('is-open')) {
                return;
            }

            gsap.fromTo(filterPanel, {
                autoAlpha: 0,
                y: -8
            }, {
                autoAlpha: 1,
                y: 0,
                duration: 0.3,
                ease: 'power2.out',
                overwrite: true,
                clearProps: 'opacity,visibility,transform'
            });
        };

        if (filterPanel) {
            const filterObserver = new MutationObserver((mutations) => {
                if (mutations.some((mutation) => mutation.attributeName === 'class')) {
                    animateFilterOpen();
                }
            });

            filterObserver.observe(filterPanel, {
                attributes: true,
                attributeFilter: ['class']
            });

            if (filterToggle?.getAttribute('aria-expanded') === 'true') {
                window.requestAnimationFrame(animateFilterOpen);
            }
        }

        if (!gsap || reduceMotion) {
            revealScopeCards();
            animateResultCount();
            return;
        }

        if (heroCopy) {
            const heroItems = [
                heroCopy.querySelector('.catalog-v2__kicker'),
                heroCopy.querySelector('h1'),
                heroCopy.querySelector('.catalog-v2__lead'),
                heroCopy.querySelector('.catalog-quick-search'),
                heroCopy.querySelector('.catalog-v2__trust')
            ].filter(Boolean);

            gsap.fromTo(heroItems, {
                autoAlpha: 0,
                y: 22
            }, {
                autoAlpha: 1,
                y: 0,
                duration: 0.7,
                stagger: 0.085,
                ease: 'power3.out',
                clearProps: 'opacity,visibility,transform'
            });
        }

        if (scene && window.innerWidth > 760) {
            gsap.fromTo(scene, {
                autoAlpha: 0,
                x: 24,
                rotate: 1.2
            }, {
                autoAlpha: 1,
                x: 0,
                rotate: 0,
                duration: 0.9,
                delay: 0.16,
                ease: 'power3.out',
                clearProps: 'opacity,visibility,transform'
            });

            const paths = [...scene.querySelectorAll('[data-data-line]')];

            paths.forEach((path, index) => {
                const length = path.getTotalLength();

                gsap.set(path, {
                    strokeDasharray: length,
                    strokeDashoffset: length
                });

                gsap.to(path, {
                    strokeDashoffset: 0,
                    duration: 1.05,
                    delay: 0.42 + (index * 0.08),
                    ease: 'power2.inOut'
                });
            });

            const nodes = [...scene.querySelectorAll('[data-data-node]')];
            const outputs = [...scene.querySelectorAll('[data-data-output]')];
            const pulses = [...scene.querySelectorAll('[data-data-pulse]')];
            const core = scene.querySelector('[data-data-core]');
            const rings = [...scene.querySelectorAll('.catalog-data-core__ring')];

            gsap.fromTo([...nodes, ...outputs, core].filter(Boolean), {
                autoAlpha: 0,
                scale: .88
            }, {
                autoAlpha: 1,
                scale: 1,
                duration: .55,
                delay: .58,
                stagger: .07,
                ease: 'back.out(1.45)',
                clearProps: 'opacity,visibility,scale'
            });

            nodes.forEach((node, index) => {
                gsap.to(node, {
                    y: index % 2 === 0 ? -5 : 5,
                    duration: 2.4 + (index * .22),
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });

            outputs.forEach((output, index) => {
                gsap.to(output, {
                    y: index === 0 ? 4 : -4,
                    duration: 2.7 + (index * .35),
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });

            if (core) {
                gsap.to(core, {
                    y: -5,
                    duration: 2.1,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            }

            rings.forEach((ring, index) => {
                gsap.to(ring, {
                    rotate: index === 0 ? 360 : -360,
                    duration: index === 0 ? 18 : 24,
                    repeat: -1,
                    ease: 'none'
                });
            });

            pulses.forEach((pulse, index) => {
                gsap.fromTo(pulse, {
                    autoAlpha: .25,
                    scale: .65
                }, {
                    autoAlpha: 1,
                    scale: 1.3,
                    duration: .85,
                    delay: index * .24,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });

            const handlePointerMove = (event) => {
                const bounds = scene.getBoundingClientRect();
                const x = ((event.clientX - bounds.left) / bounds.width) - .5;
                const y = ((event.clientY - bounds.top) / bounds.height) - .5;

                gsap.to(scene.querySelector('.catalog-data-scene__canvas'), {
                    x: x * 5,
                    y: y * 4,
                    duration: .65,
                    ease: 'power2.out',
                    overwrite: true
                });
            };

            const resetPointer = () => {
                gsap.to(scene.querySelector('.catalog-data-scene__canvas'), {
                    x: 0,
                    y: 0,
                    duration: .65,
                    ease: 'power2.out',
                    overwrite: true
                });
            };

            scene.addEventListener('pointermove', handlePointerMove);
            scene.addEventListener('pointerleave', resetPointer);
        }

        revealScopeCards();
        animateResultCount();
    });
})();

(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const page = document.querySelector('[data-dataset-detail]');

        if (!page) {
            return;
        }

        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        const gsap = window.gsap;

        const heroItems = [
            ...page.querySelectorAll('[data-hero-item]')
        ];

        const scene = page.querySelector('[data-detail-scene]');

        const revealItems = [
            ...page.querySelectorAll('[data-detail-reveal]')
        ];

        const showWithoutAnimation = (items) => {
            items.forEach((item) => {
                item.style.opacity = '1';
                item.style.visibility = 'visible';
                item.style.transform = 'none';
            });
        };

        /*
         * Menghitung jumlah variabel yang dipilih
         * pada formulir permintaan akses.
         */
        const setupSelectedCounter = () => {
            const form = page.querySelector('[data-access-form]');
            const counter = form?.querySelector('[data-selected-count]');

            if (!form || !counter) {
                return;
            }

            const variableInputs = [
                ...form.querySelectorAll(
                    'input[name="variables[]"], input[name="variable_ids[]"]'
                )
            ];

            const updateCounter = () => {
                const selected = variableInputs.filter(
                    (input) => input.checked
                ).length;

                counter.textContent = `${selected} dipilih`;
            };

            variableInputs.forEach((input) => {
                input.addEventListener('change', updateCounter);
            });

            updateCounter();
        };

        setupSelectedCounter();

        /*
         * Memberi penanda visual ketika user datang
         * dari halaman detail variabel.
         */
        const accessPanel = page.querySelector('[data-access-panel]');

        const selectionConfirm = page.querySelector(
            '[data-selection-confirm]'
        );

        if (
            accessPanel &&
            selectionConfirm &&
            window.location.hash === '#access'
        ) {
            accessPanel.setAttribute('aria-live', 'polite');

            if (gsap && !reduceMotion) {
                gsap.fromTo(
                    selectionConfirm,
                    {
                        scale: 0.96,
                        boxShadow:
                            '0 0 0 rgba(242, 103, 63, 0)'
                    },
                    {
                        scale: 1,
                        boxShadow:
                            '0 0 0 5px rgba(242, 103, 63, .10)',
                        duration: 0.45,
                        delay: 0.55,
                        ease: 'back.out(1.7)',
                        yoyo: true,
                        repeat: 1,
                        clearProps: 'transform,boxShadow'
                    }
                );
            }
        }

        /*
         * Jika GSAP tidak ditemukan atau pengguna
         * mengaktifkan reduced motion, semua elemen
         * tetap ditampilkan normal.
         */
        if (!gsap || reduceMotion) {
            showWithoutAnimation(
                [
                    ...heroItems,
                    scene,
                    ...revealItems
                ].filter(Boolean)
            );

            return;
        }

        /*
         * Animasi teks dan informasi hero.
         */
        if (heroItems.length) {
            gsap.fromTo(
                heroItems,
                {
                    autoAlpha: 0,
                    y: 20
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.68,
                    stagger: 0.075,
                    ease: 'power3.out',
                    clearProps:
                        'opacity,visibility,transform'
                }
            );
        }

        /*
         * Animasi ilustrasi aliran data.
         */
        if (scene) {
            gsap.fromTo(
                scene,
                {
                    autoAlpha: 0,
                    x: 28,
                    rotate: 1.1
                },
                {
                    autoAlpha: 1,
                    x: 0,
                    rotate: 0,
                    duration: 0.9,
                    delay: 0.14,
                    ease: 'power3.out',
                    clearProps:
                        'opacity,visibility,transform'
                }
            );

            const lines = [
                ...scene.querySelectorAll('[data-detail-line]')
            ];

            const nodes = [
                ...scene.querySelectorAll('[data-detail-node]')
            ];

            const pulses = [
                ...scene.querySelectorAll('[data-detail-pulse]')
            ];

            const core = scene.querySelector(
                '[data-detail-core]'
            );

            /*
             * Menggambar garis penghubung data.
             */
            lines.forEach((line, index) => {
                if (typeof line.getTotalLength !== 'function') {
                    return;
                }

                const length = line.getTotalLength();

                gsap.set(line, {
                    strokeDasharray: length,
                    strokeDashoffset: length
                });

                gsap.to(line, {
                    strokeDashoffset: 0,
                    duration: 1.05,
                    delay: 0.42 + (index * 0.08),
                    ease: 'power2.inOut'
                });
            });

            /*
             * Menampilkan node sumber, output,
             * dan pusat data.
             */
            gsap.fromTo(
                [...nodes, core].filter(Boolean),
                {
                    autoAlpha: 0,
                    scale: 0.88
                },
                {
                    autoAlpha: 1,
                    scale: 1,
                    duration: 0.55,
                    delay: 0.58,
                    stagger: 0.07,
                    ease: 'back.out(1.45)',
                    clearProps:
                        'opacity,visibility,scale'
                }
            );

            /*
             * Gerakan mengambang untuk node.
             */
            nodes.forEach((node, index) => {
                gsap.to(node, {
                    y: index % 2 === 0 ? -4 : 4,
                    duration: 2.5 + (index * 0.18),
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });

            /*
             * Gerakan pusat data dan cincin.
             */
            if (core) {
                gsap.to(core, {
                    y: -5,
                    duration: 2.2,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });

                const rings = [
                    ...core.querySelectorAll('i')
                ];

                if (rings.length) {
                    gsap.to(rings, {
                        rotate: 360,
                        duration: 14,
                        repeat: -1,
                        ease: 'none',
                        transformOrigin: '50% 50%',
                        stagger: {
                            each: 0.4,
                            from: 'end'
                        }
                    });
                }
            }

            /*
             * Efek denyut pada titik aliran data.
             */
            pulses.forEach((pulse, index) => {
                gsap.to(pulse, {
                    scale: 1.8,
                    opacity: 0.25,
                    duration: 1.15,
                    delay: index * 0.34,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });
        }

        /*
         * Animasi kartu saat masuk viewport.
         */
        if (revealItems.length) {
            gsap.set(revealItems, {
                autoAlpha: 0,
                y: 28
            });

            /*
             * Fallback untuk browser lama yang
             * belum mendukung IntersectionObserver.
             */
            if (!('IntersectionObserver' in window)) {
                gsap.to(revealItems, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.65,
                    stagger: 0.08,
                    ease: 'power3.out',
                    clearProps:
                        'opacity,visibility,transform'
                });

                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        observer.unobserve(entry.target);

                        gsap.to(entry.target, {
                            autoAlpha: 1,
                            y: 0,
                            duration: 0.65,
                            ease: 'power3.out',
                            clearProps:
                                'opacity,visibility,transform'
                        });
                    });
                },
                {
                    threshold: 0.09,
                    rootMargin: '0px 0px -7% 0px'
                }
            );

            revealItems.forEach((item) => {
                observer.observe(item);
            });
        }
    });
})();
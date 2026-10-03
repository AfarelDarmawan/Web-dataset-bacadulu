/*
|--------------------------------------------------------------------------
| BacaDulu Dataset — Homepage Motion V16
|--------------------------------------------------------------------------
| Animasi dibuat halus dan fungsional:
| - hero entrance
| - reveal ketika section masuk viewport
| - counter statistik
| - micro interaction
| - tetap menghormati prefers-reduced-motion
*/

(() => {
    const initHomePage = () => {
        const home = document.querySelector('[data-home-page]');

        if (!home) {
            return;
        }

        const gsap = window.gsap;

        const reducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        home.dataset.motionRuntime = reducedMotion
            ? 'reduced'
            : gsap
                ? 'gsap'
                : 'static';

        const searchInput = home.querySelector('#home-query');

        document.addEventListener('keydown', (event) => {
            if (
                event.key !== '/' ||
                event.altKey ||
                event.ctrlKey ||
                event.metaKey
            ) {
                return;
            }

            const activeElement = document.activeElement;

            if (
                activeElement?.matches(
                    'input, textarea, select, [contenteditable="true"]'
                )
            ) {
                return;
            }

            event.preventDefault();
            searchInput?.focus();
        });

        if (!gsap || reducedMotion) {
            return;
        }

        const canHover = window.matchMedia(
            '(hover: hover) and (pointer: fine)'
        ).matches;

        const markAsAnimated = (items) => {
            items.forEach((item) => {
                item.dataset.homeMotion = '';
            });

            return items;
        };

        const heroKicker = home.querySelector('.home-kicker');
        const heroTitle = home.querySelector('.home-hero h1');
        const heroLead = home.querySelector('.home-hero__lead');
        const heroSearch = home.querySelector('.home-search');
        const heroHint = home.querySelector('.home-hero__hint');

        const heroFootItems = [
            ...home.querySelectorAll('.home-hero__foot span')
        ];

        const catalogPanel = home.querySelector('.home-paths');
        const catalogHeader = home.querySelector('.home-paths__header');
        const catalogIntro = home.querySelector('.home-paths__intro');
        const dataFlow = home.querySelector('[data-home-dataflow]');
        const catalogChoose = home.querySelector('.home-paths__choose');

        const catalogLinks = [
            ...home.querySelectorAll('.home-path')
        ];

        const catalogMeta = home.querySelector('.home-paths__meta');

        const heroItems = markAsAnimated(
            [
                heroKicker,
                heroTitle,
                heroLead,
                heroSearch,
                heroHint
            ].filter(Boolean)
        );

        const catalogItems = markAsAnimated(
            [
                catalogHeader,
                catalogIntro,
                dataFlow,
                catalogChoose,
                ...catalogLinks,
                catalogMeta
            ].filter(Boolean)
        );

        const heroTimeline = gsap.timeline({
            defaults: {
                ease: 'power3.out'
            }
        });

        heroTimeline.fromTo(
            heroItems,
            {
                autoAlpha: 0,
                x: -16,
                y: 22
            },
            {
                autoAlpha: 1,
                x: 0,
                y: 0,
                duration: 0.72,
                stagger: 0.09,
                clearProps: 'opacity,visibility,transform'
            }
        );

        if (catalogPanel) {
            catalogPanel.dataset.homeMotion = '';

            heroTimeline.fromTo(
                catalogPanel,
                {
                    autoAlpha: 0,
                    x: 28,
                    y: 18
                },
                {
                    autoAlpha: 1,
                    x: 0,
                    y: 0,
                    duration: 0.78,
                    clearProps: 'opacity,visibility,transform'
                },
                '-=0.48'
            );

            heroTimeline.fromTo(
                catalogItems,
                {
                    autoAlpha: 0,
                    y: 13
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.46,
                    stagger: 0.07,
                    clearProps: 'opacity,visibility,transform'
                },
                '-=0.48'
            );
        }

        if (heroFootItems.length) {
            markAsAnimated(heroFootItems);

            heroTimeline.fromTo(
                heroFootItems,
                {
                    autoAlpha: 0,
                    y: 10
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.4,
                    stagger: 0.06,
                    clearProps: 'opacity,visibility,transform'
                },
                '-=0.2'
            );
        }

        const kickerDot = home.querySelector('.home-kicker > span');

        if (kickerDot) {
            gsap.to(kickerDot, {
                scale: 1.4,
                opacity: 0.55,
                duration: 1.35,
                repeat: -1,
                yoyo: true,
                ease: 'sine.inOut',
                delay: 1
            });
        }

        if (catalogPanel && window.innerWidth > 980) {
            const floatingPanel = gsap.to(catalogPanel, {
                y: -4,
                duration: 3.6,
                repeat: -1,
                yoyo: true,
                ease: 'sine.inOut',
                delay: 1.4
            });

            catalogPanel.addEventListener('mouseenter', () => {
                floatingPanel.pause();
            });

            catalogPanel.addEventListener('mouseleave', () => {
                floatingPanel.resume();
            });
        }

        if (dataFlow) {
            const flowPaths = [
                ...dataFlow.querySelectorAll('[data-home-flow-path]')
            ];

            const flowSources = [
                ...dataFlow.querySelectorAll('[data-home-flow-source]')
            ];

            const flowPackets = [
                ...dataFlow.querySelectorAll('[data-home-flow-packet]')
            ];

            const flowCore = dataFlow.querySelector(
                '[data-home-flow-core]'
            );

            const flowOutput = dataFlow.querySelector(
                '[data-home-flow-output]'
            );

            gsap.to(flowPaths, {
                strokeDashoffset: -28,
                duration: 2.2,
                repeat: -1,
                ease: 'none'
            });

            gsap.fromTo(
                flowSources,
                {
                    autoAlpha: 0.58,
                    x: -4
                },
                {
                    autoAlpha: 1,
                    x: 0,
                    duration: 1.05,
                    stagger: 0.18,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                }
            );

            flowPackets.forEach((packet, index) => {
                const startX = Number(packet.dataset.startX);
                const startY = Number(packet.dataset.startY);
                const endX = Number(packet.dataset.endX);
                const endY = Number(packet.dataset.endY);

                if (
                    !Number.isFinite(startX) ||
                    !Number.isFinite(startY) ||
                    !Number.isFinite(endX) ||
                    !Number.isFinite(endY)
                ) {
                    return;
                }

                gsap.fromTo(
                    packet,
                    {
                        attr: {
                            cx: startX,
                            cy: startY
                        },
                        autoAlpha: 0
                    },
                    {
                        attr: {
                            cx: endX,
                            cy: endY
                        },
                        autoAlpha: 1,
                        duration: index === flowPackets.length - 1
                            ? 1.25
                            : 1.45,
                        delay: 0.45 + (index * 0.34),
                        repeat: -1,
                        repeatDelay: 1.25,
                        ease: 'power1.inOut'
                    }
                );
            });

            if (flowCore) {
                gsap.to(flowCore, {
                    scale: 1.035,
                    transformOrigin: '50% 50%',
                    duration: 1.25,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            }

            if (flowOutput) {
                gsap.to(flowOutput, {
                    opacity: 0.68,
                    duration: 1.15,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut',
                    delay: 0.7
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Preview dataset pada CTA
        |--------------------------------------------------------------------------
        */

        const outroPreview = home.querySelector(
            '[data-home-outro-preview]'
        );

        if (outroPreview) {
            const previewScan = outroPreview.querySelector(
                '[data-home-preview-scan]'
            );

            const previewRows = [
                ...outroPreview.querySelectorAll(
                    '[data-home-preview-row]'
                )
            ];

            const previewStatusDots = [
                ...outroPreview.querySelectorAll(
                    '.home-outro__preview-status i'
                )
            ];

            if (previewScan) {
                gsap.fromTo(
                    previewScan,
                    {
                        y: 0,
                        autoAlpha: 0
                    },
                    {
                        y: 104,
                        autoAlpha: 1,
                        duration: 2.8,
                        repeat: -1,
                        repeatDelay: 0.65,
                        ease: 'power1.inOut'
                    }
                );
            }

            if (previewRows.length) {
                gsap.fromTo(
                    previewRows,
                    {
                        backgroundColor: 'rgba(242, 103, 63, 0)',
                        borderColor: 'rgba(255, 255, 255, .08)'
                    },
                    {
                        backgroundColor: 'rgba(242, 103, 63, .12)',
                        borderColor: 'rgba(242, 143, 112, .34)',
                        duration: 0.55,
                        stagger: 0.7,
                        repeat: -1,
                        repeatDelay: 0.85,
                        yoyo: true,
                        ease: 'sine.inOut'
                    }
                );
            }

            if (previewStatusDots.length) {
                gsap.to(
                    previewStatusDots,
                    {
                        scale: 1.55,
                        opacity: 0.5,
                        transformOrigin: '50% 50%',
                        duration: 0.85,
                        stagger: 0.25,
                        repeat: -1,
                        yoyo: true,
                        ease: 'sine.inOut'
                    }
                );
            }
        }

        const revealConfigs = [
            {
                root: home.querySelector('.home-status'),
                selector:
                    '.home-status__caption, ' +
                    '.home-status__primary, ' +
                    '.home-status__chip',
                x: 0,
                y: 16,
                stagger: 0.08
            },
            {
                root: home.querySelector('.home-featured'),
                selector:
                    '.home-section__heading > *, ' +
                    '.home-featured__list .variable-row, ' +
                    '.home-empty',
                x: 0,
                y: 24,
                stagger: 0.08
            },
            {
                root: home.querySelector('.home-how'),
                selector:
                    '.home-how__grid > div > *, ' +
                    '.home-steps > li',
                x: 0,
                y: 22,
                stagger: 0.09
            },
            {
                root: home.querySelector('.home-standards'),
                selector:
                    '.home-section__heading > *, ' +
                    '.home-standards__grid > article',
                x: 0,
                y: 24,
                stagger: 0.1
            },
            {
                root: home.querySelector('.home-outro'),
                selector:
                    '.home-outro__inner > div:first-child > *, ' +
                    '.home-outro__preview, ' +
                    '.home-outro__actions > *',
                x: 0,
                y: 20,
                stagger: 0.09
            }
        ].filter((config) => config.root);

        const revealSection = (config) => {
            const items = [
                ...config.root.querySelectorAll(config.selector)
            ];

            if (!items.length) {
                return;
            }

            markAsAnimated(items);

            gsap.fromTo(
                items,
                {
                    autoAlpha: 0,
                    x: config.x,
                    y: config.y
                },
                {
                    autoAlpha: 1,
                    x: 0,
                    y: 0,
                    duration: 0.66,
                    stagger: config.stagger,
                    ease: 'power3.out',
                    clearProps: 'opacity,visibility,transform'
                }
            );
        };

        if ('IntersectionObserver' in window) {
            const configMap = new Map(
                revealConfigs.map((config) => [
                    config.root,
                    config
                ])
            );

            const sectionObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        sectionObserver.unobserve(entry.target);

                        const config = configMap.get(entry.target);

                        if (config) {
                            revealSection(config);
                        }
                    });
                },
                {
                    rootMargin: '0px 0px -10% 0px',
                    threshold: 0.08
                }
            );

            revealConfigs.forEach((config) => {
                sectionObserver.observe(config.root);
            });
        } else {
            revealConfigs.forEach(revealSection);
        }

        const counters = [
            ...home.querySelectorAll('[data-home-counter]')
        ];

        const animateCounter = (counter) => {
            const total = Number(
                counter.dataset.homeCounter
            );

            if (!Number.isFinite(total) || total < 0) {
                return;
            }

            const formatter = new Intl.NumberFormat('id-ID');

            const state = {
                value: 0
            };

            gsap.to(state, {
                value: total,
                duration: 1.25,
                ease: 'power2.out',

                onUpdate: () => {
                    counter.textContent = formatter.format(
                        Math.round(state.value)
                    );
                },

                onComplete: () => {
                    counter.textContent = formatter.format(total);
                }
            });
        };

        if (
            counters.length &&
            'IntersectionObserver' in window
        ) {
            const counterObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        counterObserver.unobserve(entry.target);
                        animateCounter(entry.target);
                    });
                },
                {
                    threshold: 0.25
                }
            );

            counters.forEach((counter) => {
                counterObserver.observe(counter);
            });
        } else {
            counters.forEach(animateCounter);
        }

        if (canHover) {
            catalogLinks.forEach((link) => {
                const index = link.querySelector(
                    '.home-path__index'
                );

                const arrow = link.querySelector(
                    '.home-path__arrow'
                );

                link.addEventListener('mouseenter', () => {
                    gsap.to(link, {
                        x: 4,
                        duration: 0.24,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });

                    if (index) {
                        gsap.to(index, {
                            scale: 1.08,
                            rotation: -2,
                            duration: 0.24,
                            ease: 'power2.out',
                            overwrite: 'auto'
                        });
                    }

                    if (arrow) {
                        gsap.to(arrow, {
                            x: 3,
                            y: -3,
                            duration: 0.24,
                            ease: 'power2.out',
                            overwrite: 'auto'
                        });
                    }
                });

                link.addEventListener('mouseleave', () => {
                    gsap.to(link, {
                        x: 0,
                        duration: 0.3,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });

                    if (index) {
                        gsap.to(index, {
                            scale: 1,
                            rotation: 0,
                            duration: 0.3,
                            ease: 'power2.out',
                            overwrite: 'auto'
                        });
                    }

                    if (arrow) {
                        gsap.to(arrow, {
                            x: 0,
                            y: 0,
                            duration: 0.3,
                            ease: 'power2.out',
                            overwrite: 'auto'
                        });
                    }
                });
            });

            const standardCards = [
                ...home.querySelectorAll(
                    '.home-standards article'
                )
            ];

            standardCards.forEach((card) => {
                card.addEventListener('mouseenter', () => {
                    gsap.to(card, {
                        y: -5,
                        duration: 0.28,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                });

                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        y: 0,
                        duration: 0.32,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                });
            });

            const primaryCta = home.querySelector(
                '.home-outro__primary'
            );

            const primaryCtaArrow = primaryCta?.querySelector(
                '[aria-hidden="true"]'
            );

            primaryCta?.addEventListener(
                'mouseenter',
                () => {
                    if (!primaryCtaArrow) {
                        return;
                    }

                    gsap.to(primaryCtaArrow, {
                        x: 4,
                        y: -3,
                        duration: 0.24,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                }
            );

            primaryCta?.addEventListener(
                'mouseleave',
                () => {
                    if (!primaryCtaArrow) {
                        return;
                    }

                    gsap.to(primaryCtaArrow, {
                        x: 0,
                        y: 0,
                        duration: 0.3,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                }
            );
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initHomePage,
            {
                once: true
            }
        );
    } else {
        initHomePage();
    }
})();
document.documentElement.classList.add('has-js');

document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const gsap = window.gsap;
    if (gsap) document.documentElement.classList.add('gsap-ready');
    const header = document.querySelector('[data-site-header]');
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navMenu = document.querySelector('[data-nav-menu]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const accountMenu = document.querySelector('[data-account-menu]');
    const accountToggle = document.querySelector('[data-account-menu-toggle]');
    const accountPanel = document.querySelector('[data-account-menu-panel]');

    const setBodyLock = () => {
        const navigationOpen = navMenu?.classList.contains('is-open') ?? false;
        const sidebarOpen = sidebar?.classList.contains('is-open') ?? false;
        document.body.classList.toggle('is-locked', navigationOpen || sidebarOpen);
    };

    const setNavigation = (open) => {
        navMenu?.classList.toggle('is-open', open);
        navToggle?.setAttribute('aria-expanded', String(open));
        setBodyLock();
    };

    const setSidebar = (open) => {
        sidebar?.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        document.querySelector('[data-sidebar-open]')?.setAttribute('aria-expanded', String(open));
        setBodyLock();
    };

    navToggle?.addEventListener('click', () => setNavigation(!navMenu?.classList.contains('is-open')));
    navMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setNavigation(false)));
    document
    .querySelector('[data-sidebar-open]')
    ?.addEventListener('click', () => setSidebar(true));

document
    .querySelector('[data-sidebar-close]')
    ?.addEventListener('click', () => setSidebar(false));

backdrop?.addEventListener('click', () => setSidebar(false));

/*
 * Tutup sidebar setelah menu dipilih pada tampilan mobile.
 */
sidebar?.querySelectorAll('nav a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 900px)').matches) {
            setSidebar(false);
        }
    });
});

/*
 * Pencarian koleksi.
 * Form dikirim ke halaman Koleksi Data menggunakan parameter ?q=.
 */
const adminSearch = document.querySelector('[data-admin-search]');
const adminSearchInput = document.querySelector(
    '[data-admin-search-input]'
);

adminSearch?.addEventListener('submit', (event) => {
    const query = adminSearchInput?.value.trim() ?? '';

    if (!query) {
        event.preventDefault();
        adminSearchInput?.focus();
        return;
    }

    if (adminSearchInput) {
        adminSearchInput.value = query;
    }
});

/*
 * Tekan tombol "/" untuk membuka dan memfokuskan pencarian.
 */
document.addEventListener('keydown', (event) => {
    const target = event.target;

    const isTyping =
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target?.isContentEditable;

    if (
        event.key === '/' &&
        !isTyping &&
        adminSearchInput &&
        !event.ctrlKey &&
        !event.metaKey &&
        !event.altKey
    ) {
        event.preventDefault();

        if (
            document.body.classList.contains('is-admin-collapsed') &&
            window.innerWidth > 900
        ) {
            document
                .querySelector('[data-admin-collapse]')
                ?.click();
        }

        if (window.innerWidth <= 900) {
            setSidebar(true);
        }

        window.setTimeout(() => {
            adminSearchInput.focus();
        }, 180);
    }
});

    const adminCollapse = document.querySelector('[data-admin-collapse]');
    if (adminCollapse && sidebar) {
        let collapsed = false;
        try { collapsed = localStorage.getItem('bacadulu-admin-sidebar-collapsed') === 'true'; } catch (_) {}
        const setAdminCollapsed = (value) => {
            collapsed = value;
            document.body.classList.toggle('is-admin-collapsed', value);
            adminCollapse.setAttribute('aria-expanded', String(!value));
            adminCollapse.setAttribute('aria-label', value ? 'Perluas sidebar' : 'Ringkas sidebar');
            adminCollapse.title = value ? 'Perluas sidebar' : 'Ringkas sidebar';
            try { localStorage.setItem('bacadulu-admin-sidebar-collapsed', String(value)); } catch (_) {}
        };
        setAdminCollapsed(collapsed);
        adminCollapse.addEventListener('click', () => setAdminCollapsed(!collapsed));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                setSidebar(false);
                document.querySelector('[data-sidebar-open]')?.focus();
            }
        });
    }

    const adminNextDialog = document.querySelector('[data-admin-next-step]');
    if (adminNextDialog) {
        const nextNav = adminNextDialog.dataset.adminNextNav;
        if (['datasets', 'quality'].includes(nextNav)) {
            const target = sidebar?.querySelector(`[data-admin-nav="${nextNav}"]`);
            if (target) {
                const bubble = document.createElement('span');
                bubble.className = 'admin-next-bubble';
                bubble.textContent = 'Lanjut';
                bubble.setAttribute('aria-hidden', 'true');
                target.appendChild(bubble);
                target.classList.add('has-next-step');
            }
        }
        adminNextDialog.showModal();
        if (!reduceMotion && gsap) {
            gsap.fromTo(adminNextDialog.querySelector('.admin-next-dialog__card'),
                { autoAlpha: 0, y: 18, scale: 0.98 },
                { autoAlpha: 1, y: 0, scale: 1, duration: 0.42, ease: 'power3.out', clearProps: 'all' });
        }
        adminNextDialog.querySelectorAll('[data-admin-next-close]').forEach((button) => {
            button.addEventListener('click', () => adminNextDialog.close());
        });
    }

    const setAccountMenu = (open) => {
        accountPanel?.toggleAttribute('hidden', !open);
        accountMenu?.classList.toggle('is-open', open);
        accountToggle?.setAttribute('aria-expanded', String(open));
    };
    accountToggle?.addEventListener('click', () => setAccountMenu(!accountMenu?.classList.contains('is-open')));
    document.addEventListener('click', (event) => {
        if (accountMenu && !accountMenu.contains(event.target)) setAccountMenu(false);
    });

    const avatarInput = document.querySelector('[data-avatar-input]');
    const avatarPreview = document.querySelector('[data-avatar-preview]');
    const animateAvatar = (element, intensity = 1) => {
        if (!element || reduceMotion) return;

        if (gsap) {
            gsap.fromTo(
                element,
                { autoAlpha: 0.45, scale: 0.88, rotate: -3 * intensity },
                {
                    autoAlpha: 1,
                    scale: 1,
                    rotate: 0,
                    duration: 0.48,
                    ease: 'back.out(1.7)',
                    overwrite: 'auto',
                }
            );
            return;
        }

        element.animate(
            [
                { opacity: 0.45, transform: `scale(.88) rotate(${-3 * intensity}deg)` },
                { opacity: 1, transform: 'scale(1) rotate(0deg)' },
            ],
            { duration: 480, easing: 'cubic-bezier(.22, 1.4, .36, 1)' }
        );
    };

    if (avatarInput?.dataset.inlinePreview !== 'true') avatarInput?.addEventListener('change', () => {
        const file = avatarInput.files?.[0];
        if (!file || !file.type.startsWith('image/') || !avatarPreview) return;

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            avatarPreview.innerHTML = '';
            const image = document.createElement('img');
            image.src = String(reader.result);
            image.alt = 'Pratinjau foto profil';
            avatarPreview.appendChild(image);
            animateAvatar(avatarPreview, 1.1);
        });
        reader.readAsDataURL(file);
    });

    document.querySelectorAll('[data-gsap-avatar]').forEach((avatar) => {
        avatar.addEventListener('mouseenter', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(avatar, { scale: 1.045, duration: 0.22, ease: 'power2.out', overwrite: 'auto' });
        });

        avatar.addEventListener('mouseleave', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(avatar, { scale: 1, duration: 0.28, ease: 'power2.out', overwrite: 'auto' });
        });
    });

    document.querySelectorAll('[data-profile-page] .profile-esgi-card, [data-edit-profile-page] .profile-edit-preview-card-v2, [data-edit-profile-page] .profile-edit-card-v2').forEach((card) => {
        card.addEventListener('mouseenter', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(card, { y: -3, duration: 0.24, ease: 'power2.out', overwrite: 'auto' });
        });

        card.addEventListener('mouseleave', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(card, { y: 0, duration: 0.3, ease: 'power2.out', overwrite: 'auto' });
        });
    });

    document.querySelectorAll('[data-edit-profile-page] .profile-field-v2 input:not([readonly])').forEach((input) => {
        input.addEventListener('focus', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(input, { x: 2, duration: 0.18, ease: 'power2.out', overwrite: 'auto' });
        });

        input.addEventListener('blur', () => {
            if (reduceMotion || !gsap) return;
            gsap.to(input, { x: 0, duration: 0.2, ease: 'power2.out', overwrite: 'auto' });
        });
    });

    const scrollProgress = document.createElement('progress');
    scrollProgress.className = 'page-progress';
    scrollProgress.setAttribute('aria-hidden', 'true');
    scrollProgress.max = 1;
    scrollProgress.value = 0;
    document.body.prepend(scrollProgress);

    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.type = 'button';
    backToTop.setAttribute('aria-label', 'Kembali ke bagian atas halaman');
    backToTop.innerHTML = '<span aria-hidden="true">↑</span><b>Ke atas</b>';
    document.body.append(backToTop);
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));

    let scrollTicking = false;
    const updateScrollInterface = () => {
        const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
        const scrollable = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
        const progress = Math.min(scrollTop / scrollable, 1);

        header?.classList.toggle('is-scrolled', scrollTop > 8);
        backToTop.classList.toggle('is-visible', scrollTop > 520);
        scrollProgress.value = progress;
        scrollTicking = false;
    };
    const requestScrollUpdate = () => {
        if (scrollTicking) return;
        scrollTicking = true;
        window.requestAnimationFrame(updateScrollInterface);
    };
    updateScrollInterface();
    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
    window.addEventListener('resize', requestScrollUpdate, { passive: true });

    document.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const message = button.closest('[data-dismissible]');
            if (!message) return;
            if (reduceMotion) {
                message.remove();
                return;
            }
            if (gsap) {
                gsap.to(message, { autoAlpha: 0, y: -8, duration: 0.2, ease: 'power2.out', onComplete: () => message.remove() });
                return;
            }
            const animation = message.animate(
                [{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(-8px)' }],
                { duration: 200, easing: 'ease-out' }
            );
            animation.addEventListener('finish', () => message.remove());
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.textContent = visible ? 'Lihat' : 'Sembunyikan';
            button.setAttribute('aria-pressed', String(!visible));
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-file-input]').forEach((input) => {
        const dropField = input.closest('label');
        const fileName = dropField?.querySelector('[data-file-name]');
        input.addEventListener('change', () => {
            if (fileName) fileName.textContent = input.files?.[0]?.name || 'Belum ada file dipilih';
            dropField?.classList.toggle('has-file', Boolean(input.files?.length));
        });
        ['dragenter', 'dragover'].forEach((eventName) => dropField?.addEventListener(eventName, () => dropField.classList.add('is-dragging')));
        ['dragleave', 'drop'].forEach((eventName) => dropField?.addEventListener(eventName, () => dropField.classList.remove('is-dragging')));
    });

    document.querySelectorAll('[data-character-count]').forEach((input) => {
        const output = document.querySelector(`[data-character-output="${input.id}"]`);
        if (!output) return;
        const maximum = Number(input.getAttribute('maxlength')) || 0;
        const updateCount = () => {
            const current = [...input.value].length;
            output.textContent = `${current.toLocaleString('id-ID')} / ${maximum.toLocaleString('id-ID')}`;
            output.classList.toggle('is-near-limit', maximum > 0 && current >= maximum * 0.9);
        };
        updateCount();
        input.addEventListener('input', updateCount);
    });

    document.querySelectorAll('form[data-busy-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (!form.checkValidity()) return;
            const button = form.querySelector('button[type="submit"]');
            if (!button || button.dataset.allowRepeat === 'true') return;
            button.dataset.originalLabel = button.textContent.trim();
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = form.dataset.busyLabel || 'Sedang memproses…';
        });
    });

    const reviewCheckAll = document.querySelector('[data-review-check-all]');
    const reviewCheckboxes = [...document.querySelectorAll('[data-review-checkbox]')];
    const syncReviewCheckAll = () => {
        if (!reviewCheckAll) return;
        const selected = reviewCheckboxes.filter((checkbox) => checkbox.checked).length;
        reviewCheckAll.checked = reviewCheckboxes.length > 0 && selected === reviewCheckboxes.length;
        reviewCheckAll.indeterminate = selected > 0 && selected < reviewCheckboxes.length;
    };
    reviewCheckAll?.addEventListener('change', () => {
        reviewCheckboxes.forEach((checkbox) => { checkbox.checked = reviewCheckAll.checked; });
        syncReviewCheckAll();
    });
    reviewCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncReviewCheckAll));

    const filterToggle = document.querySelector('[data-filter-toggle]');
    const filterPanel = document.querySelector('[data-filter-panel]');
    const setFilterPanel = (open) => {
        filterPanel?.classList.toggle('is-open', open);
        filterToggle?.setAttribute('aria-expanded', String(open));
    };
    if (filterToggle && filterPanel) {
        setFilterPanel(filterToggle.dataset.startOpen === 'true');
        filterToggle.addEventListener('click', () => setFilterPanel(!filterPanel.classList.contains('is-open')));
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setNavigation(false);
            setSidebar(false);
            setAccountMenu(false);
            if (filterPanel) setFilterPanel(false);
            return;
        }

        const target = event.target;
        const typing = target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement || target?.isContentEditable;
        if (event.key === '/' && !typing) {
            const search = document.querySelector('[data-catalog-search], #hero-q');
            if (search) {
                event.preventDefault();
                search.focus();
                search.select?.();
            }
        }
    });

    const motionTargets = [
        ...document.querySelectorAll('[data-reveal], [data-motion-item], .variable-row, .principle-grid article, .process-list li, .coverage-track, .scope-switch__item, .metric-strip > div, .quality-command, .auth-panel__inner, .admin-auth-panel__inner'),
    ];
    const uniqueMotionTargets = [...new Set(motionTargets)];
    uniqueMotionTargets.forEach((item) => {
        item.dataset.motionState = reduceMotion ? 'visible' : 'pending';
        if (reduceMotion) item.classList.add('is-visible');
    });

    if (!reduceMotion && gsap && uniqueMotionTargets.length && 'IntersectionObserver' in window) {
        gsap.set(uniqueMotionTargets, { autoAlpha: 0, y: 16 });
        const revealObserver = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .map((entry) => entry.target);

            if (!visible.length) return;

            visible.forEach((item) => {
                item.dataset.motionState = 'visible';
                item.classList.add('is-visible');
                revealObserver.unobserve(item);
            });

            gsap.to(visible, {
                autoAlpha: 1,
                y: 0,
                duration: 0.58,
                stagger: 0.065,
                ease: 'power2.out',
                overwrite: 'auto',
                clearProps: 'opacity,visibility,transform',
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
        uniqueMotionTargets.forEach((item) => revealObserver.observe(item));
    } else if (!reduceMotion && 'IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.dataset.motionState = 'visible';
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
        uniqueMotionTargets.forEach((item) => revealObserver.observe(item));
    } else {
        uniqueMotionTargets.forEach((item) => {
            item.dataset.motionState = 'visible';
            item.classList.add('is-visible');
        });
        if (gsap && uniqueMotionTargets.length) gsap.set(uniqueMotionTargets, { clearProps: 'all' });
    }

    const countElements = [...document.querySelectorAll('[data-count]')];
    const animateCount = (element) => {
        const finalValue = Number(element.dataset.count || element.textContent.replace(/[^0-9]/g, ''));
        if (!Number.isFinite(finalValue) || finalValue < 1 || reduceMotion) return;
        const duration = Math.min(1.2, 0.5 + Math.log10(finalValue + 1) * 0.22);
        const formatter = new Intl.NumberFormat('id-ID');

        if (gsap) {
            const counter = { value: 0 };
            gsap.to(counter, {
                value: finalValue,
                duration,
                ease: 'power2.out',
                onUpdate: () => { element.textContent = formatter.format(Math.round(counter.value)); },
                onComplete: () => { element.textContent = formatter.format(finalValue); },
            });
            return;
        }

        const started = performance.now();
        const tick = (now) => {
            const progress = Math.min((now - started) / (duration * 1000), 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = formatter.format(Math.round(finalValue * eased));
            if (progress < 1) window.requestAnimationFrame(tick);
        };
        window.requestAnimationFrame(tick);
    };

    if (!reduceMotion && 'IntersectionObserver' in window) {
        const countObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target.dataset.counted === 'true') return;
                entry.target.dataset.counted = 'true';
                animateCount(entry.target);
                countObserver.unobserve(entry.target);
            });
        }, { threshold: 0.55 });
        countElements.forEach((element) => countObserver.observe(element));
    }
});

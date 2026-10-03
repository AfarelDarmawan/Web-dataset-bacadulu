(() => {
  const card = document.querySelector('[data-auth-card]');
  if (!card) return;

  const panes = [...card.querySelectorAll('[data-auth-pane]')];
  const heroSlides = [...card.querySelectorAll('[data-auth-hero]')];
  const links = [...card.querySelectorAll('a[data-auth-view]')];
  const forms = card.querySelector('[data-auth-forms]');
  const wave = card.querySelector('[data-auth-wave]');
  const wavePath = card.querySelector('[data-auth-wave-path]');
  const visual = card.querySelector('[data-auth-visual]');
  const visualCore = card.querySelector('[data-auth-core]');
  const visualOrbits = [...card.querySelectorAll('[data-auth-orbit]')];
  const visualNodes = [...card.querySelectorAll('[data-auth-node]')];
  const visualTiles = [...card.querySelectorAll('[data-auth-tile]')];
  const dataSources = [...card.querySelectorAll('[data-auth-source]')];
  const dataRoutes = [...card.querySelectorAll('[data-auth-route]')];
  const dataPackets = [...card.querySelectorAll('[data-auth-packet]')];
  const dataRows = [...card.querySelectorAll('[data-auth-row]')];
  const dataOutput = card.querySelector('[data-auth-output]');
  const mobile = window.matchMedia('(max-width: 900px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  card.querySelectorAll('[data-auth-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target);
      if (!input) return;

      const showPassword = input.type === 'password';
      input.type = showPassword ? 'text' : 'password';

      const label = showPassword
        ? 'Sembunyikan kata sandi'
        : 'Tampilkan kata sandi';

      button.setAttribute('aria-pressed', String(showPassword));
      button.setAttribute('aria-label', label);
      button.setAttribute('title', label);
    });
  });

  const waveStart = 'M -170 0 C -60 90 -260 210 -160 300 C -60 420 -240 520 -140 600 L -320 600 L -320 0 Z';
  const waveCover = 'M 1200 0 C 1310 90 1110 210 1210 300 C 1310 420 1130 520 1230 600 L -320 600 L -320 0 Z';
  const waveEnd = 'M 1350 0 C 1460 90 1260 210 1360 300 C 1460 420 1280 520 1380 600 L 1200 600 L 1200 0 Z';
  let transition = null;
  let focusTimer;
  let resizeTimer;

  const startVisualMotion = () => {
    if (!window.gsap || reducedMotion.matches || !visual) return;
    const gsap = window.gsap;

    const logo = visualCore?.querySelector('.auth-visual__core-logo');
    const grid = visual.querySelector('.auth-visual__grid');

    if (visualCore) {
      gsap.set(visualCore, {
        xPercent: -50,
        yPercent: -50,
        rotation: 0,
        transformOrigin: '50% 50%',
      });

      gsap.timeline({ repeat: -1, yoyo: true })
        .to(visualCore, {
          y: -7,
          rotation: 0,
          duration: 2.6,
          ease: 'sine.inOut',
        })
        .to(visualCore, {
          y: 3,
          rotation: 0,
          duration: 2.2,
          ease: 'sine.inOut',
        });
    }

    if (logo) {
      gsap.to(logo, {
        scale: 1.035,
        duration: 2.3,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
      });
    }

    if (grid) {
      gsap.to(grid, {
        backgroundPosition: '30px 30px',
        duration: 12,
        repeat: -1,
        ease: 'none',
      });
    }

    if (dataRoutes.length) {
      gsap.set(dataRoutes, { strokeDasharray: '8 10', strokeDashoffset: 36 });
      gsap.to(dataRoutes, {
        strokeDashoffset: 0,
        duration: 2.4,
        repeat: -1,
        ease: 'none',
      });
    }

    dataPackets.forEach((packet, index) => {
      const startX = Number(packet.dataset.startX);
      const startY = Number(packet.dataset.startY);
      const endX = Number(packet.dataset.endX);
      const endY = Number(packet.dataset.endY);

      gsap.set(packet, {
        attr: { cx: startX, cy: startY },
        autoAlpha: 0,
        transformOrigin: '50% 50%',
      });

      gsap.timeline({ repeat: -1, repeatDelay: .8, delay: index * .55 })
        .to(packet, { autoAlpha: 1, scale: 1, duration: .16 })
        .to(packet, {
          attr: { cx: endX, cy: endY },
          duration: 1.35,
          ease: 'power1.inOut',
        })
        .to(packet, { autoAlpha: 0, scale: .45, duration: .2 });
    });

    dataSources.forEach((source, index) => {
      gsap.to(source, {
        y: index === 1 ? 5 : -5,
        duration: 3.1 + (index * .35),
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: index * .18,
      });

      const indicators = source.querySelectorAll('.auth-dataset__mini-rows i, .auth-dataset__mini-bars i');
      gsap.fromTo(indicators,
        { scaleX: .38, transformOrigin: '0 50%' },
        { scaleX: 1, duration: .55, stagger: .13, repeat: -1, repeatDelay: 1.25, yoyo: true, ease: 'power2.inOut' });
    });

    if (dataOutput && dataRows.length) {
      gsap.to(dataOutput, {
        y: 5,
        duration: 3.7,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
      });

      gsap.timeline({ repeat: -1, repeatDelay: .7 })
        .to(dataRows, { x: 3, backgroundColor: 'rgba(243,187,76,.22)', duration: .28, stagger: .22 })
        .to(dataRows, { x: 0, backgroundColor: 'rgba(255,255,255,0)', duration: .35, stagger: .15 }, '+=.25');
    }

    visualOrbits.forEach((orbit, index) => {
      gsap.set(orbit, {
        xPercent: -50,
        yPercent: -50,
        transformOrigin: '50% 50%',
      });

      gsap.to(orbit, {
        rotation: index === 0 ? 360 : -360,
        duration: index === 0 ? 38 : 27,
        repeat: -1,
        ease: 'none',
      });
    });

    const nodeMotion = [
      { x: 7, y: -9, duration: 2.9 },
      { x: -6, y: 8, duration: 3.4 },
      { x: 8, y: 6, duration: 3.1 },
      { x: -5, y: -7, duration: 3.7 },
    ];

    visualNodes.forEach((node, index) => {
      const movement = nodeMotion[index] || nodeMotion[0];

      gsap.to(node, {
        x: movement.x,
        y: movement.y,
        duration: movement.duration,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: index * .18,
      });
    });

    const tileMotion = [
      { x: -5, y: -10, rotation: 3, duration: 3.8 },
      { x: 6, y: 9, rotation: -3, duration: 4.4 },
      { x: 4, y: -7, rotation: 2, duration: 4.1 },
    ];

    visualTiles.forEach((tile, index) => {
      const movement = tileMotion[index] || tileMotion[0];

      gsap.to(tile, {
        x: movement.x,
        y: movement.y,
        rotation: `${movement.rotation >= 0 ? '+' : '-'}=${Math.abs(movement.rotation)}`,
        duration: movement.duration,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: index * .22,
      });
    });
  };

  const heightFor = (view, animate = false) => {
    if (!mobile.matches) {
      window.gsap?.killTweensOf(forms);
      forms.style.removeProperty('height');
      forms.style.removeProperty('--form-height');
      return;
    }

    const inner = panes
      .find((pane) => pane.dataset.authPane === view)
      ?.querySelector('.auth-card__form-inner');

    if (!inner) return;

    const targetHeight = Math.ceil(inner.scrollHeight + 2);
    const currentHeight = forms.getBoundingClientRect().height;

    forms.style.setProperty('--form-height', `${targetHeight}px`);

    if (
      animate
      && window.gsap
      && !reducedMotion.matches
      && Math.abs(currentHeight - targetHeight) > 1
    ) {
      window.gsap.fromTo(forms, { height: currentHeight }, {
        height: targetHeight,
        duration: .54,
        ease: 'sine.inOut',
        overwrite: 'auto',
        onComplete: () => forms.style.removeProperty('height'),
      });
    } else {
      window.gsap?.killTweensOf(forms);
      forms.style.removeProperty('height');
    }
  };

  const setView = (
    view,
    { focus = false, updateHistory = false, delay = 0, animateHeight = false } = {}
  ) => {
    if (view !== 'signin' && view !== 'signup') return;

    clearTimeout(focusTimer);

    const previousView = card.dataset.view;
    const activePane = panes.find((pane) => pane.dataset.authPane === previousView);

    if (activePane?.contains(document.activeElement)) {
      document.activeElement.blur();
    }

    card.dataset.view = view;

    panes.forEach((pane) => {
      const active = pane.dataset.authPane === view;
      pane.inert = !active;
      pane.setAttribute('aria-hidden', String(!active));

      if (active) pane.scrollTop = 0;
    });

    heroSlides.forEach((slide) => {
      slide.setAttribute('aria-hidden', String(slide.dataset.authHero !== view));
    });

    links.forEach((link) => {
      if (link.dataset.authView === view) {
        link.setAttribute('aria-current', 'page');
      } else {
        link.removeAttribute('aria-current');
      }
    });

    heightFor(view, animateHeight);

    document.title = `${view === 'signup' ? 'Daftar Peneliti' : 'Masuk Peneliti'} — BacaDulu Dataset`;

    if (updateHistory && previousView !== view) {
      const destination = links.find((link) => link.dataset.authView === view);

      if (destination) {
        history.pushState({ authView: view }, '', destination.href);
      }
    }

    if (focus) {
      focusTimer = setTimeout(() => {
        panes
          .find((pane) => pane.dataset.authPane === view)
          ?.querySelector('input:not([type="hidden"])')
          ?.focus({ preventScroll: true });
      }, delay);
    }
  };

  const switchView = (view, options = {}) => {
    if (view === card.dataset.view || transition) return;

    if (!window.gsap || reducedMotion.matches || !wave || !wavePath) {
      setView(view, { ...options, delay: 0 });
      return;
    }

    const gsap = window.gsap;
    const origin = card.dataset.view === 'signup' ? 'right' : 'left';

    card.classList.add('is-switching');

    gsap.set(wave, {
      visibility: 'visible',
      scaleX: origin === 'right' ? -1 : 1,
      transformOrigin: '50% 50%',
    });

    gsap.set(wavePath, { attr: { d: waveStart } });

    transition = gsap.timeline({
      onComplete: () => {
        gsap.set(wave, { visibility: 'hidden' });
        gsap.set(wavePath, { attr: { d: waveStart } });
        card.classList.remove('is-switching');
        transition = null;

        if (options.focus) {
          clearTimeout(focusTimer);

          focusTimer = setTimeout(() => {
            panes
              .find((pane) => pane.dataset.authPane === view)
              ?.querySelector('input:not([type="hidden"])')
              ?.focus({ preventScroll: true });
          }, 0);
        }
      },
    });

    transition.to(visual, {
      scale: .965,
      rotation: origin === 'right' ? -2 : 2,
      duration: .28,
      ease: 'sine.inOut',
    }, 0)
      .to(wavePath, {
        attr: { d: waveCover },
        duration: .52,
        ease: 'sine.inOut',
      }, 0)
      .call(() => {
        setView(view, {
          updateHistory: options.updateHistory,
          animateHeight: true,
        });
      })
      .to(wavePath, {
        attr: { d: waveEnd },
        duration: .56,
        ease: 'sine.inOut',
      })
      .to(visual, {
        scale: 1,
        rotation: 0,
        duration: .38,
        ease: 'power2.out',
      }, '-=.38');
  };

  heightFor(card.dataset.view);

  if (window.gsap && !reducedMotion.matches) {
    window.gsap.fromTo(card, { autoAlpha: 0, y: 8 }, {
      autoAlpha: 1,
      y: 0,
      duration: .45,
      ease: 'power2.out',
      clearProps: 'transform,opacity,visibility',
    });

    window.gsap.fromTo(
      [...visualTiles, ...visualNodes, ...dataSources, dataOutput].filter(Boolean),
      { autoAlpha: 0, scale: .7 },
      {
        autoAlpha: 1,
        scale: 1,
        duration: .65,
        stagger: .07,
        delay: .18,
        ease: 'back.out(1.7)',
        clearProps: 'opacity,visibility,scale',
      }
    );
  }

  startVisualMotion();

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
      ) {
        return;
      }

      event.preventDefault();

      switchView(link.dataset.authView, {
        focus: true,
        updateHistory: true,
      });
    });
  });

  window.addEventListener('popstate', () => {
    const view = location.pathname.replace(/\/$/, '').endsWith('/register')
      ? 'signup'
      : 'signin';

    if (transition) transition.progress(1);

    switchView(view);
  });

  window.addEventListener('resize', () => {
    if (transition) transition.progress(1);

    card.classList.add('is-resizing');
    heightFor(card.dataset.view);

    clearTimeout(resizeTimer);

    resizeTimer = setTimeout(() => {
      card.classList.remove('is-resizing');
    }, 150);
  });
})();
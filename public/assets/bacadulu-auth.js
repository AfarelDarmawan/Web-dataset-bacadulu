(() => {
  const card = document.querySelector('[data-auth-card]');
  if (!card) return;

  const panes = [...card.querySelectorAll('[data-auth-pane]')];
  const heroSlides = [...card.querySelectorAll('[data-auth-hero]')];
  const links = [...card.querySelectorAll('a[data-auth-view]')];
  const forms = card.querySelector('[data-auth-forms]');
  const wave = card.querySelector('[data-auth-wave]');
  const wavePath = card.querySelector('[data-auth-wave-path]');
  const mobile = window.matchMedia('(max-width: 760px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const waveStart = 'M -170 0 C -60 90 -260 210 -160 300 C -60 420 -240 520 -140 600 L -320 600 L -320 0 Z';
  const waveCover = 'M 1200 0 C 1310 90 1110 210 1210 300 C 1310 420 1130 520 1230 600 L -320 600 L -320 0 Z';
  const waveEnd = 'M 1350 0 C 1460 90 1260 210 1360 300 C 1460 420 1280 520 1380 600 L 1200 600 L 1200 0 Z';
  let transition = null;
  let focusTimer;
  let resizeTimer;

  const heightFor = (view, animate = false) => {
    if (!mobile.matches) {
      window.gsap?.killTweensOf(forms);
      forms.style.removeProperty('height');
      forms.style.removeProperty('--form-height');
      return;
    }
    const inner = panes.find((pane) => pane.dataset.authPane === view)?.querySelector('.auth-card__form-inner');
    if (!inner) return;
    const targetHeight = Math.ceil(inner.scrollHeight + 2);
    const currentHeight = forms.getBoundingClientRect().height;
    forms.style.setProperty('--form-height', `${targetHeight}px`);
    if (animate && window.gsap && !reducedMotion.matches && Math.abs(currentHeight - targetHeight) > 1) {
      window.gsap.fromTo(forms, { height: currentHeight }, {
        height: targetHeight, duration: .54, ease: 'sine.inOut', overwrite: 'auto',
        onComplete: () => forms.style.removeProperty('height'),
      });
    } else {
      window.gsap?.killTweensOf(forms);
      forms.style.removeProperty('height');
    }
  };

  const setView = (view, { focus = false, updateHistory = false, delay = 0, animateHeight = false } = {}) => {
    if (view !== 'signin' && view !== 'signup') return;
    clearTimeout(focusTimer);
    const previousView = card.dataset.view;
    const activePane = panes.find((pane) => pane.dataset.authPane === previousView);
    if (activePane?.contains(document.activeElement)) document.activeElement.blur();

    card.dataset.view = view;
    panes.forEach((pane) => {
      const active = pane.dataset.authPane === view;
      pane.inert = !active;
      pane.setAttribute('aria-hidden', String(!active));
      if (active) pane.scrollTop = 0;
    });
    heroSlides.forEach((slide) => slide.setAttribute('aria-hidden', String(slide.dataset.authHero !== view)));
    links.forEach((link) => {
      if (link.dataset.authView === view) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');
    });
    heightFor(view, animateHeight);
    document.title = `${view === 'signup' ? 'Daftar Peneliti' : 'Masuk Peneliti'} — BacaDulu Dataset`;
    if (updateHistory && previousView !== view) {
      const destination = links.find((link) => link.dataset.authView === view);
      if (destination) history.pushState({ authView: view }, '', destination.href);
    }
    if (focus) {
      focusTimer = setTimeout(() => {
        panes.find((pane) => pane.dataset.authPane === view)?.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
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
    gsap.set(wave, { visibility: 'visible', scaleX: origin === 'right' ? -1 : 1, transformOrigin: '50% 50%' });
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
            panes.find((pane) => pane.dataset.authPane === view)?.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
          }, 0);
        }
      },
    });
    transition.to(wavePath, { attr: { d: waveCover }, duration: .52, ease: 'sine.inOut' })
      .call(() => setView(view, { updateHistory: options.updateHistory, animateHeight: true }))
      .to(wavePath, { attr: { d: waveEnd }, duration: .56, ease: 'sine.inOut' });
  };

  heightFor(card.dataset.view);
  if (window.gsap && !reducedMotion.matches) {
    window.gsap.fromTo(card, { autoAlpha: 0, y: 8 }, {
      autoAlpha: 1, y: 0, duration: .45, ease: 'power2.out', clearProps: 'transform,opacity,visibility',
    });
  }

  links.forEach((link) => link.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    switchView(link.dataset.authView, { focus: true, updateHistory: true });
  }));

  window.addEventListener('popstate', () => {
    const view = location.pathname.replace(/\/$/, '').endsWith('/register') ? 'signup' : 'signin';
    if (transition) transition.progress(1);
    switchView(view);
  });
  window.addEventListener('resize', () => {
    if (transition) transition.progress(1);
    card.classList.add('is-resizing');
    heightFor(card.dataset.view);
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => card.classList.remove('is-resizing'), 150);
  });
})();

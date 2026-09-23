@php
    $contactEmail = (string) config('bacadulu.contact.email');
    $whatsappNumber = (string) config('bacadulu.contact.whatsapp_number');
    $whatsappLabel = (string) config('bacadulu.contact.whatsapp_label');
@endphp

<footer id="main-footer" class="bd-footer">
    <div class="bd-footer-decoration" aria-hidden="true"></div>

    <div class="bd-footer-container">
        <div class="bd-footer-grid">
            <div class="bd-footer-column bd-footer-column--brand">
                <div>
                    <h2 class="bd-footer-title">
                        Baca Dulu,
                        <span>Pahami Kemudian.</span>
                    </h2>

                    <p class="bd-footer-description">
                        Katalog data penelitian yang terstruktur, tertelusur, dan siap diolah—mulai dari
                        statistik wilayah dan pemerintah hingga data perusahaan dan ESG.
                    </p>
                </div>

                <div class="bd-footer-company" aria-label="Dikelola oleh PT Bina Cendikia Academy">
                    <span class="bd-footer-company__logo"><img src="{{ asset('assets/bacadulu-logo.png') }}" alt="BacaDulu" width="447" height="447" loading="lazy"></span>
                    <span>
                        <small>Dikelola oleh</small>
                        <strong>PT Bina Cendikia Academy</strong>
                    </span>
                </div>
            </div>

            <div class="bd-footer-column">
                <h3 class="bd-footer-heading">Lokasi Kantor</h3>

                <div class="bd-footer-location">
                    <h4><span aria-hidden="true"></span>The Manhattan Square</h4>
                    <p>
                        Jl. TB Simatupang, Lt. 12, RT.3/RW.3, Cilandak Timur,
                        Pasar Minggu, Jakarta Selatan.
                    </p>
                </div>

                <div class="bd-footer-map">
                    <iframe
                        src="https://maps.google.com/maps?q=The%20Manhattan%20Square%2C%20Jl.%20TB%20Simatupang%2C%20Jakarta%20Selatan&amp;t=&amp;z=16&amp;ie=UTF8&amp;iwloc=&amp;output=embed"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Lokasi kantor BacaDulu"
                    ></iframe>
                </div>
            </div>

            <div class="bd-footer-column">
                <h3 class="bd-footer-heading">Hubungi Kami</h3>

                <div class="bd-footer-contact-list">
                    <a href="mailto:{{ $contactEmail }}" class="bd-footer-contact" aria-label="Kirim email ke BacaDulu">
                        <span class="bd-footer-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </span>
                        <span>{{ $contactEmail }}</span>
                    </a>

                    @if($whatsappNumber !== '')
                        <a
                            href="https://wa.me/{{ $whatsappNumber }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="bd-footer-contact"
                            aria-label="Hubungi BacaDulu melalui WhatsApp"
                        >
                            <span class="bd-footer-contact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12.04 2a9.84 9.84 0 00-8.42 14.93L2 22l5.2-1.58A9.9 9.9 0 1012.04 2zm0 17.82a7.9 7.9 0 01-4.03-1.1l-.29-.17-3.09.94.97-3-.19-.31a7.81 7.81 0 116.63 3.64zm4.31-5.86c-.24-.12-1.4-.69-1.62-.77-.22-.08-.37-.12-.53.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.9-1.18-.7-.63-1.18-1.4-1.32-1.64-.14-.24-.01-.37.1-.49.11-.11.24-.28.35-.41.12-.14.16-.24.24-.4.08-.15.04-.29-.02-.41-.06-.12-.53-1.28-.73-1.75-.19-.46-.39-.4-.53-.4h-.45c-.16 0-.41.06-.63.29-.22.24-.83.81-.83 1.97s.85 2.29.96 2.45c.12.16 1.66 2.54 4.03 3.56.56.24 1 .39 1.35.49.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.02.14-1.12-.06-.1-.22-.16-.46-.28z"/></svg>
                            </span>
                            <span>{{ $whatsappLabel }}</span>
                        </a>
                    @endif
                </div>

                <div class="bd-footer-links">
                    <h4>Jelajahi</h4>
                    <a href="{{ route('datasets.index') }}">Katalog variabel</a>
                    <a href="{{ route('home') }}#cara-kerja">Cara kerja</a>
                    <a href="{{ route('home') }}#standar-data">Standar data</a>
                </div>

                <div class="bd-footer-social-area">
                    <h4>Ikuti Kami</h4>
                    <div class="bd-footer-socials">
                        <a href="https://www.youtube.com/@Bacaduluofficial" target="_blank" rel="noopener noreferrer" aria-label="YouTube BacaDulu">
                            <svg class="bd-social-icon bd-social-icon--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.163a3.003 3.003 0 00-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.516 0-9.387.507a3.003 3.003 0 00-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 002.11 2.11c1.871.507 9.387.507 9.387.507s7.517 0 9.387-.507a3.003 3.003 0 002.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/bacaduluofficial/" target="_blank" rel="noopener noreferrer" aria-label="Instagram BacaDulu">
                            <svg class="bd-social-icon bd-social-icon--instagram" viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="2.5" width="19" height="19" rx="5.5"/><circle cx="12" cy="12" r="4.25"/><circle class="bd-social-instagram-dot" cx="17.6" cy="6.45" r="1.15"/></svg>
                        </a>
                        <a href="https://www.linkedin.com/in/bacadulu?originalSubdomain=id" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn BacaDulu">
                            <svg class="bd-social-icon bd-social-icon--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.047c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 110-4.124 2.062 2.062 0 010 4.124zM7.119 20.452H3.555V9h3.564v11.452z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bd-footer-bottom">
            <p>&copy; {{ now()->year }} BacaDulu Dataset. All rights reserved.</p>
            <p>Data yang bisa ditelusuri, bukan sekadar diunduh.</p>
        </div>
    </div>
</footer>

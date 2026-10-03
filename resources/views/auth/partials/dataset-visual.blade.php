<aside class="auth-card__hero" aria-hidden="true">
    <div class="auth-visual auth-dataset-visual" data-auth-visual>
        <span class="auth-visual__grid"></span>

        <svg
            class="auth-dataset__network"
            viewBox="0 0 500 500"
            preserveAspectRatio="none"
            focusable="false"
        >
            <path
                class="auth-dataset__route"
                data-auth-route
                d="M118 130 L220 226"
            />

            <path
                class="auth-dataset__route"
                data-auth-route
                d="M382 130 L280 226"
            />

            <path
                class="auth-dataset__route"
                data-auth-route
                d="M118 370 L220 274"
            />

            <path
                class="auth-dataset__route auth-dataset__route--ready"
                data-auth-route
                d="M280 274 L382 370"
            />

            <circle
                class="auth-dataset__packet"
                data-auth-packet
                data-start-x="118"
                data-start-y="130"
                data-end-x="220"
                data-end-y="226"
                cx="118"
                cy="130"
                r="5"
            />

            <circle
                class="auth-dataset__packet"
                data-auth-packet
                data-start-x="382"
                data-start-y="130"
                data-end-x="280"
                data-end-y="226"
                cx="382"
                cy="130"
                r="5"
            />

            <circle
                class="auth-dataset__packet"
                data-auth-packet
                data-start-x="118"
                data-start-y="370"
                data-end-x="220"
                data-end-y="274"
                cx="118"
                cy="370"
                r="5"
            />

            <circle
                class="auth-dataset__packet auth-dataset__packet--ready"
                data-auth-packet
                data-start-x="280"
                data-start-y="274"
                data-end-x="382"
                data-end-y="370"
                cx="280"
                cy="274"
                r="5"
            />
        </svg>

        <div
            class="auth-dataset__source auth-dataset__source--csv"
            data-auth-source
        >
            <span class="auth-dataset__source-type">CSV</span>
            <strong>Data mentah</strong>

            <span class="auth-dataset__mini-rows">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>

        <div
            class="auth-dataset__source auth-dataset__source--api"
            data-auth-source
        >
            <span class="auth-dataset__source-type">API</span>
            <strong>Sinkronisasi</strong>

            <span class="auth-dataset__mini-bars">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>

        <div
            class="auth-dataset__source auth-dataset__source--bps"
            data-auth-source
        >
            <span class="auth-dataset__source-type">BPS</span>
            <strong>Data publik</strong>

            <span class="auth-dataset__mini-rows">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>

        <span class="auth-visual__core" data-auth-core>
            <span class="auth-visual__core-logo">
                <img
                    src="{{ asset('assets/bacadulu-logo.png') }}"
                    alt=""
                >
            </span>
        </span>

        <div class="auth-dataset__output" data-auth-output>
            <span class="auth-dataset__output-head">
                <strong>Katalog siap</strong>
                <i>✓</i>
            </span>

            <span class="auth-dataset__row" data-auth-row>
                <b>Variabel</b>
                <i></i>
            </span>

            <span class="auth-dataset__row" data-auth-row>
                <b>Periode</b>
                <i></i>
            </span>

            <span class="auth-dataset__row" data-auth-row>
                <b>Sumber</b>
                <i></i>
            </span>
        </div>
    </div>
</aside>

<svg
    class="auth-card__wave"
    data-auth-wave
    viewBox="0 0 1000 600"
    preserveAspectRatio="none"
    aria-hidden="true"
    focusable="false"
>
    <path
        data-auth-wave-path
        d="M -200 0 C -100 150 -250 450 -150 600 L -300 600 L -300 0 Z"
        fill="#f3bb4c"
    />
</svg>
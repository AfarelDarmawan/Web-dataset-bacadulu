<?php

$adminPath = trim(
    (string) env(
        'BACADULU_ADMIN_PATH',
        'panel-adminbaca'
    ),
    '/'
);

$reservedAdminPaths = [
    'admin',
    'login',
    'register',
    'dashboard',
    'datasets',
    'my-requests',
    'up',
];

if (
    ! preg_match(
        '/^[a-z0-9][a-z0-9-]{4,79}$/',
        $adminPath
    )
    || in_array(
        $adminPath,
        $reservedAdminPaths,
        true
    )
) {
    $adminPath = 'panel-adminbaca';
}

return [
    'admin' => [
        'name' => env(
            'BACADULU_ADMIN_NAME',
            'BacaDulu Admin'
        ),
        'email' => env(
            'BACADULU_ADMIN_EMAIL',
            'admin@bacadulu.test'
        ),
        'password' => env(
            'BACADULU_ADMIN_PASSWORD',
            'ChangeMe123!'
        ),
        'path' => $adminPath,
    ],

    'catalog' => [
        'preview_limit' => max(
            1,
            min(
                100,
                (int) env(
                    'BACADULU_PREVIEW_LIMIT',
                    25
                )
            )
        ),

        'import_limit' => max(
            1,
            min(
                200000,
                (int) env(
                    'BACADULU_IMPORT_LIMIT',
                    50000
                )
            )
        ),

        'import_max_kilobytes' => max(
            100,
            min(
                51200,
                (int) env(
                    'BACADULU_IMPORT_MAX_KB',
                    20480
                )
            )
        ),
    ],

    'contact' => [
        'email' => env(
            'BACADULU_CONTACT_EMAIL',
            'admnbacadulu.net@gmail.com'
        ),

        'whatsapp_number' => preg_replace(
            '/\D+/',
            '',
            (string) env(
                'BACADULU_CALL_CENTER_WA',
                ''
            )
        ),

        'whatsapp_label' => env(
            'BACADULU_CALL_CENTER',
            'WhatsApp BacaDulu'
        ),

        'service_hours' => env(
            'BACADULU_SERVICE_HOURS',
            'Senin–Jumat, 09.00–17.00 WIB'
        ),

        'urgent_response' => env(
            'BACADULU_URGENT_RESPONSE',
            'Pesan urgent diprioritaskan pada jam layanan.'
        ),
    ],

    'payment' => [
        'mode' => env(
            'BACADULU_PAYMENT_MODE',
            'manual_verified'
        ),

        'label' => env(
            'BACADULU_PAYMENT_LABEL',
            'Pembayaran terverifikasi'
        ),

        'instruction' => env(
            'BACADULU_PAYMENT_INSTRUCTION',
            'Admin akan mengirim invoice dan metode pembayaran resmi setelah cakupan data diperiksa.'
        ),
    ],

    'bps' => [
        'enabled' => filter_var(
            env(
                'BACADULU_BPS_ENABLED',
                false
            ),
            FILTER_VALIDATE_BOOL
        ),

        'api_key' => env('BPS_API_KEY'),

        'endpoint' =>
            'https://webapi.bps.go.id/v1/api/list',

        'mock' => filter_var(
            env(
                'BACADULU_BPS_MOCK',
                false
            ),
            FILTER_VALIDATE_BOOL
        ),

        'timeout_seconds' => max(
            5,
            min(
                60,
                (int) env(
                    'BACADULU_BPS_TIMEOUT',
                    25
                )
            )
        ),

        'max_rows' => max(
            10,
            min(
                100000,
                (int) env(
                    'BACADULU_BPS_MAX_ROWS',
                    20000
                )
            )
        ),

        'max_response_kilobytes' => max(
            100,
            min(
                20480,
                (int) env(
                    'BACADULU_BPS_MAX_RESPONSE_KB',
                    5120
                )
            )
        ),
    ],

    'ai' => [
        'enabled' => filter_var(
            env(
                'BACADULU_AI_ENABLED',
                false
            ),
            FILTER_VALIDATE_BOOL
        ),

        'api_key' => env('OPENAI_API_KEY'),

        'model' => env(
            'BACADULU_AI_MODEL',
            'gpt-4.1-mini'
        ),

        'endpoint' =>
            'https://api.openai.com/v1/responses',

        'disk' => 'local',

        'max_kilobytes' => max(
            100,
            min(
                20480,
                (int) env(
                    'BACADULU_AI_MAX_KB',
                    10240
                )
            )
        ),

        'max_rows' => max(
            1,
            min(
                1000,
                (int) env(
                    'BACADULU_AI_MAX_ROWS',
                    1000
                )
            )
        ),

        'timeout_seconds' => max(
            30,
            min(
                300,
                (int) env(
                    'BACADULU_AI_TIMEOUT',
                    180
                )
            )
        ),

        'stale_after_seconds' => max(
            120,
            min(
                1800,
                (int) env(
                    'BACADULU_AI_STALE_AFTER',
                    300
                )
            )
        ),

        'max_output_tokens' => max(
            2000,
            min(
                30000,
                (int) env(
                    'BACADULU_AI_MAX_OUTPUT_TOKENS',
                    12000
                )
            )
        ),

        'pdf_detail' => in_array(
            env(
                'BACADULU_AI_PDF_DETAIL',
                'auto'
            ),
            ['auto', 'low', 'high'],
            true
        )
            ? env(
                'BACADULU_AI_PDF_DETAIL',
                'auto'
            )
            : 'auto',

        'prompt_version' =>
            'dataset-extract-v2',
    ],
];
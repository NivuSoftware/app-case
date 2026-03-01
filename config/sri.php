<?php

return [
    'max_pending_hours' => (int) env('SRI_MAX_PENDING_HOURS', 24),
    'max_review_hours' => (int) env('SRI_MAX_REVIEW_HOURS', 72),
    'documents_disk' => env(
        'SRI_DOCUMENTS_DISK',
        env(
            'DEFAULT_FILESYSTEM_DRIVER',
            env(
                'FILESYSTEM_DRIVER',
                env(
                    'FILESYSTEM_DISK',
                    explode(',', (string) env('SUPPORTED_FILE_SYSTEMS', 'local'))[0]
                )
            )
        )
    ),
];

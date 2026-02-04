<?php

return [
    'max_pending_hours' => (int) env('SRI_MAX_PENDING_HOURS', 24),
    'max_review_hours' => (int) env('SRI_MAX_REVIEW_HOURS', 72),
];

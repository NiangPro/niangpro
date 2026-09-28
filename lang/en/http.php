<?php

// HTTP error messages (HttpException, Handler, error pages, authentication middleware).
return [
    '401' => 'Authentication required.',
    '403' => 'This action is unauthorized.',
    '404' => 'Page not found.',
    '405' => 'Method not allowed.',
    '419' => 'Invalid or expired CSRF token.',
    '429' => 'Too many requests, please try again later.',
    'server_error' => 'Server error.',
    'database_error' => 'Database error.',
    'other' => 'HTTP error :status.',
    'forbidden_ability' => 'This action is unauthorized: :ability',
    'csrf_mismatch' => 'Invalid or expired CSRF token. Reload the page and try again.',
    'unauthenticated' => 'Unauthenticated.',
    'invalid_token' => 'Invalid or revoked API token.',
    'email_not_verified' => 'Email address not verified.',
    'invalid_signature' => 'Invalid, expired or modified link.',
    'page_not_found' => 'This page does not exist.',
    'page_forbidden' => 'You are not allowed to perform this action.',
    'page_server_error' => 'Something went wrong. The team has been notified.',
    'back_home' => 'Back to home',
];

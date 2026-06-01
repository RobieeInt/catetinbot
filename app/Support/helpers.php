<?php

if (!function_exists('rp')) {
    function rp(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('wita_now')) {
    function wita_now(): \Carbon\Carbon
    {
        return \Carbon\Carbon::now(config('app.timezone'));
    }
}

if (!function_exists('week_start')) {
    function week_start(): string
    {
        return \Carbon\Carbon::now(config('app.timezone'))->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
    }
}

if (!function_exists('week_end')) {
    function week_end(): string
    {
        return \Carbon\Carbon::now(config('app.timezone'))->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
    }
}

if (!function_exists('month_start')) {
    function month_start(): string
    {
        return \Carbon\Carbon::now(config('app.timezone'))->startOfMonth()->format('Y-m-d');
    }
}

if (!function_exists('month_end')) {
    function month_end(): string
    {
        return \Carbon\Carbon::now(config('app.timezone'))->endOfMonth()->format('Y-m-d');
    }
}

if (!function_exists('today_wita')) {
    function today_wita(): string
    {
        return \Carbon\Carbon::now(config('app.timezone'))->format('Y-m-d');
    }
}

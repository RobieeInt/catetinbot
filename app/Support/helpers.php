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
        return \Carbon\Carbon::now('Asia/Makassar');
    }
}

if (!function_exists('week_start')) {
    function week_start(): string
    {
        return \Carbon\Carbon::now('Asia/Makassar')->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
    }
}

if (!function_exists('week_end')) {
    function week_end(): string
    {
        return \Carbon\Carbon::now('Asia/Makassar')->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
    }
}

if (!function_exists('month_start')) {
    function month_start(): string
    {
        return \Carbon\Carbon::now('Asia/Makassar')->startOfMonth()->format('Y-m-d');
    }
}

if (!function_exists('month_end')) {
    function month_end(): string
    {
        return \Carbon\Carbon::now('Asia/Makassar')->endOfMonth()->format('Y-m-d');
    }
}

if (!function_exists('today_wita')) {
    function today_wita(): string
    {
        return \Carbon\Carbon::now('Asia/Makassar')->format('Y-m-d');
    }
}

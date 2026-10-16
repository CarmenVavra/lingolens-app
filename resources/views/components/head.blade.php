@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#123C69">
<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%23AC3B61'/%3E%3Ccircle cx='16' cy='16' r='5.5' fill='none' stroke='%23EDC7B7' stroke-width='3'/%3E%3C/svg%3E">
@vite(['resources/css/app.css', 'resources/js/app.js'])

@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#123C69">
<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%23AC3B61'/%3E%3Ccircle cx='16' cy='16' r='5.5' fill='none' stroke='%23EDC7B7' stroke-width='3'/%3E%3C/svg%3E">
{{-- Installable app (home screen without the browser badge) --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('app-icons/apple-touch-icon.png') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="LingoLens">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
@vite(['resources/css/app.css', 'resources/js/app.js'])

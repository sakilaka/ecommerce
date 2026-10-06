<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Aurelia | Premium Ecommerce in Bangladesh</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Shop authentic electronics, fashion, beauty, home, grocery, and lifestyle products across Bangladesh.">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="Aurelia | Premium Ecommerce in Bangladesh">
        <meta property="og:description" content="Shop Eid offers, electronics, fashion, beauty, home essentials, grocery, and fast Bangladesh delivery from Aurelia.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Aurelia Premium Market">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/premium-hero.png') }}">
        <meta property="og:locale" content="en_BD">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Aurelia | Premium Ecommerce in Bangladesh">
        <meta name="twitter:description" content="Shop premium ecommerce collections with nationwide Bangladesh delivery.">
        <meta name="twitter:image" content="{{ asset('images/premium-hero.png') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

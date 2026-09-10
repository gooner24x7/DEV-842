<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <base href="/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'IndieNetwork Dashboard') }}</title>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="/mn.css?t=<?=time() ?>" rel="stylesheet">
</head>

<body>

<script type="text/javascript">
    window.wsSettings = {
        'logo_image': '{{ $logo_image }}'
    };
</script>

@if ($color)
<style>
    .v-application .primary {
        background-color: {{ $color }} !important;
        border-color: {{ $color }} !important;
    }

    .middle-menu .active {
        background-color: {{ $color }} !important;
    }

    .v-application .primary--text {
        color: {{ $color }} !important;
        caret-color: {{ $color }} !important;
    }
</style>
@endif

<div id="app"></div>
<script>
    window.banners = `
<!--<a href="https://www.thebuildchain.co.uk/mind/"><img class="mind" src="https://thebuildchain.mnnet.co.uk/hire/dashboard/img/mind.png"></a><br />
<a href=""><img src="https://ntuk.co.uk/hire/dashboard/img/Enterprise-Ad.png"></a><br />-->`;
</script>


<!-- Scripts -->
<script src="/js/app.js?id=<?=time() ?>" defer></script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-NS4HY7BHE3"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-NS4HY7BHE3');
</script>

</body>

</html>

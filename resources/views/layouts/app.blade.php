<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestion de Projets')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        {{-- Header, Sidebar AdminLTE si nécessaire --}}
        <div id="app"></div> {{-- Vue 3 montera ici --}}
        {{-- Footer --}}
    </div>
</body>
</html>

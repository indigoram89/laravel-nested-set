<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Управление деревьями - Nested Set</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    
    <!-- Sortable для drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        [v-cloak] { display: none; }
        
        /* Анимации */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        /* Стили для drag & drop */
        .sortable-ghost {
            opacity: 0.5;
        }
        
        .sortable-drag {
            cursor: move;
        }
        
        /* Tailwind-подобные стили для случаев, когда CDN недоступен */
        .nested-set-manager {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="nested-set-app" class="min-h-screen" v-cloak>
        <nested-set-manager></nested-set-manager>
    </div>
    
    <!-- Подключение standalone версии приложения -->
    <script src="{{ asset('vendor/nested-set/js/nested-set-standalone.js') }}"></script>
</body>
</html>
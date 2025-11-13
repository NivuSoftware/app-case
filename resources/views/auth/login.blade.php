<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CASE APP - Login</title>
    <link rel="icon" type="image/png" href="{{ asset('lg.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-screen">

    <!-- Fondo -->
    <div class="absolute inset-0 bg-cover bg-center" 
         style="background-image: url('{{ asset('images/bg.jpeg') }}'); filter: brightness(0.9);">
    </div>

    <!-- Panel translúcido -->
    <div class="relative z-10 flex w-full md:w-1/2 h-full bg-white/5 backdrop-blur-md border-r border-white/10">
        <div class="m-auto w-full max-w-sm px-8 md:px-12">

            <!-- Logo -->
            <div class="mb-10 text-left">
                <h1 class="text-5xl font-extrabold text-white drop-shadow-md">
                    <span class="text-white">CASE</span> APP<span class="text-blue-500"> $</span>
                </h1>
                <p class="text-gray-200 mt-2">Inicia sesión para continuar</p>
            </div>

            <!-- Formulario -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Usuario -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Usuario</label>
                    <div class="relative">
                        <input id="email" name="email" type="email" required autofocus
                               placeholder="ejemplo@correo.com"
                               class="w-full rounded-md border border-white/30 bg-white/10 text-white placeholder-gray-300 py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150">
                        <!-- Icono usuario -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                        </svg>
                    </div>
                </div>

                <!-- Contraseña -->
                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-medium text-gray-200 mb-1">Contraseña</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required
                               placeholder="••••••••"
                               class="w-full rounded-md border border-white/30 bg-white/10 text-white placeholder-gray-300 py-2 pl-10 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150">
                        <!-- Icono candado -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V7.5a4.5 4.5 0 00-9 0v3m-.75 0h10.5A1.5 1.5 0 0118.75 12v7.5A1.5 1.5 0 0117.25 21H6.75A1.5 1.5 0 015.25 19.5V12a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                        <!-- Icono mostrar/ocultar -->
                        <button type="button" @click="show = !show" class="absolute right-3 top-2.5 text-gray-300 hover:text-white">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.964 9.964 0 012.598-4.276M6.1 6.1L4 4m16 16l-2.1-2.1M9.88 9.88a3 3 0 104.24 4.24" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Botón -->
                <button type="submit"
                        class="w-full py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all duration-200 shadow-md">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>

</body>
</html>

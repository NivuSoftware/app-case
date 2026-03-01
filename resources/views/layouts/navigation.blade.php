<nav x-data="{ open: false }" class="bg-blue-800 border-b border-blue-700 shadow-md">
    <!-- Desktop -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Logo + Links -->
            <div class="flex items-center space-x-8">

                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <h1 class="text-xl font-extrabold text-white tracking-wide">
                        CASE<span class="text-blue-300">APP$</span>
                    </h1>
                </a>

                <!-- Menu izquierdo en desktop -->
                <div class="hidden sm:flex space-x-6">

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="text-blue-100 text-sm font-medium hover:text-white transition
                              {{ request()->routeIs('dashboard') ? 'underline underline-offset-4 text-white' : '' }}">
                        Dashboard
                    </a>

                    <!-- Usuarios (SOLO ADMIN) -->
                    @role('admin')
                    <a href="{{ route('usuarios.index') }}"
                       class="text-blue-100 text-sm font-medium hover:text-white transition
                              {{ request()->routeIs('usuarios.*') ? 'underline underline-offset-4 text-white' : '' }}">
                        Usuarios
                    </a>
                    @endrole
                    <!-- SRI (SOLO ADMIN) -->
                    @role('admin')
                    <a href="{{ route('sri.config.edit') }}"
                       class="text-blue-100 text-sm font-medium hover:text-white transition
                              {{ request()->routeIs('sri.*') ? 'underline underline-offset-4 text-white' : '' }}">
                        Configuración SRI
                    </a>
                    @endrole

                </div>
            </div>

            <!-- Usuario (Desktop) -->
            <div class="hidden sm:flex items-center space-x-3">

                <!-- Nombre del usuario -->
                <span class="text-sm text-blue-100 font-medium">
                    {{ Auth::user()->name ?? '' }}
                </span>

                <!-- Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center p-1.5 rounded-md bg-blue-700 hover:bg-blue-600 text-white transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM6 20c0-2.21 3.58-4 6-4s6 1.79 6 4v1H6v-1z" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">

                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>

                    </x-slot>
                </x-dropdown>

            </div>

            <!-- Botón Mobile -->
            <div class="sm:hidden flex items-center">
                <button @click="open = ! open"
                        class="p-2 rounded-md text-blue-100 hover:text-white hover:bg-blue-700 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }"
                              class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }"
                              class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden sm:hidden bg-blue-700">
        <div class="pt-3 pb-3 space-y-1">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="block px-4 py-2 text-blue-100 hover:bg-blue-600 hover:text-white
                      {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : '' }}">
                Dashboard
            </a>

            <!-- Usuarios (SOLO ADMIN) -->
            @role('admin')
            <a href="{{ route('usuarios.index') }}"
               class="block px-4 py-2 text-blue-100 hover:bg-blue-600 hover:text-white
                      {{ request()->routeIs('usuarios.*') ? 'bg-blue-600 text-white' : '' }}">
                Usuarios
            </a>
            @endrole

        </div>

        <!-- Mobile user -->
        <div class="border-t border-blue-600 pt-4 pb-3">

            <div class="px-4 mb-3">
                <div class="text-base font-medium text-white">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-blue-200">{{ Auth::user()->email }}</div>
            </div>

            <div class="space-y-1">

                <a href="{{ route('profile.edit') }}"
                   class="block px-4 py-2 text-blue-100 hover:bg-blue-600 hover:text-white">
                    Perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="block px-4 py-2 text-blue-100 hover:bg-blue-600 hover:text-white">
                        Cerrar sesión
                    </a>
                </form>

            </div>

        </div>
    </div>
</nav>

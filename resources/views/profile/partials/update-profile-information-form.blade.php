<section class="bg-white/40 backdrop-blur-md rounded-xl p-6 shadow-sm border border-blue-100">

    <!-- Header -->
    <header class="mb-6">
        <h2 class="text-xl font-semibold text-blue-900">
            Información del Perfil
        </h2>

        <p class="mt-1 text-sm text-blue-700/80">
            Actualiza el nombre y correo de tu cuenta.
        </p>
    </header>

    <!-- Profile Update Form -->
    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <!-- Nombre -->
        <div>
            <label for="name" class="block text-sm font-medium text-blue-900">Nombre</label>

            <input id="name" name="name" type="text"
                   value="{{ old('name', $user->name) }}"
                   required autofocus autocomplete="name"
                   class="mt-1 block w-full rounded-lg bg-blue-50 border border-blue-200 text-blue-900
                          focus:border-blue-500 focus:ring-blue-500 px-3 py-2 shadow-sm" />

            <x-input-error class="mt-2 text-red-500 text-sm" :messages="$errors->get('name')" />
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-blue-900">Correo electrónico</label>

            <input id="email" name="email" type="email"
                   value="{{ old('email', $user->email) }}"
                   required autocomplete="username"
                   class="mt-1 block w-full rounded-lg bg-blue-50 border border-blue-200 text-blue-900
                          focus:border-blue-500 focus:ring-blue-500 px-3 py-2 shadow-sm" />

            <x-input-error class="mt-2 text-red-500 text-sm" :messages="$errors->get('email')" />
        </div>

        <!-- Botón -->
        <div class="flex items-center gap-4">

            <button type="submit"
                class="px-5 py-2 rounded-lg bg-blue-700 text-white font-semibold shadow-md hover:bg-blue-800 transition">
                Guardar cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-green-600">
                    Guardado correctamente.
                </p>
            @endif
        </div>

    </form>
</section>

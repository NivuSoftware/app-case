<section class="bg-white/40 backdrop-blur-md rounded-xl p-6 shadow-sm border border-blue-100">

    <!-- Header -->
    <header class="mb-6">
        <h2 class="text-xl font-semibold text-blue-900">
            Actualizar contraseña
        </h2>

        <p class="mt-1 text-sm text-blue-700/80">
            Asegúrate de usar una contraseña segura y difícil de recordar.
        </p>
    </header>

    <!-- Form -->
    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <!-- Contraseña actual -->
        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-blue-900">
                Contraseña actual
            </label>

            <input id="update_password_current_password"
                   name="current_password"
                   type="password"
                   autocomplete="current-password"
                   class="mt-1 block w-full rounded-lg bg-blue-50 border border-blue-200 text-blue-900
                          focus:border-blue-500 focus:ring-blue-500 px-3 py-2 shadow-sm" />

            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 text-red-500 text-sm" />
        </div>

        <!-- Nueva contraseña -->
        <div>
            <label for="update_password_password" class="block text-sm font-medium text-blue-900">
                Nueva contraseña
            </label>

            <input id="update_password_password"
                   name="password"
                   type="password"
                   autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg bg-blue-50 border border-blue-200 text-blue-900
                          focus:border-blue-500 focus:ring-blue-500 px-3 py-2 shadow-sm" />

            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 text-red-500 text-sm" />
        </div>

        <!-- Confirmación -->
        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-blue-900">
                Confirmar nueva contraseña
            </label>

            <input id="update_password_password_confirmation"
                   name="password_confirmation"
                   type="password"
                   autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg bg-blue-50 border border-blue-200 text-blue-900
                          focus:border-blue-500 focus:ring-blue-500 px-3 py-2 shadow-sm" />

            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2 text-red-500 text-sm" />
        </div>

        <!-- Botón -->
        <div class="flex items-center gap-4">

            <button type="submit"
                class="px-5 py-2 rounded-lg bg-blue-700 text-white font-semibold shadow-md hover:bg-blue-800 transition">
                Guardar cambios
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-green-600">
                    Contraseña actualizada.
                </p>
            @endif
        </div>
    </form>
</section>

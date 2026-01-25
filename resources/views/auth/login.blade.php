@extends("layouts.auth")

@section("title", "Connectez-vous")
@section("description", "Please sign in to your account to continue.")
@section("form-auth")
    <form method="POST" action="{{ route('login') }}" class="w-100 mt-4 pt-2">
        @csrf
        
        <div class="mb-4">
            <x-input-label for="email" :value="__('Adresse email')" />
            <x-text-input id="email" class="block mt-1 w-full" placeholder="Adresse email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div class="mb-3">
            <x-input-label for="password" :value="__('Mot de passe')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            placeholder="Mot de passe"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div class="d-flex justify-content-between">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                <label class="custom-control-label c-pointer" for="remember">Se souvenir de moi</label>
            </div>
            <div>
                {{-- <a href="auth-reset-cover.html" class="fs-11 text-primary">Forget password?</a> --}}
                 @if (Route::has('password.request'))
                    <a class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>
        </div>
        <div class="mt-5">
            <button type="submit" class="btn btn-lg btn-primary w-100">Se connecter</button>
        </div>
    </form>
@endsection
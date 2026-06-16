<x-layouts::auth :title="__('Email verification')">
    <div class="mt-2 flex flex-col gap-6">
        <x-auth-header
            :title="__('Verification de votre email')"
            :description="__('Confirmez votre adresse email en cliquant sur le lien envoye dans votre boite de reception.')"
        />

        @if (session('status') == 'verification-link-sent')
            <flux:text class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-center font-medium !text-green-700 dark:border-green-900/60 dark:bg-green-950/40 !dark:text-green-300">
                {{ __('Un nouveau lien de verification vient d etre envoye a votre adresse email.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Renvoyer le lien de verification') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Se deconnecter') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>

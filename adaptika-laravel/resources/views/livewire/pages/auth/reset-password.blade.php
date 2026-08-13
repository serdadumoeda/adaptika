<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $userName = null;
    public ?string $userRole = null;
    public ?string $userKejuruan = null;
    public ?string $userProgram = null;

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');

        if ($this->email) {
            $user = \App\Models\User::where('email', $this->email)->first();
            if ($user) {
                $this->userName = $user->name;
                $this->userRole = $user->role;
                $this->userKejuruan = $user->assigned_kejuruan;
                $this->userProgram = $user->assigned_program;
            }
        }
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $resetUser = null;

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use (&$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $resetUser = $user;
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        if ($resetUser) {
            Auth::login($resetUser);
            Session::flash('status', 'Password berhasil diatur! Selamat datang di ADAPTIKA.');
            $this->redirectRoute('dashboard', navigate: true);
        } else {
            Session::flash('status', __($status));
            $this->redirectRoute('login', navigate: true);
        }
    }
}; ?>

<div class="p-2">
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
            🔑
        </div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Aktivasi Akun & Password Baru</h2>
        <p class="text-xs text-slate-500 mt-1">Silakan atur password baru Anda untuk mengakses sistem ADAPTIKA</p>
    </div>

    @if($userName)
    <div class="bg-indigo-50/80 border border-indigo-200 rounded-xl p-4 mb-6 text-xs text-indigo-900">
        <p class="font-bold text-sm text-indigo-800">Halo, {{ $userName }}! 👋</p>
        <p class="mt-1 text-slate-600">Anda terdaftar sebagai <strong>{{ $userRole }}</strong>.</p>
        @if($userKejuruan)
            <div class="mt-2 pt-2 border-t border-indigo-200/60 flex items-center justify-between">
                <span>🏫 Penugasan Kejuruan:</span>
                <span class="font-bold text-indigo-700 bg-white px-2 py-0.5 rounded border border-indigo-200">{{ $userKejuruan }} {{ $userProgram ? '— ' . $userProgram : '' }}</span>
            </div>
        @endif
    </div>
    @endif

    <form wire:submit="resetPassword" class="space-y-4">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full bg-slate-50 text-slate-700" type="email" name="email" required readonly autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password Baru')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                          type="password"
                          name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password baru" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-3 bg-indigo-600 hover:bg-indigo-700 font-bold text-sm shadow-lg shadow-indigo-200">
                🚀 {{ __('Aktifkan Akun & Masuk Dashboard') }}
            </x-primary-button>
        </div>
    </form>
</div>

<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public ?string $assigned_kejuruan = null;
    public ?string $assigned_program = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->assigned_kejuruan = Auth::user()->assigned_kejuruan;
        $this->assigned_program = Auth::user()->assigned_program;
    }

    public function updatedAssignedKejuruan()
    {
        // Reset assigned_program if kejuruan changes
        $this->assigned_program = null;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'assigned_kejuruan' => ['nullable', 'string', 'max:255'],
            'assigned_program' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if (auth()->user()->role === 'Instruktur Teknis')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="assigned_kejuruan" :value="__('Kelas / Kejuruan Utama')" />
                    <select wire:model.live="assigned_kejuruan" id="assigned_kejuruan" name="assigned_kejuruan" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Pilih Kejuruan --</option>
                        @foreach(\App\Models\Kejuruan::orderBy('nama')->pluck('nama') as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('assigned_kejuruan')" />
                </div>

                <div>
                    <x-input-label for="assigned_program" :value="__('Program Pelatihan Khusus')" />
                    <select wire:model="assigned_program" id="assigned_program" name="assigned_program" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" {{ empty($assigned_kejuruan) ? 'disabled' : '' }}>
                        <option value="">-- Pilih Program Pelatihan --</option>
                        @if(!empty($assigned_kejuruan))
                            @php
                                $kj = \App\Models\Kejuruan::where('nama', $assigned_kejuruan)->first();
                            @endphp
                            @if($kj)
                                @foreach(\App\Models\Program::where('kejuruan_id', $kj->id)->orderBy('nama')->pluck('nama') as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            @endif
                        @endif
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Pilih kejuruan terlebih dahulu untuk memunculkan daftar program pelatihan.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('assigned_program')" />
                </div>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>

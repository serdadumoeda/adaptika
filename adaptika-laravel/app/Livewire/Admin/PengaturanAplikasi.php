<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Models\Peserta;
use App\Models\Intervensi;
use Livewire\Component;

class PengaturanAplikasi extends Component
{
    // General Settings
    public $apiUrl = '';
    public $apiKey = '';
    public $modeIntake = 'all';

    // Threshold Settings
    public $thresholdK1 = 70;
    public $thresholdK2 = 60;
    public $thresholdK4 = 50;

    // AI Prompt Settings
    public $promptPassport = '';
    public $promptInstruktur = '';
    public $promptPengantar = '';
    public $promptPemberdayaan = '';

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('is-superadmin');
    }

    public function mount()
    {
        // General
        $this->apiUrl = Setting::get('siapkerja_api_url', 'https://api.siapkerja.kemnaker.go.id');
        $this->apiKey = Setting::get('siapkerja_api_key', '');
        $this->modeIntake = Setting::get('mode_intake', 'all');

        // Thresholds
        $this->thresholdK1 = (int) Setting::get('threshold_k1', 70);
        $this->thresholdK2 = (int) Setting::get('threshold_k2', 60);
        $this->thresholdK4 = (int) Setting::get('threshold_k4', 50);

        // AI Prompts
        $this->promptPassport = Setting::get('prompt_passport', '');
        $this->promptInstruktur = Setting::get('prompt_instruktur', '');
        $this->promptPengantar = Setting::get('prompt_pengantar', '');
        $this->promptPemberdayaan = Setting::get('prompt_pemberdayaan', '');
    }

    public function saveSettings()
    {
        $this->validate([
            // General
            'apiUrl' => 'required|url',
            'apiKey' => 'nullable|string',
            'modeIntake' => 'required|in:all,api_only,csv_only',
            
            // Thresholds
            'thresholdK1' => 'required|integer|min:0|max:100',
            'thresholdK2' => 'required|integer|min:0|max:100',
            'thresholdK4' => 'required|integer|min:0|max:100',

            // Prompts
            'promptPassport' => 'required|string',
            'promptInstruktur' => 'required|string',
            'promptPengantar' => 'required|string',
            'promptPemberdayaan' => 'required|string',
        ]);

        // General
        Setting::set('siapkerja_api_url', $this->apiUrl);
        Setting::set('siapkerja_api_key', $this->apiKey ?: null);
        Setting::set('mode_intake', $this->modeIntake);

        // Thresholds
        Setting::set('threshold_k1', (string) $this->thresholdK1);
        Setting::set('threshold_k2', (string) $this->thresholdK2);
        Setting::set('threshold_k4', (string) $this->thresholdK4);

        // Prompts
        Setting::set('prompt_passport', $this->promptPassport);
        Setting::set('prompt_instruktur', $this->promptInstruktur);
        Setting::set('prompt_pengantar', $this->promptPengantar);
        Setting::set('prompt_pemberdayaan', $this->promptPemberdayaan);

        session()->flash('message_settings', 'Seluruh pengaturan aplikasi berhasil diperbarui.');
    }

    public function resetDatabase()
    {
        // Bersihkan data log tindakan dan data peserta
        Intervensi::truncate();
        Peserta::truncate();

        session()->flash('message_settings', 'Seluruh data peserta dan log tindakan intervensi berhasil dibersihkan.');
    }

    public function triggerSync()
    {
        \App\Jobs\SyncSiapKerjaJob::dispatch();

        session()->flash('message_settings', 'Sinkronisasi Massal sedang diproses di latar belakang.');
    }

    public function render()
    {
        return view('livewire.admin.pengaturan-aplikasi');
    }
}

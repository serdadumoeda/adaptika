<?php

namespace App\Livewire\Components;

use Livewire\Component;

class PesertaTimeline extends Component
{
    public $peserta;
    public $intervensis = [];

    public function mount($pesertaId)
    {
        $this->peserta = \App\Models\Peserta::findOrFail($pesertaId);
        $this->intervensis = \App\Models\Intervensi::where('peserta_id', $pesertaId)
                                ->orderBy('created_at', 'asc')
                                ->get();
    }

    public function render()
    {
        return view('livewire.components.peserta-timeline');
    }
}

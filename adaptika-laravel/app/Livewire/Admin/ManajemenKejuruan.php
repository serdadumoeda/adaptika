<?php

namespace App\Livewire\Admin;

use App\Models\Kejuruan;
use App\Models\Program;
use Livewire\Component;

class ManajemenKejuruan extends Component
{
    public $showKejuruanForm = false;
    public $showProgramForm = false;

    // Kejuruan Form
    public $editingKejuruanId = null;
    public $kejuruanNama = '';

    // Program Form
    public $editingProgramId = null;
    public $programNama = '';
    public $programKode = '';
    public $selectedKejuruanIdForProgram = null;

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('is-superadmin');
    }

    public function render()
    {
        $kejuruans = Kejuruan::with('programs')->orderBy('nama')->get();

        return view('livewire.admin.manajemen-kejuruan', [
            'kejuruans' => $kejuruans,
        ]);
    }

    // Kejuruan Actions
    public function openCreateKejuruan()
    {
        $this->resetKejuruanForm();
        $this->showKejuruanForm = true;
    }

    public function openEditKejuruan($id)
    {
        $kj = Kejuruan::findOrFail($id);
        $this->editingKejuruanId = $kj->id;
        $this->kejuruanNama = $kj->nama;
        $this->showKejuruanForm = true;
    }

    public function saveKejuruan()
    {
        $rules = [
            'kejuruanNama' => 'required|string|max:255|unique:kejuruans,nama,' . $this->editingKejuruanId,
        ];
        $this->validate($rules);

        if ($this->editingKejuruanId) {
            $kj = Kejuruan::findOrFail($this->editingKejuruanId);
            $kj->nama = $this->kejuruanNama;
            $kj->save();
            session()->flash('message_kj', "Kejuruan '{$kj->nama}' berhasil diperbarui.");
        } else {
            $kj = Kejuruan::create(['nama' => $this->kejuruanNama]);
            session()->flash('message_kj', "Kejuruan '{$kj->nama}' berhasil dibuat.");
        }

        $this->resetKejuruanForm();
    }

    public function deleteKejuruan($id)
    {
        $kj = Kejuruan::findOrFail($id);
        $nama = $kj->nama;
        $kj->delete();
        session()->flash('message_kj', "Kejuruan '{$nama}' dan seluruh programnya berhasil dihapus.");
    }

    private function resetKejuruanForm()
    {
        $this->showKejuruanForm = false;
        $this->editingKejuruanId = null;
        $this->kejuruanNama = '';
    }

    // Program Actions
    public function openCreateProgram($kejuruanId)
    {
        $this->resetProgramForm();
        $this->selectedKejuruanIdForProgram = $kejuruanId;
        $this->showProgramForm = true;
    }

    public function openEditProgram($id)
    {
        $prog = Program::findOrFail($id);
        $this->editingProgramId = $prog->id;
        $this->programNama = $prog->nama;
        $this->programKode = $prog->kode_program ?? '';
        $this->selectedKejuruanIdForProgram = $prog->kejuruan_id;
        $this->showProgramForm = true;
    }

    public function saveProgram()
    {
        $rules = [
            'programNama' => 'required|string|max:255',
            'programKode' => 'nullable|string|max:255|unique:programs,kode_program,' . $this->editingProgramId,
            'selectedKejuruanIdForProgram' => 'required|exists:kejuruans,id',
        ];
        $this->validate($rules);

        if ($this->editingProgramId) {
            $prog = Program::findOrFail($this->editingProgramId);
            $prog->nama = $this->programNama;
            $prog->kode_program = $this->programKode ?: null;
            $prog->save();
            session()->flash('message_kj', "Program '{$prog->nama}' berhasil diperbarui.");
        } else {
            $prog = Program::create([
                'kejuruan_id' => $this->selectedKejuruanIdForProgram,
                'nama' => $this->programNama,
                'kode_program' => $this->programKode ?: null,
            ]);
            session()->flash('message_kj', "Program '{$prog->nama}' berhasil ditambahkan.");
        }

        $this->resetProgramForm();
    }

    public function deleteProgram($id)
    {
        $prog = Program::findOrFail($id);
        $nama = $prog->nama;
        $prog->delete();
        session()->flash('message_kj', "Program '{$nama}' berhasil dihapus.");
    }

    private function resetProgramForm()
    {
        $this->showProgramForm = false;
        $this->editingProgramId = null;
        $this->programNama = '';
        $this->programKode = '';
        $this->selectedKejuruanIdForProgram = null;
    }
}

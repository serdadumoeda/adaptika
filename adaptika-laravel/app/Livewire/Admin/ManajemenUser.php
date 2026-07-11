<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Peserta;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class ManajemenUser extends Component
{
    use WithPagination;

    public $showForm = false;
    public $editingUserId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'Peserta Pelatihan';
    public $assigned_kejuruan = '';
    public $assigned_program = '';
    public $search = '';
    public $peserta_id = '';

    protected $roles = [
        'Superadmin',
        'Penyelenggara',
        'Instruktur Teknis',
        'Pengantar Kerja',
        'Seksi Pemberdayaan',
        'Kepala Balai',
        'Peserta Pelatihan',
    ];

    public function boot()
    {
        \Illuminate\Support\Facades\Gate::authorize('is-superadmin');
    }

    public function render()
    {
        $query = User::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('role', 'like', '%' . $this->search . '%');
            });
        }
        $users = $query->orderBy('role')->orderBy('name')->paginate(10);

        $allKejuruan = \App\Models\Kejuruan::orderBy('nama')->pluck('nama');
        $allProgram = [];
        if ($this->assigned_kejuruan) {
            $kj = \App\Models\Kejuruan::where('nama', $this->assigned_kejuruan)->first();
            if ($kj) {
                $allProgram = \App\Models\Program::where('kejuruan_id', $kj->id)
                    ->orderBy('nama')
                    ->pluck('nama');
            }
        }

        return view('livewire.admin.manajemen-user', [
            'users' => $users,
            'allRoles' => $this->roles,
            'allKejuruan' => $allKejuruan,
            'allProgram' => $allProgram,
            'allPesertas' => \App\Models\Peserta::orderBy('nama')->get(),
        ]);
    }

    public function updatedAssignedKejuruan()
    {
        $this->assigned_program = '';
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->assigned_kejuruan = $user->assigned_kejuruan ?? '';
        $this->assigned_program = $user->assigned_program ?? '';
        $this->peserta_id = $user->peserta_id ?? '';
        $this->password = '';
        $this->showForm = true;
    }

    public function saveUser()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string',
            'assigned_kejuruan' => 'nullable|string|max:255',
            'assigned_program' => 'nullable|string|max:255',
            'peserta_id' => 'nullable|exists:pesertas,id',
        ];

        if ($this->editingUserId) {
            $rules['email'] .= '|unique:users,email,' . $this->editingUserId;
            $rules['password'] = 'nullable|min:8';
        } else {
            $rules['email'] .= '|unique:users,email';
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        // Jika role bukan Peserta Pelatihan, hapus relasi peserta_id
        $finalPesertaId = ($this->role === 'Peserta Pelatihan' && $this->peserta_id) ? $this->peserta_id : null;

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            $user->assigned_kejuruan = $this->assigned_kejuruan ?: null;
            $user->assigned_program = $this->assigned_program ?: null;
            $user->peserta_id = $finalPesertaId;
            if ($this->password) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
            session()->flash('message_user', "User '{$user->name}' berhasil diperbarui.");
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'assigned_kejuruan' => $this->assigned_kejuruan ?: null,
                'assigned_program' => $this->assigned_program ?: null,
                'peserta_id' => $finalPesertaId,
            ]);
            session()->flash('message_user', "User '{$this->name}' berhasil ditambahkan.");
        }

        $this->resetForm();
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) {
            session()->flash('error_user', 'Anda tidak dapat menghapus akun Anda sendiri.');
            return;
        }
        $name = $user->name;
        $user->delete();
        session()->flash('message_user', "User '{$name}' berhasil dihapus.");
    }

    public function resetPassword($userId)
    {
        $user = User::findOrFail($userId);
        $user->password = Hash::make('password');
        $user->save();
        session()->flash('message_user', "Password '{$user->name}' berhasil direset ke default (password).");
    }

    private function resetForm()
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'Peserta Pelatihan';
        $this->assigned_kejuruan = '';
        $this->assigned_program = '';
        $this->peserta_id = '';
    }
}

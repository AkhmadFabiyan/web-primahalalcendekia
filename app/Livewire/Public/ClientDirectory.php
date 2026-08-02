<?php

namespace App\Livewire\Public;

use App\Modules\Clients\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class ClientDirectory extends Component
{
    use WithPagination;

    public $search = '';
    public $sector = '';

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'q'],
        'sector' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSector()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'sector']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Client::query()
            ->with(['project.certificate'])
            ->whereHas('project.certificate');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('project.certificate', function ($q) {
                      $q->where('certificate_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->sector)) {
            $query->where('business_sector', $this->sector);
        }

        // Sort by certificate updated_at descending
        $clients = $query->join('projects', 'clients.id', '=', 'projects.client_id')
            ->join('certificates', 'projects.id', '=', 'certificates.project_id')
            ->orderBy('certificates.updated_at', 'desc')
            ->select('clients.*')
            ->paginate(10);

        $sectors = Client::query()
            ->whereHas('project.certificate')
            ->whereNotNull('business_sector')
            ->distinct()
            ->pluck('business_sector')
            ->sort()
            ->values();

        return view('livewire.public.client-directory', [
            'clients' => $clients,
            'sectors' => $sectors,
            'hasData' => Client::whereHas('project.certificate')->exists(),
        ]);
    }
}

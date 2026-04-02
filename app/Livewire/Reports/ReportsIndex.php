<?php

namespace App\Livewire\Reports;

use App\Models\GeneratedReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsIndex extends Component
{
    use AuthorizesRequests;

    public string $type = 'provisional_pledges';

    public function mount(?string $type = null): void
    {
        if ($type) {
            $this->type = $type;
        }
    }

    public function download(string $reportId): StreamedResponse
    {
        $report = GeneratedReport::query()->where('id', $reportId)->firstOrFail();

        if ((int) $report->user_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($report->status !== GeneratedReport::STATUS_COMPLETED || ! $report->path) {
            abort(422, 'Report is not ready');
        }

        $disk = $report->disk ?: 'local';
        if (! Storage::disk($disk)->exists($report->path)) {
            abort(404, 'Report file not found');
        }

        return Storage::disk($disk)->download($report->path, $report->filename ?: ('report-'.$report->id.'.csv'));
    }

    public function render()
    {
        $userId = Auth::id();

        $reports = GeneratedReport::query()
            ->where('user_id', $userId)
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('livewire.reports.reports-index', [
            'reports' => $reports,
        ]);
    }
}

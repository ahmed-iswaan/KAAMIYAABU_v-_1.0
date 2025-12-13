<?php

namespace App\Http\Controllers;

use App\Models\VoterRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestReportController extends Controller
{
    public function exportRequests(Request $request)
    {
        if (!auth()->user()->can('requests-voters-render')) {
            abort(403);
        }

        $electionId = $request->query('electionId');
        $status = $request->query('status');
        $requestTypeId = $request->query('requestTypeId');
        $dateFrom = $request->query('dateFrom');
        $dateTo = $request->query('dateTo');
        $search = $request->query('search');

        $query = VoterRequest::query()->with([
            'type:id,name',
            'author:id,name',
            'voter:id,name,id_card_number,phones,address,street_address,current_address,current_street_address,country_id,current_country_id,properties_id,current_properties_id',
            'voter.country:id,name',
            'voter.currentCountry:id,name',
            'voter.property:id,name',
            'voter.currentProperty:id,name',
            'election:id,name'
        ]);

        if ($electionId) {
            $query->where('election_id', $electionId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($requestTypeId) {
            $query->where('request_type_id', $requestTypeId);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('voter', function ($qq) use ($term) {
                    $qq->where('name', 'like', $term)->orWhere('id_card_number', 'like', $term);
                })->orWhere('request_number', 'like', $term);
            });
        }

        $requests = $query->latest()->get();

        $fileName = 'voter_requests_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Request Number', 'Voter Name', 'Voter ID Card', 'Phone Numbers', 'Permanent Address', 'Current Address', 'Election', 'Request Type', 'Status', 'Note', 'Created By', 'Created At'];

        $callback = function () use ($requests, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($requests as $request) {
                $row['Request Number'] = $request->request_number;
                $row['Voter Name'] = $request->voter->name ?? 'N/A';
                $row['Voter ID Card'] = $request->voter->id_card_number ?? 'N/A';
                $row['Phone Numbers'] = isset($request->voter->phones) && is_array($request->voter->phones) ? implode(', ', $request->voter->phones) : 'N/A';
                $row['Permanent Address'] = $request->voter ? $request->voter->permanentLocationString() : 'N/A';
                $row['Current Address'] = $request->voter ? $request->voter->currentLocationString() : 'N/A';
                $row['Election'] = $request->election->name ?? 'N/A';
                $row['Request Type'] = $request->type->name ?? 'N/A';
                $row['Status'] = str_replace('_', ' ', ucfirst($request->status));
                $row['Note'] = $request->note;
                $row['Created By'] = $request->author->name ?? 'N/A';
                $row['Created At'] = $request->created_at ? $request->created_at->format('Y-m-d H:i:s') : 'N/A';

                fputcsv($file, [
                    $row['Request Number'],
                    $row['Voter Name'],
                    $row['Voter ID Card'],
                    $row['Phone Numbers'],
                    $row['Permanent Address'],
                    $row['Current Address'],
                    $row['Election'],
                    $row['Request Type'],
                    $row['Status'],
                    $row['Note'],
                    $row['Created By'],
                    $row['Created At']
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}

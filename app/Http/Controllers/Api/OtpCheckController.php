<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\DirectoryType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpCheckController extends Controller
{
      /**
     * POST /api/individuals/exists
     * Body: { "phone": "+96077xxxxxx" }
     * Returns: { ok, exists, count }
     */
    public function exists(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string','regex:/^\+?\d{7,15}$/'],
        ], ['phone.regex' => 'Enter a valid phone number']);

        $phone = trim($data['phone']);
        $typeId = $this->getIndividualTypeId();

        if (!$typeId) {
            return response()->json([
                'ok' => false,
                'message' => "DirectoryType 'Individual' not found.",
            ], 500);
        }

        $count = Directory::query()
            ->where('directory_type_id', $typeId)
            ->where('phone', $phone) // exact match
            ->count();

        return response()->json([
            'ok'     => true,
            'exists' => $count > 0,
            'count'  => $count,
        ]);
    }

    /**
     * POST /api/individuals/details
     * Body: { "phone": "+96077xxxxxx" }
     * Returns: { ok, exists, count, matches: [{ id, name, registration_number, phone, email, has_email }] }
     */
    public function details(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string','regex:/^\+?\d{7,15}$/'],
        ], ['phone.regex' => 'Enter a valid phone number']);

        $phone = trim($data['phone']);
        $typeId = $this->getIndividualTypeId();

        if (!$typeId) {
            return response()->json([
                'ok' => false,
                'message' => "DirectoryType 'Individual' not found.",
            ], 500);
        }

        $matches = Directory::query()
            ->where('directory_type_id', $typeId)
            ->where('phone', $phone) // exact match
            ->get(['id','name','registration_number','phone','email']);

        $payload = $matches->map(function ($d) {
            return [
                'id'                  => $d->id,
                'name'                => $d->name,
                'registration_number' => $d->registration_number,
                'phone'               => $d->phone,
                'email'               => $d->email,
                'has_email'           => !empty($d->email),
            ];
        });

        return response()->json([
            'ok'      => true,
            'exists'  => $payload->isNotEmpty(),
            'count'   => $payload->count(),
            'matches' => $payload,
        ]);
    }

    private function getIndividualTypeId(): ?string
    {
        return DirectoryType::query()
            ->where('name', 'Individual')
            ->value('id');
    }
}

<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function index(): Response
    {
        $readers = User::with('libraryCard')
            ->whereIn('user_type', ['MEMBER', 'GUEST'])
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'code' => $u->code,
                'card_number' => $u->libraryCard?->card_number,
                'issue_date' => $u->libraryCard?->issue_date?->format('Y-m-d'),
                'expiry_date' => $u->libraryCard?->expiry_date?->format('Y-m-d'),
                'faculty' => Arr::get($u->libraryCard?->metadata ?? [], 'faculty'),
                'class' => Arr::get($u->libraryCard?->metadata ?? [], 'class'),
                'type' => Arr::get($u->libraryCard?->metadata ?? [], 'type') === 'teacher' ? 'teacher' : 'student',
                'status' => $u->is_active ? 'active' : 'blocked',
                'gender' => $u->gender === 'male' ? 'Nam' : ($u->gender === 'female' ? 'Nữ' : 'Khác'),
                'email' => $u->email,
                'phone' => $u->phone,
            ]);

        return Inertia::render('Admin/Cards/Index', ['readers' => $readers]);
    }
}

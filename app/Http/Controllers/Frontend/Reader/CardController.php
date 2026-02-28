<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $card = $request->user()->libraryCard;
        $cardData = null;
        if ($card) {
            $cardData = [
                'card_number' => $card->card_number,
                'status' => $card->status,
                'issue_date' => $card->issue_date?->format('d/m/Y'),
                'expiry_date' => $card->expiry_date?->format('d/m/Y'),
                'faculty' => Arr::get($card->metadata ?? [], 'faculty'),
                'class' => Arr::get($card->metadata ?? [], 'class'),
            ];
        }
        return Inertia::render('Reader/Card/Show', ['card' => $cardData]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QuotaType;
use Illuminate\View\View;

class HowItWorksController extends Controller
{
    public function index(): View
    {
        $quotaTypes = QuotaType::query()
            ->where('active', true)
            ->orderBy('days')
            ->get();

        return view('public.how-it-works', [
            'quotaTypes' => $quotaTypes,
        ]);
    }
}

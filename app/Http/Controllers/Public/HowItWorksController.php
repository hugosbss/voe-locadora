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

        $vca = config('services.vca');

        return view('public.how-it-works', [
            'quotaTypes' => $quotaTypes,
            // 'whatsappUrl' => 'https://wa.me/'.$vca['whatsapp'],
            // 'city' => $vca['city'],
            // 'priceFrom' => $vca['price_from'],
            // 'vehicleYear' => $vca['vehicle_year'],
            // 'preLaunchLabel' => $vca['pre_launch_label'],
            // 'instagram' => $vca['instagram'],
            // 'instagramUrl' => $vca['instagram_url'],
        ]);
    }
}

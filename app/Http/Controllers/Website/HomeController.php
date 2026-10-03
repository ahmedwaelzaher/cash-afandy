<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Slider;
use Illuminate\Contracts\View\View;
use Redot\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Show the website.
     *
     * @return View
     */
    public function __invoke()
    {
        $sliders = Slider::query()
            ->where('active', true)
            ->where('locale', app()->getLocale())
            ->latest('id')
            ->get();

        $clients = Client::query()
            ->where('active', true)
            ->latest('id')
            ->get();

        // Temporary example data — will be fetched from customer accounts later.
        $testimonials = [
            [
                'avatar' => 'https://i.pravatar.cc/150?img=12',
                'name' => 'Omar Al-Farouq',
                'title' => 'Business Analyst',
                'rating' => 4,
                'content' => 'The cashback program on Cash Afandy has been a game changer for me. It\'s the best way to save while shopping online',
            ],
            [
                'avatar' => 'https://i.pravatar.cc/150?img=47',
                'name' => 'Reem Mostafa',
                'title' => 'Freelancer',
                'rating' => 4,
                'content' => 'The site saved me a lot of discounts on online shopping in a really easy way',
            ],
            [
                'avatar' => 'https://i.pravatar.cc/150?img=13',
                'name' => 'Ali Hassan',
                'title' => 'Doctor',
                'rating' => 4,
                'content' => 'Through Cash Afandy I managed to save a lot on many purchases, the site is easy to use and offers exclusive deals',
            ],
            [
                'avatar' => 'https://i.pravatar.cc/150?img=14',
                'name' => 'Nour Al-Hadi',
                'title' => 'Teacher',
                'rating' => 4,
                'content' => 'Cash Afandy gave me exclusive coupons I couldn\'t find anywhere else, along with excellent tools and services',
            ],
            [
                'avatar' => 'https://i.pravatar.cc/150?img=15',
                'name' => 'Ahmed El-Sayed',
                'title' => 'Software Developer',
                'rating' => 5,
                'content' => 'Cash Afandy makes it easy to get access to discounts, I found excellent coupons for the products I need, and I recommend it to everyone',
            ],
        ];

        $coupons = Coupon::available()
            ->with('client')
            ->latest('id')
            ->take(10)
            ->get();

        $cashbackStores = Cashback::available()
            ->with('client')
            ->latest('id')
            ->take(10)
            ->get();

        return view('website.index', [
            'sliders' => $sliders,
            'clients' => $clients,
            'testimonials' => $testimonials,
            'coupons' => $coupons,
            'cashbackStores' => $cashbackStores,
        ]);
    }
}

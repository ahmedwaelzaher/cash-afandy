<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            'privacy-policy' => [
                'en' => 'Privacy Policy',
                'ar' => 'سياسة الخصوصية',
            ],
            'terms-and-conditions' => [
                'en' => 'Terms and Conditions',
                'ar' => 'الشروط والأحكام',
            ],
            'contact-us' => [
                'en' => 'Contact Us',
                'ar' => 'تواصل معنا',
            ],
            'faq' => [
                'en' => 'FAQ',
                'ar' => 'الأسئلة الشائعة',
            ],
        ];

        foreach ($pages as $slug => $title) {
            $content = [];

            foreach (array_keys(config('app.locales')) as $locale) {
                $content[$locale] = fake_locale($locale)->realText(500);
            }

            StaticPage::updateOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'content' => $content]
            );
        }
    }
}

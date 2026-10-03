<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            TransactionType::Expense->value => [
                ['en' => 'Food', 'ar' => 'أكل', 'icon' => 'fas fa-utensils', 'color' => '#f76707'],
                ['en' => 'Transportation', 'ar' => 'مواصلات', 'icon' => 'fas fa-car', 'color' => '#4263eb'],
                ['en' => 'Bills', 'ar' => 'فواتير', 'icon' => 'fas fa-file-invoice', 'color' => '#ae3ec9'],
                ['en' => 'Rent', 'ar' => 'إيجار', 'icon' => 'fas fa-house', 'color' => '#1098ad'],
                ['en' => 'Health', 'ar' => 'صحة', 'icon' => 'fas fa-heart-pulse', 'color' => '#e03131'],
                ['en' => 'Education', 'ar' => 'تعليم', 'icon' => 'fas fa-graduation-cap', 'color' => '#2f9e44'],
                ['en' => 'Entertainment', 'ar' => 'ترفيه', 'icon' => 'fas fa-film', 'color' => '#d6336c'],
                ['en' => 'Shopping', 'ar' => 'تسوق', 'icon' => 'fas fa-bag-shopping', 'color' => '#f59f00'],
                ['en' => 'Other', 'ar' => 'أخرى', 'icon' => 'fas fa-ellipsis', 'color' => '#868e96'],
            ],
            TransactionType::Income->value => [
                ['en' => 'Salary', 'ar' => 'مرتب', 'icon' => 'fas fa-briefcase', 'color' => '#2f9e44'],
                ['en' => 'Freelance', 'ar' => 'فريلانس', 'icon' => 'fas fa-laptop-code', 'color' => '#1098ad'],
                ['en' => 'Bonus', 'ar' => 'مكافأة', 'icon' => 'fas fa-award', 'color' => '#f59f00'],
                ['en' => 'Gift', 'ar' => 'هدية', 'icon' => 'fas fa-gift', 'color' => '#d6336c'],
                ['en' => 'Investment', 'ar' => 'استثمار', 'icon' => 'fas fa-chart-line', 'color' => '#4263eb'],
                ['en' => 'Other', 'ar' => 'أخرى', 'icon' => 'fas fa-ellipsis', 'color' => '#868e96'],
            ],
        ];

        foreach ($categories as $type => $items) {
            foreach ($items as $item) {
                FinanceCategory::firstOrCreate(
                    ['user_id' => null, 'type' => $type, 'title->en' => $item['en']],
                    ['title' => ['en' => $item['en'], 'ar' => $item['ar']], 'icon' => $item['icon'], 'color' => $item['color']],
                );
            }
        }
    }
}

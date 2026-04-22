<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedUsers();
        $this->seedCatalog();
        $this->seedServiceAreas();
        $this->seedSettings();
    }

    private function seedUsers(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@catering.test'],
            ['name' => 'Admin', 'role' => User::ROLE_ADMIN, 'password' => 'password', 'email_verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'staff@catering.test'],
            ['name' => 'Staff', 'role' => User::ROLE_STAFF, 'password' => 'password', 'email_verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'driver@catering.test'],
            ['name' => 'Driver Dave', 'phone' => '+447700900123', 'role' => User::ROLE_DRIVER, 'password' => 'password', 'email_verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'customer@catering.test'],
            ['name' => 'Sam Customer', 'phone' => '+447700900456', 'role' => User::ROLE_CUSTOMER, 'password' => 'password', 'email_verified_at' => now()],
        );
    }

    private function seedCatalog(): void
    {
        $data = [
            ['Sandwich Platters', [
                ['Classic platter', 2400, 'Assorted classic sandwiches (serves 5)'],
                ['Vegan platter', 2400, 'Roasted veg and hummus sandwiches (serves 5)'],
                ['Deluxe platter', 3200, 'Premium fillings — smoked salmon, roast beef, brie (serves 5)'],
            ]],
            ['Hot Buffet', [
                ['Butter chicken + rice', 850, 'Per head'],
                ['Vegetable lasagne', 750, 'Per head'],
                ['BBQ pulled pork sliders', 900, 'Per head'],
            ]],
            ['Cold Buffet', [
                ['Cheese board', 1800, 'British and European cheeses with crackers'],
                ['Charcuterie board', 2200, 'Cured meats and pickles'],
                ['Salad trio', 1500, 'Three seasonal salads'],
            ]],
            ['Desserts', [
                ['Mini cake selection', 1200, 'Twelve assorted mini cakes'],
                ['Fruit platter', 1600, 'Seasonal fruit'],
            ]],
            ['Drinks', [
                ['Still water 330ml', 120, 'Per bottle'],
                ['Sparkling water 330ml', 130, 'Per bottle'],
                ['Fresh orange juice 1L', 380, 'Per carton'],
            ]],
        ];

        foreach ($data as $sortIdx => [$categoryName, $products]) {
            $category = Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $sortIdx, 'is_active' => true],
            );

            foreach ($products as $productData) {
                [$name, $price, $description] = $productData;
                Product::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => $description,
                        'price_pence' => $price,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function seedServiceAreas(): void
    {
        // Newcastle upon Tyne postcode prefixes
        $areas = [
            ['NE1', 500],  ['NE2', 500],  ['NE3', 600],  ['NE4', 600],
            ['NE5', 700],  ['NE6', 600],  ['NE7', 700],  ['NE8', 600],
            ['NE12', 800], ['NE13', 800], ['NE15', 800],
        ];

        foreach ($areas as [$prefix, $fee]) {
            ServiceArea::firstOrCreate(
                ['postcode_prefix' => $prefix],
                ['delivery_fee_pence' => $fee, 'is_active' => true],
            );
        }
    }

    private function seedSettings(): void
    {
        Setting::put('business.name', 'Dialawhip');
        Setting::put('business.phone', '0191 000 0000');
        Setting::put('business.email', 'hello@example.com');
        Setting::put('business.address', 'Newcastle upon Tyne, UK');
        Setting::put('business.hours', [
            'mon_fri' => '08:00–17:00',
            'sat' => '09:00–14:00',
            'sun' => 'Closed',
        ]);
        Setting::put('order.minimum_pence', 2000);
        Setting::put('order.lead_time_hours', 24);
        Setting::put('order.is_open', true);
        Setting::put('vat.rate_bps', 0);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            ['name' => 'Admin', 'role' => User::ROLE_ADMIN, 'password' => 'password', 'email_verified_at' => now(), 'verification_status' => User::VERIFY_VERIFIED, 'verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'staff@catering.test'],
            ['name' => 'Staff', 'role' => User::ROLE_STAFF, 'password' => 'password', 'email_verified_at' => now(), 'verification_status' => User::VERIFY_VERIFIED, 'verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'driver@catering.test'],
            ['name' => 'Driver Dave', 'phone' => '+447700900123', 'role' => User::ROLE_DRIVER, 'password' => 'password', 'email_verified_at' => now(), 'verification_status' => User::VERIFY_VERIFIED, 'verified_at' => now()],
        );

        User::firstOrCreate(
            ['email' => 'customer@catering.test'],
            ['name' => 'Sam Customer', 'phone' => '+447700900456', 'role' => User::ROLE_CUSTOMER, 'password' => 'password', 'email_verified_at' => now(), 'verification_status' => User::VERIFY_VERIFIED, 'verified_at' => now()],
        );
    }

    /**
     * Supplies catalogue — 10 categories, real Newcastle rapid-delivery SKUs.
     * Prices in pence. age_restricted flags for N2O / CO2 products.
     */
    private function seedCatalog(): void
    {
        $catalog = [
            'cream-chargers' => [
                'name' => 'Cream chargers',
                'blurb' => '8g & 8.2g N₂O canisters — food-grade. 18+ with ID.',
                'products' => [
                    ['Supremewhip 24-pack 8g', 'Supremewhip', 2495, true, ['capacity_g' => 8, 'pack_count' => 24, 'purity' => '99.9%']],
                    ['Supremewhip 48-pack 8g', 'Supremewhip', 4595, true, ['capacity_g' => 8, 'pack_count' => 48, 'purity' => '99.9%']],
                    ['Mosa 120-pack 8g', 'Mosa', 9995, true, ['capacity_g' => 8, 'pack_count' => 120, 'purity' => '99.9%']],
                    ['Mosa 240-pack 8g', 'Mosa', 17995, true, ['capacity_g' => 8, 'pack_count' => 240, 'purity' => '99.9%']],
                    ['BestWhip 8.2g — 24-pack', 'BestWhip', 2795, true, ['capacity_g' => 8.2, 'pack_count' => 24, 'purity' => '99.9%']],
                ],
            ],
            'smartwhip-tanks' => [
                'name' => 'Smartwhip tanks',
                'blurb' => '580g, 640g, 666g disposable tanks — equivalent to 70+ chargers.',
                'products' => [
                    ['1 Smartwhip tank 580g', 'Smartwhip', 2995, true, ['capacity_g' => 580, 'equivalent_chargers' => 72, 'purity' => '99.9%']],
                    ['2 Smartwhip tanks 580g', 'Smartwhip', 5900, true, ['capacity_g' => 580, 'tanks' => 2, 'equivalent_chargers' => 144]],
                    ['3 Smartwhip tanks 580g', 'Smartwhip', 8500, true, ['capacity_g' => 580, 'tanks' => 3, 'equivalent_chargers' => 216]],
                    ['4 Smartwhip tanks 580g', 'Smartwhip', 9900, true, ['capacity_g' => 580, 'tanks' => 4, 'equivalent_chargers' => 288, 'badge' => 'Priority delivery included']],
                    ['6 Smartwhip tanks 580g', 'Smartwhip', 12900, true, ['capacity_g' => 580, 'tanks' => 6, 'equivalent_chargers' => 432]],
                ],
            ],
            'maxxi-tanks' => [
                'name' => 'MAXXI tanks',
                'blurb' => '2KG & 4KG disposables — each tank ≈ 250 chargers.',
                'products' => [
                    ['1 MAXXI tank 2KG', 'MAXXI', 9900, true, ['capacity_g' => 2000, 'equivalent_chargers' => 250, 'purity' => '99.9%', 'casing' => '100% steel']],
                    ['2 MAXXI tanks 2KG', 'MAXXI', 18900, true, ['capacity_g' => 2000, 'tanks' => 2, 'equivalent_chargers' => 500]],
                    ['1 MAXXI NERO tank 2KG (Black Edition)', 'MAXXI', 10400, true, ['capacity_g' => 2000, 'edition' => 'NERO', 'equivalent_chargers' => 250]],
                ],
            ],
            'whippers' => [
                'name' => 'Whippers',
                'blurb' => 'Quarter and half-litre cream dispensers, pro-grade.',
                'products' => [
                    ['Liss quarter-litre whipper', 'Liss', 3495, false, ['capacity_l' => 0.25, 'material' => 'stainless steel']],
                    ['Liss half-litre whipper', 'Liss', 4495, false, ['capacity_l' => 0.5, 'material' => 'stainless steel']],
                    ['ISI Gourmet half-litre whipper', 'ISI', 7995, false, ['capacity_l' => 0.5, 'material' => 'stainless steel', 'badge' => 'Bar standard']],
                ],
            ],
            'co2-cartridges' => [
                'name' => 'CO₂ cartridges',
                'blurb' => 'For soda siphons — Pro Fizz, Liss, Mosa, ISI.',
                'products' => [
                    ['Pro Fizz CO₂ — 10-pack', 'Pro Fizz', 1195, false, ['gas' => 'CO₂', 'pack_count' => 10]],
                    ['Liss CO₂ — 10-pack', 'Liss', 1095, false, ['gas' => 'CO₂', 'pack_count' => 10]],
                    ['Mosa CO₂ — 10-pack', 'Mosa', 1295, false, ['gas' => 'CO₂', 'pack_count' => 10]],
                    ['ISI CO₂ — 10-pack', 'ISI', 1495, false, ['gas' => 'CO₂', 'pack_count' => 10]],
                ],
            ],
            'soda-siphons' => [
                'name' => 'Soda siphons',
                'blurb' => 'ISI 1L — bar-standard sparkling water maker.',
                'products' => [
                    ['ISI 1L classic soda siphon', 'ISI', 8995, false, ['capacity_l' => 1.0, 'material' => 'aluminium']],
                ],
            ],
            'monin-syrups' => [
                'name' => 'Monin syrups',
                'blurb' => 'Premium French syrups — cocktails, coffee, desserts.',
                'products' => [
                    ['Monin strawberry 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Strawberry']],
                    ['Monin gomme 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Gomme']],
                    ['Monin blue curaçao 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Blue curaçao']],
                    ['Monin vanilla 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Vanilla']],
                    ['Monin caramel 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Caramel']],
                    ['Monin hazelnut 70cl', 'Monin', 895, false, ['volume_ml' => 700, 'flavour' => 'Hazelnut']],
                ],
            ],
            'coffee' => [
                'name' => 'Coffee',
                'blurb' => 'Lavazza, Starbucks, Costa, Nescafé — beans & instant.',
                'products' => [
                    ['Lavazza Qualità Rossa beans 1kg', 'Lavazza', 1895, false, ['form' => 'beans', 'weight_g' => 1000]],
                    ['Starbucks House Blend ground 200g', 'Starbucks', 595, false, ['form' => 'ground', 'weight_g' => 200]],
                    ['Costa Signature Blend beans 1kg', 'Costa', 1995, false, ['form' => 'beans', 'weight_g' => 1000]],
                    ['Nescafé Gold instant 300g', 'Nescafé', 895, false, ['form' => 'instant', 'weight_g' => 300]],
                    ['Kenco Smooth instant 300g', 'Kenco', 795, false, ['form' => 'instant', 'weight_g' => 300]],
                    ['Douwe Egberts Pure Gold 200g', 'Douwe Egberts', 695, false, ['form' => 'instant', 'weight_g' => 200]],
                ],
            ],
            'baking' => [
                'name' => 'Baking',
                'blurb' => 'Flour, sugar, eggs, baking powder — trade quantities.',
                'products' => [
                    ['Plain flour 16kg', 'Catering', 1795, false, ['weight_kg' => 16]],
                    ['Caster sugar 10kg', 'Catering', 1495, false, ['weight_kg' => 10]],
                    ['Free-range eggs — tray of 30', 'Local farm', 1095, false, ['count' => 30]],
                    ['Baking powder 1kg', 'Catering', 495, false, ['weight_g' => 1000]],
                    ['Dr. Oetker rainbow sprinkles 600g', 'Dr. Oetker', 595, false, ['weight_g' => 600]],
                    ['Dr. Oetker chocolate vermicelli 500g', 'Dr. Oetker', 495, false, ['weight_g' => 500]],
                ],
            ],
            'disposables' => [
                'name' => 'Disposables',
                'blurb' => 'Plates, cups, bags, cutlery — eco where possible.',
                'products' => [
                    ['Bagasse 9in plates — pack of 125', 'Catering', 1595, false, ['material' => 'bagasse', 'diameter_in' => 9, 'pack_count' => 125]],
                    ['Palm-leaf 10in plates — pack of 100', 'Catering', 3495, false, ['material' => 'palm leaf', 'diameter_in' => 10, 'pack_count' => 100]],
                    ['Wooden forks — pack of 1000', 'Catering', 1195, false, ['material' => 'birch', 'pack_count' => 1000]],
                    ['Wooden cutlery combo — pack of 500', 'Catering', 995, false, ['material' => 'birch', 'pack_count' => 500]],
                    ['Pizza boxes 12in — pack of 100', 'Catering', 1895, false, ['size_in' => 12, 'pack_count' => 100]],
                    ['Burger boxes — pack of 200', 'Catering', 1395, false, ['pack_count' => 200]],
                    ['SOS paper bags medium — pack of 500', 'Catering', 895, false, ['size' => 'medium', 'pack_count' => 500]],
                    ['12oz coffee cups + lids — pack of 100', 'Catering', 1395, false, ['volume_oz' => 12, 'pack_count' => 100]],
                ],
            ],
        ];

        $sortIdx = 0;
        foreach ($catalog as $slug => $cat) {
            $category = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $cat['name'], 'sort_order' => $sortIdx, 'is_active' => true],
            );
            $sortIdx++;

            foreach ($cat['products'] as $p) {
                [$name, $brand, $price, $ageRestricted, $spec] = $p;
                Product::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'brand' => $brand,
                        'description' => $cat['blurb'],
                        'price_pence' => $price,
                        'short_spec' => $spec,
                        'is_active' => true,
                        'is_age_restricted' => $ageRestricted,
                        'stock_count' => 50,
                    ],
                );
            }
        }
    }

    private function seedServiceAreas(): void
    {
        // Tyneside postcode coverage — ETA and fee per zone.
        // [prefix, standard_fee, standard_eta_min, priority_eta_min, priority_fee, super_fee]
        $areas = [
            ['NE1', 300, 18, 10, 500, 1500],  // city core — fastest
            ['NE2', 300, 18, 10, 500, 1500],
            ['NE3', 400, 22, 12, 500, 1500],
            ['NE4', 300, 18, 10, 500, 1500],
            ['NE5', 500, 26, 14, 500, 1500],
            ['NE6', 400, 22, 12, 500, 1500],
            ['NE7', 500, 26, 14, 500, 1500],
            ['NE8', 400, 22, 12, 500, 1500],  // Gateshead
            ['NE9', 500, 26, 14, 500, 1500],
            ['NE10', 500, 28, 15, 500, 1500],
            ['NE11', 500, 28, 15, 500, 1500],
            ['NE12', 600, 30, 16, 500, 1500],
            ['NE13', 600, 30, 16, 500, 1500],
            ['NE15', 600, 30, 16, 500, 1500],
            ['NE25', 700, 35, 20, 500, 1500],  // Whitley Bay
            ['NE28', 700, 35, 20, 500, 1500],  // Wallsend
            ['NE29', 700, 35, 20, 500, 1500],  // North Shields
            ['NE30', 700, 35, 20, 500, 1500],  // Tynemouth
        ];

        foreach ($areas as [$prefix, $fee, $etaStd, $etaPri, $feePri, $feeSup]) {
            ServiceArea::updateOrCreate(
                ['postcode_prefix' => $prefix],
                [
                    'delivery_fee_pence' => $fee,
                    'eta_standard_minutes' => $etaStd,
                    'eta_priority_minutes' => $etaPri,
                    'priority_fee_pence' => $feePri,
                    'super_fee_pence' => $feeSup,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedSettings(): void
    {
        Setting::put('business.name', 'Dialawhip');
        Setting::put('business.tagline', 'Newcastle · 20-minute catering supplies');
        Setting::put('business.phone', '0191 000 0000');
        Setting::put('business.email', 'hello@dialawhip.test');
        Setting::put('business.address', 'Newcastle upon Tyne, UK');
        Setting::put('business.hours', [
            'tue_sun' => '10:00–03:00',
            'mon' => 'Closed',
        ]);
        Setting::put('order.minimum_pence', 1500);
        Setting::put('order.lead_time_hours', 0); // immediate
        Setting::put('order.is_open', true);
        Setting::put('vat.rate_bps', 2000); // 20% UK VAT
        Setting::put('compliance.age_minimum', 18);
        Setting::put('compliance.id_required_categories', ['cream-chargers', 'smartwhip-tanks', 'maxxi-tanks']);
    }
}

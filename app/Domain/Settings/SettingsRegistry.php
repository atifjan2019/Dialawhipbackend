<?php

namespace App\Domain\Settings;

/**
 * Central registry of all website settings that can be managed from the admin panel.
 *
 * Each setting declares:
 *   - group       : logical grouping for the admin UI (branding, business, social, etc.)
 *   - type        : string | text | int | bool | url | email | image | json | array<string>
 *   - public      : whether the key is exposed via /v1/settings/public
 *   - label       : human-readable label
 *   - default     : default value applied on first seed
 *   - validation  : Laravel validation rules (array)
 */
class SettingsRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            // Branding
            'branding.logo_url' => [
                'group' => 'branding', 'type' => 'image', 'public' => true,
                'label' => 'Primary logo', 'default' => null,
                'validation' => ['nullable', 'string', 'max:1024'],
            ],
            'branding.logo_dark_url' => [
                'group' => 'branding', 'type' => 'image', 'public' => true,
                'label' => 'Dark-mode logo', 'default' => null,
                'validation' => ['nullable', 'string', 'max:1024'],
            ],
            'branding.favicon_url' => [
                'group' => 'branding', 'type' => 'image', 'public' => true,
                'label' => 'Favicon', 'default' => null,
                'validation' => ['nullable', 'string', 'max:1024'],
            ],
            'branding.og_image_url' => [
                'group' => 'branding', 'type' => 'image', 'public' => true,
                'label' => 'Social share image', 'default' => null,
                'validation' => ['nullable', 'string', 'max:1024'],
            ],
            'branding.primary_color' => [
                'group' => 'branding', 'type' => 'string', 'public' => true,
                'label' => 'Primary brand colour', 'default' => '#111827',
                'validation' => ['nullable', 'string', 'max:16'],
            ],
            'branding.accent_color' => [
                'group' => 'branding', 'type' => 'string', 'public' => true,
                'label' => 'Accent colour', 'default' => '#22c55e',
                'validation' => ['nullable', 'string', 'max:16'],
            ],

            // Business info
            'business.name' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'Business name', 'default' => 'Dialawhip',
                'validation' => ['nullable', 'string', 'max:120'],
            ],
            'business.tagline' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'Tagline', 'default' => 'Newcastle · 20-minute catering supplies',
                'validation' => ['nullable', 'string', 'max:200'],
            ],
            'business.phone' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'Contact phone', 'default' => '0191 000 0000',
                'validation' => ['nullable', 'string', 'max:40'],
            ],
            'business.whatsapp' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'WhatsApp number', 'default' => null,
                'validation' => ['nullable', 'string', 'max:40'],
            ],
            'business.email' => [
                'group' => 'business', 'type' => 'email', 'public' => true,
                'label' => 'Contact email', 'default' => 'hello@dialawhip.test',
                'validation' => ['nullable', 'email', 'max:120'],
            ],
            'business.support_email' => [
                'group' => 'business', 'type' => 'email', 'public' => true,
                'label' => 'Support email', 'default' => null,
                'validation' => ['nullable', 'email', 'max:120'],
            ],
            'business.address' => [
                'group' => 'business', 'type' => 'text', 'public' => true,
                'label' => 'Street address', 'default' => 'Newcastle upon Tyne, UK',
                'validation' => ['nullable', 'string', 'max:500'],
            ],
            'business.city' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'City', 'default' => 'Newcastle upon Tyne',
                'validation' => ['nullable', 'string', 'max:80'],
            ],
            'business.postcode' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'Postcode', 'default' => null,
                'validation' => ['nullable', 'string', 'max:20'],
            ],
            'business.country' => [
                'group' => 'business', 'type' => 'string', 'public' => true,
                'label' => 'Country', 'default' => 'United Kingdom',
                'validation' => ['nullable', 'string', 'max:80'],
            ],
            'business.hours' => [
                'group' => 'business', 'type' => 'json', 'public' => true,
                'label' => 'Opening hours', 'default' => ['tue_sun' => '10:00-03:00', 'mon' => 'Closed'],
                'validation' => ['nullable', 'array'],
            ],

            // Social
            'social.facebook' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'Facebook', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],
            'social.instagram' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'Instagram', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],
            'social.twitter' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'X / Twitter', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],
            'social.tiktok' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'TikTok', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],
            'social.youtube' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'YouTube', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],
            'social.linkedin' => [
                'group' => 'social', 'type' => 'url', 'public' => true,
                'label' => 'LinkedIn', 'default' => null,
                'validation' => ['nullable', 'url', 'max:255'],
            ],

            // SEO
            'seo.meta_title' => [
                'group' => 'seo', 'type' => 'string', 'public' => true,
                'label' => 'Default meta title', 'default' => 'Dialawhip — 20-minute catering supplies',
                'validation' => ['nullable', 'string', 'max:180'],
            ],
            'seo.meta_description' => [
                'group' => 'seo', 'type' => 'text', 'public' => true,
                'label' => 'Default meta description', 'default' => 'Rapid delivery of catering supplies across Newcastle.',
                'validation' => ['nullable', 'string', 'max:400'],
            ],
            'seo.meta_keywords' => [
                'group' => 'seo', 'type' => 'string', 'public' => true,
                'label' => 'Meta keywords', 'default' => null,
                'validation' => ['nullable', 'string', 'max:400'],
            ],
            'seo.google_analytics_id' => [
                'group' => 'seo', 'type' => 'string', 'public' => true,
                'label' => 'Google Analytics ID', 'default' => null,
                'validation' => ['nullable', 'string', 'max:40'],
            ],
            'seo.gtm_id' => [
                'group' => 'seo', 'type' => 'string', 'public' => true,
                'label' => 'Google Tag Manager ID', 'default' => null,
                'validation' => ['nullable', 'string', 'max:40'],
            ],
            'seo.facebook_pixel_id' => [
                'group' => 'seo', 'type' => 'string', 'public' => true,
                'label' => 'Facebook Pixel ID', 'default' => null,
                'validation' => ['nullable', 'string', 'max:40'],
            ],

            // Order / delivery defaults
            'order.is_open' => [
                'group' => 'order', 'type' => 'bool', 'public' => true,
                'label' => 'Shop accepting orders', 'default' => true,
                'validation' => ['nullable', 'boolean'],
            ],
            'order.minimum_pence' => [
                'group' => 'order', 'type' => 'int', 'public' => true,
                'label' => 'Minimum order (pence)', 'default' => 1500,
                'validation' => ['nullable', 'integer', 'min:0', 'max:100000'],
            ],
            'order.lead_time_hours' => [
                'group' => 'order', 'type' => 'int', 'public' => true,
                'label' => 'Lead time (hours)', 'default' => 0,
                'validation' => ['nullable', 'integer', 'min:0', 'max:168'],
            ],
            'order.free_delivery_threshold_pence' => [
                'group' => 'order', 'type' => 'int', 'public' => true,
                'label' => 'Free delivery above (pence)', 'default' => 0,
                'validation' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            ],
            'delivery.default_fee_pence' => [
                'group' => 'delivery', 'type' => 'int', 'public' => true,
                'label' => 'Default delivery fee (pence)', 'default' => 500,
                'validation' => ['nullable', 'integer', 'min:0', 'max:100000'],
            ],
            'delivery.default_priority_fee_pence' => [
                'group' => 'delivery', 'type' => 'int', 'public' => true,
                'label' => 'Default priority surcharge (pence)', 'default' => 500,
                'validation' => ['nullable', 'integer', 'min:0', 'max:100000'],
            ],
            'delivery.default_super_fee_pence' => [
                'group' => 'delivery', 'type' => 'int', 'public' => true,
                'label' => 'Default super surcharge (pence)', 'default' => 1500,
                'validation' => ['nullable', 'integer', 'min:0', 'max:100000'],
            ],
            'delivery.default_eta_standard_minutes' => [
                'group' => 'delivery', 'type' => 'int', 'public' => true,
                'label' => 'Default standard ETA (minutes)', 'default' => 25,
                'validation' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ],
            'delivery.default_eta_priority_minutes' => [
                'group' => 'delivery', 'type' => 'int', 'public' => true,
                'label' => 'Default priority ETA (minutes)', 'default' => 15,
                'validation' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ],

            // VAT
            'vat.rate_bps' => [
                'group' => 'tax', 'type' => 'int', 'public' => true,
                'label' => 'VAT rate (basis points)', 'default' => 2000,
                'validation' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ],

            // Compliance
            'compliance.age_minimum' => [
                'group' => 'compliance', 'type' => 'int', 'public' => true,
                'label' => 'Minimum age', 'default' => 18,
                'validation' => ['nullable', 'integer', 'min:0', 'max:120'],
            ],
            'compliance.id_required_categories' => [
                'group' => 'compliance', 'type' => 'json', 'public' => false,
                'label' => 'Categories requiring ID', 'default' => ['cream-chargers', 'smartwhip-tanks', 'maxxi-tanks'],
                'validation' => ['nullable', 'array'],
            ],

            // Legal
            'legal.terms_url' => [
                'group' => 'legal', 'type' => 'url', 'public' => true,
                'label' => 'Terms & Conditions URL', 'default' => null,
                'validation' => ['nullable', 'url', 'max:500'],
            ],
            'legal.privacy_url' => [
                'group' => 'legal', 'type' => 'url', 'public' => true,
                'label' => 'Privacy Policy URL', 'default' => null,
                'validation' => ['nullable', 'url', 'max:500'],
            ],
            'legal.cookies_url' => [
                'group' => 'legal', 'type' => 'url', 'public' => true,
                'label' => 'Cookies Policy URL', 'default' => null,
                'validation' => ['nullable', 'url', 'max:500'],
            ],
            'legal.refund_url' => [
                'group' => 'legal', 'type' => 'url', 'public' => true,
                'label' => 'Refund Policy URL', 'default' => null,
                'validation' => ['nullable', 'url', 'max:500'],
            ],

            // Maintenance
            'maintenance.enabled' => [
                'group' => 'maintenance', 'type' => 'bool', 'public' => true,
                'label' => 'Maintenance mode', 'default' => false,
                'validation' => ['nullable', 'boolean'],
            ],
            'maintenance.message' => [
                'group' => 'maintenance', 'type' => 'text', 'public' => true,
                'label' => 'Maintenance message', 'default' => 'We are back shortly.',
                'validation' => ['nullable', 'string', 'max:500'],
            ],

            // Notifications
            'notifications.admin_email' => [
                'group' => 'notifications', 'type' => 'email', 'public' => false,
                'label' => 'Admin notification email', 'default' => null,
                'validation' => ['nullable', 'email', 'max:120'],
            ],
            'notifications.order_alert_sms' => [
                'group' => 'notifications', 'type' => 'string', 'public' => false,
                'label' => 'Order alert SMS number', 'default' => null,
                'validation' => ['nullable', 'string', 'max:40'],
            ],
        ];
    }

    /**
     * @return array<string>
     */
    public static function keys(): array
    {
        return array_keys(self::schema());
    }

    /**
     * @return array<string>
     */
    public static function publicKeys(): array
    {
        return array_keys(array_filter(self::schema(), fn ($s) => ($s['public'] ?? false) === true));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function validationRules(): array
    {
        $rules = [];
        foreach (self::schema() as $key => $meta) {
            $rules[$key] = $meta['validation'] ?? ['nullable'];
        }
        return $rules;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::schema());
    }

    public static function meta(string $key): ?array
    {
        return self::schema()[$key] ?? null;
    }
}

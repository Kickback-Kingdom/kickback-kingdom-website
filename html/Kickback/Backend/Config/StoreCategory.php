<?php
declare(strict_types=1);

namespace Kickback\Backend\Config;

class StoreCategory
{
    public static function getAll(): array
    {
        return [
            'default' => [
                'label' => 'All',
                'slug' => 'all',
                'themeClass' => 'default',
                'bgImage' => '/assets/images/store/store-back-1.png',
                'icon' => 'fa-globe',
                'color' => '#33ffee',
                'description' => 'Show all available items',
            ],
            'general' => [
                'label' => 'General',
                'slug' => 'general',
                'themeClass' => 'general',
                'bgImage' => '/assets/images/store/store-back-9.png',
                'icon' => 'fa-box-open',
                'color' => '#55ccff',
                'description' => 'Basic and essential goods for all travelers',
            ],
            'lich' => [
                'label' => 'L.I.C.H.',
                'slug' => 'lich',
                'themeClass' => 'lich',
                'bgImage' => '/assets/images/store/store-back-3.png',
                'icon' => 'fa-skull-crossbones',
                'color' => '#aa88ff',
                'description' => 'Arcane, forbidden, and necrotic items',
            ],
            'special' => [
                'label' => 'Special',
                'slug' => 'special',
                'themeClass' => 'special',
                'bgImage' => '/assets/images/store/store-back-6.png',
                'icon' => 'fa-star',
                'color' => '#ffcc33',
                'description' => 'Rare and unique finds for discerning adventurers',
            ],
            'equipment' => [
                'label' => 'Equipment',
                'slug' => 'equipment',
                'themeClass' => 'equipment',
                'bgImage' => '/assets/images/store/store-back-5.png',
                'icon' => 'fa-shirt', 
                'color' => '#ffaa00',
                'description' => 'Gear, tools, and wearable items for adventurers',
            ],

        ];
    }

    public static function getRandomCategorySlugList(int $count = 1): array
    {
        $slugs = array_map(fn($cat) => $cat['slug'], self::getAll());
        shuffle($slugs);
        return array_slice($slugs, 0, max(1, min($count, count($slugs))));
    }

    
    public static function getCategory(string $slug): ?array
    {
        foreach (self::getAll() as $cat) {
            if ($cat['slug'] === $slug) {
                return $cat;
            }
        }
        return null;
    }
    

    public static function getThemeCss(): string
    {
        $css = "";
        foreach (self::getAll() as $category) {
            $theme = $category['themeClass'];
            $bg = $category['bgImage'];
            $css .= ".emberwood-store.theme-{$theme} { background-image: url('{$bg}'); }\n";
        }
        return $css;
    }

    public static function renderCategoryPills(string $active = 'all'): string
    {
        $html = '';
        foreach (self::getAll() as $slug => $cat) {
            $activeClass = $cat['slug'] === $active ? 'active' : '';

            $colorStyle = $cat['color'] ? "style=\"border-color: {$cat['color']}\"" : '';
            $iconHtml = $cat['icon'] ? "<i class=\"fas {$cat['icon']}\" style=\"margin-right: 6px;\"></i>" : '';
            $tooltip = $cat['description'] ?? '';

            $html .= "<button class='pill-btn {$activeClass}' data-category='{$cat['slug']}' data-theme='{$cat['themeClass']}' title='" . htmlspecialchars($tooltip) . "' {$colorStyle}>"
                   . $iconHtml . htmlspecialchars($cat['label']) . "</button>\n";
        }
        return $html;
    }
}

<?php
declare(strict_types=1);

namespace Kickback\Backend\Config;

class StoreTag
{
    public static function getAll(): array
    {
        return [
            'popular' => [
                'label' => 'Popular',
                'color' => '#ff9933',
                'description' => 'Fan-favorite items with high demand.',
            ],
            'seasonal' => [
                'label' => 'Seasonal',
                'color' => '#ff3366',
                'description' => 'Limited-time goods for this season.',
            ],
            'new' => [
                'label' => 'New',
                'color' => '#1833ff',
                'description' => 'Recently added products.',
            ],
            'limited' => [
                'label' => 'Limited',
                'color' => '#cc33ff',
                'description' => 'Extremely rare or time-sensitive items.',
            ],
        ];
    }
    public static function getRandomCategorySlug(): string
    {
        $slugs = array_map(fn($cat) => $cat['slug'], self::getAll());
        return $slugs[array_rand($slugs)];
    }
    
    public static function getRandomTagSlug(): string
    {
        $keys = array_keys(self::getAll());
        return $keys[array_rand($keys)];
    }


    public static function getTagCss(): string
    {
        $css = '';
        foreach (self::getAll() as $slug => $tag) {
            $color = $tag['color'];
            $css .= ".ribbon-{$slug} { background: {$color}; }\n";
        }
        return $css;
    }
    
    public static function getTag(string $slug): ?array
    {
        return self::getAll()[$slug] ?? null;
    }

    public static function renderRibbon(string $slug): string
    {
        $tag = self::getTag(strtolower($slug));
        if (!$tag) return '';

        $label = htmlspecialchars($tag['label']);
        $color = htmlspecialchars($tag['color']);

        return "<div class='item-ribbon' style='background: {$color};'>{$label}</div>";
    }
}

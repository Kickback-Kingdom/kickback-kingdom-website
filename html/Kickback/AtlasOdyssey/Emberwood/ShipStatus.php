<?php

namespace Kickback\AtlasOdyssey\Emberwood;

class ShipStatus
{
    public string $text;
    public string $icon;
    public string $bootstrapColorClass;

    public function __construct(string $text, string $icon, string $bootstrapColorClass)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->bootstrapColorClass = $bootstrapColorClass;
    }

    // Determine text color based on the bootstrapColorClass background color
    public function textColor(): string
    {
        // Define Bootstrap background classes that work well with white text
        $darkBackgrounds = ['bg-primary', 'bg-danger', 'bg-dark', 'bg-secondary', 'bg-info', 'bg-warning'];

        // Return white for dark backgrounds, otherwise return dark gray for readability
        return in_array($this->bootstrapColorClass, $darkBackgrounds) ? '#ffffff' : '#333333';
    }
}

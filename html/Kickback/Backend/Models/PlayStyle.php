<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;


enum PlayStyle: int {
    case Casual = 0;
    case Ranked = 1;
    case Hardcore = 2;
    case Roleplay = 3;


    public static function getPlayStyleJSON(): ?string
    {
        $playStyles = [];
        foreach (self::cases() as $case) {
            $playStyles[] = [
                'name' => $case->getName(),
                'description' => $case->getDescription()
            ];
        }

        $json = json_encode($playStyles);
        return ($json !== false) ? $json : null;
    }
    
    public function getName(): string
    {
        return match($this) {
            self::Casual => 'Casual',
            self::Ranked => 'Ranked',
            self::Hardcore => 'Hardcore',
            self::Roleplay => 'Roleplay',
        };
    }
    
    public function getDescription(): string
    {
        return match($this) {
            self::Casual => 'This refers to a play style or game mode where the primary focus is on fun, relaxation, and social interaction. The rules are usually easier to grasp, the competition level is lower, and there is less emphasis on long-term strategy or high levels of skill. This can also include social activities such as raffles or conversations.',
            self::Ranked => 'Ranked gameplay involves a high level of competition and results will be recorded in the ranking system. Players are typically more dedicated and spend more time honing their skills to compete against other players. Games may involve teams battling against each other or individual players competing for the top spot.',
            self::Hardcore => 'In this mode, players confront intense challenges underscored by the stern reality of permadeath. A character\'s demise can be definitive either individually or when an entire team falls. Mastery in skill, precision, and unyielding focus is essential. A deep understanding of game mechanics and unwavering concentration are crucial to navigating this demanding environment and claiming victory.',
            self::Roleplay => 'In roleplay modes, players assume the roles of characters and create narratives collaboratively. Gameplay may be guided by rules or freeform, but the emphasis is typically on story development, character interaction, and exploration. Players are expected to stay "in character" at all times and participate in the collective storytelling.',
        };
    }


}

?>

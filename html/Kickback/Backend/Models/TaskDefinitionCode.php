<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

enum TaskDefinitionCode: string
{
    case VIEW_BLOG_POST = 'view_blog_post';
    case VIEW_RAFFLE = 'view_raffle';
    case GO_TO_TOWN_SQUARE = 'go_to_town_square';
    case VIEW_PROFILE = 'view_profile';
    case VIEW_QUEST = 'view_quest';
    case PLAY_RANKED_MATCH = 'play_ranked_match';
    case VISIT_STORE = 'visit_store';

    case PARTICIPATE_QUEST = 'participate_quest';
    case VIEW_LICH_CARD_WIKI = 'view_lich_card_wiki';
    case PARTICIPATE_RAFFLE = 'participate_raffle';
    case PLAY_RANKED_LICH = 'play_ranked_lich';
    case WIN_RANKED_LICH = 'win_ranked_lich';
    case SPEND_PRESTIGE_TOKEN = 'spend_prestige_token';
    case CHANGE_PROFILE_PICTURE = 'change_profile_picture';
    case HAVE_WROP_USED = 'have_wrop_used';
    case WIN_TOURNAMENT = 'win_tournament';
    case VISIT_ANALYTICS_PAGE = 'visit_analytics_page';
    case WIN_RANKED_MATCH = 'win_ranked_match';


    /**
     * Safely cast from string
     */
    public static function fromString(string $code): self
    {
        return self::from($code);
    }

    public function getFaIcon(): string
    {
        return match ($this) {
            self::VIEW_BLOG_POST => 'fa-book-open',
            self::VIEW_RAFFLE => 'fa-ticket',
            self::GO_TO_TOWN_SQUARE => 'fa-city',
            self::VIEW_PROFILE => 'fa-user',
            self::VIEW_QUEST => 'fa-scroll',
            self::PLAY_RANKED_MATCH => 'fa-chess',
            self::VISIT_STORE => 'fa-store',
            self::PARTICIPATE_QUEST => 'fa-dungeon',
            self::VIEW_LICH_CARD_WIKI => 'fa-dragon',
            self::PARTICIPATE_RAFFLE => 'fa-ticket-simple',
            self::PLAY_RANKED_LICH => 'fa-hat-wizard',
            self::SPEND_PRESTIGE_TOKEN => 'fa-coins',
            self::CHANGE_PROFILE_PICTURE => 'fa-image',
            self::HAVE_WROP_USED => 'fa-passport',
            self::WIN_TOURNAMENT => 'fa-trophy',
            self::VISIT_ANALYTICS_PAGE => 'fa-chart-line',
            self::WIN_RANKED_MATCH => 'fa-trophy',
            

            default => 'fa-circle-question', // fallback icon
        };
    }

    public function getPageIdPattern(): ?string
    {
        return match($this) {
            TaskDefinitionCode::VIEW_BLOG_POST       => '/blog-post/%',
            TaskDefinitionCode::VIEW_RAFFLE          => '/raffles/%',
            TaskDefinitionCode::GO_TO_TOWN_SQUARE    => '/town-square%',
            TaskDefinitionCode::VIEW_PROFILE         => '/u/%',
            TaskDefinitionCode::VIEW_QUEST           => '/q/%',
            TaskDefinitionCode::VISIT_STORE          => '/market%',
            TaskDefinitionCode::VIEW_LICH_CARD_WIKI  => '/lich-card/%',
            TaskDefinitionCode::VISIT_ANALYTICS_PAGE => '/analytics%',
            // Extend with more patterns as needed
            default => null
        };
    }


}

?>
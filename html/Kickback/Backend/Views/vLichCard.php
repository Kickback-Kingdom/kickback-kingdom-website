<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\LichCard;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Services\Session;

class vLichCard extends vRecordId
{
    public string $name;
    public int $type;
    public int $rarity;
    public string $description;
    public float $nameFontSize;
    public float $typeFontSize;
    public float $descriptionFontSize;
    public int $health;
    public int $intelligence;
    public int $defense;
    public int $arcanic;
    public int $abyssal;
    public int $thermic;
    public int $verdant;
    public int $luminate;
    public array $subTypes;
    public string $locator;
    public vContent $content;
    public vLichSet $set;
    public vReviewStatus $reviewStatus;

    public vMedia $art;
    public vMedia $cardImage;
    public ?vItem $item = null;

    function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);


        $this->name = "";
        $this->type = 0;
        $this->rarity = 0;
        $this->description = "";
        $this->nameFontSize = 0.8;
        $this->typeFontSize = 0.7;
        $this->descriptionFontSize = 0.7;
        $this->health = 0;
        $this->intelligence = 0;
        $this->defense = 0;
        $this->arcanic = 0;
        $this->abyssal = 0;
        $this->thermic = 0;
        $this->verdant = 0;
        $this->luminate = 0;
        $this->subTypes = [];
        $this->art = vMedia::defaultIcon();
        $this->cardImage = vMedia::defaultIcon();
        $this->locator = "";
        $this->reviewStatus = new vReviewStatus();
        $this->content = new vContent();
        $this->set = new vLichSet();
        $this->item = null;
    }
    
    
    public function hydrate(array|object $data): void
    {
        foreach ((array) $data as $key => $value) {
            if (property_exists($this, $key)) {
                switch ($key) {
                    case 'art':
                    case 'cardImage':
                        if (is_object($value)) {
                            $media = new vMedia();
                            $media->url = $value->url ?? null;
                            $media->crand = $value->crand ?? null;
                            $this->$key = $media;
                        }
                        break;

                        case 'reviewStatus':
                            if (is_object($value)) {
                                $reviewStatus = new vReviewStatus();
                                $reviewStatus->published = $value->published ?? false;
                                $reviewStatus->beingReviewed = $value->beingReviewed ?? false;
                                $reviewStatus->closed = $value->closed ?? false;
                                $this->$key = $reviewStatus;
                            }
                            break;

                        case 'content':
                            if (is_object($value)) {
                                $content = new vContent();
                                $content->pageContent($value->pageContent ?? null);
                                $this->$key = $content;
                            }
                            break;

                        case 'set':
                            if (is_object($value)) {
                                $set = new vLichSet();
                                $set->ctime = $value->ctime;
                                $set->crand = $value->crand;
                                $set->name = $value->name;
                                $set->locator = $value->locator;
                                $this->$key = $set;
                            }
                            break;

                        case 'item':
                            if (is_object($value)) {
                                $item = new vItem();
                                $item->ctime = $value->ctime;
                                $item->crand = $value->crand;
                                $this->$key = $item;
                            }
                            break;

                    default:
                        $this->$key = $value;
                        break;
                }
            }
        }
    }

    public function toModel(): LichCard
    {
        $model = new LichCard();
        $model->name = $this->name;
        $model->type = $this->type;
        $model->rarity = $this->rarity;
        $model->description = $this->description;
        $model->nameFontSize = $this->nameFontSize;
        $model->typeFontSize = $this->typeFontSize;
        $model->descriptionFontSize = $this->descriptionFontSize;
        $model->health = $this->health;
        $model->intelligence = $this->intelligence;
        $model->defense = $this->defense;
        $model->arcanic = $this->arcanic;
        $model->abyssal = $this->abyssal;
        $model->thermic = $this->thermic;
        $model->verdant = $this->verdant;
        $model->luminate = $this->luminate;
        $model->subTypes = $this->subTypes;
        $model->ctime = $this->ctime;
        $model->crand = $this->crand;


        $model->locator = $this->locator;

        $model->reviewStatus = $this->reviewStatus;
        
        $model->cardImage = new ForeignRecordId(
            $this->cardImage->ctime,
            $this->cardImage->crand
        );

        $model->art = new ForeignRecordId(
            $this->art->ctime,
            $this->art->crand
        );

        $model->set = new ForeignRecordId(
            $this->set->ctime,
            $this->set->crand
        );

         if ($this->item != null)
         { 

            $model->item = new ForeignRecordId(
                $this->item->ctime,
                $this->item->crand
            );
         }

        return $model;
    }

    

    public function hasPageContent() : bool {
        return $this->content->hasPageContent();
    }

    public function pageContent() : vPageContent {
        return $this->content->pageContent();
    }

    public function canEdit() : bool {
        return Session::isServantOfTheLich();
    }

    public function populateContent() : void {
        if ($this->hasPageContent())
        {
            $this->content->populateContent("LICH-CARD",$this->locator);
        }
    }

    
    public function populateEverything() : void {
        $this->populateContent();
    }

}

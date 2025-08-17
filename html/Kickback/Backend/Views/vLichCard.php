<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\LichCard;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Services\Session;

#[\AllowDynamicProperties]
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
    /** @var array<array{crand: int,  ctime: string,  name: string}> */
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

    /** @param array<string,mixed> $value */
    private function hydrateMixedValue(string $key, mixed $value) : void
    {
        // This is semantically equivalent to the code that this was refactored
        // from (during PHPStan bughunt), but I am not sure if this behavior
        // is intentional. I am proceeding regardless because it seems like
        // a corner-case where the data is invalid if it's non-object AND
        // has a key with an object-suggestive name. If you authored this code
        // and know the intent, please alter this code as necessary to reflect
        // that intent and replace this message with an appropriate comment
        // that explains the intent/purpose.
        // -- Chad Joan  2025-06-13
        switch ($key)
        {
            case 'art':           break;
            case 'cardImage':     break;
            case 'reviewStatus':  break;
            case 'content':       break;
            case 'set':           break;
            case 'item':          break;

            default:
                $this->$key = $value;
        }
    }

    private function hydrateObjectValue(string $key, object $value) : void
    {
        // This code caused me to write `Obj::populateFromArray`.
        //
        // It might make sense to use it here: although you'd still need
        // to dispatch which class to construct, you'd no longer need to
        // explicitly assign all of the fields from the array.
        // (If the line isn't still around by the time you read this:
        // use `get_object_vars` to acquire the array from `$value`.)
        //
        // However, I am unable to test this code, so I am trying to keep
        // my changes minimal. Which is why I didn't just use it.
        // (I don't want to break this thing that I don't know how to test.)
        //
        // -- Chad Joan  2025-06-13

        $vars = get_object_vars($value);
        switch ($key)
        {
            case 'art':
            case 'cardImage':
                $media = new vMedia();
                $ok = true;
                // @phpstan-ignore booleanAnd.leftAlwaysTrue
                $ok = $ok && self::readStrInto($vars, 'url',    $media->url);
                $ok = $ok && self::readIntInto($vars, 'crand',  $media->crand);
                if ( $ok ) {
                    if ( $key === 'art' ) {
                        $this->art = $media;
                    } else {
                        $this->cardImage = $media;
                    }
                }
                break;

            case 'reviewStatus':
                $reviewStatus = new vReviewStatus();
                $reviewStatus->published     = self::readNullableBool($vars, 'published')     ?? false;
                $reviewStatus->beingReviewed = self::readNullableBool($vars, 'beingReviewed') ?? false;
                $reviewStatus->closed        = self::readNullableBool($vars, 'closed')        ?? false;
                $this->reviewStatus = $reviewStatus;
                break;

            case 'content':
                // pageContent might be `null` during some situations,
                // such as API endpoint calls, where it isn't necessary
                // to display content to the user (and so we wouldn't
                // want to spend database I/O on retrieving the content).
                if (isset($value->pageContent)) {
                    $content = new vContent();
                    $content->pageContent($value->pageContent);
                    $this->content = $content;
                }
                break;

            case 'set':
                $set = new vLichSet();
                $ok = true;
                // @phpstan-ignore booleanAnd.leftAlwaysTrue
                $ok = $ok && self::readStrInto($vars, 'ctime',   $set->ctime);
                $ok = $ok && self::readIntInto($vars, 'crand',   $set->crand);
                $ok = $ok && self::readStrInto($vars, 'name',    $set->name);
                $ok = $ok && self::readStrInto($vars, 'locator', $set->locator);
                if ( $ok ) {
                    $this->set = $set;
                }
                break;

            case 'item':
                $item = new vItem();
                $ok = true;
                // @phpstan-ignore booleanAnd.leftAlwaysTrue
                $ok = $ok && self::readStrInto($vars, 'ctime',  $item->ctime);
                $ok = $ok && self::readIntInto($vars, 'crand',  $item->crand);
                if ( $ok ) {
                    $this->item = $item;
                }
                break;

            default:
                $this->$key = $value;
                break;
        }
    }

    /** @param array<string,mixed>|object $data */
    public function hydrate(array|object $data): void
    {
        foreach ((array) $data as $key => $value)
        {
            if (!property_exists($this, $key)) {
                continue;
            }

            if (is_object($value)) {
                $this->hydrateObjectValue($key,$value);
            } else {
                $this->hydrateMixedValue($key,$value);
            }
        }
    }

    /** @param array<string,mixed> $a */
    private static function readNullableBool(array $a, string $name) : ?bool
    {
        $val = self::readValue($a,$name);
        return is_bool($val) ? $val : null;
    }

    /** @param array<string,mixed> $a */
    private static function readNullableInt(array $a, string $name) : ?int
    {
        $val = self::readValue($a,$name);
        return is_int($val) ? $val : null;
    }

    // /** @param array<string,mixed> $a */
    // private static function readNullableStr(array $a, string $name) : ?string
    // {
    //     return self::readNullableString($a, $name);
    // }

    /** @param array<string,mixed> $a */
    private static function readNullableString(array $a, string $name) : ?string
    {
        $val = self::readValue($a,$name);
        return is_string($val) ? $val : null;
    }

    // /** @param array<string,mixed> $a */
    // private static function readNullableFloat(array $a, string $name) : ?float
    // {
    //     $val = self::readValue($a,$name);
    //     return is_float($val) ? $val : null;
    // }

    /** @param array<string,mixed> $a */
    private static function readIntInto(array $a, string $name, int &$dest) : bool
    {
        $val = self::readNullableInt($a,$name);
        if ( !is_null($val) ) {
            $dest = $val;
            return true;
        } else {
            return false;
        }
    }

    /** @param array<string,mixed> $a */
    private static function readStrInto(array $a, string $name, ?string &$dest) : bool
    {
        return self::readStringInto($a, $name, $dest);
    }

    /** @param array<string,mixed> $a */
    private static function readStringInto(array $a, string $name, ?string &$dest) : bool
    {
        $val = self::readNullableString($a,$name);
        if ( !is_null($val) ) {
            $dest = $val;
            return true;
        } else {
            return false;
        }
    }

    /** @param array<string,mixed> $a */
    private static function readValue(array $a, string $name) : mixed
    {
        if ( array_key_exists($name, $a) ) {
            return $a[$name];
        } else {
            return null;
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

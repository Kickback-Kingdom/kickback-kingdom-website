<?php
declare(strict_types=1);

namespace Kickback\Common;

use \SplFixedArray;

use \Kickback\Utilities\Str;

/**
* Class containing version info and history for the Kickback Kingdom website, backend, and API.
*
* These version numbers use [Semantic Versioning](https://semver.org/).
*
* Beware: These versions may diverge once components are isolated into their
* own namespaces or projects. At that point, this class would be deprecated
* or removed as it is replaced by multiple version classes, each for their
* respective component/project.
*/
final class Version
{
    private const COL_MAJOR = 0; // Mandatory, int
    private const COL_MINOR = 1; // Mandatory, int
    private const COL_PATCH = 2; // Mandatory, int
    private const COL_PRERELEASE = 3; // Optional, string
    private const COL_BUILD = 4; // Optional, string
    private const COL_CONTENT_NAME = 5; // Mandatory, string

    /**
    * @var array<array{int,int,int,string,string,string}>
    */
    private const HISTORY = [
        [0,0,1,"","",   "update-writ-of-passage"]
    ];

    //private const LAST_HISTORY_ENTRY_IDX = array_key_last(self::HISTORY);

    public static function current() : Version
    {
        $history = self::history();
        $ver = $history[$history->count() - 1];
        assert(isset($ver));
        return $ver;
    }

/*
    public const MAJOR        = self::LAST_HISTORY_ENTRY[self::COL_MAJOR];
    public const MINOR        = self::LAST_HISTORY_ENTRY[self::COL_MINOR];
    public const PATCH        = self::LAST_HISTORY_ENTRY[self::COL_PATCH];
    public const PRERELEASE   = self::LAST_HISTORY_ENTRY[self::COL_PRERELEASE];
    public const BUILD        = self::LAST_HISTORY_ENTRY[self::COL_BUILD];
    public const CONTENT_NAME = self::LAST_HISTORY_ENTRY[self::COL_CONTENT_NAME];

    // TODO: How to get PHPStan to stop complaining about constant expressions
    // in ternary expressions being always true or always false?

    public const NUMBER =
        strval(self::MAJOR) . '.' . strval(self::MINOR) . '.' . strval(self::PATCH) .
        ((0 === strlen(self::PRERELEASE)) ? ('-' . self::PRERELEASE) : "") . // @phpstan-ignore identical.alwaysTrue
        ((0 === strlen(self::BUILD))      ? ('+' . self::BUILD) : ""); // @phpstan-ignore identical.alwaysTrue

    // TODO: Change this to use normal separators instead of ALL hyphens?
    // Dots are valid and non-special characters in both URLs and filesystem paths.
    // (The plus-sign at the beginning of the build-metadata part might be annoying in some contexts,
    // like inside HTML query strings (but not paths! those are fine!),
    // and require escaping in most shells, but are otherwise still valid.)
    // Ideally this would just be (self::NUMBER . '-' . self::CONTENT_NAME).
    public const URL_PATH_COMPONENT =
        strval(self::MAJOR) . '-' . strval(self::MINOR) . '-' . strval(self::PATCH) .
        ((0 === strlen(self::PRERELEASE))   ? ('-' . self::PRERELEASE) : "") . // @phpstan-ignore identical.alwaysTrue
        ((0 === strlen(self::BUILD))        ? ('-' . self::BUILD) : "") . // @phpstan-ignore identical.alwaysTrue
        ((0 === strlen(self::CONTENT_NAME)) ? ('-' . self::CONTENT_NAME) : ""); // @phpstan-ignore identical.alwaysFalse

    public const FILE_PATH_COMPONENT = self::URL_PATH_COMPONENT;
*/

    // We use `SplFixedArray` to enforce integer-valued indices.
    /** @var ?\SplFixedArray<Version> */
    private static ?\SplFixedArray $history_ = null;

    /** @var ?array<string, Version> */
    private static ?array          $history_by_number_ = null;

    /** @var ?array<string, Version> */
    private static ?array          $history_by_blogpost_locator_ = null;

    /** @return \SplFixedArray<Version> */
    public static function history() : \SplFixedArray
    {
        if ( !self::$initialized ) {
            self::initialize();
        }
        assert(isset(self::$history_));
        return self::$history_;
    }

    /** @return array<string, Version> */
    public static function history_by_number() : array
    {
        if ( !self::$initialized ) {
            self::initialize();
        }
        assert(isset(self::$history_by_number_));
        return self::$history_by_number_;
    }


    /** @return array<string, Version> */
    public static function history_by_blogpost_locator() : array
    {
        if ( !self::$initialized ) {
            self::initialize();
        }
        assert(isset(self::$history_by_blogpost_locator_));
        return self::$history_by_blogpost_locator_;
    }

    // We can't have class objects as constants, so we will lazily-initialize an
    // array of static instances instead.
    // Note that class-statics are thread-local, so there are no synchronization
    // concerns for this function (aside from the duplicate allocation+initialization
    // of immutable things between threads, which is suboptimal, but oh well).
    private static bool $initialized = false;
    private static function initialize() : void
    {
        assert( !self::$initialized );

        $n_vers = count(self::HISTORY);

        $hist_by_index = new \SplFixedArray($n_vers);
        $hist_by_number = [];
        $hist_by_locator = [];
        for ( $i = 0; $i < $n_vers; $i++ )
        {
            $raw_version_info = self::HISTORY[$i];
            $version_info = new Version(
                $raw_version_info[self::COL_MAJOR],
                $raw_version_info[self::COL_MINOR],
                $raw_version_info[self::COL_PATCH],
                $raw_version_info[self::COL_PRERELEASE],
                $raw_version_info[self::COL_BUILD],
                $raw_version_info[self::COL_CONTENT_NAME]
            );
            $hist_by_index[$i] = $version_info;
            $hist_by_number[$version_info->number()] = $version_info;
            $hist_by_locator[$version_info->blogpost_locator()] = $version_info;
            // TODO: Print error message(s) if there are duplicate version numbers?
        }

        // @phpstan-ignore assign.propertyType
        self::$history_ = $hist_by_index;
        self::$history_by_number_ = $hist_by_number;
        self::$history_by_blogpost_locator_ = $hist_by_locator;

        self::$initialized = true;
    }

    public function __construct(
        private int    $major_,
        private int    $minor_,
        private int    $patch_,
        private string $prerelease_,
        private string $build_,
        private string $content_name_
    ) {}

    public function major() : int { return $this->major_; }
    public function minor() : int { return $this->minor_; }
    public function patch() : int { return $this->patch_; }
    public function prerelease()   : string { return $this->prerelease_; }
    public function build()        : string { return $this->build_; }
    public function content_name() : string { return $this->content_name_; }

    private ?string $number_ = null;
    public function number() : string
    {
        if ( !isset($this->number_) ) {
            $this->populate_number();
            assert(isset($this->number_));
        }
        return $this->number_;
    }

    private function populate_number() : void
    {
        $this->number_ =
            strval($this->major_) . '.' . strval($this->minor_) . '.' . strval($this->patch_) .
            (!Str::empty($this->prerelease_) ? ('-' . $this->prerelease_) : "") .
            (!Str::empty($this->build_)      ? ('+' . $this->build_     ) : "");
    }

    private ?string $url_path_component_ = null;
    public function url_path_component() : string
    {
        if ( !isset($this->url_path_component_) ) {
            $this->populate_url_path_component();
            assert(isset($this->url_path_component_));
        }
        return $this->url_path_component_;
    }

    // TODO: Change this to use normal separators instead of ALL hyphens?
    // Dots are valid and non-special characters in both URLs and filesystem paths.
    // (The plus-sign at the beginning of the build-metadata part might be annoying
    // in some contexts, like inside HTML query strings, such as blogpost_locator(),
    // and require escaping in most shells, but are otherwise still valid for
    // file paths and URL path components.)
    // Ideally this would just be (self::NUMBER . '-' . self::CONTENT_NAME).
    private function populate_url_path_component() : void
    {
        $this->url_path_component_ =
            strval($this->major_) . '-' . strval($this->minor_) . '-' . strval($this->patch_) .
            (!Str::empty($this->prerelease_)   ? ('-' . $this->prerelease_  ) : "") .
            (!Str::empty($this->build_)        ? ('-' . $this->build_       ) : "") .
            (!Str::empty($this->content_name_) ? ('-' . $this->content_name_) : "");
    }

    public function file_path_component() : string
    {
        return $this->url_path_component();
    }

    public function blogpost_locator() : string
    {
        return $this->url_path_component();
    }

    // #website-only
    public static bool $show_version_popup = true;

    // #website-only
    public static bool $client_is_viewing_blogpost_for_current_version_update = false;
}
?>
<!--
$_globalVersionInfo = [
    "0.0.1" => "0-0-1-update-writ-of-passage"
];

// Automatically sets the latest version based on the array
$_globalVersionNumbers = array_keys($_globalVersionInfo);
$_globalVersionCurrent = $_globalVersionNumbers[0];

$_globalDoNotShowNewVersionPopup = false;
-->

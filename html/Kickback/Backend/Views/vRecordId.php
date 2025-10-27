<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

use DateTime;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Common\Utility\IDCrypt;
use Kickback\Backend\Config\ServiceCredentials;

class vRecordId
{
    public string $ctime;
    public int $crand;
    
    function __construct(string $ctime = '', int $crand = -1)
    {
        $this->ctime = $ctime;
        $this->crand = $crand;
    }

    function getVRecordId() : vRecordId
    {
        return new vRecordId($this->ctime, $this->crand);
    }

    function getForeignRecordId() : ForeignRecordId {
        return new ForeignRecordId($this->ctime, $this->crand);
    }

    public function isset() : bool {
        return $this->crand != -1;
    }

    public static function from(int $ctime, int $crand) : string
    {
        return \sprintf('%X:%X', $ctime, $crand);
    }

    

    public static function to(string $guid, int &$ctime, int &$crand) : bool
    {
        $ctime_len = \strcspn($guid, ':');
        $crand_pos = $ctime_len+1;
        $crand_len = \strlen($guid) - $crand_pos;
        $ctime = \hexdec(\substr($guid, 0, $ctime_len));
        $crand = \hexdec(\substr($guid, $ctime_len+1, $crand_len));
        return true; // TODO: parse failures? throw or return false? probably throw?
    }


    /**
     * Evaluates the equality between this vRecordId and another
     * 
     * @param self $other the other instance of vRecordId to be compared to
     * @param bool $formatToLegacyPrecision optional boolean flag for if sub second precision should be used when comparing ctime. This is to allow legacy datetime columns to be successfully compared
     * 
     * @return bool the boolean result representing the equality between the self and other vRecordId instances
     */
    public function equals(self $other, bool $formatToLegacyPrecision = false) : bool
    {
        if($formatToLegacyPrecision)
        {
            $selfDate = new DateTime($this->ctime);
            $selfCtime =  $selfDate->format("Y-m-d H:i:s");  
            $otherDate = new Datetime($other->ctime);
            $otherCtime =  $otherDate->format("Y-m-d H:i:s");  

            return ($selfCtime === $otherCtime && $this->crand === $other->crand);
        }

        return ($this->ctime === $other->ctime && $this->crand === $other->crand);
    }

    private static function getEncryptionKey(): string {
        return ServiceCredentials::get("crypt_key_quest_id");
    }
    
    public function toURLEncodedEncrypted() : string {
        return urlencode($this->toEncrypted());
    }
    
    public function toEncrypted() : string {
        $idString = $this->ctime . '|' . $this->crand;
        $idCrypt = new IDCrypt(self::getEncryptionKey());
        return $idCrypt->encrypt($idString);
    }
    
    public static function fromEncrypted(string $encrypted) : ?self {
        $idCrypt = new IDCrypt(self::getEncryptionKey());
        $decrypted = $idCrypt->decrypt($encrypted);
    
        if ($decrypted === null) {
            return null;
        }
    
        [$ctime, $crand] = explode('|', $decrypted);
    
        return new self($ctime, (int)$crand);
    }
}

?>

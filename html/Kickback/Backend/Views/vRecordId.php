<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

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

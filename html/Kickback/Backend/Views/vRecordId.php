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

    function getForeignRecordId() : ForeignRecordId {
        return new ForeignRecordId($this->ctime, $this->crand);
    }

    // As of 2025-06-12, these methods are unused.
    // And they contribute (either directly or indirectly) to PHPStan bug lists.
    // So I am removing them.
    //
    // (But I don't know if someone needed it later, so I'm not deleting them outright.)
    //
    // -- Chad Joan  2025-06-12
    //
    //private static function getEncryptionKey(): string {
    //    return ServiceCredentials::get("crypt_key_quest_id");
    //}
    //
    //public function toURLEncodedEncrypted() : string {
    //    return urlencode($this->toEncrypted());
    //}
    //
    //public function toEncrypted() : string {
    //    $idString = $this->ctime . '|' . $this->crand;
    //    $idCrypt = new IDCrypt(self::getEncryptionKey());
    //    return $idCrypt->encrypt($idString);
    //}
    //
    //public static function fromEncrypted(string $encrypted) : ?self {
    //    $idCrypt = new IDCrypt(self::getEncryptionKey());
    //    $decrypted = $idCrypt->decrypt($encrypted);
    //
    //    if ($decrypted === null) {
    //        return null;
    //    }
    //
    //    [$ctime, $crand] = explode('|', $decrypted);
    //
    //    return new self($ctime, (int)$crand);
    //}
}

?>

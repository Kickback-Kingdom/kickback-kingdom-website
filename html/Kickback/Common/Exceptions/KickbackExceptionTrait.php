<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\KickbackThrowableTrait;

// Currently a stub that just forwards KickbackThrowableTrait.
// It could be useful future-proofing if exceptions end up
// with functionality that errors _shouldn't_ have.
// (Fatal errors already have functionality that is
// not relevant/appropriate for exceptions.)

/**
* Assists with defining errors when extending PHP or 3rd party exceptions.
*
* @see IKickbackException
*/
trait KickbackExceptionTrait
{
    use KickbackThrowableTrait;
}
?>

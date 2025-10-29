<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions;

use Kickback\Common\Exceptions\KickbackThrowableTrait;

// Currently a stub.
// It could be useful future-proofing if exceptions end up
// with functionality that errors _shouldn't_ have.
// (Fatal errors already have functionality that is
// not relevant/appropriate for exceptions.)

/**
* Assists with defining errors when extending PHP or 3rd party exceptions.
*
* @phpstan-require-implements  \Kickback\Common\Exceptions\IKickbackException
*
* @see IKickbackException
*/
trait KickbackExceptionTrait
{
}
?>

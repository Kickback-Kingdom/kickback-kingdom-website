<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Fatal;

use Kickback\Common\Exceptions\Fatal\IUnsupportedOperationError;
use Kickback\Common\Exceptions\Fatal\UnsupportedOperationError;

interface IImmutableModificationError extends IUnsupportedOperationError {}
class ImmutableModificationError extends UnsupportedOperationError implements IImmutableModificationError {}
?>

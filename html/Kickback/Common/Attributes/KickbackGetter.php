<?php
declare(strict_types=1);

namespace Kickback\Common\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class KickbackGetter
{
}
?>

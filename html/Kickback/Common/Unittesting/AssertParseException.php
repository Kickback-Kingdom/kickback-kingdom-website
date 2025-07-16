<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting;

use Kickback\Common\Unittesting\IAssertException;
use Kickback\Common\Unittesting\AssertException;

interface IAssertParseException extends IAssertException {}
class AssertParseException extends AssertException implements IAssertParseException {}
?>

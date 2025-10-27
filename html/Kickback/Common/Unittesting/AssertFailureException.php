<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting;

use Kickback\Common\Unittesting\IAssertException;
use Kickback\Common\Unittesting\AssertException;

interface IAssertFailureException extends IAssertException {}
class AssertFailureException extends AssertException implements IAssertFailureException {}
?>

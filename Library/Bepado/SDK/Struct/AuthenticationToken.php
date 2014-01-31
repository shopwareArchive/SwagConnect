<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class AuthenticationToken extends Struct
{
    public $authenticated = false;
    public $userIdentifier;

    /**
     * Potential error message, if authentication failed.
     *
     * @var string|null
     */
    public $errorMessage;
}

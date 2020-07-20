<?php

namespace Albismart\Exceptions;

use Exception;

class SNMPException extends Exception
{
}

class NoResponseException extends SNMPException
{
}

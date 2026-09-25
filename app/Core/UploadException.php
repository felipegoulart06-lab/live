<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Upload rejected for a reason the user can fix; the message is safe to show. */
final class UploadException extends RuntimeException
{
}

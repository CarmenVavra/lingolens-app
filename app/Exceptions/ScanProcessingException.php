<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Expected failure while analysing a scan (e.g. unreadable handwriting).
 */
class ScanProcessingException extends RuntimeException {}

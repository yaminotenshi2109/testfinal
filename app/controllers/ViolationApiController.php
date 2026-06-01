<?php
/**
 * app/controllers/ViolationApiController.php
 * JSON API for Violation Records
 */

declare(strict_types=1);

require_once __DIR__ . '/ViolationController.php';

class ViolationApiController extends ViolationController
{
    public function __construct()
    {
        parent::__construct();
        // Uses ViolationController's construct and requireAdmin
    }
}

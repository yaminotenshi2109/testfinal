<?php
/**
 * app/controllers/UserAdminController.php
 * Autoload bridge for UserAdminController (extends UserController)
 */

declare(strict_types=1);

require_once __DIR__ . '/UserController.php';

class UserAdminController extends UserController
{
    // Inherits all admin CRUD methods (index, show, create, edit, store, update, destroy, etc.)
}

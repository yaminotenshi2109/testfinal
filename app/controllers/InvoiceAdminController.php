<?php
/**
 * app/controllers/InvoiceAdminController.php
 * Admin Invoice Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BillingController.php';

class InvoiceAdminController extends BillingController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function generate(array $params = []): void
    {
        $this->generateBatch($params);
    }

    public function pdf(array $params = []): void
    {
        $this->getPdf($params);
    }
}

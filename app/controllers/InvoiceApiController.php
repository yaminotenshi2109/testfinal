<?php
/**
 * app/controllers/InvoiceApiController.php
 * JSON API for Invoice Management
 */

declare(strict_types=1);

require_once __DIR__ . '/BillingController.php';

class InvoiceApiController extends BillingController
{
    public function __construct()
    {
        parent::__construct();
        // Checked via api middleware
    }

    public function generate(array $params = []): void
    {
        $this->generateInvoice($params);
    }

    public function pay(array $params = []): void
    {
        $this->markPaid($params);
    }
}

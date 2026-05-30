<?php
/**
 * app/controllers/HomeController.php
 * ─────────────────────────────────────────────────────────────
 *  Controller home page and about page.
 */

declare(strict_types=1);

class HomeController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->view('home/index', [
            'title' => 'Trang chủ',
        ]);
    }

    public function about(array $params = []): void
    {
        $this->view('home/about', [
            'title' => 'Giới thiệu',
        ]);
    }
}

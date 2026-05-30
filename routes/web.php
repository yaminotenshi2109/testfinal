<?php
/**
 * routes/web.php
 * ─────────────────────────────────────────────────────────────
 *  Định nghĩa toàn bộ routes của hệ thống KTX
 *  Được require từ public/index.php
 *
 *  Quy ước đặt tên routes (->name()):
 *    resource.action  →  room.index, room.show, room.store ...
 *    auth.login       →  trang đăng nhập
 *    admin.dashboard  →  dashboard admin
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

$router = Router::getInstance();


// ─────────────────────────────────────────────────────────────
// PUBLIC — không cần đăng nhập
// ─────────────────────────────────────────────────────────────

$router->get('/', 'HomeController@index')->name('home');
$router->get('/about', 'HomeController@about')->name('about');


// ─────────────────────────────────────────────────────────────
// AUTH — chỉ dành cho khách (chưa đăng nhập)
// ─────────────────────────────────────────────────────────────

$router->group('/auth', function (Router $r) {
    $r->get('/login',    'AuthController@showLogin')->name('auth.login');
    $r->post('/login',   'AuthController@login');
    $r->get('/register', 'AuthController@showRegister')->name('auth.register');
    $r->post('/register','AuthController@register');
}, ['guest']);

$router->post('/logout', 'AuthController@logout')->name('auth.logout')->middleware('auth');


// ─────────────────────────────────────────────────────────────
// STUDENT — đăng nhập, role = student
// ─────────────────────────────────────────────────────────────

$router->group('/student', function (Router $r) {

    $r->get('/dashboard', 'StudentController@dashboard')->name('student.dashboard');
    $r->get('/profile',   'StudentController@profile')->name('student.profile');
    $r->post('/profile',  'StudentController@updateProfile');

    // Đăng ký phòng
    $r->get('/registrations',         'RegistrationController@index')->name('registration.index');
    $r->get('/registrations/create',  'RegistrationController@create')->name('registration.create');
    $r->post('/registrations',        'RegistrationController@store')->name('registration.store');
    $r->delete('/registrations/:id',  'RegistrationController@cancel')->name('registration.cancel')
        ->where('id', '\d+');

    // Hợp đồng
    $r->get('/contracts',      'ContractController@index')->name('contract.index');
    $r->get('/contracts/:id',  'ContractController@show')->name('contract.show')
        ->where('id', '\d+');

    // Hóa đơn
    $r->get('/invoices',       'InvoiceController@myInvoices')->name('invoice.my');
    $r->get('/invoices/:id',   'InvoiceController@show')->name('invoice.show')
        ->where('id', '\d+');

    // Yêu cầu bảo trì
    $r->get('/maintenance',         'MaintenanceController@myRequests')->name('maintenance.my');
    $r->post('/maintenance',        'MaintenanceController@store');
    $r->delete('/maintenance/:id',  'MaintenanceController@cancel')
        ->where('id', '\d+');

    // Vi phạm của bản thân
    $r->get('/violations', 'ViolationController@myViolations')->name('violation.my');

    // Thông báo
    $r->get('/notifications',         'NotificationController@index')->name('notification.index');
    $r->post('/notifications/:id/read','NotificationController@markRead')
        ->where('id', '\d+');

}, ['auth']);


// ─────────────────────────────────────────────────────────────
// ADMIN PANEL — role = admin
// ─────────────────────────────────────────────────────────────

$router->group('/admin', function (Router $r) {

    $r->get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');

    // ── Quản lý Users ──────────────────────────────────
    $r->resource('/users', 'UserAdminController')->name('user.index');

    // ── Quản lý Sinh viên ──────────────────────────────
    $r->resource('/students', 'StudentAdminController');
    $r->get('/students/:id/violations', 'StudentAdminController@violations')
        ->where('id', '\d+');

    // ── Tòa nhà ────────────────────────────────────────
    $r->resource('/buildings', 'BuildingController');

    // ── Phòng ──────────────────────────────────────────
    $r->resource('/rooms', 'RoomController');
    $r->get('/rooms/:id/amenities', 'RoomController@amenities')
        ->where('id', '\d+')->name('room.amenities');

    // ── Đăng ký phòng ──────────────────────────────────
    $r->get('/registrations',            'RegistrationAdminController@index')->name('admin.registration.index');
    $r->get('/registrations/pending',    'RegistrationAdminController@pending');
    $r->get('/registrations/:id',        'RegistrationAdminController@show')->where('id', '\d+');
    $r->post('/registrations/:id/approve','RegistrationAdminController@approve')->where('id', '\d+');
    $r->post('/registrations/:id/reject', 'RegistrationAdminController@reject')->where('id', '\d+');

    // ── Hợp đồng ───────────────────────────────────────
    $r->resource('/contracts', 'ContractAdminController');
    $r->post('/contracts/:id/terminate', 'ContractAdminController@terminate')
        ->where('id', '\d+');

    // ── Chỉ số điện nước ───────────────────────────────
    $r->get('/utilities',          'UtilityController@index')->name('utility.index');
    $r->get('/utilities/create',   'UtilityController@create');
    $r->post('/utilities',         'UtilityController@store');
    $r->get('/utilities/:id/edit', 'UtilityController@edit')->where('id', '\d+');
    $r->put('/utilities/:id',      'UtilityController@update')->where('id', '\d+');

    // ── Hóa đơn ────────────────────────────────────────
    $r->get('/invoices',              'InvoiceAdminController@index')->name('admin.invoice.index');
    $r->post('/invoices/generate',    'InvoiceAdminController@generate');
    $r->get('/invoices/:id',          'InvoiceAdminController@show')->where('id', '\d+');
    $r->post('/invoices/:id/pay',     'InvoiceAdminController@markPaid')->where('id', '\d+');
    $r->get('/invoices/:id/pdf',      'InvoiceAdminController@pdf')->where('id', '\d+');

    // ── Vi phạm ────────────────────────────────────────
    $r->resource('/violations', 'ViolationController');
    $r->post('/violations/:id/dismiss', 'ViolationController@dismiss')->where('id', '\d+');

    // ── Bảo trì ────────────────────────────────────────
    $r->get('/maintenance',           'MaintenanceAdminController@index')->name('maintenance.index');
    $r->get('/maintenance/:id',       'MaintenanceAdminController@show')->where('id', '\d+');
    $r->post('/maintenance/:id/resolve','MaintenanceAdminController@resolve')->where('id', '\d+');
    $r->post('/maintenance/:id/close', 'MaintenanceAdminController@close')->where('id', '\d+');

    // ── Thông báo ──────────────────────────────────────
    $r->get('/notifications',       'NotificationController@adminIndex');
    $r->post('/notifications/send', 'NotificationController@send');

    // ── Báo cáo ────────────────────────────────────────
    $r->get('/reports/revenue',     'ReportController@revenue')->name('report.revenue');
    $r->get('/reports/occupancy',   'ReportController@occupancy');
    $r->get('/reports/violations',  'ReportController@violations');

}, ['auth', 'admin']);


// ─────────────────────────────────────────────────────────────
// REST API — JSON responses, dùng cho AJAX frontend
// ─────────────────────────────────────────────────────────────

$router->group('/api', function (Router $r) {

    // Rooms
    $r->get('/rooms',              'RoomApiController@index');
    $r->get('/rooms/available',    'RoomApiController@available');
    $r->get('/rooms/:id',          'RoomApiController@show')->where('id', '\d+');
    $r->post('/rooms',             'RoomApiController@store');
    $r->put('/rooms/:id',          'RoomApiController@update')->where('id', '\d+');
    $r->delete('/rooms/:id',       'RoomApiController@destroy')->where('id', '\d+');

    // Students
    $r->get('/students',           'StudentApiController@index');
    $r->get('/students/:id',       'StudentApiController@show')->where('id', '\d+');
    $r->put('/students/:id',       'StudentApiController@update')->where('id', '\d+');

    // Invoices
    $r->get('/invoices',           'InvoiceApiController@index');
    $r->get('/invoices/:id',       'InvoiceApiController@show')->where('id', '\d+');
    $r->post('/invoices/generate', 'InvoiceApiController@generate');
    $r->post('/invoices/:id/pay',  'InvoiceApiController@pay')->where('id', '\d+');

    // Violations
    $r->get('/violations',         'ViolationApiController@index');
    $r->post('/violations',        'ViolationApiController@store');
    $r->delete('/violations/:id',  'ViolationApiController@destroy')->where('id', '\d+');

    // Notifications
    $r->get('/notifications',              'NotificationApiController@index');
    $r->post('/notifications/:id/read',    'NotificationApiController@markRead')
        ->where('id', '\d+');
    $r->post('/notifications/read-all',    'NotificationApiController@markAllRead');

    // Dashboard stats
    $r->get('/stats/dashboard',    'StatsApiController@dashboard');
    $r->get('/stats/revenue',      'StatsApiController@revenue');
    $r->get('/stats/occupancy',    'StatsApiController@occupancy');

}, ['api']);

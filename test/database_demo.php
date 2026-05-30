<?php
/**
 * tests/database_demo.php
 * ─────────────────────────────────────────────────────────────
 *  Demo chạy thử tất cả tính năng của Database Singleton
 *  Chạy: php tests/database_demo.php
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/BaseModel.php';
require_once __DIR__ . '/../app/models/Models.php';

// Hàm in kết quả đẹp
function demo(string $title, callable $fn): void
{
    echo "\n" . str_repeat('─', 55) . "\n";
    echo "  {$title}\n";
    echo str_repeat('─', 55) . "\n";
    try {
        $fn();
    } catch (DatabaseException $e) {
        echo "  [DatabaseException] " . $e->getMessage() . "\n";
    } catch (\Throwable $e) {
        echo "  [Error] " . $e->getMessage() . "\n";
    }
}


// ════════════════════════════════════════════════════════════
// 1. SINGLETON — luôn cùng một instance
// ════════════════════════════════════════════════════════════
demo('Singleton: cùng instance?', function () {
    $db1 = Database::getInstance();
    $db2 = Database::getInstance();

    if ($db1 === $db2) {
        echo "  ✓ Đúng — $db1 === $db2 (cùng object)\n";
    } else {
        echo "  ✗ Sai — không phải Singleton!\n";
    }
});


// ════════════════════════════════════════════════════════════
// 2. SELECT cơ bản
// ════════════════════════════════════════════════════════════
demo('SELECT: lấy tất cả users', function () {
    $db    = Database::getInstance();
    $users = $db->select("SELECT id, username, email, role FROM users LIMIT 5");

    foreach ($users as $u) {
        echo "  [{$u['id']}] {$u['username']} <{$u['email']}> — {$u['role']}\n";
    }
});

demo('selectOne: tìm user theo email', function () {
    $db   = Database::getInstance();
    $user = $db->selectOne(
        "SELECT id, username, role FROM users WHERE email = ?",
        ['admin@ktx.edu.vn']
    );

    if ($user) {
        echo "  Tìm thấy: [{$user['id']}] {$user['username']} ({$user['role']})\n";
    } else {
        echo "  Không tìm thấy\n";
    }
});

demo('selectValue: đếm sinh viên', function () {
    $count = Database::getInstance()->selectValue("SELECT COUNT(*) FROM students");
    echo "  Tổng sinh viên: {$count}\n";
});


// ════════════════════════════════════════════════════════════
// 3. INSERT / UPDATE / DELETE
// ════════════════════════════════════════════════════════════
demo('INSERT: tạo thông báo mới', function () {
    $db = Database::getInstance();
    $id = $db->insert('notifications', [
        'user_id' => 1,
        'title'   => 'Test Singleton',
        'message' => 'Kiểm tra Database::getInstance() hoạt động.',
        'type'    => 'system',
    ]);
    echo "  Đã tạo notification ID = {$id}\n";
});

demo('UPDATE: đổi trạng thái notification', function () {
    $db      = Database::getInstance();
    $latest  = $db->selectOne(
        "SELECT id FROM notifications WHERE title = ? ORDER BY id DESC LIMIT 1",
        ['Test Singleton']
    );
    if ($latest) {
        $rows = $db->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$latest['id']]
        );
        echo "  Đã cập nhật {$rows} hàng (id={$latest['id']})\n";
    }
});

demo('DELETE: xóa notification test', function () {
    $db   = Database::getInstance();
    $rows = $db->delete('notifications', "title = 'Test Singleton'");
    echo "  Đã xóa {$rows} hàng\n";
});


// ════════════════════════════════════════════════════════════
// 4. TRANSACTION
// ════════════════════════════════════════════════════════════
demo('TRANSACTION: thành công', function () {
    $db = Database::getInstance();

    $result = $db->transaction(function (Database $db) {
        $id = $db->insert('notifications', [
            'user_id' => 1,
            'title'   => 'Transaction Test',
            'message' => 'Tạo trong transaction.',
            'type'    => 'system',
        ]);
        $db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
        $db->delete('notifications', 'id = ?', [$id]);
        return 'OK';
    });

    echo "  Transaction hoàn thành: {$result}\n";
});

demo('TRANSACTION: rollback khi lỗi', function () {
    $db = Database::getInstance();
    $before = $db->count('notifications');

    try {
        $db->transaction(function (Database $db) {
            $db->insert('notifications', [
                'user_id' => 1,
                'title'   => 'Sẽ bị rollback',
                'message' => '...',
                'type'    => 'system',
            ]);
            // Cố tình gây lỗi
            throw new \RuntimeException('Lỗi giả lập — rollback!');
        });
    } catch (\RuntimeException $e) {
        echo "  Bắt được lỗi: {$e->getMessage()}\n";
    }

    $after = $db->count('notifications');
    echo "  Trước: {$before} hàng | Sau: {$after} hàng (rollback thành công)\n";
});


// ════════════════════════════════════════════════════════════
// 5. EXISTS / COUNT / FIND
// ════════════════════════════════════════════════════════════
demo('exists() / count() / find()', function () {
    $db = Database::getInstance();

    $exists = $db->exists('users', 'email = ?', ['admin@ktx.edu.vn']);
    echo "  admin tồn tại: " . ($exists ? 'Có' : 'Không') . "\n";

    $total = $db->count('rooms', 'status = ?', ['available']);
    echo "  Phòng available: {$total}\n";

    $room = $db->find('rooms', 1);
    echo "  Room #1: {$room['room_number']} ({$room['room_type']})\n";
});


// ════════════════════════════════════════════════════════════
// 6. PAGINATE
// ════════════════════════════════════════════════════════════
demo('paginate(): phân trang sinh viên', function () {
    $db     = Database::getInstance();
    $result = $db->paginate(
        "SELECT id, full_name, faculty FROM students ORDER BY id",
        [],
        1,   // trang 1
        2    // 2 sinh viên/trang
    );

    echo "  Tổng: {$result['total']} | Trang {$result['current_page']}/{$result['last_page']}\n";
    foreach ($result['data'] as $s) {
        echo "    [{$s['id']}] {$s['full_name']} — {$s['faculty']}\n";
    }
});


// ════════════════════════════════════════════════════════════
// 7. MODEL LAYER (BaseModel + Repository)
// ════════════════════════════════════════════════════════════
demo('UserModel::authenticate()', function () {
    $userModel = new UserModel();
    $user      = $userModel->authenticate('admin@ktx.edu.vn', 'Admin@123');

    if ($user) {
        echo "  ✓ Đăng nhập OK: [{$user['id']}] {$user['username']} ({$user['role']})\n";
        echo "  password_hash không bị lộ: " . (isset($user['password_hash']) ? 'LỖI' : 'An toàn ✓') . "\n";
    } else {
        echo "  ✗ Đăng nhập thất bại\n";
    }
});

demo('RoomModel::availableFor()', function () {
    $roomModel = new RoomModel();
    $rooms     = $roomModel->availableFor('male');

    echo "  Phòng khả dụng cho nam: " . count($rooms) . " phòng\n";
    foreach ($rooms as $r) {
        echo "    {$r['building_name']} - {$r['room_number']} ({$r['room_type']}) "
           . "| {$r['current_occupants']}/{$r['capacity']} giường\n";
    }
});

demo('InvoiceModel::revenueByMonth()', function () {
    $invoiceModel = new InvoiceModel();
    $revenue      = $invoiceModel->revenueByMonth(2025);

    foreach ($revenue as $r) {
        echo "  Tháng {$r['month']}/2025 | "
           . "Tổng: " . number_format((float)$r['revenue'], 0, ',', '.') . " VND | "
           . "Đã thu: " . number_format((float)$r['collected'], 0, ',', '.') . " VND\n";
    }
});

demo('ViolationModel::totalPoints()', function () {
    $vm     = new ViolationModel();
    $points = $vm->totalPoints(1);
    echo "  Sinh viên #1 tổng điểm vi phạm: {$points} / " . VIOLATION_THRESHOLD . "\n";
    echo "  Trạng thái: " . ($points >= VIOLATION_THRESHOLD ? 'UNDER REVIEW ⚠' : 'Bình thường ✓') . "\n";
});

demo('BuildingModel::withRoomStats()', function () {
    $bm       = new BuildingModel();
    $buildings = $bm->withRoomStats();

    foreach ($buildings as $b) {
        echo "  {$b['name']} ({$b['gender_type']}) | "
           . "Giường: {$b['occupied_beds']}/{$b['total_beds']} "
           . "| Còn trống: {$b['available_beds']}\n";
    }
});


// ════════════════════════════════════════════════════════════
// 8. DEBUG STATS
// ════════════════════════════════════════════════════════════
demo('getStats(): thống kê query', function () {
    $stats = Database::getInstance()->getStats();
    echo "  Số query thực hiện  : {$stats['query_count']}\n";
    echo "  Tổng thời gian      : {$stats['total_time_ms']} ms\n";
    echo "  Thời gian trung bình: {$stats['avg_time_ms']} ms/query\n";
    echo "  Stmt cache size     : {$stats['stmt_cache_size']}\n";
});

echo "\n" . str_repeat('═', 55) . "\n";
echo "  Tất cả demo hoàn tất!\n";
echo str_repeat('═', 55) . "\n\n";

<?php
/**
 * test/fix_db.php
 * Database migration helper to align SQL schema with PHP logic
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::getInstance();
    echo "Connecting to database...\n";

    // 1. Check if preferred_building_id already exists in room_registrations
    $columns = $db->select("SHOW COLUMNS FROM room_registrations");
    $columnNames = array_column($columns, 'Field');

    if (!in_array('preferred_building_id', $columnNames, true)) {
        echo "Modifying room_registrations table...\n";
        
        // Remove foreign key fk_reg_room to modify room_id safely
        $db->query("ALTER TABLE room_registrations DROP FOREIGN KEY fk_reg_room");

        // Perform Alter table
        $db->query("
            ALTER TABLE room_registrations
            MODIFY room_id INT UNSIGNED NULL DEFAULT NULL,
            ADD COLUMN preferred_building_id INT UNSIGNED NULL DEFAULT NULL AFTER student_id,
            ADD COLUMN preferred_room_type VARCHAR(50) NULL DEFAULT NULL AFTER preferred_building_id,
            ADD COLUMN assigned_room_id INT UNSIGNED NULL DEFAULT NULL AFTER preferred_room_type,
            ADD CONSTRAINT fk_reg_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON UPDATE CASCADE ON DELETE SET NULL,
            ADD CONSTRAINT fk_reg_pref_building FOREIGN KEY (preferred_building_id) REFERENCES buildings(id) ON UPDATE CASCADE ON DELETE SET NULL,
            ADD CONSTRAINT fk_reg_assigned_room FOREIGN KEY (assigned_room_id) REFERENCES rooms(id) ON UPDATE CASCADE ON DELETE SET NULL
        ");
        
        echo "✅ Table room_registrations altered successfully!\n";
    } else {
        echo "⚡ Table room_registrations already altered.\n";
    }

    // 2. Make sure seed users have the correct password hashes
    echo "Updating seed users' password hashes...\n";
    $db->query("UPDATE users SET password_hash = ? WHERE username = ?", ['$2y$12$YcUa8SlifN4ZjfKe9bkfDecuhJcwggsEk0Ncer.F9VTt0ffoRpnHC', 'admin']);
    $db->query("UPDATE users SET password_hash = ? WHERE role = ? AND username LIKE 'sv%'", ['$2y$12$P3odYaHQhYx4uflwHDtV8e.FQWoDdbP.sQZJwyS7WL3yTTcyj9CXq', 'student']);
    echo "✅ Password hashes updated successfully!\n";

    echo "✅ Database schema alignment complete!\n";
} catch (\Throwable $e) {
    echo "❌ Error migrating database: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

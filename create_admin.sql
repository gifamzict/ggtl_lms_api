INSERT INTO users (name, email, password, role, phone, bio, created_at, updated_at)
VALUES (
    'Admin User',
    'admin@ggtl.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    '+1234567890',
    'System Administrator',
    NOW(),
    NOW()
);

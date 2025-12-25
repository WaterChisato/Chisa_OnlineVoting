<?php
// config.php
session_start();

// 使用您提供的数据库账号密码
$host = 'localhost';
$dbname = 'serula3hqa8nv'; // 数据库名
$username = 'serula3hqa8nv'; // 数据库账号
$password = '3X7L51sLn8h6'; // 数据库密码

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建表结构（如果不存在）
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        question_text VARCHAR(500) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INT(11) NOT NULL AUTO_INCREMENT,
        question_id INT(11) NOT NULL,
        option_text VARCHAR(100) NOT NULL,
        option_color VARCHAR(7) NOT NULL,
        vote_count INT(11) DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS vote_records (
        id INT(11) NOT NULL AUTO_INCREMENT,
        question_id INT(11) NOT NULL,
        voter_ip VARCHAR(45) NOT NULL,
        voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    // 检查是否已有管理员，没有则创建默认账户（密码114514）
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('114514', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admin_users (username, password) VALUES ('admin', '$hashed_password')");
    }
    
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

date_default_timezone_set('Asia/Shanghai');
?>

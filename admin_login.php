<?php
// admin_login.php
require_once 'config.php';
// 处理退出登录
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
// 如果已登录，重定向到管理页面
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: admin.php');
        exit;
    } else {
        $error = "用户名或密码错误！";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理员登录 - 投票系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Microsoft YaHei', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; justify-content: center; align-items: center;
        }
        .login-container { 
            background: white; padding: 40px; border-radius: 10px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px;
        }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { 
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px;
            font-size: 16px; transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: #667eea; }
        button { 
            width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 12px; border: none; border-radius: 5px; 
            font-size: 16px; cursor: pointer; transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .error { 
            color: #e74c3c; background: #ffeaea; padding: 10px; border-radius: 5px;
            border-left: 4px solid #e74c3c; margin-bottom: 15px; text-align: center;
        }
        .default-info { 
            background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 5px;
            padding: 10px; margin-top: 20px; font-size: 14px; color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>管理员登录</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名：</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label for="password">密码：</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">登录</button>
        </form>
        <div class="default-info">
            <strong>默认登录信息：</strong><br>
            用户名: admin<br>
            密码: 114514
        </div>
    </div>
</body>
</html>

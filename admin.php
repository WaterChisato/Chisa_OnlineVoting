<?php
// admin.php - 管理员页面（包含账户设置功能）
// 重构管理界面，支持更改用户名
require_once 'config.php';

// 检查管理员登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$success_msg = "";
$error_msg = "";
$account_success = "";
$account_error = "";

// 处理添加/删除投票问题的逻辑（原有功能）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 添加新投票问题
    if (isset($_POST['add_question'])) {
        $questionText = trim($_POST['question_text']);
        if (!empty($questionText)) {
            $stmt = $pdo->prepare("INSERT INTO questions (question_text) VALUES (?)");
            $stmt->execute([$questionText]);
            $questionId = $pdo->lastInsertId();
            
            // 自动添加选项（拒绝-蓝色，同意-红色）
            $options = [
                ['拒绝', '#0000FF'],  // 蓝色
                ['同意', '#FF0000']   // 红色
            ];
            
            foreach ($options as $option) {
                $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, option_color) VALUES (?, ?, ?)");
                $stmt->execute([$questionId, $option[0], $option[1]]);
            }
            
            $success_msg = "投票问题添加成功！";
        }
    }
    // 删除投票问题
    elseif (isset($_POST['delete_question'])) {
        $questionId = $_POST['question_id'];
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        if ($stmt->execute([$questionId])) {
            $success_msg = "投票问题删除成功！";
        } else {
            $error_msg = "删除失败！";
        }
    }
    // 修改管理员账户信息（新增功能）
    elseif (isset($_POST['update_admin_account'])) {
        $current_password = $_POST['current_password'];
        $new_username = trim($_POST['new_username']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $admin_id = $_SESSION['admin_id'];

        // 基础验证
        if (empty($current_password)) {
            $account_error = "必须输入当前密码进行身份验证。";
        } else {
            try {
                // 验证当前密码是否正确
                $stmt = $pdo->prepare("SELECT username, password FROM admin_users WHERE id = ?");
                $stmt->execute([$admin_id]);
                $current_admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$current_admin || !password_verify($current_password, $current_admin['password'])) {
                    $account_error = "当前密码错误！";
                } else {
                    // 准备动态构建更新SQL的语句和参数
                    $update_fields = [];
                    $update_data = [];
                    
                    // 处理用户名的修改
                    if (!empty($new_username) && $new_username !== $current_admin['username']) {
                        // 检查新用户名是否已被其他管理员占用
                        $check_username_stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
                        $check_username_stmt->execute([$new_username, $admin_id]);
                        if ($check_username_stmt->fetch()) {
                            $account_error = "用户名 '".htmlspecialchars($new_username)."' 已被占用，请更换。";
                        } else {
                            $update_fields[] = "username = ?";
                            $update_data[] = $new_username;
                            // 更新会话中的用户名
                            $_SESSION['admin_username'] = $new_username;
                        }
                    }
                    
                    // 处理密码的修改
                    if (!empty($new_password)) {
                        if ($new_password !== $confirm_password) {
                            $account_error = "新密码与确认密码不一致。";
                        } else {
                            $update_fields[] = "password = ?";
                            $update_data[] = password_hash($new_password, PASSWORD_DEFAULT);
                        }
                    }
                    
                    // 执行更新（如果没有错误，并且有字段需要更新）
                    if (empty($account_error)) {
                        if (!empty($update_fields)) {
                            $update_data[] = $admin_id;
                            $sql = "UPDATE admin_users SET " . implode(", ", $update_fields) . " WHERE id = ?";
                            $update_stmt = $pdo->prepare($sql);
                            
                            if ($update_stmt->execute($update_data)) {
                                $account_success = "账户信息更新成功！";
                            } else {
                                $account_error = "更新失败，请重试。";
                            }
                        } else {
                            $account_error = "未检测到任何更改。";
                        }
                    }
                }
            } catch (PDOException $e) {
                $account_error = "系统错误，操作失败。";
            }
        }
    }
}

// 获取当前管理员信息
$stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
$current_username = $admin_info['username'];

// 获取所有投票问题
$questions = $pdo->query("SELECT * FROM questions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>投票管理页面</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Microsoft YaHei', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .tab-container { margin-bottom: 20px; }
        .tabs { 
            display: flex; 
            border-bottom: 1px solid #ddd; 
            background: #f9f9f9;
            border-radius: 5px 5px 0 0;
        }
        .tab { 
            padding: 12px 20px; 
            cursor: pointer; 
            border: 1px solid transparent; 
            margin-right: 5px; 
            background: #f0f0f0;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
        }
        .tab.active { 
            background: white; 
            border-color: #ddd #ddd white #ddd; 
            border-bottom: none;
            font-weight: bold;
            color: #4CAF50;
        }
        .tab-content { 
            display: none; 
            padding: 20px; 
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            background: white;
        }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], textarea, input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box;
            font-size: 16px;
        }
        input:focus, textarea:focus { 
            outline: none; 
            border-color: #4CAF50; 
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        button { 
            background: #4CAF50; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover { background: #45a049; }
        .question-item { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 4px;
            background: #f9f9f9;
        }
        .success { 
            color: #155724; 
            background: #d4edda; 
            padding: 12px; 
            border-radius: 4px; 
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        .error { 
            color: #721c24; 
            background: #f8d7da; 
            padding: 12px; 
            border-radius: 4px; 
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .current-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #333;
            border-left: 4px solid #4CAF50;
            padding-left: 10px;
            margin: 25px 0 15px 0;
        }
        .nav-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .nav-links a {
            color: #666;
            text-decoration: none;
            margin: 0 10px;
        }
        .nav-links a:hover {
            text-decoration: underline;
            color: #333;
        }
        .account-form {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>投票管理页面</h1>
            <div>
                欢迎，<?php echo $_SESSION['admin_username']; ?>！
                <a href="admin_login.php?logout=1" style="margin-left: 15px; color: #666;">退出登录</a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('manage')">投票管理</div>
                <div class="tab" onclick="switchTab('account')">账户设置</div>
            </div>
            
            <!-- 投票管理标签页 -->
            <div id="manage" class="tab-content active">
                <h2>添加新投票</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="question_text">投票问题：</label>
                        <textarea id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <p><strong>选项将自动创建：</strong></p>
                        <ul>
                            <li style="color: #0000FF; font-weight: bold;">拒绝（蓝色）</li>
                            <li style="color: #FF0000; font-weight: bold;">同意（红色）</li>
                        </ul>
                    </div>
                    <button type="submit" name="add_question">添加投票问题</button>
                </form>
                
                <h2>现有投票问题</h2>
                <?php foreach ($questions as $question): 
                    $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                    $stmt->execute([$question['id']]);
                    $questionOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                    <div class="question-item">
                        <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                        <p>创建时间: <?php echo $question['created_at']; ?></p>
                        <p>选项: 
                            <?php foreach ($questionOptions as $opt): ?>
                                <span style="color: <?php echo $opt['option_color']; ?>; font-weight: bold; margin-right: 15px;">
                                    <?php echo $opt['option_text']; ?> (<?php echo $opt['vote_count']; ?>票)
                                </span>
                            <?php endforeach; ?>
                        </p>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                            <button type="submit" name="delete_question" onclick="return confirm('确定删除这个投票问题吗？')">删除</button>
                        </form>
                        <a href="index.php?question_id=<?php echo $question['id']; ?>" target="_blank" style="margin-left: 10px;">查看投票页面</a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 账户设置标签页 -->
            <div id="account" class="tab-content">
                <h2>管理员账户设置</h2>
                
                <?php if ($account_success): ?>
                    <div class="success"><?php echo $account_success; ?></div>
                <?php endif; ?>
                
                <?php if ($account_error): ?>
                    <div class="error"><?php echo $account_error; ?></div>
                <?php endif; ?>
                
                <div class="current-info">
                    <strong>当前账户信息：</strong><br>
                    用户名：<?php echo htmlspecialchars($current_username); ?><br>
                    最后登录时间：<?php echo date('Y-m-d H:i:s'); ?>
                </div>
                
                <form method="POST" class="account-form">
                    <h3 class="section-title">修改账户信息</h3>
                    
                    <div class="form-group">
                        <label for="new_username">新用户名</label>
                        <input type="text" id="new_username" name="new_username" 
                               placeholder="留空则不修改用户名" 
                               value="<?php echo htmlspecialchars($_POST['new_username'] ?? ''); ?>">
                        <small style="color: #666; font-size: 0.9em;">如需修改用户名，请在此输入新的用户名</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="留空则不修改密码">
                        <small style="color: #666; font-size: 0.9em;">如需修改密码，请在此输入新密码</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认新密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="再次输入新密码">
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">* 当前密码</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="请输入当前密码以验证身份" required>
                        <small style="color: #666; font-size: 0.9em;">修改账户信息前必须验证您的身份</small>
                    </div>
                    
                    <button type="submit" name="update_admin_account" style="width: 100%;">更新账户信息</button>
                </form>
                
                <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <h4>安全提示：</h4>
                    <ul style="color: #666; line-height: 1.6;">
                        <li>定期修改密码可以提高账户安全性</li>
                        <li>建议使用包含字母、数字和特殊字符的复杂密码</li>
                        <li>修改用户名后，请使用新用户名登录系统</li>
                        <li>如有任何异常情况，请及时检查账户设置</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="index.php" target="_blank">查看投票页面</a> | 
            <a href="admin_login.php?logout=1">退出登录</a>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // 隐藏所有标签内容
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            // 移除所有标签的活动状态
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            // 显示选中的标签内容
            document.getElementById(tabName).classList.add('active');
            // 设置当前标签为活动状态
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>

<?php
// admin.php
require_once 'config.php';

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// 处理各种操作
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 修改密码
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($currentPassword, $admin['password'])) {
            if ($newPassword === $confirmPassword) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                if ($stmt->execute([$newPasswordHash, $_SESSION['admin_id']])) {
                    $success = "密码修改成功！";
                } else {
                    $error = "密码修改失败！";
                }
            } else {
                $error = "新密码和确认密码不一致！";
            }
        } else {
            $error = "当前密码错误！";
        }
    }
    // 添加投票问题
    elseif (isset($_POST['add_question'])) {
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
            
            $success = "投票问题添加成功！";
        }
    }
    // 删除投票问题
    elseif (isset($_POST['delete_question'])) {
        $questionId = $_POST['question_id'];
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        if ($stmt->execute([$questionId])) {
            $success = "投票问题删除成功！";
        } else {
            $error = "删除失败！";
        }
    }
}

// 获取所有投票问题
$questions = $pdo->query("SELECT * FROM questions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>投票管理页面</title>
    <style>
        body { 
            font-family: 'Microsoft YaHei', sans-serif; 
            margin: 0; padding: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 1000px; margin: 0 auto; 
            background: white; padding: 20px; 
            border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;
        }
        .tab-container { margin-bottom: 20px; }
        .tabs { display: flex; border-bottom: 1px solid #ddd; }
        .tab { padding: 10px 20px; cursor: pointer; border: 1px solid transparent; margin-right: 5px; }
        .tab.active { background: #f0f0f0; border-color: #ddd; border-bottom: none; }
        .tab-content { display: none; padding: 20px 0; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; 
            border-radius: 4px; box-sizing: border-box;
        }
        button { 
            background: #4CAF50; color: white; padding: 10px 15px; 
            border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;
        }
        button:hover { background: #45a049; }
        .question-item { 
            border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; 
            border-radius: 4px; background: #f9f9f9;
        }
        .success { color: green; background: #f0ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>投票管理页面</h1>
            <div>欢迎，<?php echo $_SESSION['admin_username']; ?>！ 
                <a href="admin_login.php?logout=1" style="margin-left: 15px; color: #666;">退出登录</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('manage')">投票管理</div>
                <div class="tab" onclick="switchTab('password')">修改密码</div>
            </div>
            
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
            
            <div id="password" class="tab-content">
                <h2>修改密码</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">当前密码：</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">新密码：</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认新密码：</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password">修改密码</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>

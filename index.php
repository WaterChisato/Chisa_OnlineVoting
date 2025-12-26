<?php
// index.php
require_once 'config.php';

// 获取当前投票问题
$questionId = $_GET['question_id'] ?? null;
if ($questionId) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND is_active = 1");
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM questions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    $questionId = $question['id'] ?? null;
}

// 获取选项
$options = [];
if ($questionId) {
    $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY id");
    $stmt->execute([$questionId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 处理投票
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && $questionId) {
    $optionId = $_POST['option_id'] ?? null;
    $voterIp = $_SERVER['REMOTE_ADDR'];
    
    // 检查是否已投过票
    $stmt = $pdo->prepare("SELECT id FROM vote_records WHERE question_id = ? AND voter_ip = ?");
    $stmt->execute([$questionId, $voterIp]);
    
    if ($stmt->fetch()) {
        $error = "您已经投过票了！";
    } elseif (!$optionId) {
        $error = "请选择一个选项！";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 更新票数
            $stmt = $pdo->prepare("UPDATE options SET vote_count = vote_count + 1 WHERE id = ?");
            $stmt->execute([$optionId]);
            
            // 记录投票IP
            $stmt = $pdo->prepare("INSERT INTO vote_records (question_id, voter_ip) VALUES (?, ?)");
            $stmt->execute([$questionId, $voterIp]);
            
            $pdo->commit();
            $success = "投票成功！感谢您的参与。";
            
            // 刷新选项数据
            $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY id");
            $stmt->execute([$questionId]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "投票失败，请重试！";
        }
    }
}

// 获取所有投票问题（用于导航）
$allQuestions = $pdo->query("SELECT id, question_text FROM questions WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>在线投票系统</title>
    <style>
        body {
            background: url('https://logo.kf.waterchisato.top/tybj.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Microsoft YaHei', sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .question-nav {
            margin-bottom: 20px;
            background: rgba(255,255,255,0.9);
            padding: 15px;
            border-radius: 5px;
        }
        .question-nav select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .question-text {
            font-size: 1.3em;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-left: 5px solid #4CAF50;
            border-radius: 5px;
        }
        .option-label {
            display: block;
            margin: 15px 0;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1em;
        }
        .option-label:hover {
            background-color: #f8f9fa;
            border-color: #aaa;
            transform: translateY(-2px);
        }
        .option-label input {
            margin-right: 15px;
            transform: scale(1.2);
        }
        .vote-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .result-bar {
            height: 35px;
            margin: 15px 0;
            border-radius: 5px;
            position: relative;
            background-color: #f0f0f0;
            overflow: hidden;
        }
        .result-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s;
            display: flex;
            align-items: center;
            padding: 0 15px;
            color: white;
            font-weight: bold;
            min-width: fit-content;
        }
        .total-votes {
            text-align: center;
            margin: 25px 0;
            font-style: italic;
            color: #666;
            font-size: 1.1em;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .error {
            color: #e74c3c;
            background: #ffeaea;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #e74c3c;
            margin-bottom: 15px;
        }
        .success {
            color: #27ae60;
            background: #eaffea;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #27ae60;
            margin-bottom: 15px;
        }
        .admin-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .admin-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
        }
        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>在线投票系统</h1>
        
        <?php if (!empty($allQuestions)): ?>
        <div class="question-nav">
            <label for="question_select">选择投票问题：</label>
            <select id="question_select" onchange="location.href='index.php?question_id='+this.value;">
                <option value="">-- 请选择投票问题 --</option>
                <?php foreach ($allQuestions as $q): ?>
                    <option value="<?php echo $q['id']; ?>" <?php echo $q['id'] == $questionId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($question && !empty($options)): ?>
            <div class="question-text">
                <?php echo htmlspecialchars($question['question_text']); ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="question_id" value="<?php echo $questionId; ?>">
                
                <?php foreach ($options as $option): ?>
                    <label class="option-label">
                        <input type="radio" name="option_id" value="<?php echo $option['id']; ?>" required>
                        <span style="color: <?php echo $option['option_color']; ?>; font-weight: bold;">
                            <?php echo htmlspecialchars($option['option_text']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
                
                <button type="submit" name="vote" class="vote-btn">提交投票</button>
            </form>
            
            <?php
            $totalVotes = 0;
            foreach ($options as $option) {
                $totalVotes += $option['vote_count'];
            }
            ?>
            
            <div class="total-votes">
                总投票数: <?php echo $totalVotes; ?>
            </div>
            
            <h3>当前投票结果：</h3>
            <?php foreach ($options as $option): 
                $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 1) : 0;
            ?>
                <div>
                    <strong style="color: <?php echo $option['option_color']; ?>">
                        <?php echo htmlspecialchars($option['option_text']); ?>:
                    </strong>
                    <?php echo $option['vote_count']; ?> 票 (<?php echo $percentage; ?>%)
                </div>
                <div class="result-bar">
                    <div class="result-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $option['option_color']; ?>;">
                        <?php if ($percentage > 10): ?>
                            <?php echo $percentage; ?>%
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <p style="font-size: 1.2em;">暂无活跃的投票问题。</p>
                <p><a href="admin_login.php">管理员登录</a> 创建新的投票</p>
            </div>
        <?php endif; ?>
        
        <div class="admin-link">
            <a href="admin_login.php">管理员入口</a>
        </div>
    </div>
    <!-- 网站版权信息 -->
<footer style="
    text-align: center; 
    margin-top: 40px; 
    padding: 20px; 
    background-color: rgba(255, 255, 255, 0.9); 
    border-top: 1px solid #ddd;
    font-size: 14px;
    color: #666;
">
    <p>此网站由水喝千束制作，已开源，链接点击 
        <a href="https://github.com/WaterChisato/Chisa_OnlineVoting/" 
           target="_blank" 
           style="color: #0366d6; text-decoration: none; font-weight: bold;">
           这里
        </a>
    </p>
</footer>

</body>
</html>

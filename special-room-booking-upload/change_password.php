<?php
require __DIR__ . '/bootstrap.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post(); verify_csrf();
    $current = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($new) < 10 || $new !== $confirm) { flash('error', '새 비밀번호는 10자 이상이며 확인 값과 같아야 합니다.'); }
    else {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? AND active = 1'); $stmt->execute([current_user()['id']]); $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) { flash('error', '현재 비밀번호가 맞지 않습니다.'); }
        else { db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), current_user()['id']]); $_SESSION['user']['must_change_password'] = false; flash('success', '비밀번호를 변경했습니다.'); redirect('dashboard.php'); }
    }
}
layout_start('비밀번호 변경');
?><section class="card narrow"><h1>비밀번호 변경</h1><form method="post"><label>현재 비밀번호<input type="password" name="current_password" required></label><label>새 비밀번호 (10자 이상)<input type="password" name="new_password" minlength="10" required></label><label>새 비밀번호 확인<input type="password" name="confirm_password" minlength="10" required></label><?= csrf_field() ?><button>변경</button></form></section><?php layout_end();

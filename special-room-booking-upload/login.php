<?php
require __DIR__ . '/bootstrap.php';
if (current_user()) { redirect('dashboard.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id, username, full_name, password_hash, role, must_change_password FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'full_name' => $user['full_name'], 'role' => $user['role'], 'must_change_password' => (bool)$user['must_change_password']];
        redirect($user['must_change_password'] ? 'change_password.php' : 'dashboard.php');
    }
    flash('error', '아이디 또는 비밀번호가 올바르지 않습니다.');
}
layout_start('로그인');
?><section class="card narrow"><h1>교사 로그인</h1><form method="post"><label>아이디<input name="username" required autocomplete="username"></label><label>비밀번호<input type="password" name="password" required autocomplete="current-password"></label><?= csrf_field() ?><button>로그인</button></form></section><?php layout_end();

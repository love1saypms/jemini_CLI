<?php
declare(strict_types=1);

/* Temporary one-time administrator recovery page. Delete after successful use. */
require __DIR__ . '/bootstrap.php';

const RESET_KEY_HASH = '00f428d32843766145e3dfa89d88f73baa8ec9630a1e4765990b5981b7e3bab7';
$key = $_GET['key'] ?? '';
if (!is_string($key) || !hash_equals(RESET_KEY_HASH, hash('sha256', $key))) {
    http_response_code(403);
    exit('접근이 거부되었습니다.');
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($password) < 10 || $password !== $confirm) {
        $error = '새 비밀번호는 10자 이상이며 확인 값과 같아야 합니다.';
    } else {
        $stmt = db()->prepare("UPDATE users SET password_hash = ?, must_change_password = 0, active = 1 WHERE username = 'admin' AND must_change_password = 1");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT)]);
        if ($stmt->rowCount() === 1) {
            $message = '관리자 비밀번호를 재설정했습니다. login.php에서 새 비밀번호로 로그인한 뒤 이 reset-admin.php 파일을 즉시 삭제하세요.';
        } else {
            $error = '이미 복구가 완료됐거나 admin 계정이 없습니다. 이 파일을 삭제하세요.';
        }
    }
}
layout_start('관리자 비밀번호 복구');
?>
<section class="card narrow">
  <h1>관리자 비밀번호 복구</h1>
  <?php if ($message): ?><p class="flash success"><?= e($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="flash error"><?= e($error) ?></p><?php endif; ?>
  <?php if (!$message): ?>
  <form method="post">
    <label>새 관리자 비밀번호 (10자 이상)<input type="password" name="password" minlength="10" required autofocus></label>
    <label>새 비밀번호 확인<input type="password" name="confirm_password" minlength="10" required></label>
    <?= csrf_field() ?>
    <button>관리자 비밀번호 재설정</button>
  </form>
  <?php endif; ?>
</section>
<?php layout_end();

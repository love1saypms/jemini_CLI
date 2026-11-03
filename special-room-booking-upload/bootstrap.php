<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secureCookie,
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO
{
    global $config;
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $db = $config['db'];
    if (str_starts_with((string)$db['user'], 'CHANGE_ME_') || str_starts_with((string)$db['pass'], 'CHANGE_ME_')) {
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><title>DB 설정 필요</title><h1>데이터베이스 설정이 필요합니다.</h1><p><code>config.php</code>의 DB 이름, 사용자명, 비밀번호를 웹호스팅 제어판의 실제 값으로 변경하세요.</p>');
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $error) {
        error_log('Special room booking DB connection failed: ' . $error->getMessage());
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><title>DB 연결 실패</title><h1>데이터베이스에 연결할 수 없습니다.</h1><p><code>config.php</code>의 DB 호스트, DB 이름, 사용자명, 비밀번호를 확인하세요. 상세 오류는 서버 오류 로그에만 기록됩니다.</p>');
    }
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function take_flashes(): array { $items = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $items; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void {
    if (!current_user()) { flash('error', '로그인이 필요합니다.'); redirect('login.php'); }
    $stmt = db()->prepare('SELECT active, role, full_name, must_change_password FROM users WHERE id = ?');
    $stmt->execute([current_user()['id']]); $fresh = $stmt->fetch();
    if (!$fresh || !(bool)$fresh['active']) { $_SESSION = []; session_destroy(); flash('error', '비활성화된 계정입니다.'); redirect('login.php'); }
    $_SESSION['user']['role'] = $fresh['role']; $_SESSION['user']['full_name'] = $fresh['full_name']; $_SESSION['user']['must_change_password'] = (bool)$fresh['must_change_password'];
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($_SESSION['user']['must_change_password'] && !in_array($script, ['change_password.php', 'logout.php'], true)) { flash('error', '초기 비밀번호를 변경해야 합니다.'); redirect('change_password.php'); }
}
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('관리자 권한이 필요합니다.'); } }
function require_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST 요청만 허용됩니다.'); } }
function csrf_token(): string { if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); } return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { $token = $_POST['csrf'] ?? ''; if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(400); exit('잘못된 요청입니다.'); } }
function valid_date(string $date): bool { $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d && $d->format('Y-m-d') === $date; }
function duplicate_error(PDOException $error): bool { return $error->getCode() === '23000'; }
function layout_start(string $title): void {
    global $config;
    $user = current_user();
    ?><!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#ffffff"><title><?= e($title) ?> | <?= e($config['app_name']) ?></title><link rel="stylesheet" href="assets/style.css"></head><body><div class="aurora aurora-one"></div><div class="aurora aurora-two"></div><header><a class="brand" href="dashboard.php"><span class="brand-mark" aria-hidden="true">✦</span><span><?= e($config['app_name']) ?></span></a><?php if ($user): ?><nav><span class="user-chip"><?= e($user['full_name']) ?>님</span><a href="dashboard.php">예약</a><?php if (is_admin()): ?><a href="admin.php">관리자</a><?php endif; ?><a href="change_password.php">비밀번호 변경</a><a class="signout" href="logout.php">로그아웃</a></nav><?php endif; ?></header><main><?php foreach (take_flashes() as $item): ?><p class="flash <?= e($item['type']) ?>"><?= e($item['message']) ?></p><?php endforeach; ?><?php
}
function layout_end(): void { ?></main></body></html><?php }





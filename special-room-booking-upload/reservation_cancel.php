<?php
require __DIR__ . '/bootstrap.php'; require_login(); require_post(); verify_csrf();
$id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT); if (!$id) { flash('error', '잘못된 예약입니다.'); redirect('dashboard.php'); }
$pdo = db(); $pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ? FOR UPDATE'); $stmt->execute([$id]); $reservation = $stmt->fetch();
    if (!$reservation || (!is_admin() && (int)$reservation['user_id'] !== (int)current_user()['id'])) { throw new RuntimeException('forbidden'); }
    $pdo->prepare('INSERT INTO reservation_audit (reservation_id, room_id, period_id, reservation_date, reserved_user_id, actor_user_id, action, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$reservation['id'], $reservation['room_id'], $reservation['period_id'], $reservation['reservation_date'], $reservation['user_id'], current_user()['id'], is_admin() ? 'admin_delete' : 'cancel', $reservation['purpose']]);
    $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$id]); $pdo->commit(); flash('success', '예약을 취소했습니다.');
} catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); } flash('error', '예약을 취소할 수 없습니다.'); }
redirect('dashboard.php');

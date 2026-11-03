<?php
require __DIR__ . '/bootstrap.php'; require_login(); require_post(); verify_csrf();
$date = (string)($_POST['reservation_date'] ?? ''); $roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT); $periodId = filter_input(INPUT_POST, 'period_id', FILTER_VALIDATE_INT); $purpose = trim((string)($_POST['purpose'] ?? ''));
if (!$roomId || !$periodId || !valid_date($date) || $date < date('Y-m-d') || $purpose === '') { flash('error', '예약 정보를 다시 확인하세요.'); redirect('dashboard.php?date=' . rawurlencode($date)); }
try {
    $stmt = db()->prepare('INSERT INTO reservations (room_id, period_id, reservation_date, user_id, purpose) SELECT r.id, p.id, ?, ?, ? FROM rooms r CROSS JOIN periods p WHERE r.id = ? AND r.active = 1 AND p.id = ? AND p.active = 1');
    $stmt->execute([$date, current_user()['id'], $purpose, $roomId, $periodId]);
    if ($stmt->rowCount() !== 1) { throw new RuntimeException('Invalid room or period'); }
    flash('success', '예약했습니다.');
} catch (PDOException $error) { if (duplicate_error($error)) { flash('error', '이미 다른 교사가 예약한 시간입니다.'); } else { error_log($error->getMessage()); flash('error', '예약 처리 중 오류가 발생했습니다.'); } }
catch (RuntimeException $error) { flash('error', '사용할 수 없는 특별실 또는 교시입니다.'); }
redirect('dashboard.php?date=' . rawurlencode($date));

<?php
header('Content-Type: application/json');
define("ACCESS",true);
include_once '_config.php';
$conn = connDB();

$room_id = trim($_GET['room_id'] ?? 'default');
$limit = 20;

if (empty($room_id) || strlen($room_id) > 50) {
    echo json_encode(['success' => false, 'error' => 'Room ID Invalid!']);
    exit;
}

$stmt = mysqli_prepare($conn, 
    "SELECT id, room_id, username, message, timestamp 
     FROM chat_messages 
     WHERE room_id = ? 
     ORDER BY timestamp DESC 
     LIMIT ?"
);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $room_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'room_id' => $room_id,
        'messages' => $messages,
        'count' => count($messages)
    ]);

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Error prepare: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
<?php
header('Content-Type: application/json');
define("ACCESS",true);
include_once '_config.php';
$conn = connDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = trim($_POST['room_id'] ?? 'default');
    $username = trim($_POST['username'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($username) || empty($message) || empty($room_id)) {
        echo json_encode(['success' => false, 'error' => 'Username, message or room_id empty!']);
        exit;
    }

    if (strlen($message) > 200) {
        echo json_encode(['success' => false, 'error' => 'The message is too long (200 character limit)!']);
        exit;
    }

    if (strlen($room_id) > 50 || strlen($room_id) < 1) {
        echo json_encode(['success' => false, 'error' => 'Room ID Invalid (1-50 character)!']);
        exit;
    }

    // Normalize message to check spam: trim + lowercase
    $norm_message = strtolower(trim($message));

    // CHECK SPAM:
    $check_stmt = mysqli_prepare($conn, 
        "SELECT message FROM chat_messages 
         WHERE room_id = ? 
         ORDER BY timestamp DESC 
         LIMIT 20"
    );
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "s", $room_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $existing_messages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $norm_existing = strtolower(trim($row['message']));
            if (!empty($norm_existing)) {
                $existing_messages[] = $norm_existing;
            }
        }
        mysqli_stmt_close($check_stmt);

        // Check spam: normalize OR Levenshtein <= 2
        $spam_threshold = 2;
        foreach ($existing_messages as $existing) {
            if ($existing === $norm_message) {
                echo json_encode(['success' => false, 'error' => 'Spam detected! Duplicate messages!']);
                mysqli_close($conn);
                exit;
            }
            if (levenshtein($existing, $norm_message) <= $spam_threshold) {
                echo json_encode(['success' => false, 'error' => 'Spam detected! The message is too similar (variant)!']);
                mysqli_close($conn);
                exit;
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error check spam: ' . mysqli_error($conn)]);
        mysqli_close($conn);
        exit;
    }

    // Delete old messages (keep the 100 most recent messages for each room ID)
    $stmt = mysqli_prepare($conn, "INSERT INTO chat_messages (room_id, username, message) VALUES (?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $room_id, $username, $message);
        if (mysqli_stmt_execute($stmt)) {           
            $delete_stmt = mysqli_prepare($conn, 
                "DELETE FROM chat_messages 
                 WHERE room_id = ? AND id NOT IN (
                     SELECT id FROM (
                         SELECT id FROM chat_messages WHERE room_id = ? ORDER BY timestamp DESC LIMIT 100
                     ) AS temp
                 )"
            );
            if ($delete_stmt) {
                mysqli_stmt_bind_param($delete_stmt, "ss", $room_id, $room_id);
                mysqli_stmt_execute($delete_stmt);
                mysqli_stmt_close($delete_stmt);
            }
            echo json_encode(['success' => true, 'message' => 'Successfully sent to the room ' . $room_id . '!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error insert: ' . mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error prepare: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'POST only!']);
}

mysqli_close($conn);
?>
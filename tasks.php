<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";  // パスワードなし
$dbname = "todo_app";

// データベース接続
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// タスク追加処理
if (isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $description);

    if ($stmt->execute()) {
        header("Location: tasks.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// タスク編集処理
if (isset($_POST['edit_task'])) {
    $task_id = $_POST['task_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, is_completed = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssiii", $title, $description, $is_completed, $task_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        header("Location: tasks.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// タスク削除処理
if (isset($_GET['delete_task'])) {
    $task_id = $_GET['delete_task'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        header("Location: tasks.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// タスクの取得
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, title, description, is_completed FROM tasks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Tasks</title>
</head>
<body>
    <h1>My Tasks</h1>

    <form method="post">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required>
        <br>
        <label for="description">Description:</label>
        <textarea id="description" name="description"></textarea>
        <br>
        <button type="submit" name="add_task">Add Task</button>
    </form>

    <h2>Task List</h2>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <p>Status: <?php echo $row['is_completed'] ? 'Completed' : 'Not completed'; ?></p>
                <a href="edit_task.php?id=<?php echo $row['id']; ?>">Edit</a>
                <a href="tasks.php?delete_task=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
            </li>
        <?php endwhile; ?>
    </ul>

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>

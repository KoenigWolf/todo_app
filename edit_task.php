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

$task_id = $_GET['id'];

// タスクの取得
$stmt = $conn->prepare("SELECT title, description, is_completed FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($title, $description, $is_completed);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Task</title>
</head>
<body>
    <h1>Edit Task</h1>
    <form method="post" action="tasks.php">
        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        <br>
        <label for="description">Description:</label>
        <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
        <br>
        <label for="is_completed">Completed:</label>
        <input type="checkbox" id="is_completed" name="is_completed" <?php echo $is_completed ? 'checked' : ''; ?>>
        <br>
        <button type="submit" name="edit_task">Update Task</button>
    </form>
</body>
</html>

<?php
$conn->close();
?>

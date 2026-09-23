<?php
require_once '../config.php';
login_required('user');

$user_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

/* Return Book */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $issue_id = (int)$_POST['return_id'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "SELECT id, book_id, due_date, status
             FROM issues
             WHERE id=? AND user_id=? AND status IN ('Issued','Overdue')
             FOR UPDATE"
        );
        $stmt->bind_param('ii', $issue_id, $user_id);
        $stmt->execute();
        $issue = $stmt->get_result()->fetch_assoc();

        if (!$issue) {
            throw new Exception('This book is already returned or the record was not found.');
        }

        $today = new DateTime(date('Y-m-d'));
        $due = new DateTime($issue['due_date']);
        $late_days = max(0, (int)$due->diff($today)->days);

        if ($today < $due) {
            $late_days = 0;
        }

        $fine = $late_days * 10;
        $return_date = date('Y-m-d');

        $update = $conn->prepare(
            "UPDATE issues
             SET return_date=?, status='Returned', fine=?
             WHERE id=? AND user_id=?"
        );
        $update->bind_param('sdii', $return_date, $fine, $issue_id, $user_id);
        $update->execute();

        if ($update->affected_rows !== 1) {
            throw new Exception('Unable to return the book.');
        }

        $book = $conn->prepare(
            "UPDATE books SET available=LEAST(quantity, available+1) WHERE id=?"
        );
        $book->bind_param('i', $issue['book_id']);
        $book->execute();

        $conn->commit();

        $message = $fine > 0
            ? "Book returned successfully! Fine: ₹" . number_format($fine, 2)
            : "Book returned successfully! No fine.";
    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

/* Mark old books as overdue for display */
mysqli_query(
    $conn,
    "UPDATE issues
     SET status='Overdue'
     WHERE user_id=$user_id
       AND status='Issued'
       AND due_date < CURDATE()"
);

$rows = mysqli_query(
    $conn,
    "SELECT i.*, b.title, b.author
     FROM issues i
     JOIN books b ON i.book_id=b.id
     WHERE i.user_id=$user_id
     ORDER BY i.id DESC"
);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Books - BookHub</title>
    <link rel="stylesheet" href="../assets/css/user/sidebar.css">
    <link rel="stylesheet" href="../assets/css/user/my_books.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>

    <main class="main">
        <h1>📖 My Books</h1>
        <p class="subtitle">Borrowed books, due dates and return options</p>

        <?php if ($message): ?>
            <div class="success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <table>
                <tr>
                    <th>Book</th>
                    <th>Author</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Fine</th>
                    <th>Action</th>
                </tr>

                <?php if (mysqli_num_rows($rows) === 0): ?>
                    <tr>
                        <td colspan="8" class="empty">
                            You have not borrowed any book yet.
                            <a href="books.php">Borrow a Book</a>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                    <tr>
                        <td><strong><?= e($r['title']) ?></strong></td>
                        <td><?= e($r['author']) ?></td>
                        <td><?= e($r['issue_date']) ?></td>
                        <td><?= e($r['due_date']) ?></td>
                        <td><?= e($r['return_date'] ?: '-') ?></td>
                        <td>
                            <span class="status <?= strtolower(e($r['status'])) ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td>₹<?= number_format((float)$r['fine'], 2) ?></td>
                        <td>
                            <?php if (in_array($r['status'], ['Issued', 'Overdue'], true)): ?>
                                <form method="post" class="return-form"
                                      onsubmit="return confirm('Are you sure you want to return this book?');">
                                    <input type="hidden" name="return_id" value="<?= (int)$r['id'] ?>">
                                    <button class="return-btn" type="submit">↩ Return Book</button>
                                </form>
                            <?php else: ?>
                                <span class="returned">✓ Returned</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>

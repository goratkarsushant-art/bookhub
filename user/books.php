<?php
require_once '../config.php';
login_required('user');

$message = '';
$error = '';
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $book_id = (int)$_POST['borrow_id'];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id, title, available FROM books WHERE id=? FOR UPDATE");
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if (!$book) throw new Exception('Book not found.');
        if ((int)$book['available'] <= 0) throw new Exception('This book is currently not available.');

        $check = $conn->prepare("SELECT id FROM issues WHERE user_id=? AND book_id=? AND status IN ('Issued','Overdue') LIMIT 1");
        $check->bind_param('ii', $user_id, $book_id);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) throw new Exception('You already have this book borrowed.');

        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+7 days'));
        $ins = $conn->prepare("INSERT INTO issues(book_id,user_id,issue_date,due_date,status) VALUES(?,?,?,?, 'Issued')");
        $ins->bind_param('iiss', $book_id, $user_id, $issue_date, $due_date);
        $ins->execute();

        $upd = $conn->prepare("UPDATE books SET available=available-1 WHERE id=? AND available>0");
        $upd->bind_param('i', $book_id);
        $upd->execute();
        if ($upd->affected_rows !== 1) throw new Exception('Book availability changed. Please try again.');

        $conn->commit();
        $message = 'Book borrowed successfully! Due date: ' . $due_date;
    } catch (Throwable $ex) {
        $conn->rollback();
        $error = $ex->getMessage();
    }
}

$search = trim($_GET['search'] ?? '');
$like = '%' . $search . '%';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ? OR isbn LIKE ? ORDER BY title");
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result();
} else {
    $rows = mysqli_query($conn, 'SELECT * FROM books ORDER BY title');
}
?>
<!doctype html>
<html><head><title>Books - BookHub</title>
<link rel="stylesheet" href="../assets/css/user/sidebar.css">
<link rel="stylesheet" href="../assets/css/user/books.css"></head>
<body><div class="layout"><?php include 'sidebar.php'; ?><main class="main">
<h1>📚 Search & Borrow Books</h1>
<?php if ($message): ?><div class="success"><?=e($message)?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?=e($error)?></div><?php endif; ?>
<form class="search-form" method="get"><input type="text" name="search" value="<?=e($search)?>" placeholder="Search by book title, author, category or ISBN..."><button class="btn" type="submit">🔍 Search</button><?php if($search): ?><a class="clear" href="books.php">Clear</a><?php endif; ?></form>
<div class="panel"><table><tr><th>Title</th><th>Author</th><th>Category</th><th>Available</th><th>Action</th></tr>
<?php if(mysqli_num_rows($rows)===0): ?><tr><td colspan="5" class="empty">No books found.</td></tr><?php endif; ?>
<?php while($r=mysqli_fetch_assoc($rows)): ?><tr><td><strong><?=e($r['title'])?></strong></td><td><?=e($r['author'])?></td><td><?=e($r['category'])?></td><td><?= (int)$r['available'] ?></td><td><?php if((int)$r['available']>0): ?><form method="post" class="borrow-form"><input type="hidden" name="borrow_id" value="<?=$r['id']?>"><button class="btn borrow" type="submit">📖 Borrow Book</button></form><?php else: ?><span class="unavailable">Not Available</span><?php endif; ?></td></tr><?php endwhile; ?></table></div>
</main></div></body></html>

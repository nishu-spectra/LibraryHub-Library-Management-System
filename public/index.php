<?php
require_once __DIR__ . '/../app/layout.php'; require_login();
$pdo=db();
$stats = [
 'Titles' => (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
 'Physical copies' => (int)$pdo->query('SELECT COUNT(*) FROM book_copies WHERE status <> "withdrawn"')->fetchColumn(),
 'Available copies' => (int)$pdo->query('SELECT COUNT(*) FROM book_copies WHERE status="available"')->fetchColumn(),
 'Currently issued' => (int)$pdo->query('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL')->fetchColumn(),
 'Public members' => (int)$pdo->query('SELECT COUNT(*) FROM members WHERE status="active"')->fetchColumn(),
 'Overdue loans' => (int)$pdo->query('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND due_at < CURDATE()')->fetchColumn(),
];
$recent=$pdo->query("SELECT l.id,l.issued_at,l.due_at,l.status,l.returned_at,b.title,m.full_name FROM loans l JOIN book_copies c ON c.id=l.copy_id JOIN books b ON b.id=c.book_id JOIN members m ON m.id=l.member_id ORDER BY l.id DESC LIMIT 7")->fetchAll();
page_start('Dashboard');
echo '<div class="grid stats">';
$icons=['Titles'=>'▤','Physical copies'=>'▣','Available copies'=>'✓','Currently issued'=>'↗','Public members'=>'♙','Overdue loans'=>'!'];
foreach($stats as $label=>$value) echo '<article class="card"><div class="stat-top"><span>'.e($label).'</span><span class="stat-icon">'.e($icons[$label]).'</span></div><div class="stat-value">'.number_format($value).'</div><div class="stat-foot">'.($label==='Overdue loans' && $value ? '<span style="color:#C43B57">Requires attention</span>' : 'Current database totals').'</div></article>';
echo '</div><div class="grid two-col"><section class="card"><div class="section-head"><div><h2>Recent circulation</h2><p>Latest book issue records</p></div><a class="btn btn-light" href="loans.php">View all</a></div><div class="table-wrap"><table><thead><tr><th>Book / member</th><th>Issue date</th><th>Due date</th><th>Status</th></tr></thead><tbody>';
if(!$recent) echo '<tr><td colspan="4" class="empty">No circulation activity yet. Issue your first book to get started.</td></tr>';
foreach($recent as $r){$overdue=!$r['returned_at'] && $r['due_at']<date('Y-m-d');$status=$r['status']==='returned'?'Returned':($overdue?'Overdue':'Issued');echo '<tr><td><span class="book-title">'.e($r['title']).'</span><span class="subtext">'.e($r['full_name']).'</span></td><td>'.e(substr($r['issued_at'],0,10)).'</td><td>'.e($r['due_at']).'</td><td><span class="badge '.strtolower($status).'">'.e($status).'</span></td></tr>';}
echo '</tbody></table></div></section><section class="card"><div class="section-head"><div><h2>Quick actions</h2><p>Common library tasks</p></div></div><div class="quick-actions"><a class="quick-action" href="books.php"><span>＋</span>Add a book<small>Catalogue and copies</small></a><a class="quick-action" href="members.php"><span>♙</span>Register member<small>Public membership</small></a><a class="quick-action" href="loans.php"><span>↔</span>Issue / return<small>Circulation desk</small></a><a class="quick-action" href="movements.php"><span>▣</span>Stock movement<small>Entry and exit register</small></a></div><div class="notice"><b>Fine policy:</b> <?= e(CURRENCY_CODE) ?> <?= number_format(DEFAULT_DAILY_FINE,2) ?> per overdue day. The rate is a starter setting in <code>app/config.php</code>; make it configurable from the admin interface in a later stage.</div></section></div>';
page_end();

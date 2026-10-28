<?php
require_once __DIR__ . '/../app/layout.php';require_login();$pdo=db();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();try{$action=$_POST['action']??'';
 if($action==='issue'){
  $member=(int)($_POST['member_id']??0);$copy=(int)($_POST['copy_id']??0);$due=(string)($_POST['due_at']??'');
  if(!$member||!$copy||!$due)throw new RuntimeException('Choose a member, an available copy and a due date.');
  $pdo->beginTransaction();
  $m=$pdo->prepare("SELECT * FROM members WHERE id=? AND status='active' AND (valid_until IS NULL OR valid_until>=CURDATE()) FOR UPDATE");$m->execute([$member]);if(!$m->fetch())throw new RuntimeException('Member is inactive, expired or does not exist.');
  $c=$pdo->prepare("SELECT id FROM book_copies WHERE id=? AND status='available' FOR UPDATE");$c->execute([$copy]);if(!$c->fetch())throw new RuntimeException('This copy is no longer available.');
  $active=$pdo->prepare('SELECT COUNT(*) FROM loans WHERE member_id=? AND returned_at IS NULL');$active->execute([$member]);if((int)$active->fetchColumn()>=5)throw new RuntimeException('Member has reached the starter borrowing limit of 5 active loans.');
  $s=$pdo->prepare("INSERT INTO loans(copy_id,member_id,issued_by,issued_at,due_at,status) VALUES(?,?,?,NOW(),?,'issued')");$s->execute([$copy,$member,(int)$_SESSION['user_id'],$due]);
  $pdo->prepare("UPDATE book_copies SET status='issued' WHERE id=?")->execute([$copy]);$pdo->commit();flash('success','Book issued successfully. Due date: '.$due);redirect('loans.php');
 }
 if($action==='return'){
  $loan=(int)($_POST['loan_id']??0);$condition=(string)($_POST['condition']??'good');
  $pdo->beginTransaction();$s=$pdo->prepare('SELECT l.*,c.id copy_id FROM loans l JOIN book_copies c ON c.id=l.copy_id WHERE l.id=? AND l.returned_at IS NULL FOR UPDATE');$s->execute([$loan]);$l=$s->fetch();if(!$l)throw new RuntimeException('This loan has already been returned or does not exist.');
  $pdo->prepare("UPDATE loans SET returned_at=NOW(),returned_by=?,status='returned' WHERE id=?")->execute([(int)$_SESSION['user_id'],$loan]);
  $newStatus=($condition==='damaged'?'damaged':($condition==='repair'?'repair':'available'));
  $pdo->prepare('UPDATE book_copies SET status=?,condition_note=? WHERE id=?')->execute([$newStatus,$condition==='good'?null:$condition,(int)$l['copy_id']]);
  $days=max(0,(int)floor((strtotime(date('Y-m-d'))-strtotime($l['due_at']))/86400));$fine=round($days*DEFAULT_DAILY_FINE,2);
  if($fine>0){$pdo->prepare("INSERT INTO fines(loan_id,amount,status) VALUES(?,?,'unpaid') ON DUPLICATE KEY UPDATE amount=VALUES(amount)")->execute([$loan,$fine]);}
  $pdo->commit();flash('success','Return recorded. '.($fine>0?'Estimated overdue fine: '.CURRENCY_CODE.' '.number_format($fine,2):'No overdue fine assessed.'));redirect('loans.php');
 }
}catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$error=$ex instanceof RuntimeException?$ex->getMessage():'Transaction failed. Please check the records and try again.';}}
$members=$pdo->query("SELECT id,member_code,full_name FROM members WHERE status='active' AND (valid_until IS NULL OR valid_until>=CURDATE()) ORDER BY full_name")->fetchAll();
$copies=$pdo->query("SELECT c.id,c.accession_code,b.title,b.author FROM book_copies c JOIN books b ON b.id=c.book_id WHERE c.status='available' ORDER BY b.title")->fetchAll();
$loans=$pdo->query("SELECT l.*,m.full_name,m.member_code,b.title,c.accession_code,f.amount fine_amount,f.paid_amount FROM loans l JOIN members m ON m.id=l.member_id JOIN book_copies c ON c.id=l.copy_id JOIN books b ON b.id=c.book_id LEFT JOIN fines f ON f.loan_id=l.id ORDER BY (l.returned_at IS NULL) DESC,l.id DESC LIMIT 300")->fetchAll();
page_start('Issue & return');if($error)echo '<div class="alert error">'.e($error).'</div>';
echo '<div class="grid two-col"><section class="card"><div class="section-head"><div><h2>Issue a book</h2><p>Available copies only; member borrowing limit applies</p></div></div><form method="post"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="issue"><div class="form-grid"><div class="field full"><label>Public member *</label><select name="member_id" required><option value="">Select member</option>';foreach($members as $m)echo '<option value="'.(int)$m['id'].'">'.e($m['member_code'].' — '.$m['full_name']).'</option>';echo '</select></div><div class="field full"><label>Book copy / accession code *</label><select name="copy_id" required><option value="">Select available copy</option>';foreach($copies as $c)echo '<option value="'.(int)$c['id'].'">'.e($c['title'].' — '.$c['accession_code'].' ('.$c['author'].')').'</option>';echo '</select></div><div class="field full"><label>Due date *</label><input type="date" name="due_at" value="'.date('Y-m-d',strtotime('+'.DEFAULT_LOAN_DAYS.' days')).'" min="'.date('Y-m-d').'" required></div><div class="field full"><label>Default loan period</label><input value="'.DEFAULT_LOAN_DAYS.' days" disabled></div></div><div class="form-actions"><button class="btn btn-primary">Confirm issue</button></div></form></section><section class="card"><div class="section-head"><div><h2>Return a book</h2><p>Choose an active loan and record its condition</p></div></div><form method="post"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="return"><div class="field"><label>Active loan *</label><select name="loan_id" required><option value="">Select active loan</option>';foreach($loans as $l)if(!$l['returned_at'])echo '<option value="'.(int)$l['id'].'">'.e('#'.$l['id'].' — '.$l['title'].' / '.$l['full_name'].' / due '.$l['due_at']).'</option>';echo '</select></div><div class="field" style="margin-top:14px"><label>Returned copy condition</label><select name="condition"><option value="good">Good — available again</option><option value="damaged">Damaged — mark damaged</option><option value="repair">Needs repair</option></select></div><div class="notice">Overdue fine is calculated using the configured daily rate. Payment collection and waiver workflows will be added in a later stage.</div><div class="form-actions"><button class="btn btn-primary">Record return</button></div></form></section></div><section class="card" style="margin-top:18px"><div class="section-head"><div><h2>Circulation register</h2><p>Issue, due date, return status and estimated fine</p></div></div><div class="table-wrap"><table><thead><tr><th>Ref</th><th>Book / accession</th><th>Member</th><th>Issued</th><th>Due</th><th>Returned</th><th>Status</th><th>Fine</th></tr></thead><tbody>';
if(!$loans)echo '<tr><td colspan="8" class="empty">No loan records yet.</td></tr>';
foreach ($loans as $l) {
    $overdue = empty($l['returned_at']) && $l['due_at'] < date('Y-m-d');
    $status = !empty($l['returned_at']) ? 'Returned' : ($overdue ? 'Overdue' : 'Issued');
    if ($l['fine_amount'] !== null) {
        $fineDisplay = e(CURRENCY_CODE) . ' ' . number_format((float)$l['fine_amount'], 2);
    } elseif ($overdue) {
        $daysOverdue = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($l['due_at'])) / 86400));
        $fineDisplay = e(CURRENCY_CODE) . ' ' . number_format($daysOverdue * DEFAULT_DAILY_FINE, 2);
    } else {
        $fineDisplay = '—';
    }
    echo '<tr><td>#' . (int)$l['id'] . '</td>';
    echo '<td><span class="book-title">' . e($l['title']) . '</span><span class="subtext">' . e($l['accession_code']) . '</span></td>';
    echo '<td>' . e($l['full_name']) . '<span class="subtext">' . e($l['member_code']) . '</span></td>';
    echo '<td>' . e(substr($l['issued_at'], 0, 10)) . '</td><td>' . e($l['due_at']) . '</td>';
    echo '<td>' . e(!empty($l['returned_at']) ? substr($l['returned_at'], 0, 10) : '—') . '</td>';
    echo '<td><span class="badge ' . strtolower($status) . '">' . e($status) . '</span></td><td>' . $fineDisplay . '</td></tr>';
}
echo '</tbody></table></div></section>';
page_end();

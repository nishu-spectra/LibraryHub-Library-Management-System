<?php
require_once __DIR__ . '/../app/layout.php'; require_login(); $pdo=db(); $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check(); $action=$_POST['action']??'';
 try {
  if($action==='add'){
   $title=trim((string)($_POST['title']??''));$author=trim((string)($_POST['author']??''));$quantity=max(1,min(500,(int)($_POST['quantity']??1)));
   if($title===''||$author==='') throw new RuntimeException('Title and author are required.');
   $pdo->beginTransaction();
   $stmt=$pdo->prepare('INSERT INTO books(title,author,isbn,category,publisher,publication_year,shelf_location) VALUES(?,?,?,?,?,?,?)');
   $stmt->execute([$title,$author,trim((string)($_POST['isbn']??''))?:null,trim((string)($_POST['category']??''))?:null,trim((string)($_POST['publisher']??''))?:null,($_POST['year']??'')!==''?(int)$_POST['year']:null,trim((string)($_POST['shelf']??''))?:null]);
   $bookId=(int)$pdo->lastInsertId();
   $copy=$pdo->prepare('INSERT INTO book_copies(book_id,accession_code,barcode,acquired_at) VALUES(?,?,?,CURDATE())');
   $movement=$pdo->prepare("INSERT INTO stock_movements(copy_id,movement_type,movement_date,reason,handled_by) VALUES(?,'entry',NOW(),'Initial catalogue entry',?)");
   for($i=0;$i<$quantity;$i++){
    $code='LIB-'.date('ym').'-'.str_pad((string)random_int(1,999999),6,'0',STR_PAD_LEFT);
    $copy->execute([$bookId,$code,trim((string)($_POST['barcode_prefix']??''))!==''?trim((string)$_POST['barcode_prefix']).'-'.($i+1):$code]);
    $movement->execute([(int)$pdo->lastInsertId(),(int)$_SESSION['user_id']]);
   }
   $pdo->commit(); flash('success',"Book added with {$quantity} individual copy record(s).");
   redirect('books.php');
  }
 } catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$error=$ex instanceof RuntimeException?$ex->getMessage():'Could not save book. Check that accession/barcode values are unique.';}
}
$q=trim((string)($_GET['q']??''));
$sql="SELECT b.*,COUNT(c.id) total_copies,SUM(c.status='available') available_copies FROM books b LEFT JOIN book_copies c ON c.book_id=b.id";
$params=[];
if($q!==''){$sql.=" WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR c.accession_code LIKE ? OR c.barcode LIKE ?";$like="%$q%";$params=[$like,$like,$like,$like,$like];}
$sql.=" GROUP BY b.id ORDER BY b.created_at DESC LIMIT 300";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$books=$stmt->fetchAll();
page_start('Books catalogue');
if($error)echo '<div class="alert error">'.e($error).'</div>';
echo '<div class="grid two-col"><section class="card"><div class="section-head"><div><h2>Catalogue</h2><p>Search titles, authors, ISBN or copy barcode</p></div></div><form class="toolbar" method="get"><input class="search" name="q" value="'.e($q).'" placeholder="Search books or accession code"><button class="btn btn-light">Search</button></form><div class="table-wrap"><table><thead><tr><th>Title / author</th><th>Category</th><th>Copies</th><th>Available</th><th>Shelf</th></tr></thead><tbody>';
if(!$books)echo '<tr><td colspan="5" class="empty">No books found. Add the first title using the form.</td></tr>';
foreach($books as $b)echo '<tr><td><span class="book-title">'.e($b['title']).'</span><span class="subtext">'.e($b['author']).($b['isbn']?' · ISBN '.e($b['isbn']):'').'</span></td><td>'.e($b['category']??'—').'</td><td>'.(int)$b['total_copies'].'</td><td><span class="badge available">'.(int)$b['available_copies'].'</span></td><td>'.e($b['shelf_location']??'—').'</td></tr>';
echo '</tbody></table></div></section><section class="card"><div class="section-head"><div><h2>Add a book</h2><p>One title can have multiple individually tracked copies</p></div></div><form method="post"><input type="hidden" name="csrf_token" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="add"><div class="form-grid"><div class="field full"><label>Book title *</label><input name="title" required maxlength="255"></div><div class="field"><label>Author *</label><input name="author" required maxlength="190"></div><div class="field"><label>Category</label><input name="category" maxlength="100" placeholder="e.g. Computing"></div><div class="field"><label>ISBN</label><input name="isbn" maxlength="32"></div><div class="field"><label>Publisher</label><input name="publisher" maxlength="190"></div><div class="field"><label>Publication year</label><input type="number" name="year" min="1000" max="'.date('Y').'"></div><div class="field"><label>Shelf / location</label><input name="shelf" maxlength="80" placeholder="A-03-02"></div><div class="field"><label>Number of copies *</label><input type="number" name="quantity" min="1" max="500" value="1" required></div><div class="field full"><label>Barcode prefix (optional)</label><input name="barcode_prefix" maxlength="70" placeholder="Leave blank to generate codes from accession IDs"><span class="subtext">Each copy receives a unique accession code. Barcode labels/scanner support can be enhanced in Stage 2.</span></div></div><div class="form-actions"><button class="btn btn-primary">Save book and copies</button></div></form></section></div>';
page_end();

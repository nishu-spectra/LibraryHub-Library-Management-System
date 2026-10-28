<?php
require_once __DIR__ . '/config.php';

/** Shared HTML layout. Styling is kept in public/assets/style.css. */
function page_start(string $title): void
{
    $active = basename($_SERVER['PHP_SELF'] ?? '');
    $items = [
        'index.php' => ['layout-dashboard', 'Dashboard'],
        'books.php' => ['book-open', 'Books'],
        'members.php' => ['users', 'Members'],
        'loans.php' => ['arrow-left-right', 'Issue & Return'],
        'movements.php' => ['package', 'Entry & Exit'],
    ];

    echo '<!doctype html><html lang="en"><head>';
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="theme-color" content="#5B3DF5">';
    echo '<title>' . e($title) . ' · ' . APP_NAME . '</title>';
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '<script src="https://unpkg.com/lucide@latest"></script>';
    echo '</head><body>';

    echo '<aside class="sidebar" id="librarySidebar">';
    echo '<a class="brand" href="index.php"><span class="brand-icon">▣</span><span><b>LibraryHub</b><small>Library operations</small></span></a>';
    echo '<nav aria-label="Main navigation">';

    foreach ($items as $href => $item) {
        $icon = $item[0];
        $label = $item[1];
        $isActive = ($active === $href);
        echo '<a class="' . ($isActive ? 'active' : '') . '" href="' . e($href) . '"' . ($isActive ? ' aria-current="page"' : '') . '>';
        echo '<span class="nav-icon"><i data-lucide="' . e($icon) . '" aria-hidden="true"></i></span>';
        echo '<span class="nav-label">' . e($label) . '</span></a>';
    }

    echo '</nav>';
    echo '<div class="side-bottom"><span class="avatar">' . e(strtoupper(substr((string)($_SESSION['name'] ?? 'A'), 0, 1))) . '</span>';
    echo '<span><b>' . e((string)($_SESSION['name'] ?? 'Administrator')) . '</b><small>' . e((string)($_SESSION['role'] ?? 'admin')) . '</small></span>';
    echo '<a class="logout" href="logout.php" title="Sign out" aria-label="Sign out">↗</a></div></aside>';

    echo '<div class="sidebar-overlay" id="sidebarOverlay"></div>';
    echo '<main class="main"><header class="topbar"><div class="page-heading-wrap">';
    // echo '<button type="button" class="mobile-menu-button" id="sidebarToggle" aria-label="Toggle navigation" aria-controls="librarySidebar" aria-expanded="false"><i data-lucide="menu"></i></button>';
    echo '<span class="eyebrow">LIBRARY OPERATIONS</span><h1>' . e($title) . '</h1></div>';
    echo '<div class="top-actions"><span class="today">' . e(date('D, d M Y')) . '</span><a class="btn btn-primary" href="loans.php">＋ Issue a book</a></div></header>';

    if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = (string)($f['type'] ?? '');
        $message = (string)($f['message'] ?? '');
        echo '<div class="alert ' . e($type) . '">' . e($message) . '</div>';
    }
}

function page_end(): void
{
    echo '<footer>LibraryHub <span>·</span> Accurate records. Better library service.</footer>';
    echo '</main><script>';
    echo '(function(){';
    echo 'var toggle=document.getElementById("sidebarToggle");';
    echo 'var overlay=document.getElementById("sidebarOverlay");';
    echo 'function closeMenu(){document.body.classList.remove("sidebar-open");if(toggle){toggle.setAttribute("aria-expanded","false");}}';
    echo 'if(toggle){toggle.addEventListener("click",function(){var opened=document.body.classList.toggle("sidebar-open");toggle.setAttribute("aria-expanded",opened?"true":"false");});}';
    echo 'if(overlay){overlay.addEventListener("click",closeMenu);}';
    echo 'document.addEventListener("keydown",function(e){if(e.key==="Escape"){closeMenu();}});';
    echo 'if(window.lucide&&typeof window.lucide.createIcons==="function"){window.lucide.createIcons();}';
    echo '})();';
    echo '</script></body></html>';
}

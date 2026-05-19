<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>PMB Online</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<style>

/* ===========================
   BASE
=========================== */
*, *::before, *::after { box-sizing: border-box; }

body {
    background: #f5f7fb;
    margin: 0;
    font-family: system-ui, -apple-system, sans-serif;
}

/* ===========================
   SIDEBAR
=========================== */
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: #0d6efd;
    color: white;
    padding-top: 20px;
    z-index: 1050;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
}

.sidebar a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 20px;
    border-radius: 10px;
    margin: 3px 10px;
    transition: background 0.2s;
    font-size: 15px;
}

.sidebar a:hover,
.sidebar a.sidebar-active,
.sidebar a[style*="rgba(255,255,255,0.2)"] {
    background: rgba(255, 255, 255, 0.18) !important;
}

.sidebar a i { font-size: 17px; flex-shrink: 0; }

/* ===========================
   OVERLAY (mobile)
=========================== */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1040;
    backdrop-filter: blur(2px);
    transition: opacity 0.3s;
}
.sidebar-overlay.show { display: block; }

/* ===========================
   TOPBAR (mobile hamburger)
=========================== */
.topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #0d6efd;
    z-index: 1030;
    align-items: center;
    padding: 0 16px;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(13,110,253,0.3);
}
.topbar-title {
    color: white;
    font-weight: 700;
    font-size: 16px;
    flex: 1;
}
.hamburger-btn {
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 8px;
    line-height: 1;
    transition: background 0.2s;
}
.hamburger-btn:hover { background: rgba(255,255,255,0.15); }

/* ===========================
   MAIN CONTENT
=========================== */
.main-content {
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
}

/* ===========================
   CARD MODERN
=========================== */
.card-modern {
    border: none;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

/* ===========================
   RESPONSIVE
=========================== */
@media (max-width: 768px) {

    /* Show topbar */
    .topbar { display: flex; }

    /* Push content down for topbar */
    .main-content {
        margin-left: 0;
        padding: 80px 16px 24px;
    }

    /* Sidebar slides in from left */
    .sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 24px rgba(0,0,0,0.15);
    }
    .sidebar.open {
        transform: translateX(0);
    }

    /* Nicer cards on mobile */
    .card-modern { border-radius: 14px; }
}

@media (min-width: 769px) {
    .topbar       { display: none !important; }
    .sidebar      { transform: translateX(0) !important; }
    .sidebar-overlay { display: none !important; }
}

</style>

</head>
<body>

<!-- TOPBAR (mobile only) -->
<div class="topbar" id="topbar">
    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Menu">
        <i class="bi bi-list" id="hamburgerIcon"></i>
    </button>
    <div class="topbar-title">PMB Online</div>
</div>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar  = document.querySelector('.sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const icon     = document.getElementById('hamburgerIcon');
    const isOpen   = sidebar.classList.toggle('open');
    overlay.classList.toggle('show', isOpen);
    icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
    document.body.style.overflow = isOpen ? 'hidden' : '';
}
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const icon    = document.getElementById('hamburgerIcon');
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    icon.className = 'bi bi-list';
    document.body.style.overflow = '';
}
// Close sidebar on nav link click (mobile)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
});
</script>
<?php
    include 'controllerProduct.php';


    $limit = 20;

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max($page, 1);

    $offset = ($page - 1) * $limit;

    $category = $_GET['category'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    /* SAFE WHERE BUILDER */
    $where = "WHERE 1=1";

    /* CATEGORY FILTER */
    if ($category !== 'all') {
        $category = $conn->real_escape_string($category);
        $where .= " AND LOWER(c.category_name) = LOWER('$category')";
    }

    /* STOCK STATUS FILTER */
    if ($status !== 'all') {

        if ($status === 'instock') {
            $where .= " AND p.product_quantity BETWEEN r.range_min AND r.range_max";

        } elseif ($status === 'low') {
            $where .= " AND p.product_quantity > 0 AND p.product_quantity < r.range_min";

        } elseif ($status === 'out') {
            $where .= " AND p.product_quantity <= 0";

        } elseif ($status === 'over') {
            $where .= " AND p.product_quantity > r.range_max";
        }
    }

    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where .= " AND (
            LOWER(p.product_name) = LOWER('$search')
            OR p.product_name LIKE '%$search%'
        )";
    }


    $sql = "
        SELECT 
            p.product_name,
            c.category_name,
            p.product_quantity,
            r.range_min,
            r.range_max
        FROM inv_product p
        LEFT JOIN inv_category c ON p.category_id = c.category_id
        LEFT JOIN inv_range r ON p.range_id = r.range_id 
        $where 
        ORDER BY p.product_name
        LIMIT $limit OFFSET $offset
    ";

    //PAGINATION
    $totalResult = $conn->query("
        SELECT COUNT(*) AS total 
        FROM inv_product p
        LEFT JOIN inv_category c ON p.category_id = c.category_id
        LEFT JOIN inv_range r ON p.range_id = r.range_id
        $where
    ");

    $totalRow = $totalResult->fetch_assoc();
    $totalProducts = $totalRow['total'];

    $totalPages = ceil($totalProducts / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Stock Monitoring</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>

        :root {
            --primary-emerald: #10b981;
            --primary-navy: #0f172a;
            --primary-bg: #f1f5f9; 
            --active-item-bg: rgba(255, 255, 255, 0.1); 
            --text-on-dark: #f1f5f9;
            --text-on-light: #1f2937;
            --card-border-radius: 16px; 
            --transition-speed: 0.3s;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-on-light);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* --- Mobile Overlay --- */
        .mobile-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 998; opacity: 0; visibility: hidden; transition: all var(--transition-speed) ease;
        }
        .mobile-overlay.active { opacity: 1; visibility: visible; }

        /* --- Sidebar Styles --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-navy);
            color: var(--text-on-dark);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: width var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
            padding: 15px 10px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .brand-logo-area {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-on-dark);
        }

        .brand-logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 12px;
            object-fit: contain;
        }

        .brand-text-area { display: flex; flex-direction: column; }
        .brand-name { font-weight: 800; font-size: 1.1rem; }
        .brand-subtext { font-size: 0.8rem; color: #94a3b8; }

        .expanded-only { display: inherit; }
        .collapsed-only { display: none; }
        .sidebar.collapsed .expanded-only { display: none !important; }
        .sidebar.collapsed .collapsed-only { display: inherit; }

        #sidebarToggle {
            background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px;
            transition: transform var(--transition-speed); display: flex; align-items: center; justify-content: center;
        }
        .sidebar.collapsed #sidebarToggle { transform: rotate(180deg); }

        .menu-list {
            list-style: none; padding: 0; margin: 0; flex-grow: 1; overflow-y: auto; overflow-x: hidden;
        }
        .menu-list::-webkit-scrollbar { display: none; }

        .menu-group-title {
            text-transform: uppercase; font-size: 0.75rem; font-weight: 700; color: #64748b;
            margin: 20px 0 10px; letter-spacing: 0.05em;
        }

        .menu-item { margin-bottom: 5px; }

        .menu-link {
            display: flex; align-items: center; text-decoration: none; color: #94a3b8;
            padding: 12px; border-radius: 10px; font-weight: 500; transition: all 0.2s ease;
        }
        .menu-link.active {
            color: var(--text-on-dark); background-color: var(--active-item-bg); font-weight: 600;
        }
        .menu-link:hover {
            color: var(--text-on-dark); background-color: var(--active-item-bg); transform: translateX(4px);
        }
        
        .menu-icon {
            width: 24px; height: 24px; margin-right: 12px; color: #94a3b8; display: flex; align-items: center; justify-content: center; transition: color 0.2s;
        }
        .menu-link:hover .menu-icon, .menu-link.active .menu-icon { color: #10b981; }
        .sidebar.collapsed .menu-icon { margin-right: 0; justify-content: center; }
        .sidebar.collapsed .menu-link { padding: 12px; justify-content: center; }

        .sidebar-bottom { margin-top: auto; border-top: 1px solid #334155; padding-top: 15px; font-size: 0.8rem; color: #94a3b8; }
        .demo-info { margin-bottom: 10px; font-weight: 500;}

        .sign-out-link {
            display: flex; align-items: center; text-decoration: none; color: #ef4444; font-weight: 600; padding: 10px 0; transition: all 0.2s;
        }
        .sign-out-link:hover { color: #f87171; transform: translateX(4px); }
        .sidebar.collapsed .sign-out-link { justify-content: center; padding: 10px; }

        /* --- Main Content Area --- */
        .content-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; }

        /* --- Header Styles --- */
        .main-header {
            background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px);
            padding: 15px 30px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5); flex-shrink: 0; z-index: 10;
        }

        .header-title-area { display: flex; align-items: center; gap: 15px;}
        .mobile-burger { display: none; background: none; border: none; color: var(--primary-navy); cursor: pointer; padding: 5px; }

        .header-title { font-size: 1.5rem; font-weight: 700; color: var(--primary-navy); margin: 0; }

        .header-search { flex: 1; max-width: 400px; position: relative; margin: 0 30px; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input {
            width: 100%; padding: 12px 12px 12px 45px; border: 1.5px solid transparent; border-radius: 20px;
            box-sizing: border-box; background-color: #e2e8f0; font-size: 0.9rem; font-weight: 500;
            color: var(--primary-navy); transition: all 0.3s ease; outline: none;
        }
        .search-input:focus { background-color: #fff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .search-input::placeholder { color: #64748b; }

        .header-right { display: flex; align-items: center; gap: 20px;}

        .notification-area { position: relative; }
        .header-btn {
            background: #f1f5f9; border: none; color: #64748b; cursor: pointer; padding: 10px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .header-btn:hover { background-color: #e2e8f0; color: var(--primary-navy); transform: scale(1.05); }

        .notification-badge {
            position: absolute; top: -2px; right: -2px; width: 18px; height: 18px; background-color: #ef4444; color: #fff;
            border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 2px solid #fff;
        }

        .user-dropdown { display: flex; align-items: center; cursor: pointer; padding: 5px; border-radius: 30px; transition: background-color 0.2s; }
        .user-dropdown:hover { background-color: #f1f5f9; }

        .user-avatar {
            width: 44px; height: 44px; border-radius: 50%; background-color: #3b82f6; color: #fff; display: flex;
            align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; margin-right: 12px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        .user-details { display: flex; flex-direction: column; text-align: right; margin-right: 8px;}
        .user-name { font-weight: 700; font-size: 0.95rem; color: var(--primary-navy); }
        .user-role { font-size: 0.8rem; font-weight: 500; color: #64748b; }

        /* --- Content Layout --- */
        .content-scrollable {
            flex: 1; padding: 30px; overflow-y: auto; overflow-x: hidden; background-color: transparent; box-sizing: border-box;
        }

        /* --- Unified Card Design --- */
        .card {
            background: rgba(255, 255, 255, 0.85); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6); display: flex; flex-direction: column;
        }

        /* --- Stock Monitoring Specific UI --- */
        .pill-filters {
            display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;
        }
        .filter-pill {
            padding: 8px 18px; border: 1px solid #cbd5e1; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600; color: #64748b; background: white;
            cursor: pointer; transition: all 0.2s ease; outline: none; white-space: nowrap;
            text-decoration: none;
        }
        .filter-pill:hover:not(.active) { background: #f8fafc; color: var(--primary-navy); border-color: #94a3b8;}
        .filter-pill.active { background: var(--primary-navy); color: white; border-color: var(--primary-navy); }

        .filter-bar {
            display: flex; align-items: center; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;
        }
        
        .filter-search { position: relative; width: 250px; height: 40px; display: flex; align-items: center;}
        .filter-search i { position: absolute; left: 12px; color: #94a3b8; pointer-events: none; }
        .filter-search input { 
            width: 100%; height: 100%; padding: 0 12px 0 38px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 0.9rem; font-weight: 500; transition: border-color 0.2s; outline: none; background: #fff; box-sizing: border-box;
        }
        .filter-search input:focus { border-color: #3b82f6; }

        .filter-dropdown {
            height: 40px; padding: 0 35px 0 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;
            font-size: 0.9rem; color: var(--primary-navy); background-color: white; cursor: pointer; outline: none; box-sizing: border-box;
            appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat; background-position: right 10px center; background-size: 16px;
        }
        .filter-dropdown:focus { border-color: #3b82f6; }

        /* Responsive Table */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px;}
        .products-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; min-width: 800px; }
        
        .products-table th {
            text-align: left; color: #64748b; font-weight: 800; padding: 12px 15px 16px 15px;
            border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
        }

        .products-table td { padding: 16px 15px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); }
        
        /* Clean Static Hover */
        .products-table tbody tr { transition: background-color 0.2s ease; }
        .products-table tbody tr:hover { background-color: #f8fafc; }
        .products-table tbody tr:last-child td { border-bottom: none; }

        .col-product { font-weight: 700; color: var(--primary-navy); }
        .col-category { color: #64748b; font-weight: 500; }
        .col-qty { font-weight: 600; color: var(--primary-navy); }
        .col-minmax { font-weight: 500; color: #475569; }

        /* Status Badges */
        .status-badge {
            display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 700; white-space: nowrap;
        }
        .status-instock { background-color: #dcfce7; color: #15803d; }
        .status-low { background-color: #fef3c7; color: #b45309; }
        .status-out { background-color: #fee2e2; color: #b91c1c; }
        .status-over { background-color: #dbeafe; color: #1d4ed8; }

        /* --- Responsive Media Queries --- */
        @media (max-width: 900px) {
            .sidebar { position: fixed; top: 0; left: -280px; height: 100vh; width: 280px; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
            .sidebar.active { left: 0; }
            #sidebarToggle { display: none; }
            .mobile-burger { display: flex; align-items: center; justify-content: center; }
            .main-header { padding: 15px 20px; }
            .header-search { display: none; }
            .user-details { display: none; }
            .content-scrollable { padding: 20px 15px; }
        }

        @media (max-width: 650px) {
            .filter-search { width: 100%; }
            .filter-dropdown { flex: 1; }
        }

        .pagination-area { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 15px;}
        .pagination-info { font-size: 0.85rem; font-weight: 500; color: #64748b; }
        .pagination-controls { display: flex; gap: 6px; }
        .page-btn {
            padding: 6px 12px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer;
            font-weight: 600; font-size: 0.85rem; color: #64748b; transition: all 0.2s; display: flex; align-items: center;
        }
        .page-btn:hover:not(.active) { background: #f1f5f9; color: var(--primary-navy); }
        .page-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }

        @media (max-width: 650px) {
            .pagination-area { flex-direction: column; align-items: center; justify-content: center;}
        }

        .notif-dropdown{
            position: absolute;
            right: 0;
            top: 50px;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: none;
            overflow: hidden;
            z-index: 999;
        }

        .notif-dropdown.active{
            display: block;
        }

        .notif-item{
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .notif-item.unread{
            background: #f1f5f9;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="brand-logo-area">
                <img src="img/TallyTrack 2.0.png" alt="Logo" class="brand-logo-icon" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display:none; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 8px; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; font-size: 1.2rem; color: white;">TT</div>
                <div class="brand-text-area expanded-only">
                    <div class="brand-name">TallyTrack</div>
                    <div class="brand-subtext">Inventory System</div>
                </div>
            </a>
            <button id="sidebarToggle" class="expanded-only">
                <i data-lucide="chevron-left" size="20"></i>
            </button>
        </div>

        <ul class="menu-list">
            <li class="menu-group-title expanded-only">Main</li>

            <li class="menu-item">
                <a href="dashboard.php" class="menu-link">
                    <i data-lucide="layout-grid" class="menu-icon"></i>
                    <span class="expanded-only">Dashboard</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="product.php" class="menu-link">
                    <i data-lucide="package" class="menu-icon"></i>
                    <span class="expanded-only">Product Management</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="stock.php" class="menu-link active">
                    <i data-lucide="bar-chart-3" class="menu-icon"></i>
                    <span class="expanded-only">Stock Monitoring</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="stockin.php" class="menu-link">
                    <i data-lucide="arrow-down-to-line" class="menu-icon"></i>
                    <span class="expanded-only">Stock-In</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="cashier.php" class="menu-link">
                    <i data-lucide="calculator" class="menu-icon"></i>
                    <span class="expanded-only">Cashier</span>
                </a>
            </li>

            <li class="menu-group-title expanded-only">Insights</li>

            <li class="menu-item">
                <a href="alert.php" class="menu-link">
                    <i data-lucide="bell" class="menu-icon"></i>
                    <span class="expanded-only">Alerts</span>
                </a>
            </li>

            <?php if ($role !== 'staff'): ?> 
                <li class="menu-item">
                    <a href="report.php" class="menu-link">
                        <i data-lucide="file-text" class="menu-icon"></i>
                        <span class="expanded-only">Reports & Analytics</span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="transaction.php" class="menu-link">
                        <i data-lucide="history" class="menu-icon"></i>
                        <span class="expanded-only">Transaction History</span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="user.php" class="menu-link">
                        <i data-lucide="users" class="menu-icon"></i>
                        <span class="expanded-only">User Management</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-bottom">

            <form action="controllerProduct.php" method="POST" style="margin:0;" id="logoutForm">
                <button 
                    type="button"
                    class="sign-out-link"
                    style="background:none;border:none;cursor:pointer;"
                    onclick="confirmLogout()"
                >
                    <i data-lucide="door-open" class="menu-icon"></i>
                    <span class="expanded-only">Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="content-area">
        <header class="main-header">
            <div class="header-left">
                <button class="mobile-burger" id="burgerToggle">
                    <i data-lucide="menu" size="24"></i>
                </button>
                <h1 class="header-title">Stock Monitoring</h1>
            </div>

            
            <form method="GET" class="header-search">
                <i data-lucide="search" class="search-icon" size="18"></i>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by product name..."
                    class="search-input"
                    onkeydown="if(event.key==='Enter') this.form.submit()"
                >

                <!-- preserve filters -->
                <input type="hidden" name="category" value="<?= $category ?>">
                <input type="hidden" name="status" value="<?= $status ?>">
                <input type="hidden" name="page" value="1">
            </form>

            <div class="header-right">
                <div class="notification-area" onclick="toggleNotifDropdown()">

                    <button class="header-btn">
                        <i data-lucide="bell" size="20"></i>
                    </button>

                    <?php if ($notifCount > 0): ?>
                        <span class="notification-badge">
                            <?php echo $notifCount; ?>
                        </span>
                    <?php endif; ?>

                    <!-- DROPDOWN -->
                    <div class="notif-dropdown" id="notifDropdown">

                        <?php if ($notifQuery->num_rows > 0): ?>

                            <?php while($notif = $notifQuery->fetch_assoc()): ?>

                                <div class="notif-item <?php echo ($notif['notification_status'] == 'read') ? '' : 'unread'; ?>">
                                    <span><?php echo htmlspecialchars($notif['notification_message']); ?></span>
                                    <small style="color:#94a3b8;">
                                        <?php echo date("M d, h:i A", strtotime($notif['created_at'])); ?>
                                    </small>
                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>
                            <div class="notif-item">
                                No notifications
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <div class="user-dropdown">
                    
                    <div class="user-avatar">
                        <?php echo $initials; ?>
                    </div>

                    <div class="user-details expanded-only">
                        <span class="user-name">
                            <?php echo htmlspecialchars($online_user['user_fullname']); ?>
                        </span>

                        <span class="user-role">
                            <?php echo htmlspecialchars(ucfirst($online_user['user_role'] ?? '')); ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-scrollable">
            
            <div class="card">
                
                <div class="pill-filters">

                    <a href="?status=all&category=<?= $category ?>&search=<?= urlencode($search) ?>"" class="filter-pill <?= ($status=='all')?'active':'' ?>">All</a>

                    <a href="?status=instock&category=<?= $category ?>&search=<?= urlencode($search) ?>"" class="filter-pill <?= ($status=='instock')?'active':'' ?>">In Stock</a>

                    <a href="?status=low&category=<?= $category ?>&search=<?= urlencode($search) ?>"" class="filter-pill <?= ($status=='low')?'active':'' ?>">Low Stock</a>

                    <a href="?status=out&category=<?= $category ?>&search=<?= urlencode($search) ?>"" class="filter-pill <?= ($status=='out')?'active':'' ?>">Out of Stock</a>

                    <a href="?status=over&category=<?= $category ?>&search=<?= urlencode($search) ?>"" class="filter-pill <?= ($status=='over')?'active':'' ?>">Overstocked</a>

                </div>

                <form method="GET" id="filterForm" class="filter-bar">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="status" value="<?= $status ?>">
                    
                    <select name="category" class="filter-dropdown" onchange="this.form.submit()">

                        <option value="all" <?= ($category == 'all') ? 'selected' : '' ?>>
                            All Categories
                        </option>

                        <option value="Food Products" <?= ($category == 'Food Products') ? 'selected' : '' ?>>Food</option>
                        <option value="Beverages" <?= ($category == 'Beverages') ? 'selected' : '' ?>>Beverages</option>
                        <option value="Personal Care Products" <?= ($category == 'Personal Care Products') ? 'selected' : '' ?>>Personal Care</option>
                        <option value="Household Products" <?= ($category == 'Household Products') ? 'selected' : '' ?>>Household</option>
                        <option value="Frozen Products" <?= ($category == 'Frozen Products') ? 'selected' : '' ?>>Frozen</option>
                        <option value="Condiments and Cooking Needs" <?= ($category == 'Condiments and Cooking Needs') ? 'selected' : '' ?>>Condiments</option>
                        <option value="School Supplies" <?= ($category == 'School Supplies') ? 'selected' : '' ?>>School Supplies</option>

                    </select>
                </div>

                <div class="table-responsive">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>CATEGORY</th>
                                <th>QUANTITY</th>
                                <th>MIN</th>
                                <th>MAX</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {

                                        $qty = (int)$row['product_quantity'];
                                        $min = (int)$row['range_min'];
                                        $max = (int)$row['range_max'];

                                        // STATUS LOGIC
                                        if ($qty <= 0) {
                                            $status = "Out of Stock";
                                            $class = "status-out";
                                        } elseif ($qty < $min) {
                                            $status = "Low Stock";
                                            $class = "status-low";
                                        } elseif ($qty > $max) {
                                            $status = "Overstocked";
                                            $class = "status-over";
                                        } else {
                                            $status = "In Stock";
                                            $class = "status-instock";
                                        }

                                        echo "
                                        <tr>
                                            <td class='col-product'>{$row['product_name']}</td>
                                            <td class='col-category'>{$row['category_name']}</td>
                                            <td class='col-qty'>{$qty}</td>
                                            <td class='col-minmax'>{$min}</td>
                                            <td class='col-minmax'>{$max}</td>
                                            <td><span class='status-badge {$class}'>{$status}</span></td>
                                        </tr>
                                        ";
                                    }
                                } else {
                                    echo "
                                    <tr>
                                        <td colspan='6' style='text-align:center; padding:20px; color:#64748b;'>
                                            No products found
                                        </td>
                                    </tr>
                                    ";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-area">

                    <div class="pagination-info">
                        <?php
                            $start = $offset + 1;
                            $end = min($offset + $limit, $totalProducts);
                        ?>
                        Showing <?= $start ?>–<?= $end ?> of <?= $totalProducts ?>
                    </div>

                    <div class="pagination-controls">

                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $i ?>&category=<?= $category ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-btn">
                                <i data-lucide="chevron-left" size="16"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&category=<?= $category ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" 
                            class="page-btn <?= ($i == $page) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $i ?>&category=<?= $category ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-btn">
                                <i data-lucide="chevron-right" size="16"></i>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Initialise Lucide icons
        lucide.createIcons();

        // --- Sidebar Logic for Desktop & Mobile ---
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const burgerToggle = document.getElementById('burgerToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // Desktop Toggle Collapse
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });

        // Mobile Burger Menu Toggle
        function toggleMobileMenu() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        }

        burgerToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);
        
        // --- Interactive Pill Filters Script ---
        const filterPills = document.querySelectorAll('.filter-pill');
        
        filterPills.forEach(pill => {
            pill.addEventListener('click', function() {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function confirmLogout() {
            const ok = confirm("Do you really want to log out of your account?");
            if (ok) {
                window.location.href = "controllerProduct.php?logout=1";
            }
        }

        function toggleNotifDropdown() {
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('active');

            if (dropdown.classList.contains('active')) {
                fetch('controllerProduct.php?mark_seen=1')
                    .then(() => {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) badge.style.display = 'none';
                    });
            }
        }
    </script>
</body>
</html>
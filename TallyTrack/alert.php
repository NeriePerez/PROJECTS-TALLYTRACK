<?php
    include 'controllerProduct.php';    

    $lowStockQuery = mysqli_query($conn, "
        SELECT COUNT(*) AS total_low
        FROM inv_product
        WHERE stockLevel = 'Low Stock'
    ");

    $lowStockData = mysqli_fetch_assoc($lowStockQuery);
    $lowStockCount = $lowStockData['total_low'];

    // OUT OF STOCK COUNT
    $outStockQuery = mysqli_query($conn, "
        SELECT COUNT(*) AS total_out
        FROM inv_product
        WHERE stockLevel = 'Out of Stock'
    ");

    $outStockData = mysqli_fetch_assoc($outStockQuery);
    $outStockCount = $outStockData['total_out'];


    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Alerts</title>
    
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

        /* --- Sidebar & Header styles remain unchanged for consistency --- */
        .mobile-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 998; opacity: 0; visibility: hidden; transition: all var(--transition-speed) ease;
        }
        .mobile-overlay.active { opacity: 1; visibility: visible; }

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

        /* --- Content Scrollable Box --- */
        .content-scrollable {
            flex: 1; padding: 30px; overflow-y: auto; overflow-x: hidden; background-color: transparent; box-sizing: border-box;
        }

        /* --- Unified Card Design --- */
        .card {
            background: rgba(255, 255, 255, 0.85); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6); display: flex; flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Added for hover effect */
        }

        /* --- Alerts Specific UI --- */
        
        /* Top KPI Grid */
        .alerts-kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .alert-kpi-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 20px;
            cursor: pointer; /* Feedback that it is interactive */
        }

        /* ENHANCED DESIGN: Hover lift effect */
        .alert-kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(255, 255, 255, 0.9);
        }

        .alert-kpi-icon {
            width: 56px; height: 56px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.3s ease; /* Animate the icon specifically */
        }

        /* Icon scale up on card hover */
        .alert-kpi-card:hover .alert-kpi-icon {
            transform: scale(1.1);
        }
        
        .icon-amber { background-color: #fef3c7; color: #b45309; }
        .icon-red { background-color: #fee2e2; color: #b91c1c; }

        .alert-kpi-info { display: flex; flex-direction: column; }
        .alert-kpi-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 4px; }
        .alert-kpi-value { font-size: 2rem; font-weight: 800; color: var(--primary-navy); line-height: 1; letter-spacing: -1px;}

        /* Table styles remain consistent with system design */
        .table-header {
            font-size: 1.15rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 24px; letter-spacing: -0.5px;
        }

        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px;}
        .stock-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; min-width: 600px; }
        
        .stock-table th {
            text-align: left; color: #64748b; font-weight: 800; padding: 12px 15px 16px 15px;
            border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
        }

        .stock-table td { padding: 16px 15px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); vertical-align: middle; }
        .stock-table tbody tr { transition: background-color 0.2s ease; }
        .stock-table tbody tr:hover td { background-color: #f8fafc; }
        .stock-table tbody tr:last-child td { border-bottom: none; }

        .col-product { font-weight: 700; color: var(--primary-navy); }
        .col-qty { font-weight: 700; color: var(--primary-navy); } 

        .status-badge {
            display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 700; white-space: nowrap;
        }
        .status-low { background-color: #fef9c3; color: #ca8a04; }
        .status-out { background-color: #fee2e2; color: #dc2626; }

        .btn-action-solid {
            padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; 
            border: none; cursor: pointer; color: white; transition: background-color 0.2s; 
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-restock { background-color: var(--primary-emerald); }
        .btn-restock:hover { background-color: #059669; }
        .btn-reorder { background-color: #ef4444; }
        .btn-reorder:hover { background-color: #dc2626; }

        /* Media queries remain unchanged */
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
            .alerts-kpi-grid { grid-template-columns: 1fr; gap: 16px; }
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
                <a href="stock.php" class="menu-link">
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
                <a href="alert.php" class="menu-link active">
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
            <div class="header-title-area">
                <button class="mobile-burger" id="burgerToggle">
                    <i data-lucide="menu" size="24"></i>
                </button>
                <h1 class="header-title">Alerts</h1>
            </div>

            <div class="header-search">
                <i data-lucide="search" class="search-icon" size="18"></i>
                <input 
                    type="text" 
                    id="alertSearchInput"
                    placeholder="Search products by name..."
                    class="search-input"
                >
            </div>

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
            
            <div class="alerts-kpi-grid">
                
                <div class="card alert-kpi-card">
                    <div class="alert-kpi-icon icon-amber">
                        <i data-lucide="alert-triangle" size="28"></i>
                    </div>
                    <div class="alert-kpi-info">
                        <span class="alert-kpi-label">Low Stock Items</span>
                        <span class="alert-kpi-value"><?php echo $lowStockCount; ?></span>
                    </div>
                </div>

                <div class="card alert-kpi-card">
                    <div class="alert-kpi-icon icon-red">
                        <i data-lucide="x-octagon" size="28"></i>
                    </div>
                    <div class="alert-kpi-info">
                        <span class="alert-kpi-label">Out of Stock</span>
                        <span class="alert-kpi-value"><?php echo $outStockCount; ?></span>
                    </div>
                </div>

            </div>

            <div class="card">
                <div class="table-header">All Alerts</div>
                
                <div class="table-responsive">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>REMAINING QTY</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php

                                $alertQuery = mysqli_query($conn, "
                                    SELECT *
                                    FROM inv_product
                                    WHERE stockLevel IN ('Low Stock', 'Out of Stock')
                                    ORDER BY 
                                        CASE
                                            WHEN stockLevel = 'Out of Stock' THEN 1
                                            WHEN stockLevel = 'Low Stock' THEN 2
                                        END,
                                        product_quantity ASC
                                ");

                                if (mysqli_num_rows($alertQuery) > 0) {

                                    while ($row = mysqli_fetch_assoc($alertQuery)) {

                                        $productName = $row['product_name'];
                                        $quantity = $row['product_quantity'];
                                        $status = $row['stockLevel'];

                                        // badge class
                                        $badgeClass = ($status == 'Out of Stock')
                                            ? 'status-out'
                                            : 'status-low';

                                        // button class
                                        $buttonClass = ($status == 'Out of Stock')
                                            ? 'btn-reorder'
                                            : 'btn-restock';

                                        // button text
                                        $buttonText = ($status == 'Out of Stock')
                                            ? 'Reorder'
                                            : 'Restock';

                            ?>

                                <tr class="alert-row" data-name="<?php echo strtolower($productName); ?>">
                                    <td class="col-product">
                                        <?php echo htmlspecialchars($productName); ?>
                                    </td>

                                    <td class="col-qty">
                                        <?php echo $quantity; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo $badgeClass; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <button 
                                            class="btn-action-solid <?php echo $buttonClass; ?>" 
                                            onclick="goToStockIn(<?php echo $row['product_id']; ?>)"
                                        >
                                            <?php echo $buttonText; ?>
                                        </button>
                                    </td>
                                </tr>

                            <?php
                                    }

                                } else {

                            ?>

                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px; color:#64748b;">
                                        No stock alerts found.
                                    </td>
                                </tr>

                            <?php
                                }
                            ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Initialise Lucide icons
        lucide.createIcons();

        // --- Navigation Logic ---
        function goToStockIn(productId) {
            window.location.href = 'stockin.php?product_id=' + productId;
        }

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
        


        //SEARCH
        const alertSearchInput = document.getElementById('alertSearchInput');
        const alertRows = document.querySelectorAll('.alert-row');

        alertSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();

            alertRows.forEach(row => {
                const name = row.dataset.name;

                const match = name.includes(query);

                row.style.display = match ? '' : 'none';
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
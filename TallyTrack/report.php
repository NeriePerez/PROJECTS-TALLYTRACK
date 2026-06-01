<?php 
    include 'controllerProduct.php';
    
    // =========================
    // PRODUCTS BY CATEGORY
    // =========================
    $categoryLabels = [];
    $categoryCounts = [];

    $categoryQuery = "
        SELECT 
            c.category_name,
            COUNT(p.product_id) AS total_products
        FROM inv_category c
        LEFT JOIN inv_product p 
            ON p.category_id = c.category_id
        GROUP BY c.category_id
        ORDER BY total_products DESC
    ";

    $categoryResult = mysqli_query($conn, $categoryQuery);

    while($row = mysqli_fetch_assoc($categoryResult)) {
        $categoryLabels[] = $row['category_name'];
        $categoryCounts[] = $row['total_products'];
    }


    // =========================
    // STOCK QUANTITY PER CATEGORY
    // =========================
    $stockCategoryLabels = [];
    $stockCategoryTotals = [];

    $stockQuery = "
        SELECT 
            c.category_name,
            SUM(p.product_quantity) AS total_quantity
        FROM inv_category c
        LEFT JOIN inv_product p
            ON p.category_id = c.category_id
        GROUP BY c.category_id
        ORDER BY total_quantity DESC
    ";

    $stockResult = mysqli_query($conn, $stockQuery);

    while($row = mysqli_fetch_assoc($stockResult)) {
        $stockCategoryLabels[] = $row['category_name'];
        $stockCategoryTotals[] = (int)$row['total_quantity'];
    }


    // =========================
    // STOCK-IN (THIS MONTH)
    // =========================

    $stockInCount = 0;
    $stockInQuantity = 0;

    $stockInQuery = "
        SELECT 
            COUNT(*) AS total_stockin,
            COALESCE(SUM(stockIn_quantity), 0) AS total_quantity
        FROM inv_stockin si
        JOIN inv_transaction t ON t.transaction_id = si.transaction_id
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
    ";

    $stockInResult = mysqli_query($conn, $stockInQuery);

    if ($row = mysqli_fetch_assoc($stockInResult)) {
        $stockInCount = $row['total_stockin'];
        $stockInQuantity = $row['total_quantity'];
    }

    $topStockInLabels = [];
    $topStockInData = [];

    $topStockInQuery = "
        SELECT 
            p.product_name,
            SUM(s.stockIn_quantity) AS total_in
        FROM inv_stockin s
        JOIN inv_transaction t 
            ON t.transaction_id = s.transaction_id
        JOIN inv_product p 
            ON p.product_id = s.product_id
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())  
        GROUP BY s.product_id 
        ORDER BY total_in DESC
        LIMIT 5
    ";

    $topStockInResult = mysqli_query($conn, $topStockInQuery);

    while ($row = mysqli_fetch_assoc($topStockInResult)) {
        $topStockInLabels[] = $row['product_name'];
        $topStockInData[] = (int)$row['total_in'];
    }


    // =========================
    // STOCK-OUT (THIS MONTH)
    // =========================

    $stockOutCount = 0;
    $stockOutQuantity = 0;

    $stockOutQuery = "
        SELECT 
            COUNT(*) AS total_stockout,
            COALESCE(SUM(stockOut_quantity), 0) AS total_quantity
        FROM inv_stockout s 
        JOIN inv_transaction t ON t.transaction_id = s.transaction_id 
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
    ";

    $stockOutResult = mysqli_query($conn, $stockOutQuery);

    if ($row = mysqli_fetch_assoc($stockOutResult)) {
        $stockOutCount = $row['total_stockout'];
        $stockOutQuantity = $row['total_quantity'];
    }

    $topStockOutLabels = [];
    $topStockOutData = [];

    $topStockOutQuery = "
        SELECT 
            p.product_name,
            SUM(s.stockOut_quantity) AS total_out
        FROM inv_stockout s 
        JOIN inv_transaction t 
            ON t.transaction_id = s.transaction_id
        JOIN inv_product p 
            ON p.product_id = s.product_id
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
        GROUP BY s.product_id
        ORDER BY total_out DESC
        LIMIT 5
    ";

    $topStockOutResult = mysqli_query($conn, $topStockOutQuery);

    while ($row = mysqli_fetch_assoc($topStockOutResult)) {
        $topStockOutLabels[] = $row['product_name'];
        $topStockOutData[] = (int)$row['total_out'];
    }


    // =========================
    // DAILY STOCK MOVEMENT (CURRENT MONTH ONLY)
    // =========================

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

    $days = [];
    $stockInDaily = array_fill(1, $daysInMonth, 0);
    $stockOutDaily = array_fill(1, $daysInMonth, 0);

    for ($i = 1; $i <= $daysInMonth; $i++) {
        $days[] = $i;
    }

    // STOCK IN (THIS MONTH DAILY)
    $stockInDayQuery = "
        SELECT 
            DAY(t.transaction_date) AS day_num,
            SUM(s.stockIn_quantity) AS total_in
        FROM inv_stockin s
        JOIN inv_transaction t ON t.transaction_id = s.transaction_id
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
        GROUP BY DAY(t.transaction_date)
    ";

    $resIn = mysqli_query($conn, $stockInDayQuery);

    while ($row = mysqli_fetch_assoc($resIn)) {
        $stockInDaily[(int)$row['day_num']] = (int)$row['total_in'];
    }

    // STOCK OUT (THIS MONTH DAILY)
    $stockOutDayQuery = "
        SELECT 
            DAY(t.transaction_date) AS day_num,
            SUM(s.stockOut_quantity) AS total_out
        FROM inv_stockout s
        JOIN inv_transaction t ON t.transaction_id = s.transaction_id
        WHERE MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
        GROUP BY DAY(t.transaction_date)
    ";

    $resOut = mysqli_query($conn, $stockOutDayQuery);

    while ($row = mysqli_fetch_assoc($resOut)) {
        $stockOutDaily[(int)$row['day_num']] = (int)$row['total_out'];
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Reports & Analytics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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

        .sidebar.collapsed { width: var(--sidebar-collapsed-width); padding: 15px 10px; }

        .sidebar-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;
        }

        .brand-logo-area {
            display: flex; align-items: center; text-decoration: none; color: var(--text-on-dark);
        }

        .brand-logo-icon {
            width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; margin-right: 12px; object-fit: contain;
        }

        .brand-text-area { display: flex; flex-direction: column; }
        .brand-name { font-weight: 800; font-size: 1.1rem; }
        .brand-subtext { font-size: 0.8rem; color: #94a3b8; }

        .expanded-only { display: inherit; }
        .sidebar.collapsed .expanded-only { display: none !important; }

        #sidebarToggle {
            background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px;
            display: flex; align-items: center; justify-content: center;
        }
        .sidebar.collapsed #sidebarToggle { transform: rotate(180deg); }

        .menu-list { list-style: none; padding: 0; margin: 0; flex-grow: 1; overflow-y: auto; overflow-x: hidden; }
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
        .menu-link.active { color: var(--text-on-dark); background-color: var(--active-item-bg); font-weight: 600; }
        .menu-link:hover { color: var(--text-on-dark); background-color: var(--active-item-bg); transform: translateX(4px); }
        
        .menu-icon { width: 24px; height: 24px; margin-right: 12px; display: flex; align-items: center; justify-content: center; }
        .menu-link:hover .menu-icon, .menu-link.active .menu-icon { color: #10b981; }
        .sidebar.collapsed .menu-icon { margin-right: 0; }
        .sidebar.collapsed .menu-link { justify-content: center; }

        .sidebar-bottom { margin-top: auto; border-top: 1px solid #334155; padding-top: 15px; font-size: 0.8rem; color: #94a3b8; }
        .demo-info { margin-bottom: 10px; font-weight: 500;}
        .sign-out-link { display: flex; align-items: center; text-decoration: none; color: #ef4444; font-weight: 600; padding: 10px 0; }
        .sign-out-link:hover { color: #f87171; transform: translateX(4px); }

        /* --- Content Area Layout --- */
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* --- Main Global Header Elements --- */
        .main-header {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            flex-shrink: 0;
            z-index: 10;
        }

        .header-title-container {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-navy);
            margin: 0;
        }

        .header-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }
        
        /* --- Search Field Controller --- */
        .header-search {
            flex: 1;
            max-width: 400px;
            position: relative;
            margin: 0 30px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 1.5px solid transparent;
            border-radius: 20px;
            box-sizing: border-box;
            background-color: #e2e8f0;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--primary-navy);
            transition: all 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            background-color: #fff;
            border-color: #3b82f6;
        }

        /* --- Quick Actions (Right Panel) --- */
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-btn {
            background: #f1f5f9;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            position: relative;
        }

        .header-btn:hover {
            background-color: #e2e8f0;
            color: var(--primary-navy);
            transform: scale(1.05);
        }

        .notification-area {
            position: relative;
            display: flex;
            align-items: center;
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            background-color: #ef4444;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            border: 2px solid #fff;
            z-index: 1;
        }

        /* --- User Profile Dropdown Context --- */
        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px;
            border-radius: 30px;
            transition: background-color 0.2s;
        }

        .user-dropdown:hover {
            background-color: #f1f5f9;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 12px;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        .user-details {
            display: flex;
            flex-direction: column;
            text-align: right;
            margin-right: 8px;
        }

        .user-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--primary-navy);
        }

        .user-role {
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
        }

        /* --- Scrollable Content Window --- */
        .content-scrollable {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        /* --- Data Filters Toolbar --- */
        .controls-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }

        .dropdown-select {
            background-color: white;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-navy);
            outline: none;
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        .dropdown-select:focus {
            border-color: #3b82f6;
        }

        .export-btn {
            background-color: var(--primary-emerald);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-speed);
        }

        .export-btn:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        /* --- Card Structural Units --- */
        .card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius);
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .card-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary-navy);
            letter-spacing: -0.5px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
        }

        .chart-container {
            position: relative;
            height: 500px;
            width: 100%;
        }

        .reports-grid-two-col {
            display: grid;
            margin-bottom: 10px;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .reports-grid-unequal {
            display: grid;
            grid-template-columns: 5fr 7fr;
            gap: 24px;
        }

        .inner-metrics-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .mini-metric-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .mini-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: white;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        .mini-card-details {
            display: flex;
            flex-direction: column;
        }

        .mini-card-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .mini-card-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary-navy);
            margin-top: 2px;
        }

        .text-emerald {
            color: var(--primary-emerald);
        }

        .text-rose {
            color: var(--danger-rose);
        }

        @media (max-width: 1200px) {
            .reports-grid-two-col,
            .reports-grid-unequal {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .inner-metrics-row {
                grid-template-columns: 1fr;
            }
            .header-search {
                display: none;
            }
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

        @media print {

            /* Hide everything except report content */
            .sidebar,
            .main-header,
            .controls-bar {
                display: none !important;
            }

            body {
                background: white !important;
                overflow: visible !important;
            }

            .content-area {
                width: 100% !important;
            }

            .content-scrollable {
                padding: 0 !important;
            }

            /* Make cards full width for clean report */
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                break-inside: avoid;
            }

            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body>

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="brand-logo-area">
                <img src="TallyTrack 2.0.png" alt="Logo" class="brand-logo-icon" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display:none; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 8px; align-items: center; justify-content: center; font-weight: bold; color: white;">TT</div>
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
                <a href="alert.php" class="menu-link">
                    <i data-lucide="bell" class="menu-icon"></i>
                    <span class="expanded-only">Alerts</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="report.php" class="menu-link active">
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
            <div style="display:flex; align-items:center; gap:15px;">
                <button id="burgerToggle" style="display:none; background:none; border:none; cursor:pointer;"><i data-lucide="menu"></i></button>
                <div class="header-title-container">
                    <h1 class="header-title">Reports</h1>
                </div>
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
            
            <div class="controls-bar">
                <button class="export-btn" onclick="exportPDF()">
                    <i data-lucide="download" size="16"></i> Export PDF
                </button>
                <button class="export-btn" onclick="printReport()">
                    <i data-lucide="printer" size="16"></i> Print
                </button>
            </div>

            <div id="reportArea">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-wrapper">
                            <h2 class="card-title"><i data-lucide="trending-up" class="text-emerald" size="20"></i> Monthly Stock Movement</h2>
                            <span class="card-subtitle">Stock-in vs Stock-out trend across months</span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyMovementChart"></canvas>
                    </div>
                </div>

                <div class="reports-grid-two-col">
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title-wrapper">
                                <h2 class="card-title text-emerald"><i data-lucide="arrow-down-circle" size="20"></i> Stock-In Report</h2>
                                <span class="card-subtitle">Performance breakdown for this month</span>
                            </div>
                        </div>
                        
                        <div class="inner-metrics-row">
                            <div class="mini-metric-card">
                                <div class="mini-icon-box"><i data-lucide="file-text" size="18"></i></div>
                                <div class="mini-card-details">
                                    <span class="mini-card-label">Stock-In Count</span>
                                    <span class="mini-card-value"><?= $stockInCount ?></span>
                                </div>
                            </div>
                            <div class="mini-metric-card">
                                <div class="mini-icon-box"><i data-lucide="layers" size="18"></i></div>
                                <div class="mini-card-details">
                                    <span class="mini-card-label">Quantity Added</span>
                                    <span class="mini-card-value"><?= number_format($stockInQuantity) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="chart-container" style="height: 240px;">
                            <canvas id="topStockedChart"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title-wrapper">
                                <h2 class="card-title text-rose"><i data-lucide="arrow-up-circle" size="20"></i> Stock-Out Report</h2>
                                <span class="card-subtitle">Performance breakdown for this month</span>
                            </div>
                        </div>

                        <div class="inner-metrics-row">
                            <div class="mini-metric-card">
                                <div class="mini-icon-box"><i data-lucide="shopping-cart" size="18"></i></div>
                                <div class="mini-card-details">
                                    <span class="mini-card-label">Stock-Out Count</span>
                                    <span class="mini-card-value"><?= $stockOutCount ?></span>
                                </div>
                            </div>
                            <div class="mini-metric-card">
                                <div class="mini-icon-box"><i data-lucide="trending-up" size="18"></i></div>
                                <div class="mini-card-details">
                                    <span class="mini-card-label">Quantity Sold</span>
                                    <span class="mini-card-value"><?= number_format($stockOutQuantity) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="chart-container" style="height: 240px;">
                            <canvas id="mostSoldChart"></canvas>
                        </div>
                    </div>

                </div>

                <div class="reports-grid-unequal">
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title-wrapper">
                                <h2 class="card-title"><i data-lucide="pie-chart" style="color: #a855f7;" size="20"></i> Products by Category</h2>
                                <span class="card-subtitle">Inventory share distribution</span>
                            </div>
                        </div>
                        <div class="chart-container" style="height: 280px; display: flex; justify-content: center;">
                            <canvas id="productsByCategoryChart"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title-wrapper">
                                <h2 class="card-title"><i data-lucide="bar-chart-4" style="color: #3b82f6;" size="20"></i> Stock Quantity per Category</h2>
                                <span class="card-subtitle">Total remaining stored units</span>
                            </div>
                        </div>
                        <div class="chart-container" style="height: 280px;">
                            <canvas id="stockQuantityChart"></canvas>
                        </div>
                    </div>

                </div>

                
            </div>
            

        </div>
    </main>

    <script>
        // Init Lucide Vector Asset Engine
        lucide.createIcons();
        
        // Collapsible Responsive Navigation logic matched to reference file
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));

        // Shared Configuration Metrics for accurate cross-dragging tooltips
        const standardChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            }
        };

        // 1. Line Graphic: Monthly Stock Movement
        new Chart(document.getElementById('monthlyMovementChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_values($stockInDaily)) ?>,
                datasets: [
                    {
                        label: 'Stock-In',
                        data: <?= json_encode(array_values($stockInDaily)) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.03)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Stock-Out',
                        data: <?= json_encode(array_values($stockOutDaily)) ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.03)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                ...standardChartOptions,
                plugins: {
                    legend: { display: true, position: 'top', labels: { font: { family: 'Plus Jakarta Sans', weight: 500 } } } 
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
                }
            }
        });

        // 2. Bar Graphic Left: Top Products Stocked In
        new Chart(document.getElementById('topStockedChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($topStockInLabels) ?>,
                datasets: [{
                    data: <?= json_encode($topStockInData) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    barThickness: 14
                }]
            },
            options: {
                ...standardChartOptions,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
                    y: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
                }
            }
        });

        // 3. Bar Graphic Right: Most Sold Products
        new Chart(document.getElementById('mostSoldChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($topStockOutLabels) ?>,
                datasets: [{
                    data: <?= json_encode($topStockOutData) ?>,
                    backgroundColor: '#ef4444',
                    borderRadius: 6,
                    barThickness: 14
                }]
            },
            options: {
                ...standardChartOptions,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
                    y: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
                }
            }
        });

        // 4. Doughnut Share: Category Share
        new Chart(document.getElementById('productsByCategoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($categoryLabels) ?>,
                datasets: [{
                    data: <?= json_encode($categoryCounts) ?>   ,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#9ca3af'],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true, 
                        position: 'right', 
                        labels: { boxWidth: 12, padding: 12, font: { family: 'Plus Jakarta Sans', size: 12, weight: 600 } } 
                    }
                }
            }
        });

        // 5. Vertical Stack: Quantities Remaining
        new Chart(document.getElementById('stockQuantityChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($stockCategoryLabels) ?>,
                datasets: [{
                    data: <?= json_encode($stockCategoryTotals) ?>,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        const fillGradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        fillGradient.addColorStop(0, '#3b82f6');
                        fillGradient.addColorStop(1, '#10b981');
                        return fillGradient;
                    },
                    borderRadius: 6,
                    barThickness: 24
                }]
            },
            options: {
                ...standardChartOptions,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { family: 'Plus Jakarta Sans' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans' } } }
                }
            }
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

        function printReport() {
            window.print();
        }

        async function exportPDF() {
            const { jsPDF } = window.jspdf;
            const report = document.getElementById("reportArea");

            // SAVE ORIGINAL STYLE
            const originalHeight = report.style.height;
            const originalOverflow = report.style.overflow;

            // FORCE FULL RENDER
            report.style.height = "auto";
            report.style.overflow = "visible";

            const canvas = await html2canvas(report, {
                scale: 2,
                useCORS: true,
                scrollY: 0,
                windowHeight: report.scrollHeight
            });

            // RESTORE STYLE
            report.style.height = originalHeight;
            report.style.overflow = originalOverflow;

            const imgData = canvas.toDataURL("image/png");

            const pdf = new jsPDF("p", "mm", "legal");

            const pageWidth = 210;
            const imgHeight = (canvas.height * pageWidth) / canvas.width;

            pdf.addImage(imgData, "PNG", 0, 0, pageWidth, imgHeight);
            pdf.save("TallyTrack-Report.pdf");
        }
    </script>
</body>
</html>
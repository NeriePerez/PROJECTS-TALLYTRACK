<?php
    include 'controllerProduct.php';
    

    //$user_id = $_SESSION['id'];
    //$user_role = $_SESSION['role'];

    $totalProducts = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM inv_product"))[0];
    $availableStock = mysqli_fetch_row(mysqli_query($conn,"SELECT SUM(product_quantity) FROM inv_product"))[0];
    $lowStock = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM inv_product WHERE stockLevel = 'Low Stock'"))[0];
    $outOfStock = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM inv_product WHERE product_quantity = 0"))[0];
    
    $newProductsToday = mysqli_fetch_row(mysqli_query($conn, "
        SELECT COUNT(*) 
        FROM inv_product 
        WHERE DATE(created_at) = CURDATE()
    "))[0];
    $stockInToday = mysqli_fetch_row(mysqli_query($conn, "
        SELECT SUM(si.stockIn_quantity)
        FROM inv_stockin si
        JOIN inv_transaction t ON si.transaction_id = t.transaction_id
        WHERE DATE(t.transaction_date) = CURDATE()
    "))[0] ?? 0;


    $dailyStock = mysqli_query($conn, "
        SELECT 
            DATE(transaction_date) as day,
            SUM(stock_in) as stock_in,
            SUM(stock_out) as stock_out
        FROM (
            SELECT t.transaction_date, si.stockIn_quantity AS stock_in, 0 AS stock_out 
            FROM inv_stockin si
            JOIN inv_transaction t ON si.transaction_id = t.transaction_id

            UNION ALL

            SELECT t.transaction_date, 0 AS stock_in, so.stockOut_quantity AS stock_out
            FROM inv_stockout so 
            JOIN inv_transaction t ON so.transaction_id = t.transaction_id
        ) as combined
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(transaction_date)
        ORDER BY day ASC
    ");

    $labels = [];
    $stockInData = [];
    $stockOutData = [];

    while ($row = mysqli_fetch_assoc($dailyStock)) {
        $labels[] = $row['day'];
        $stockInData[] = $row['stock_in'];
        $stockOutData[] = $row['stock_out'];
    }

    $alerts = mysqli_query($conn, "
        SELECT 
            product_name,
            product_quantity
        FROM inv_product
        WHERE product_quantity <= 10
        ORDER BY product_quantity ASC 
        LIMIT 5
    ");


    $activity = mysqli_query($conn, "
    SELECT * FROM (

        -- STOCK IN
        SELECT 
            t.transaction_date AS activity_date,
            'stock-in' AS type,
            CONCAT(si.stockIn_quantity, ' × ', p.product_name) AS description
        FROM inv_stockin si
        JOIN inv_transaction t ON si.transaction_id = t.transaction_id
        JOIN inv_product p ON si.product_id = p.product_id

        UNION ALL

        -- STOCK OUT
        SELECT 
            t.transaction_date AS activity_date,
            'stock-out' AS type,
            CONCAT(so.stockOut_quantity, ' × ', p.product_name) AS description
        FROM inv_stockout so
        JOIN inv_transaction t ON so.transaction_id = t.transaction_id
        JOIN inv_product p ON so.product_id = p.product_id

        UNION ALL

        -- STOCK EDIT (assuming you have a log table OR use product update time)
        SELECT 
            t.transaction_date AS activity_date,
            'stock-edit' AS type,
            CONCAT(p.product_name, '(', se.stockEdit_quantity, ')') AS description
        FROM inv_stockedit se
        JOIN inv_transaction t ON t.transaction_id = se.transaction_id 
        JOIN inv_product p ON se.product_id = p.product_id

        UNION ALL

        -- ALERTS (low stock)
        SELECT 
            n.created_at AS activity_date,
            'alert' AS type,
            n.notification_message AS description
        FROM inv_notification n 
        JOIN inv_product p ON p.product_id = n.product_id 

    ) AS combined

    ORDER BY activity_date DESC
    LIMIT 5
    ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Dashboard Page</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        .search-input:focus { background-color: #fff; border-color: #3b82f6; }

        .header-right { display: flex; align-items: center; gap: 20px;}
        .notification-area { position: relative; }

        .header-btn {
            background: #f1f5f9; border: none; color: #64748b; cursor: pointer; padding: 10px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; position: relative; transition: all 0.2s;
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
            align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; margin-right: 12px;
        }

        .user-details { display: flex; flex-direction: column; text-align: right; margin-right: 8px;}
        .user-name { font-weight: 700; font-size: 0.95rem; color: var(--primary-navy); }
        .user-role { font-size: 0.8rem; font-weight: 500; color: #64748b; }

        /* --- Dashboard Content Grid --- */
        .content-scrollable {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background-color: transparent;
            box-sizing: border-box;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }

        /* --- ENHANCEMENT: Upgraded Glassmorphism & Hover Animations --- */
        .card {
            background: rgba(255, 255, 255, 0.85);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius);
            padding: 24px;
            /* Soft default shadow */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6);
            /* Bouncy, premium transition for hover effects */
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card:hover {
            /* Lifts up and scales slightly when hovered */
            transform: translateY(-8px) scale(1.015);
            /* Deepens the shadow to make it pop out */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(255, 255, 255, 1);
            z-index: 10;
        }

        .kpi-card {
            display: grid;
            grid-template-columns: 60px 1fr;
            align-items: center;
            gap: 15px;
        }

        .kpi-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }
        
        .card:hover .kpi-icon-box {
            transform: scale(1.1) rotate(5deg); /* playful icon bump on hover */
        }

        .kpi-info-box { display: flex; flex-direction: column; }
        .kpi-title { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--primary-navy); margin: 0 0 5px 0; letter-spacing: -0.5px;}
        .kpi-meta { font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; }

        .card-blue .kpi-icon-box { background-color: #dbeafe; color: #1d4ed8; }
        .card-green .kpi-icon-box { background-color: #dcfce7; color: #15803d; }
        .card-amber .kpi-icon-box { background-color: #fffbeb; color: #b45309; }
        .card-red .kpi-icon-box { background-color: #fee2e2; color: #b91c1c; }

        .kpi-meta.trend-prod { color: #1d4ed8; }
        .kpi-meta.trend-up { color: #15803d; }
        .kpi-meta.trend-low { color: #b45309; }
        .kpi-meta.trend-down { color: #b91c1c; }

        .middle-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .chart-title-area { display: flex; flex-direction: column; }
        .chart-title { font-size: 1.2rem; font-weight: 800; color: var(--primary-navy); margin: 0 0 5px 0; }
        .chart-subtext { font-size: 0.85rem; font-weight: 500; color: #64748b; }

        .chart-legend { display: flex; gap: 15px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .legend-item { display: flex; align-items: center; }
        .legend-color-dot { width: 10px; height: 10px; border-radius: 50%; margin-right: 7px; }

        .graph-area {
            height: 280px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            width: 100%;
        }

        .glance-title { font-size: 1.2rem; font-weight: 800; color: var(--primary-navy); margin: 0 0 20px 0; }
        .glance-list { list-style: none; padding: 0; margin: 0; }
        .glance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            font-weight: 500;
            color: #64748b;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            transition: background 0.2s ease, transform 0.2s ease;
            border-radius: 8px;
        }

        /* ENHANCEMENT: Hover effect for list items */
        .glance-item:hover {
            background: rgba(241, 245, 249, 0.8);
            transform: translateX(5px);
        }

        .glance-item:last-child { border-bottom: none; }
        .glance-value { font-weight: 800; color: var(--primary-navy); }
        .glance-currency { color: #15803d; }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .card-header-with-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-title { font-size: 1.2rem; font-weight: 800; color: var(--primary-navy); margin: 0; }
        .view-all-link { font-size: 0.85rem; color: #3b82f6; text-decoration: none; font-weight: 700; transition: color 0.2s;}
        .view-all-link:hover { color: #2563eb; text-decoration: underline; }

        .alerts-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .alerts-table th {
            text-align: left;
            color: #64748b;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .alerts-table td { padding: 14px 10px; border-bottom: 1px solid rgba(243, 244, 246, 0.6); }
        .alerts-table tr:last-child td { border-bottom: none; }
        
        /* ENHANCEMENT: Hover effect for table rows */
        .alerts-table tbody tr { transition: background-color 0.2s ease; border-radius: 8px; }
        .alerts-table tbody tr:hover { background-color: rgba(241, 245, 249, 0.8); }

        .product-col { font-weight: 700; color: var(--primary-navy); }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.low { background-color: #fef3c7; color: #b45309; }
        .status-badge.out { background-color: #fee2e2; color: #b91c1c; }

        .activity-card { display: flex; flex-direction: column; }
        .activity-list { list-style: none; padding: 0; margin: 0; }
        
        .activity-item {
            display: grid;
            grid-template-columns: 50px 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        /* ENHANCEMENT: Hover effect for activity items */
        .activity-item:hover {
            background-color: rgba(241, 245, 249, 0.8);
            transform: translateX(5px);
        }

        .activity-item:last-child { margin-bottom: 0; }
        .activity-icon-area { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }

        .activity-item.stock-in .activity-icon-area { background-color: #dcfce7; color: #15803d; }
        .activity-item.sale .activity-icon-area { background-color: #fee2e2; color: #b91c1c; }
        .activity-item.edit .activity-icon-area { background-color: #dbeafe; color: #1d4ed8; }
        .activity-item.alert .activity-icon-area { background-color: #fffbeb; color: #b45309; }

        .activity-text-area { display: flex; flex-direction: column; justify-content: center; }
        .activity-description { font-size: 0.95rem; font-weight: 500; color: var(--primary-navy); margin: 0 0 4px 0; }
        .activity-actor { font-weight: 800; margin-right: 5px; }
        .activity-timestamp { font-size: 0.8rem; font-weight: 600; color: #94a3b8; }

        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .middle-grid { grid-template-columns: 1fr; }
            .bottom-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .brand-subtext { display: none; }
            .brand-name { font-size: 0.9rem; }
            .kpi-value { font-size: 1.5rem; }
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
                <a href="dashboard.php" class="menu-link active">
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
                <h1 class="header-title">Dashboard</h1>
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
            
            <div class="kpi-grid">

                <div class="card kpi-card card-blue">
                    <div class="kpi-icon-box"><i data-lucide="package"></i></div>
                    <div>
                        <div class="kpi-title">Total Products</div>
                        <h2 class="kpi-value"><?= $totalProducts ?></h2>
                        <div class="kpi-meta trend-prod">
                            <i data-lucide="plus-circle" style="width:14px;height:14px;margin-right:5px;"></i>
                            <span><?= $newProductsToday ?> added today</span>
                        </div>
                    </div>
                </div>

                <div class="card kpi-card card-green">
                    <div class="kpi-icon-box"><i data-lucide="boxes"></i></div>
                    <div>
                        <div class="kpi-title">Available Stock</div>
                        <h2 class="kpi-value"><?= $availableStock ?></h2>
                        <div class="kpi-meta trend-up">
                            <i data-lucide="arrow-up-down" style="width:14px;height:14px;margin-right:5px;"></i>
                            <span>+<?= $stockInToday ?> stock-in today</span>
                        </div>
                    </div>
                </div>

                <div class="card kpi-card card-amber">
                    <div class="kpi-icon-box"><i data-lucide="alert-triangle"></i></div>
                    <div>
                        <div class="kpi-title">Low Stock</div>
                        <h2 class="kpi-value"><?= $lowStock ?></h2>
                        <div class="kpi-meta trend-low"> 
                            <i data-lucide="trending-down" style="width:14px;height:14px;margin-right:5px;"></i> 
                            <span>needs reorder</span> 
                        </div>
                    </div>
                </div>

                <div class="card kpi-card card-red">
                    <div class="kpi-icon-box"><i data-lucide="x-circle"></i></div>
                    <div>
                        <div class="kpi-title">Out of Stock</div>
                        <h2 class="kpi-value"><?= $outOfStock ?></h2>
                        <div class="kpi-meta trend-down"> 
                            <i data-lucide="refresh-cw" style="width:14px;height:14px;margin-right:5px;"></i> 
                            <span>needs reorder</span> 
                        </div>
                    </div>
                </div>
            </div>

            <div class="middle-grid">
                
                <div class="card chart-card">
                    <div class="chart-card-header">
                        <div class="chart-title-area">
                            <h3 class="chart-title">Daily Stock Movement</h3>
                            <div class="chart-subtext">Stock-in vs Stock-out per day</div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color-dot" style="background-color: #10b981;"></div>
                                <span>Stock-In</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color-dot" style="background-color: #ef4444;"></div>
                                <span>Stock-Out</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="graph-area">
                        <canvas id="monthlyStockGraph"></canvas>
                    </div>
                </div>
            </div>

            <div class="bottom-grid">
                
                <div class="card alerts-card">
                    <div class="card-header-with-action">
                        <h3 class="section-title">Low / Out of Stock Alerts</h3>
                        <a href="alert.php" class="view-all-link">View all →</a>
                    </div>
                    <table class="alerts-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($alerts)) { ?>
                                <tr>
                                    <td class="product-col"><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td><?= $row['product_quantity'] ?></td>
                                    <td>
                                        <?php if ($row['product_quantity'] == 0) { ?>
                                            <span class="status-badge out">Out</span>
                                        <?php } else { ?>
                                            <span class="status-badge low">Low</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="card activity-card">
                    <div class="card-header-with-action">
                        <h3 class="glance-title">Recent Activity</h3>
                        <a href="transaction.php" class="view-all-link">View all →</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php while ($row = mysqli_fetch_assoc($activity)) { 

                            $type = $row['type'];

                            if ($type == 'stock-in') {
                                $icon = "arrow-down-to-line";
                                $class = "stock-in";
                                $label = "Stock-In";
                            } 
                            elseif ($type == 'stock-out') {
                                $icon = "banknote";
                                $class = "sale";
                                $label = "Stock-Out";
                            } 
                            elseif ($type == 'stock-edit') {
                                $icon = "pencil";
                                $class = "edit";
                                $label = "Stock Edit";
                            } 
                            else {
                                $icon = "bell";
                                $class = "alert";
                                $label = "Alert";
                            }
                        ?>
                        
                        <li class="activity-item <?= $class ?>">
                            <div class="activity-icon-area">
                                <i data-lucide="<?= $icon ?>"></i>
                            </div>

                            <div class="activity-text-area">
                                <p class="activity-description">
                                    <span class="activity-actor"><?= $label ?></span> · <?= htmlspecialchars($row['description']) ?>
                                </p>

                                <span class="activity-timestamp">
                                    <?= date("M d, Y h:i A", strtotime($row['activity_date'])) ?>
                                </span>
                            </div>
                        </li>

                        <?php } ?>
                    </ul>
                </div>

            </div>

        </div>
    </main>

    <script>
        // Initialise Lucide icons
        lucide.createIcons();

        // Sidebar toggle logic
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');

        const labels = <?= json_encode($labels) ?>;
        const stockIn = <?= json_encode($stockInData) ?>;
        const stockOut = <?= json_encode($stockOutData) ?>;

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });

        // Initialize the interactive Chart.js Graph
        const canvas = document.getElementById('monthlyStockGraph');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            const gradientIn = ctx.createLinearGradient(0, 0, 0, 250);
            gradientIn.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradientIn.addColorStop(1, 'rgba(16, 185, 129, 0)');

            const gradientOut = ctx.createLinearGradient(0, 0, 0, 250);
            gradientOut.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
            gradientOut.addColorStop(1, 'rgba(239, 68, 68, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Stock-In',
                            data: stockIn,
                            borderColor: '#10b981',
                            backgroundColor: gradientIn,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4
                        },
                        {
                            label: 'Stock-Out',
                            data: stockOut,
                            borderColor: '#ef4444',
                            backgroundColor: gradientOut,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false, 
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 13, weight: '500' },
                            padding: 10,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' },
                            ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', weight: '600' } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', weight: '600' } }
                        }
                    }
                }
            });
        }

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
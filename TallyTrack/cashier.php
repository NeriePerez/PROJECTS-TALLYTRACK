<?php
    include 'controllerProduct.php';    

    $products = [];
    $search = $_GET['search'] ?? '';

    /* SAFE WHERE BUILDER */
    $where = "WHERE 1=1";

    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where .= " AND (
            p.product_name LIKE '%$search%'
            OR c.category_name LIKE '%$search%'
        )";
    }
    
    $query = mysqli_query($conn, "
        SELECT 
            p.product_id,
            p.product_name,
            c.category_name,
            p.product_sellingPrice,
            p.product_quantity
        FROM inv_product p
        JOIN inv_category c 
        ON p.category_id = c.category_id 
        $where
        ORDER BY product_name ASC
    ");

    while ($row = mysqli_fetch_assoc($query)) {

        $products[] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'category' => $row['category_name'],
            'price' => (float)$row['product_sellingPrice'],
            'stock' => (int)$row['product_quantity']
        ];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Cashier</title>
    
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


        .content-scrollable {
            flex: 1; padding: 30px; overflow-y: auto; overflow-x: hidden; background-color: transparent; box-sizing: border-box;
        }

        .cashier-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: rgba(255, 255, 255, 0.85); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.6); display: flex; flex-direction: column;
        }

        /* --- Left Side: Product Selection --- */
        .products-section {
            min-height: calc(100vh - 140px);
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;
        }
        .section-title-wrap { display: flex; flex-direction: column; }
        .section-title { font-size: 1.15rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 4px; }
        .section-subtitle { font-size: 0.85rem; color: #64748b; font-weight: 500; }

        /* FIXED: Added more padding at the top of the cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            overflow-y: auto;
            align-content: start;
            padding-top: 20px; 
        }

        /* ENHANCED DESIGN: Product Card */
        .product-card {
            border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; cursor: pointer;
            background: white; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;
            position: relative; user-select: none;
        }

        /* Hover lift effect */
        .product-card:hover:not(.empty) { 
            border-color: #94a3b8; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
            transform: translateY(-5px);
        }

        /* Empty state - Out of stock */
        .product-card.empty { 
            opacity: 0.5; 
            cursor: not-allowed; 
            background-color: #f1f5f9;
            border-color: #e2e8f0;
        }
        
        .pc-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .pc-category { font-size: 0.65rem; font-weight: 800; color: #94a3b8; background: #f8fafc; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; transition: transform 0.2s;}
        
        /* Category hover effect */
        .product-card:hover:not(.empty) .pc-category { transform: scale(1.05); color: var(--primary-navy); }
        
        .pc-stock { font-size: 0.7rem; font-weight: 600; color: #94a3b8; }
        
        .pc-name { font-size: 0.95rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 12px; line-height: 1.3;}
        
        /* Price hover effect */
        .pc-price { font-size: 1.1rem; font-weight: 800; color: var(--primary-emerald); margin-top: auto; transition: transform 0.3s ease;}
        .product-card:hover:not(.empty) .pc-price { transform: scale(1.1) translateX(5px); }

        /* --- Right Side: Receipt / Cart --- */
        .receipt-section {
            position: sticky;
            top: 0;
            height: calc(100vh - 140px);

            display: flex;
            flex-direction: column;

            overflow-y: auto;   /* 👈 ADD THIS */
            overflow-x: hidden;
        }



        .receipt-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 16px; }
        .receipt-title { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1.1rem; color: var(--primary-navy); }
        .receipt-order-id { font-size: 0.85rem; color: #64748b; font-weight: 600; }

        .cart-container {
            flex: 1;

            overflow: visible;   
            min-height: auto;

            display: flex;
            flex-direction: column;
            gap: 12px;

            padding-right: 4px;
        }
        .cart-container::-webkit-scrollbar { width: 4px; }
        .cart-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        .empty-cart { margin: auto; display: flex; flex-direction: column; align-items: center; color: #94a3b8; gap: 12px; }
        .empty-cart p { font-size: 0.9rem; font-weight: 500; }

        .cart-item { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: center; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0; }
        .ci-details { display: flex; flex-direction: column; gap: 4px; }
        .ci-name { font-size: 0.85rem; font-weight: 700; color: var(--primary-navy); }
        .ci-subtext { font-size: 0.75rem; color: #64748b; font-weight: 500;}
        
        .ci-qty-controls { display: flex; align-items: center; gap: 8px; padding: 4px; border-radius: 6px; }
        .qty-btn { background: white; border: 1px solid #cbd5e1; width: 24px; height: 24px; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--primary-navy); transition: 0.2s;}
        .qty-btn:hover { border-color: #94a3b8; background: #f8fafc;}
        .qty-val { font-size: 0.85rem; font-weight: 700; width: 16px; text-align: center; }
        
        .ci-price-col { display: flex; align-items: center; gap: 12px; }
        .ci-total { font-weight: 800; font-size: 0.9rem; color: var(--primary-navy); }
        .ci-remove { background: none; border: none; color: #ef4444; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center;}
        .ci-remove:hover { color: #dc2626; }

        .calc-area { margin-top: auto; padding-top: 16px; display: flex; flex-direction: column; gap: 16px; border-top: 1px solid #e2e8f0;}
        
        .subtotal-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; color: #64748b; font-weight: 600;}
        .subtotal-val { font-size: 1.25rem; font-weight: 800; color: var(--primary-emerald); }

        .cash-input-group { display: flex; flex-direction: column; gap: 8px; }
        .cash-input-group label { font-size: 0.85rem; font-weight: 700; color: var(--primary-navy); }
        .cash-input {
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 1rem; font-weight: 600; outline: none; transition: border-color 0.2s; background: white;
        }
        .cash-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .quick-cash-row { display: flex; gap: 8px; }
        .btn-quick-cash {
            flex: 1; padding: 6px 0; background: white; border: 1px solid #cbd5e1;
            border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;
        }
        .btn-quick-cash:hover { border-color: var(--primary-navy); color: var(--primary-navy); }

        .change-box {
            background-color: var(--primary-emerald); color: white; border-radius: 8px;
            padding: 16px; text-align: center; transition: background-color 0.3s ease;
        }
        .change-box.short { background-color: #ef4444; }
        .change-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; display: block; margin-bottom: 4px; }
        .change-val { font-size: 2rem; font-weight: 800; letter-spacing: -1px;}

        .btn-complete {
            background-color: var(--primary-emerald); color: white; border: none; padding: 14px;
            border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: background-color 0.2s;
        }
        .btn-complete:hover { background-color: #059669; }
        
        .btn-clear {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-clear:hover {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }
        /* Responsive */
        @media (max-width: 1100px) {
            .cashier-grid { grid-template-columns: 1fr; }
            .receipt-section { height: auto; position: static; }
            .products-section { min-height: 500px; }
        }

        @media (max-width: 900px) {
            .sidebar { position: fixed; top: 0; left: -280px; height: 100vh; width: 280px; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
            .sidebar.active { left: 0; }
            #sidebarToggle { display: none; }
            .mobile-burger { display: flex; align-items: center; justify-content: center; }
            .main-header { padding: 15px 20px; }
            .header-search { display: none; }
            .content-scrollable { padding: 20px 15px; }
        }

        .sale-modal {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .sale-modal-content {
            background: white;
            padding: 24px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
                <i data-lucide="chevron-left" class="menu-icon"></i>
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
                <a href="cashier.php" class="menu-link active">
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
                <button class="mobile-burger" id="burgerToggle">
                    <i data-lucide="menu"></i>
                </button>
                <h1 class="header-title">Cashier · Stock-Out</h1>
            </div>

            <div class="header-search">
                <i data-lucide="search" class="search-icon"></i>
                <input 
                    type="text" 
                    id="productSearchInput"
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
            <div class="cashier-grid">
                
                <div class="card products-section">
                    <div class="section-header">
                        <div class="section-title-wrap">
                            <h2 class="section-title">Select Products</h2>
                            <span class="section-subtitle">Tap a product to add it to the cart</span>
                        </div>
                    </div>

                    <div class="products-grid" id="productsGrid"></div>
                </div>

                <div class="card receipt-section">
                    
                    <div class="receipt-header">
                        <div class="receipt-title">
                            <i data-lucide="receipt" size="20"></i> Receipt
                        </div>
                    </div>

                    <div class="cart-container" id="cartContainer">
                        <div class="empty-cart" id="emptyCartState">
                            <i data-lucide="shopping-cart" size="40" style="color: #cbd5e1;"></i>
                            <p>Cart is empty. Tap a product to add.</p>
                        </div>
                    </div>

                    <div class="calc-area">
                        <div class="subtotal-row">
                            <span>Subtotal</span>
                            <span class="subtotal-val" id="subtotalDisplay">₱0.00</span>
                        </div>

                        <div class="cash-input-group">
                            <label>Cash Received (₱)</label>
                            <input type="number" id="cashInput" class="cash-input" placeholder="0.00" min="0" step="0.01">
                            
                            <div class="quick-cash-row">
                                <button class="btn-quick-cash" onclick="addQuickCash(50)">₱50</button>
                                <button class="btn-quick-cash" onclick="addQuickCash(100)">₱100</button>
                                <button class="btn-quick-cash" onclick="addQuickCash(200)">₱200</button>
                                <button class="btn-quick-cash" onclick="addQuickCash(500)">₱500</button>
                                <button class="btn-quick-cash" onclick="addQuickCash(1000)">₱1000</button>
                            </div>
                        </div>

                        <div class="change-box" id="changeBox">
                            <span class="change-label">Change</span>
                            <div class="change-val" id="changeDisplay">₱0.00</div>
                        </div>

                        <button class="btn-complete" id="btnCompleteSale">
                            <i data-lucide="check-circle-2" size="20"></i> Complete Sale
                        </button>
                        <button class="btn-clear" onclick="clearCart()">
                            Clear Cart
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </main>

    <!-- SALE MODAL -->
    <div id="saleModal" class="sale-modal">
        <div class="sale-modal-content">
            <h2>Sale Completed</h2>

            <div id="saleSummary"></div>

            <button onclick="closeSaleModal()" class="btn-complete" style="margin-top:15px;">
                OK
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // --- Sidebar Logic ---
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const burgerToggle = document.getElementById('burgerToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        
        function toggleMobileMenu() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        }
        burgerToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);


        // POS & CART JAVASCRIPT LOGIC

        let inventory = <?php echo json_encode($products); ?>;

        let cart = [];
        let subtotal = 0;

        const productsGrid = document.getElementById('productsGrid');
        const cartContainer = document.getElementById('cartContainer');
        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const cashInput = document.getElementById('cashInput');
        const changeBox = document.getElementById('changeBox');
        const changeDisplay = document.getElementById('changeDisplay');
        const btnCompleteSale = document.getElementById('btnCompleteSale');
        const searchInput = document.getElementById('productSearchInput');

        const formatMoney = (num) => `₱${num.toFixed(2)}`;

        // Render Products Grid
        function renderProducts(filter = '') {
            productsGrid.innerHTML = '';
            
            const filteredInventory = inventory.filter(p => 
                p.name.toLowerCase().includes(filter.toLowerCase()) || 
                p.category.toLowerCase().includes(filter.toLowerCase())
            );

            filteredInventory.forEach(prod => {
                const isOutOfStock = prod.stock === 0;
                
                const card = document.createElement('div');
                card.className = `product-card ${isOutOfStock ? 'empty' : ''}`;
                if(!isOutOfStock) card.onclick = () => addToCart(prod.id);

                card.innerHTML = `
                    <div class=\"pc-top\">
                        <span class=\"pc-category\">${prod.category}</span>
                        <span class=\"pc-stock\">${prod.stock} left</span>
                    </div>
                    <div class=\"pc-name\">${prod.name}</div>
                    <div class=\"pc-price\">${formatMoney(prod.price)}</div>
                `;
                productsGrid.appendChild(card);
            });
        }

        searchInput.addEventListener('input', (e) => renderProducts(e.target.value));

        // Add to Cart
        function addToCart(productId) {
            productId = Number(productId);

            const product = inventory.find(p => Number(p.id) === productId);
            const cartItem = cart.find(c => c.id === productId);

            if (cartItem) {
                if (cartItem.qty < product.stock) {
                    cartItem.qty++;
                }
            } else {
                cart.push({ ...product, id: productId, qty: 1 });
            }

            updateCartUI();
        }

        // Update Cart Quantity
        function updateCartQty(productId, delta) {
            productId = Number(productId);

            const cartItemIndex = cart.findIndex(c => c.id === productId);
            if (cartItemIndex > -1) {
                const item = cart[cartItemIndex];
                const product = inventory.find(p => Number(p.id) === productId);

                item.qty += delta;

                if (item.qty > product.stock) item.qty = product.stock;

                if (item.qty <= 0) cart.splice(cartItemIndex, 1);

                updateCartUI();
            }
        }

        function removeFromCart(productId) {
            cart = cart.filter(c => c.id !== productId);
            updateCartUI();
        }

        function clearCart() {
            cart = [];
            cashInput.value = '';
            updateCartUI();
        }

        // Quick Cash Helper
        function addQuickCash(amount) {
            let current = parseFloat(cashInput.value) || 0;
            cashInput.value = (current + amount).toFixed(2);
            calculateChange();
        }

        cashInput.addEventListener('input', calculateChange);

        // Update Cart UI & Math
        function updateCartUI() {

            if (cart.length > 0) {

                cartContainer.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="ci-details">
                            <span class="ci-name">${item.name}</span>
                            <span class="ci-subtext">
                                ${formatMoney(item.price)}
                            </span>
                        </div>

                        <div class="ci-qty-controls">
                            <button class="qty-btn"
                                onclick="updateCartQty(${item.id}, -1)">
                                <i data-lucide="minus" size="14"></i>
                            </button>

                            <span class="qty-val">${item.qty}</span>

                            <button class="qty-btn"
                                onclick="updateCartQty(${item.id}, 1)">
                                <i data-lucide="plus" size="14"></i>
                            </button>
                        </div>

                        <div class="ci-price-col">
                            <span class="ci-total">
                                ${formatMoney(item.price * item.qty)}
                            </span>

                            <button class="ci-remove"
                                onclick="removeFromCart(${item.id})">
                                <i data-lucide="x" size="16"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

            } else {

                cartContainer.innerHTML = `
                    <div class="empty-cart" id="emptyCartState">
                        <i data-lucide="shopping-cart"
                            size="40"
                            style="color: #cbd5e1;">
                        </i>

                        <p>Cart is empty. Tap a product to add.</p>
                    </div>
                `;
            }

            lucide.createIcons();

            subtotal = cart.reduce(
                (sum, item) => sum + (item.price * item.qty),
                0
            );

            subtotalDisplay.innerText = formatMoney(subtotal);

            calculateChange();
        }

        // Calculate Change logic
        function calculateChange() {
            const cash = parseFloat(cashInput.value) || 0;
            const difference = cash - subtotal;

            if (subtotal === 0) {
                changeBox.className = 'change-box';
                changeDisplay.innerText = '₱0.00';
                return;
            }

            if (difference < 0 && cash > 0) {
                changeBox.className = 'change-box short';
                changeDisplay.innerText = `-${formatMoney(Math.abs(difference))} short`;
            } else if (cash >= subtotal) {
                changeBox.className = 'change-box';
                changeDisplay.innerText = formatMoney(difference);
            } else {
                changeBox.className = 'change-box';
                changeDisplay.innerText = '₱0.00';
            }
        }

        // Sale Completion logic
        btnCompleteSale.addEventListener('click', async () => {

            if (cart.length === 0) {
                alert('Cart is empty.');
                return;
            }

            const cash = parseFloat(cashInput.value) || 0;

            if (cash < subtotal) {
                alert('Insufficient cash received.');
                return;
            }

            try {
                const result = await saveTransactionToDB();

                if (!result.success) {
                    alert("Transaction failed!");
                    return;
                }

                const changeAmount = cash - subtotal;

                openSaleModal(`
                    <p><b>Transaction ID:</b> ${result.transaction_id}</p>
                    <p><b>Total:</b> ₱${subtotal.toFixed(2)}</p>
                    <p><b>Cash:</b> ₱${cash.toFixed(2)}</p>
                    <p><b>Change:</b> ₱${changeAmount.toFixed(2)}</p>
                `);

                clearCart();
                renderProducts();
            } catch (err) {
                console.error(err);
                alert("Server error.");
            }
        });

        // Init
        renderProducts();

        function openSaleModal(html) {
            document.getElementById('saleSummary').innerHTML = html;
            document.getElementById('saleModal').style.display = 'flex';
        }

        function closeSaleModal() {
            document.getElementById('saleModal').style.display = 'none';
            location.reload();
        }

        async function saveTransactionToDB() {

            const formData = new FormData();
            formData.append("stockOut", "1");
            formData.append("user_id", 1);
            formData.append("cart", JSON.stringify(cart));

            const response = await fetch("controllerProduct.php", {
                method: "POST",
                body: formData
            });

            return await response.json();
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
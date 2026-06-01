<?php
    include 'controllerProduct.php';
    
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | Transaction History</title>
    
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

        /* --- Unified Card Design --- */
        .content-scrollable { flex: 1; padding: 30px; overflow-y: auto; box-sizing: border-box; }

        .card {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px);
            border-radius: var(--card-border-radius); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .card-title { font-size: 1.15rem; font-weight: 800; color: var(--primary-navy); letter-spacing: -0.5px; }

        .filters { display: flex; gap: 12px; }
        .filter-select, .date-input {
            padding: 8px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; 
            font-family: inherit; font-size: 0.85rem; font-weight: 600; color: var(--primary-navy); outline: none;
        }

        /* --- Table Styling --- */
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 8px;}
        .stock-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        
        .stock-table th {
            text-align: left; color: #64748b; font-weight: 800; padding: 12px 15px 16px 15px;
            border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
        }

        .stock-table td { padding: 16px 15px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); vertical-align: middle; }
        .stock-table tbody tr:hover td { background-color: #f8fafc; }

        .badge-type {
            padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block;
        }
        .type-stock-in { background-color: #ecfdf5; color: #10b981; }
        .type-stock-out { background-color: #fef2f2; color: #ef4444; }

        @media (max-width: 900px) {
            .sidebar { position: fixed; left: -280px; height: 100vh; }
            .sidebar.active { left: 0; }
            .mobile-burger { display: block; }
            .header-search { display: none; }
            .expanded-only { display: none; }
        }
        .type-stock-edit {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .view-btn {
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .view-btn:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(59, 130, 246, 0.25);
        }

        .view-btn:active {
            transform: translateY(0px);
            box-shadow: none;
        }

        .view-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
        .modal-body {
            padding: 10px 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .details-table th {
            background: #f8fafc;
            font-weight: 700;
        }

        .details-table tr:hover td {
            background: #f1f5f9;
        }

        .modal-content {
            background: #fff;
            width: 600px;
            max-width: 90%;
            margin: 8% auto;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: pop 0.2s ease;
        }

        @keyframes pop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .close {
            font-size: 22px;
            cursor: pointer;
            font-weight: bold;
            color: #ef4444;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            text-align: left;
            font-size: 12px;
            color: #64748b;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .modal-title-stock-in {
            color: #10b981;
        }

        .modal-title-stock-out {
            color: #ef4444;
        }

        .modal-title-stock-edit {
            color: #2563eb;
        }

        .date-filter-group{
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-filter-group label{
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            padding-left: 2px;
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
                <a href="alert.php" class="menu-link">
                    <i data-lucide="bell" class="menu-icon"></i>
                    <span class="expanded-only">Alerts</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="report.php" class="menu-link">
                    <i data-lucide="file-text" class="menu-icon"></i>
                    <span class="expanded-only">Reports & Analytics</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="transaction.php" class="menu-link active">
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
            <div class="header-title-area">
                <button class="mobile-burger" id="burgerToggle"><i data-lucide="menu"></i></button>
                <h1 class="header-title">Transaction History</h1>
            </div>
            
            <div class="header-search">
                <i data-lucide="search" class="search-icon" size="18"></i>
                <input type="text" id="searchInput" placeholder="Search product by name..." class="search-input">
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
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Transactions</h2>
                    <div class="filters">

                        <select class="filter-select" id="typeFilter">
                            <option value="all">All Types</option>
                            <option value="stock-in">Stock-In</option>
                            <option value="stock-out">Stock-Out</option>
                            <option value="stock-edit">Stock-Edit</option>
                        </select>

                        <div class="date-filter-group">
                            <label for="startDate">From</label>
                            <input type="date" class="date-input" id="startDate">
                        </div>

                        <div class="date-filter-group">
                            <label for="endDate">To</label>
                            <input type="date" class="date-input" id="endDate">
                        </div>

                    </div>
                </div>

                <div class="table-responsive">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>TYPE</th>
                                <th>PRODUCT COUNT</th>
                                <th>USER</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = $conn->query("
                                    SELECT 
                                        t.transaction_id,
                                        t.transaction_type,
                                        t.transaction_date,
                                        u.user_fullname,

                                        GROUP_CONCAT(
                                            DISTINCT COALESCE(p.product_name, '')
                                            SEPARATOR ', '
                                        ) AS products,

                                        (
                                            SELECT COUNT(*) FROM inv_stockin si WHERE si.transaction_id = t.transaction_id
                                        ) +
                                        (
                                            SELECT COUNT(*) FROM inv_stockout so WHERE so.transaction_id = t.transaction_id
                                        ) +
                                        (
                                            SELECT COUNT(*) FROM inv_stockedit se WHERE se.transaction_id = t.transaction_id
                                        ) AS product_count

                                    FROM inv_transaction t
                                    JOIN inv_user u ON t.user_id = u.user_id

                                    LEFT JOIN inv_stockin si ON si.transaction_id = t.transaction_id
                                    LEFT JOIN inv_stockout so ON so.transaction_id = t.transaction_id
                                    LEFT JOIN inv_stockedit se ON se.transaction_id = t.transaction_id

                                    LEFT JOIN inv_product p 
                                        ON p.product_id = si.product_id 
                                        OR p.product_id = so.product_id 
                                        OR p.product_id = se.product_id

                                    GROUP BY t.transaction_id
                                    ORDER BY t.transaction_date DESC
                                ");

                                while ($row = $query->fetch_assoc()) {
                            ?>
                                <tr 
                                    data-type="<?= $row['transaction_type'] ?>"
                                    data-date="<?= date('Y-m-d', strtotime($row['transaction_date'])) ?>"
                                    data-products="<?= strtolower($row['products']) ?>"
                                    data-user="<?= strtolower($row['user_fullname']) ?>"
                                >

                                    <!-- DATE -->
                                    <td>
                                        <?= date("M d, Y h:i A", strtotime($row['transaction_date'])) ?>
                                    </td>

                                    <!-- TYPE -->
                                    <td>
                                        <span class="badge-type 
                                            <?= 
                                                $row['transaction_type'] == 'stock-in' ? 'type-stock-in' : 
                                                ($row['transaction_type'] == 'stock-out' ? 'type-stock-out' : 'type-stock-edit')
                                            ?>">
                                            <?= ucfirst($row['transaction_type']) ?>
                                        </span>
                                    </td>

                                    <!-- PRODUCT COUNT -->
                                    <td style="font-weight:700;">
                                        <?= $row['product_count'] ?>
                                    </td>

                                    <!-- USER -->
                                    <td>
                                        <?= htmlspecialchars($row['user_fullname']) ?>
                                    </td>

                                    <!-- ACTION -->
                                    <td>
                                        <button class="view-btn" onclick="viewDetails(<?= $row['transaction_id'] ?>, '<?= $row['transaction_type'] ?>')">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- TRANSACTION DETAILS MODAL -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Transaction Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

            <div class="modal-body">
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody id="detailsBody">
                        <!-- dynamic -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const burgerToggle = document.getElementById('burgerToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        
        function toggleMobile() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        }
        burgerToggle.addEventListener('click', toggleMobile);
        mobileOverlay.addEventListener('click', toggleMobile);

        function viewDetails(transactionId, transactionType) {

            fetch("controllerProduct.php?get_transaction_details=1&transaction_id=" + transactionId)
                .then(res => res.json())
                .then(data => {

                    let tbody = document.getElementById("detailsBody");
                    tbody.innerHTML = "";

                    data.forEach(item => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.quantity}</td>
                            </tr>
                        `;
                    });

                    // MODAL TITLE
                    let modalTitle = document.getElementById("modalTitle");

                    // remove old classes
                    modalTitle.classList.remove(
                        "modal-title-stock-in",
                        "modal-title-stock-out",
                        "modal-title-stock-edit"
                    );

                    if(transactionType === "stock-in"){

                        modalTitle.innerText = "Stock-In Transaction";
                        modalTitle.classList.add("modal-title-stock-in");

                    } else if(transactionType === "stock-out"){

                        modalTitle.innerText = "Stock-Out Transaction";
                        modalTitle.classList.add("modal-title-stock-out");

                    } else {

                        modalTitle.innerText = "Stock-Edit Transaction";
                        modalTitle.classList.add("modal-title-stock-edit");
                    }

                    document.getElementById("detailsModal").style.display = "block";
                });
        }

        function closeModal() {
            document.getElementById("detailsModal").style.display = "none";
        }

        // click outside modal
        window.onclick = function(event) {
            let modal = document.getElementById("detailsModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }


        //FILTER
        const typeFilter = document.getElementById("typeFilter");
        const startDate = document.getElementById("startDate");
        const endDate = document.getElementById("endDate");

        function filterTransactions() {

            let selectedType = typeFilter.value;
            let start = startDate.value;
            let end = endDate.value;

            let rows = document.querySelectorAll(".stock-table tbody tr");

            rows.forEach(row => {

                let rowType = row.getAttribute("data-type");
                let rowDate = row.getAttribute("data-date");

                let typeMatch =
                    selectedType === "all" ||
                    rowType === selectedType;

                let dateMatch = true;

                if(start && rowDate < start){
                    dateMatch = false;
                }

                if(end && rowDate > end){
                    dateMatch = false;
                }

                if(typeMatch && dateMatch){

                    row.style.display = "";

                } else {

                    row.style.display = "none";
                }
            });
        }

        typeFilter.addEventListener("change", filterTransactions);
        startDate.addEventListener("change", filterTransactions);
        endDate.addEventListener("change", filterTransactions);

        //SEARCH
        const searchInput = document.getElementById("searchInput");

        searchInput.addEventListener("input", filterTransactions);

        function filterTransactions() {

            let selectedType = typeFilter.value;
            let start = startDate.value;
            let end = endDate.value;
            let keyword = searchInput.value.toLowerCase();

            let rows = document.querySelectorAll(".stock-table tbody tr");

            rows.forEach(row => {

                let rowType = row.getAttribute("data-type");
                let rowDate = row.getAttribute("data-date");
                let products = row.getAttribute("data-products") || "";
                let user = row.getAttribute("data-user") || "";

                // TYPE FILTER
                let typeMatch =
                    selectedType === "all" || rowType === selectedType;

                // DATE FILTER
                let dateMatch = true;
                if (start && rowDate < start) dateMatch = false;
                if (end && rowDate > end) dateMatch = false;

                // SEARCH FILTER (PRODUCT + USER)
                let searchMatch =
                    products.includes(keyword) ||
                    user.includes(keyword) ||
                    keyword === "";

                if (typeMatch && dateMatch && searchMatch) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
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
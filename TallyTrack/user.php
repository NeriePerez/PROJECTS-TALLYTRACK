<?php
    include 'controllerProduct.php';

    $result = $conn->query("
        SELECT 
            user_id,
            user_fullname,
            user_username,
            user_role
        FROM inv_user
    ");

    $adminCountQuery = $conn->query("
        SELECT COUNT(*) AS totalAdmins
        FROM inv_user
        WHERE user_role = 'owner'
    ");

    $adminCountRow = $adminCountQuery->fetch_assoc();
    $totalAdmins = $adminCountRow['totalAdmins'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallyTrack | User Management</title>
    
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

        .brand-logo-area { display: flex; align-items: center; text-decoration: none; color: var(--text-on-dark); }
        .brand-logo-icon {
            width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; 
            justify-content: center; font-weight: bold; font-size: 1.2rem; margin-right: 12px; object-fit: contain;
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
        .sidebar.collapsed .menu-link { justify-content: center; }
        .sidebar.collapsed .menu-icon { margin-right: 0; }

        .sidebar-bottom { margin-top: auto; border-top: 1px solid #334155; padding-top: 15px; font-size: 0.8rem; color: #94a3b8; }
        .demo-info { margin-bottom: 10px; font-weight: 500;}
        .sign-out-link { display: flex; align-items: center; text-decoration: none; color: #ef4444; font-weight: 600; padding: 10px 0; }
        .sign-out-link:hover { color: #f87171; transform: translateX(4px); }

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
        .card-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-add {
            background: var(--primary-emerald); color: white; border: none; padding: 10px 20px; border-radius: 8px;
            cursor: pointer; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .btn-add:hover { background: #059669; transform: translateY(-1px); }

        .btn-log {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-log:hover {
            background: #2563eb;
            transform: translateY(-1px);
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

        .role-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .role-admin { background-color: #e0e7ff; color: #4338ca; }
        .role-cashier { background-color: #dcfce7; color: #15803d; }

        .actions-cell { color: #94a3b8; text-align: right; }
        .actions-cell i { margin-left: 15px; cursor: pointer; transition: 0.2s; }
        .actions-cell i:hover { color: var(--primary-navy); }
        .fa-trash:hover { color: #ef4444 !important; }

        @media (max-width: 900px) {
            .sidebar { position: fixed; left: -280px; height: 100vh; }
            .sidebar.active { left: 0; }
            .mobile-burger { display: block; }
            .header-search { display: none; }
            .expanded-only { display: none; }
        }

        .action-btn{
            border: none;
            background: transparent;
            cursor: pointer;

            width: 38px;
            height: 38px;

            border-radius: 10px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            color: #64748b;

            transition: 0.2s ease;
        }

        .action-btn:hover{
            background: #f1f5f9;
            transform: translateY(-1px);
        }

        .role-btn:hover{
            color: #2563eb;
        }

        .edit-btn:hover{
            color: #10b981;
        }

        .delete-btn:hover{
            color: #ef4444;
        }

        .session-message{
            margin: 20px 30px 0 30px;
            padding: 14px 18px;
            border-radius: 12px;

            font-weight: 600;
            font-size: 0.95rem;

            animation: fadeIn 0.3s ease;
        }

        .session-message.success{
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .session-message.error{
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .session-message.warning{
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        @keyframes fadeIn{
            from{
                opacity: 0;
                transform: translateY(-5px);
            }

            to{
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* ================= MODAL ================= */

        .modal-overlay{
            position: fixed;
            inset: 0;

            background: rgba(15, 23, 42, 0.55);

            display: none;
            align-items: center;
            justify-content: center;

            z-index: 9999;

            padding: 20px;
        }

        .modal-overlay.active{
            display: flex;
        }

        .modal-box{
            width: 100%;
            max-width: 480px;

            background: white;

            border-radius: 18px;

            padding: 24px;

            box-shadow: 0 20px 40px rgba(0,0,0,0.15);

            animation: modalFade 0.25s ease;
        }

        .modal-header{
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 20px;
        }

        .modal-header h2{
            margin: 0;

            font-size: 1.3rem;
            font-weight: 800;

            color: var(--primary-navy);
        }

        .modal-close{
            border: none;
            background: #f1f5f9;

            width: 38px;
            height: 38px;

            border-radius: 10px;

            cursor: pointer;

            display: flex;
            align-items: center;
            justify-content: center;

            transition: 0.2s ease;
        }

        .modal-close:hover{
            background: #e2e8f0;
        }

        .form-group{
            display: flex;
            flex-direction: column;

            margin-bottom: 18px;
        }

        .form-group label{
            margin-bottom: 8px;

            font-weight: 700;
            font-size: 0.9rem;

            color: #334155;
        }

        .form-group input,
        .form-group select{
            padding: 12px 14px;

            border: 1.5px solid #dbe2ea;

            border-radius: 10px;

            font-size: 0.95rem;

            outline: none;

            transition: 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus{
            border-color: #10b981;

            box-shadow: 0 0 0 4px rgba(16,185,129,0.12);
        }

        .modal-actions{
            display: flex;
            justify-content: flex-end;
            gap: 12px;

            margin-top: 25px;
        }

        .btn-cancel{
            border: none;

            background: #e2e8f0;
            color: #334155;

            padding: 11px 18px;

            border-radius: 10px;

            font-weight: 700;

            cursor: pointer;
        }

        .btn-save{
            border: none;

            background: #10b981;
            color: white;

            padding: 11px 18px;

            border-radius: 10px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .btn-save:hover{
            background: #059669;
        }

        @keyframes modalFade{
            from{
                opacity: 0;
                transform: translateY(-10px);
            }

            to{
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================= LOGS MODAL OVERRIDE ================= */

        #logsModal .modal-box{
            max-width: 900px;   /* wider for table */
            width: 95%;
            padding: 20px;
        }

        /* Make table scroll nicely inside modal */
        #logsModal .table-responsive{
            max-height: 60vh;
            overflow-y: auto;
            border-radius: 12px;
        }

        /* Sticky header for readability */
        #logsModal .stock-table thead th{
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 2;
        }

        /* Better spacing for log rows */
        #logsModal .stock-table td,
        #logsModal .stock-table th{
            padding: 14px 12px;
        }

        /* Role badge consistency inside modal */
        #logsModal .role-badge{
            font-size: 0.7rem;
            padding: 5px 10px;
        }

        /* Slightly softer background for rows */
        #logsModal .stock-table tbody tr:hover td{
            background-color: #f1f5f9;
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
                <a href="transaction.php" class="menu-link">
                    <i data-lucide="history" class="menu-icon"></i>
                    <span class="expanded-only">Transaction History</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="user.php" class="menu-link active">
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
                <h1 class="header-title">User Management</h1>
            </div>
            
            <div class="header-search">
                <i data-lucide="search" class="search-icon" size="18"></i>
                <input type="text" id="userSearch" placeholder="Search user by name/username..." class="search-input">
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


        <!--SESSION MESSAGE-->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="session-message <?php echo $_SESSION['msg_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>


        <div class="content-scrollable">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Authorized Users</h2>
                    <div class="card-actions">
                            <button class="btn-log" onclick="openLogsModal()">
                            <i data-lucide="activity" size="18"></i>
                            View All Logs
                        </button>
                        <button class="btn-add" onclick="openAddUserModal()""><i data-lucide="user-plus" size="18"></i> Add User</button>
                    </div>
                    
                </div>

                <div class="table-responsive">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">

                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                    $roleClass = strtolower($row['user_role']) === 'owner'
                                        ? 'role-admin'
                                        : 'role-cashier';

                                    $roleActionTitle = strtolower($row['user_role']) === 'owner'
                                        ? 'Revoke Admin'
                                        : 'Make Admin';

                                    $roleActionIcon = strtolower($row['user_role']) === 'owner'
                                        ? 'shield-off'
                                        : 'shield-check';

                                    $user_id = $row['user_id'];

                                    $recentQuery = $conn->query("
                                        SELECT activityLog_timeIn
                                        FROM inv_activitylog
                                        WHERE user_id = '$user_id'
                                        ORDER BY activityLog_timeIn DESC
                                        LIMIT 1
                                    ");

                                    $recentLog = $recentQuery->fetch_assoc();

                                    $lastLogin = $recentLog
                                        ? date("M d, Y h:i A", strtotime($recentLog['activityLog_timeIn']))
                                        : "No activity";
                                ?>

                                <tr class="user-row">
                                    <td style="font-weight:700;" class="fullname">
                                        <?php echo htmlspecialchars($row['user_fullname']); ?>
                                    </td>

                                    <td class="username">
                                        <?php echo htmlspecialchars($row['user_username']); ?>
                                    </td>

                                    <td>
                                        <span class="role-badge <?php echo $roleClass; ?>">
                                            <?php echo htmlspecialchars(strtoupper($row['user_role'])); ?>
                                        </span>
                                    </td>

                                    <td style="color:#64748b;">
                                        <?php echo $lastLogin; ?>   
                                    </td>

                                    <td class="actions-cell">

                                        <button 
                                            class="action-btn role-btn"
                                            title="<?php echo $roleActionTitle; ?>"
                                            onclick="toggleRole(
                                                <?php echo $row['user_id']; ?>,
                                                '<?php echo $row['user_fullname']; ?>',
                                                '<?php echo $row['user_role']; ?>',
                                                <?php echo $totalAdmins; ?>
                                            )"
                                        >
                                            <i data-lucide="<?php echo $roleActionIcon; ?>" size="18"></i>
                                        </button>

                                        <button 
                                            class="action-btn edit-btn"
                                            title="Edit User"
                                            onclick="editUser(
                                                '<?php echo $row['user_id']; ?>',
                                                '<?php echo htmlspecialchars($row['user_fullname'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['user_username'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['user_role'], ENT_QUOTES); ?>'
                                            )"
                                        >
                                            <i data-lucide="edit-3" size="18"></i>
                                        </button>

                                        <button 
                                            class="action-btn delete-btn"
                                            title="Delete User"
                                            onclick="deleteUser(<?php echo $row['user_id']; ?>)"
                                        >
                                            <i data-lucide="trash-2" size="18"></i>
                                        </button>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>


    <!-- ADD USER MODAL -->
    <div class="modal-overlay" id="addUserModal">

        <div class="modal-box">

            <div class="modal-header">
                <h2>Add New User</h2>

                <button class="modal-close" onclick="closeAddUserModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form action="controllerProduct.php" method="POST">

                <div class="form-group">
                    <label>Full Name</label>

                    <input 
                        type="text"
                        name="fullname"
                        required
                        placeholder="Enter full name"
                    >
                </div>

                <div class="form-group">
                    <label>Username</label>

                    <input 
                        type="text"
                        name="username"
                        required
                        placeholder="Enter username"
                    >
                </div>

                <div class="form-group">
                    <label>Password</label>

                    <input 
                        type="password"
                        name="password"
                        required
                        placeholder="Enter password"
                    >
                </div>

                <div class="form-group">
                    <label>Role</label>

                    <select name="role" required>
                        <option value="staff">Staff</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button 
                        type="button"
                        class="btn-cancel"
                        onclick="closeAddUserModal()"
                    >
                        Cancel
                    </button>

                    <button 
                        type="submit"
                        name="add_user"
                        class="btn-save"
                    >
                        Add User
                    </button>
                </div>

            </form>

        </div>

    </div>

    <!-- EDIT USER MODAL -->
    <div class="modal-overlay" id="editUserModal">

        <div class="modal-box">

            <div class="modal-header">
                <h2>Edit User</h2>

                <button class="modal-close" onclick="closeEditUserModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form action="controllerProduct.php" method="POST">

                <input 
                    type="hidden"
                    name="edit_user_id"
                    id="edit_user_id"
                >

                <div class="form-group">
                    <label>Full Name</label>

                    <input 
                        type="text"
                        name="edit_fullname"
                        id="edit_fullname"
                        required
                        placeholder="Enter full name"
                    >
                </div>

                <div class="form-group">
                    <label>Username</label>

                    <input 
                        type="text"
                        name="edit_username"
                        id="edit_username"
                        required
                        placeholder="Enter username"
                    >
                </div>

                <div class="form-group">
                    <label>Password</label>

                    <input 
                        type="password"
                        name="edit_password"
                        placeholder="Leave blank if unchanged"
                    >
                </div>

                <div class="form-group">
                    <label>Role</label>

                    <select 
                        name="edit_role"
                        id="edit_role"
                        required
                    >
                        <option value="staff">Staff</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>

                <div class="modal-actions">

                    <button 
                        type="button"
                        class="btn-cancel"
                        onclick="closeEditUserModal()"
                    >
                        Cancel
                    </button>

                    <button 
                        type="submit"
                        name="update_user"
                        class="btn-save"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- LOG MODAL -->
    <div class="modal-overlay" id="logsModal">

        <div class="modal-box" style="max-width: 800px;">

            <div class="modal-header">
                <h2>System Activity Logs</h2>

                <button class="modal-close" onclick="closeLogsModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="table-responsive">

                <table class="stock-table">

                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                            $logs = $conn->query("
                                SELECT 
                                    u.user_fullname,
                                    u.user_role,
                                    a.activityLog_timeIn,
                                    a.activityLog_timeOut
                                FROM inv_activitylog a
                                JOIN inv_user u ON u.user_id = a.user_id
                                ORDER BY a.activityLog_timeIn DESC
                            ");
                        ?>

                        <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['user_fullname']); ?></td>

                                <td>
                                    <span class="role-badge">
                                        <?php echo htmlspecialchars(strtoupper($log['user_role'])); ?>
                                    </span>
                                </td>

                                <td style="color:#64748b;">
                                    <?php echo date("M d, Y h:i A", strtotime($log['activityLog_timeIn'])); ?>
                                </td>

                                <td style="color:#64748b; font-weight:600;">

                                    <?php if(
                                        empty($log['activityLog_timeOut']) || 
                                        $log['activityLog_timeOut'] == '0000-00-00 00:00:00'
                                    ): ?>

                                        <span style="
                                            background:#dcfce7;
                                            color:#166534;
                                            padding:6px 12px;
                                            border-radius:20px;
                                            font-size:0.75rem;
                                            font-weight:700;
                                            display:inline-block;
                                        ">
                                            ONLINE
                                        </span>

                                    <?php else: ?>

                                        <?php echo date("M d, Y h:i A", strtotime($log['activityLog_timeOut'])); ?>

                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- TO REMOVE MODAL WHEN REFRESHED -->
    <?php
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    ?>

    <script>
        lucide.createIcons();

        // Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const burgerToggle = document.getElementById('burgerToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        burgerToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });

        //changes the role
        function toggleRole(userId, fullname, currentRole, totalAdmins){

            // Prevent removing the last admin
            if(currentRole.toLowerCase() === 'owner' && totalAdmins <= 1){

                alert("There must be at least 1 admin in the system.");
                return;
            }
            
            const newRole = currentRole.toLowerCase() === 'owner'
                ? 'staff'
                : 'owner';

            const action = currentRole.toLowerCase() === 'owner'
                ? 'revoke admin access from'
                : 'grant admin access to';

            if(confirm(`Are you sure you want to ${action} ${fullname}?`)){

                window.location.href =
                    `controllerProduct.php?toggle_role=${userId}&new_role=${newRole}`;
            }
        }

        function deleteUser(userId){
            if(confirm("Are you sure you want to delete this user?")){

                window.location.href =
                    `controllerProduct.php?delete_user=${userId}`;
            }
        }


        //for add user modal
        const addUserModal = document.getElementById('addUserModal');

        function openAddUserModal(){
            addUserModal.classList.add('active');
        }

        function closeAddUserModal(){
            addUserModal.classList.remove('active');
        }


        // EDIT USER MODAL
        const editUserModal = document.getElementById('editUserModal');

        function editUser(userId, fullname, username, role){

            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_fullname').value = fullname;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;

            editUserModal.classList.add('active');

            lucide.createIcons();
        }

        function closeEditUserModal(){
            editUserModal.classList.remove('active');
        }

    
        // for userlog
        const logsModal = document.getElementById('logsModal');

        function openLogsModal(){
            logsModal.classList.add('active');
        }

        function closeLogsModal(){
            logsModal.classList.remove('active');
        }


        // USER SEARCH FUNCTION
        const userSearch = document.getElementById('userSearch');
        const userRows = document.querySelectorAll('.user-row');

        userSearch.addEventListener('input', function () {
            const keyword = this.value.toLowerCase();

            userRows.forEach(row => {
                const name = row.querySelector('.fullname').textContent.toLowerCase();
                const username = row.querySelector('.username').textContent.toLowerCase();

                if (name.includes(keyword) || username.includes(keyword)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
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
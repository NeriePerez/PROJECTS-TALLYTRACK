<?php
    session_start();

    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }

    $online_id = $_SESSION['id'] ?? 0;
    $online_role = $_SESSION['role'];
    $role = strtolower($_SESSION['role'] ?? ''); 
    
    $conn = mysqli_connect("localhost", "root", "", "inventory_ads");

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    if ($online_id) {
        $stmt = $conn->prepare("
            SELECT user_fullname, user_role
            FROM inv_user
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $online_id);
        $stmt->execute();

        $resultUser = $stmt->get_result();
        $online_user = $resultUser->fetch_assoc();
    }
    $initials = "U";

    if ($online_user) {
        $nameParts = explode(" ", $online_user['user_fullname']);
        $initials = strtoupper(substr($nameParts[0], 0, 1));

        if (isset($nameParts[1])) {
            $initials .= strtoupper(substr($nameParts[1], 0, 1));
        }
    }

    $notifQuery = $conn->query("
        SELECT *
        FROM inv_notification 
        ORDER BY created_at DESC
    ");

    $notifCountQuery = $conn->query("
        SELECT COUNT(*) AS total 
        FROM inv_notification 
        WHERE notification_status = 'unread'
    ");

    $notifCount = $notifCountQuery->fetch_assoc()['total'];

    //NOTIF SEEN
    if (isset($_GET['mark_seen'])) {

        $stmt = $conn->prepare("
            UPDATE inv_notification
            SET notification_status = 'read'
            WHERE notification_status = 'unread'
        ");

        $stmt->execute();
        exit();
    }





    //ADD PRODUCT
    if (isset($_POST['add'])) {

        $name = $_POST['product_name'];
        $category = $_POST['category'];
        $qty = (int) $_POST['quantity'];
        $orig = (float) $_POST['orig_price'];
        $selling = (float) $_POST['selling_price'];

        $min = (int) $_POST['min_stock'];
        $max = (int) $_POST['max_stock'];

        // ❌ VALIDATION 1: Selling price must not be lower than original
        if ($selling < $orig) {
            $_SESSION['message'] = "Error: Selling price cannot be lower than original price.";
            $_SESSION['message_type'] = "error";
            header("Location: product.php");
            exit();
        }

        // ❌ VALIDATION 2: negative values prevention
        if ($qty < 0 || $orig < 0 || $selling < 0 || $min < 0 || $max < 0) {
            $_SESSION['message'] = "Error: Negative values are not allowed.";
            $_SESSION['message_type'] = "error";
            header("Location: product.php");
            exit();
        }

        // get category id
        $stmt = $conn->prepare("SELECT category_id FROM inv_category WHERE category_name = ?");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $cat = $result->fetch_assoc();

        $category_id = $cat['category_id'];


        $stmt = $conn->prepare("
            SELECT range_id 
            FROM inv_range 
            WHERE range_min = ? AND range_max = ?
        ");
        $stmt->bind_param("ii", $min, $max);
        $stmt->execute();
        $result = $stmt->get_result();  

        if ($row = $result->fetch_assoc()) {
            $range_id = $row['range_id'];
        } else {
            $insertRange = $conn->prepare("
                INSERT INTO inv_range (range_min, range_max)
                VALUES (?, ?)
            ");
            $insertRange->bind_param("ii", $min, $max);
            $insertRange->execute();

            $range_id = $insertRange->insert_id;
        }


        // insert product
        $insertProduct = $conn->prepare("
            INSERT INTO inv_product (
                product_name,
                category_id,
                product_quantity,
                product_origPrice,
                product_sellingPrice,
                range_id
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $insertProduct->bind_param(
            "siiddi",
            $name,
            $category_id,
            $qty,
            $orig,
            $selling,
            $range_id
        );

        if ($insertProduct->execute()) {
            $_SESSION['message'] = "Product added successfully!";
            $_SESSION['message_type'] = "success";

            header("Location: product.php");
            exit;
        } else {
            $_SESSION['message'] = "Failed to add product.";
            $_SESSION['message_type'] = "error";
        }
    }

    // EDIT PRODUCT
    if (isset($_POST['update'])) {
        $user_id = $online_id;
        $id = $_POST['product_id'];
        $name = $_POST['product_name'];
        $category = $_POST['category'];
        $qty = (int) $_POST['quantity'];
        $orig = (float) $_POST['orig_price'];
        $selling = (float) $_POST['selling_price'];
        $min = (int) $_POST['min_stock'];
        $max = (int) $_POST['max_stock'];

        // validation
        if ($selling < $orig) {
            $_SESSION['message'] = "Error: Selling price cannot be lower than original price.";
            $_SESSION['message_type'] = "error";
            header("Location: product.php");
            exit();
        }

        // =========================
        // GET OLD PRODUCT QTY
        // =========================
        $oldQuery = $conn->prepare("
            SELECT product_quantity
            FROM inv_product
            WHERE product_id = ?
        ");

        $oldQuery->bind_param("i", $id);
        $oldQuery->execute();

        $oldData = $oldQuery->get_result()->fetch_assoc();

        $oldQty = (int)$oldData['product_quantity'];

        // get category id
        $stmt = $conn->prepare("
            SELECT category_id 
            FROM inv_category 
            WHERE category_name = ?
        ");

        $stmt->bind_param("s", $category);
        $stmt->execute();

        $cat = $stmt->get_result()->fetch_assoc();
        $category_id = $cat['category_id'];

        // get or create range
        $stmt = $conn->prepare("
            SELECT range_id 
            FROM inv_range 
            WHERE range_min = ? AND range_max = ?
        ");

        $stmt->bind_param("ii", $min, $max);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $range_id = $row['range_id'];

        } else {

            $insertRange = $conn->prepare("
                INSERT INTO inv_range (
                    range_min,
                    range_max
                )
                VALUES (?, ?)
            ");

            $insertRange->bind_param("ii", $min, $max);
            $insertRange->execute();

            $range_id = $insertRange->insert_id;
        }

        // =========================
        // UPDATE PRODUCT
        // =========================
        $stmt = $conn->prepare("
            UPDATE inv_product
            SET 
                product_name = ?,
                category_id = ?,
                product_quantity = ?,
                product_origPrice = ?,
                product_sellingPrice = ?,
                range_id = ?
            WHERE product_id = ?
        ");

        $stmt->bind_param(
            "siiddii",
            $name,
            $category_id,
            $qty,
            $orig,
            $selling,
            $range_id,
            $id
        );

        if ($stmt->execute()) {

            // =========================
            // IF QUANTITY CHANGED
            // =========================
            if ($oldQty != $qty) {

                $difference = $qty - $oldQty;

                // absolute quantity changed
                $changeQty = $difference;

                // =========================
                // INSERT TRANSACTION
                // =========================
                $transactionType = "stock-edit";

                $trans = $conn->prepare("
                    INSERT INTO inv_transaction (
                        transaction_type,
                        transaction_date,
                        user_id
                    )
                    VALUES (?, NOW(), ?)
                ");

                $trans->bind_param(
                    "si",
                    $transactionType,
                    $user_id
                );

                $trans->execute();

                // get transaction id
                $transaction_id = $trans->insert_id;

                // =========================
                // INSERT STOCK EDIT
                // =========================
                $stockEdit = $conn->prepare("
                    INSERT INTO inv_stockedit (
                        product_id,
                        stockEdit_quantity,
                        transaction_id
                    )
                    VALUES (?, ?, ?)
                ");

                $stockEdit->bind_param(
                    "iii",
                    $id,
                    $changeQty,
                    $transaction_id
                );

                $stockEdit->execute();
            }

            $_SESSION['message'] = "Product updated successfully!";
            $_SESSION['message_type'] = "success";

        } else {

            $_SESSION['message'] = "Failed to update product.";
            $_SESSION['message_type'] = "error";
        }

        header("Location: product.php");
        exit();
    }

    // DELETE USER
    if(isset($_GET['delete_product'])){

        $product_id = $_GET['delete_product'];

        $delete = $conn->query("
            DELETE FROM inv_product
            WHERE product_id = '$product_id'
        ");

        if($delete){

            $_SESSION['message'] = "Product deleted successfully!";
            $_SESSION['message_type'] = "success";

        }else{

            $_SESSION['message'] = "Failed to delete product.";
            $_SESSION['message_type'] = "error";
        }

        header("Location: product.php");
        exit();
    }

    if (isset($_POST['addStockin'])) {

        $productIds = $_POST['product_ids'];
        $quantities = $_POST['quantities'];

        if (count($productIds) !== count($quantities)) {
            $_SESSION['message'] = "Invalid input data.";
            $_SESSION['message_type'] = "error";
            header("Location: stockin.php");
            exit;
        }

        if (!empty($productIds) && !empty($quantities)) {

            $user_id = $online_id;

            // 1. create transaction first
            $conn->query("
                INSERT INTO inv_transaction (transaction_type, user_id)
                VALUES ('stock-in', $user_id)
            ");

            $transaction_id = $conn->insert_id;

            // 2. loop items
            for ($i = 0; $i < count($productIds); $i++) {

                $product_id = (int)$productIds[$i];
                $qty = (int)$quantities[$i];

                // insert stock-in record
                $conn->query("
                    INSERT INTO inv_stockin (transaction_id, product_id, stockIn_quantity)
                    VALUES ($transaction_id, $product_id, $qty)
                ");
            }

            $_SESSION['message'] = "Stock-in recorded successfully!";
            $_SESSION['message_type'] = "success";

            header("Location: stockin.php");
            exit;
        }

        $_SESSION['message'] = "No items to process.";
        $_SESSION['message_type'] = "error";
        header("Location: stockin.php");
        exit;
    }



    // STOCK-OUT
    if (isset($_POST['stockOut'])) {

        $cart = json_decode($_POST['cart'], true);
        $user_id = $online_id;

        if (!$cart || empty($cart)) {
            echo json_encode([
                "success" => false,
                "message" => "Cart is empty"
            ]);
            exit;
        }

        // 1. Create transaction
        mysqli_query($conn, "
            INSERT INTO inv_transaction (transaction_type, user_id)
            VALUES ('stock-out', $user_id)
        ");

        $transaction_id = mysqli_insert_id($conn);

        // 2. Loop cart items
        foreach ($cart as $item) {

            $product_id = (int)$item['id'];
            $qty = (int)$item['qty'];

            // Insert stockout
            $stmt = $conn->prepare("
                INSERT INTO inv_stockout 
                (product_id, stockOut_quantity, transaction_id)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param("iii", $product_id, $qty, $transaction_id);
            $stmt->execute();
        }

        echo json_encode([
            "success" => true,
            "transaction_id" => $transaction_id
        ]);

        exit;
    }
















    //LOG OUT
    if (isset($_GET['logout'])) {
        $stmt = $conn->prepare("
            SELECT activityLog_id
            FROM inv_activityLog
            WHERE user_id = ?
            AND activityLog_timeOut IS NULL
            ORDER BY activityLog_timeIn DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $online_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $log_id = $row['activityLog_id'];

            $stmt2 = $conn->prepare("
                UPDATE inv_activitylog
                SET activityLog_timeOut = NOW()
                WHERE activityLog_id = ?
            ");
            $stmt2->bind_param("i", $log_id);
            $stmt2->execute();
        }

        session_destroy();

        header("Location: login.php");
        exit();
    }

    // CHANGE ROLE
    if(isset($_GET['toggle_role']) && isset($_GET['new_role'])){

        $user_id = $_GET['toggle_role'];
        $new_role = $_GET['new_role'];

        $stmt = $conn->prepare("
            UPDATE inv_user
            SET user_role = ?
            WHERE user_id = ?
        ");

        $stmt->bind_param("si", $new_role, $user_id);

        if($stmt->execute()){

            $_SESSION['message'] = "User role updated successfully!";
            $_SESSION['msg_type'] = "success";

        } else {

            $_SESSION['message'] = "Failed to update role!";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: user.php");
        exit();
    }

    /* ================= ADD USER ================= */
    if(isset($_POST['add_user'])){

        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        // HASH PASSWORD
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // CHECK IF USERNAME EXISTS
        $check = $conn->query("
            SELECT *
            FROM inv_user
            WHERE user_username = '$username'
        ");

        if($check->num_rows > 0){

            $_SESSION['message'] = "Username already exists.";
            $_SESSION['msg_type'] = "error";

            header("Location: user.php");
            exit();
        }

        // INSERT USER
        $insert = $conn->query("
            INSERT INTO inv_user(
                user_fullname,
                user_username,
                user_password,
                user_role
            )
            VALUES(
                '$fullname',
                '$username',
                '$hashedPassword',
                '$role'
            )
        ");

        if($insert){

            $_SESSION['message'] = "User added successfully.";
            $_SESSION['msg_type'] = "success";

        }else{

            $_SESSION['message'] = "Failed to add user.";
            $_SESSION['msg_type'] = "error";
        }

        header("Location: user.php");
        exit();
    }

    /* =========================
        UPDATE USER
    ========================= */

    if(isset($_POST['update_user'])){

        $user_id   = $_POST['edit_user_id'];
        $fullname  = trim($_POST['edit_fullname']);
        $username  = trim($_POST['edit_username']);
        $password  = trim($_POST['edit_password']);
        $role      = trim($_POST['edit_role']);

        // if password changed
        if(!empty($password)){

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $query = "
                UPDATE inv_user
                SET
                    user_fullname = '$fullname',
                    user_username = '$username',
                    user_password = '$hashedPassword',
                    user_role = '$role'
                WHERE user_id = '$user_id'
            ";

        }else{

            $query = "
                UPDATE inv_user
                SET
                    user_fullname = '$fullname',
                    user_username = '$username',
                    user_role = '$role'
                WHERE user_id = '$user_id'
            ";
        }

        if($conn->query($query)){

            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['msg_type'] = "success";

        }else{

            $_SESSION['message'] = "Failed to update user!";
            $_SESSION['msg_type'] = "error";
        }

        header("Location: user.php");
        exit();
    }

    // DELETE USER
    if(isset($_GET['delete_user'])){

        $user_id = $_GET['delete_user'];

        $delete = $conn->query("
            DELETE FROM inv_user
            WHERE user_id = '$user_id'
        ");

        if($delete){

            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['msg_type'] = "success";

        }else{

            $_SESSION['message'] = "Failed to delete user.";
            $_SESSION['msg_type'] = "error";
        }

        header("Location: user.php");
        exit();
    }

    if (isset($_GET['get_transaction_details'])) {

        header('Content-Type: application/json');

        $id = intval($_GET['transaction_id']);

        $result = $conn->query("
            SELECT 
                p.product_name,
                c.category_name,
                si.stockIn_quantity AS quantity
            FROM inv_product p
            JOIN inv_category c ON p.category_id = c.category_id
            JOIN inv_stockin si ON si.product_id = p.product_id
            WHERE si.transaction_id = $id

            UNION ALL

            SELECT 
                p.product_name,
                c.category_name,
                so.stockOut_quantity AS quantity
            FROM inv_product p
            JOIN inv_category c ON p.category_id = c.category_id
            JOIN inv_stockout so ON so.product_id = p.product_id
            WHERE so.transaction_id = $id

            UNION ALL

            SELECT 
                p.product_name,
                c.category_name,
                se.stockEdit_quantity AS quantity
            FROM inv_product p
            JOIN inv_category c ON p.category_id = c.category_id
            JOIN inv_stockedit se ON se.product_id = p.product_id
            WHERE se.transaction_id = $id
        ");

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        exit;
    }
?>
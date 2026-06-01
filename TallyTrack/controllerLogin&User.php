<?php
    session_start();
    $conn = mysqli_connect("localhost", "root", "", "inventory_ads");

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // LOGIN
    if (isset($_POST['login'])) {

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("
            SELECT 
                user_id,
                user_password,
                user_role
            FROM inv_user
            WHERE user_username = ?
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify($password, $user['user_password'])) {

                $_SESSION['id'] = $user['user_id'];
                $_SESSION['role'] = $user['user_role'];

                // =========================
                // ATTENDANCE TIME IN
                // =========================
                $user_id = $user['user_id'];

                $attendance = $conn->prepare("
                    INSERT INTO inv_activityLog (
                        user_id
                    )
                    VALUES (?)
                ");

                $attendance->bind_param(
                    "i",
                    $user_id
                );

                $attendance->execute();

                header("Location: dashboard.php");
                exit();

            } else {

                $_SESSION['message'] = "❌ Incorrect password!";
                $_SESSION['msg_type'] = "danger";

                header("Location: login.php");
                exit();
            }

        } else {

            $_SESSION['message'] = "❌ Username not found!";
            $_SESSION['msg_type'] = "danger";

            header("Location: login.php");
            exit();
        }
    }
?>
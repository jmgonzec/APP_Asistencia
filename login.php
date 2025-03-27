<?php
// Start session
session_start();

// Database connection configuration
$host = "localhost";
$dbname = "umg_asistencia";
$user = "postgres";
$password = "Hortencia*1990"; // Change this to your actual PostgreSQL password

// Establish connection to PostgreSQL
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}

// Initialize variables
$correo_electronico = $contraseña = "";
$correo_electronico_err = $contraseña_err = $login_err = "";

// Process form data when the form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if(empty(trim($_POST["correo_electronico"]))) {
        $correo_electronico_err = "Por favor ingrese su correo electrónico.";
    } else {
        $correo_electronico = trim($_POST["correo_electronico"]);
    }
    
    // Validate password
    if(empty(trim($_POST["contraseña"]))) {
        $contraseña_err = "Por favor ingrese su contraseña.";
    } else {
        $contraseña = trim($_POST["contraseña"]);
    }
    
    // Check input errors before checking in database
    if(empty($correo_electronico_err) && empty($contraseña_err)) {
        try {
            // Prepare a select statement
            $sql = "SELECT id_persona, correo_electronico, contraseña, primer_nombre, primer_apellido, tipo FROM persona WHERE correo_electronico = :correo_electronico";
            
            if($stmt = $pdo->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":correo_electronico", $correo_electronico, PDO::PARAM_STR);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()) {
                    // Check if email exists
                    if($stmt->rowCount() == 1) {
                        if($row = $stmt->fetch()) {
                            $id = $row["id_persona"];
                            $correo = $row["correo_electronico"];
                            $hashed_password = $row["contraseña"];
                            $primer_nombre = $row["primer_nombre"];
                            $primer_apellido = $row["primer_apellido"];
                            $tipo = $row["tipo"];
                            
                            // Verify password
                            if(password_verify($contraseña, $hashed_password)) {
                                // Password is correct, start a new session
                                session_start();
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["correo_electronico"] = $correo;
                                $_SESSION["nombre"] = $primer_nombre . " " . $primer_apellido;
                                $_SESSION["tipo"] = $tipo;
                                
                                // Log the login activity
                                $ip = $_SERVER['REMOTE_ADDR'];
                                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                                $login_time = date("Y-m-d H:i:s");
                                
                                $log_sql = "INSERT INTO login_logs (id_persona, ip, user_agent, login_time) VALUES (:id_persona, :ip, :user_agent, :login_time)";
                                $log_stmt = $pdo->prepare($log_sql);
                                $log_stmt->bindParam(":id_persona", $id, PDO::PARAM_INT);
                                $log_stmt->bindParam(":ip", $ip, PDO::PARAM_STR);
                                $log_stmt->bindParam(":user_agent", $user_agent, PDO::PARAM_STR);
                                $log_stmt->bindParam(":login_time", $login_time, PDO::PARAM_STR);
                                $log_stmt->execute();
                                
                                // Redirect user to welcome page
                                header("location: dashboard.php");
                                exit();
                            } else {
                                // Password is not valid
                                $login_err = "Correo electrónico o contraseña incorrectos.";
                            }
                        }
                    } else {
                        // Email doesn't exist
                        $login_err = "Correo electrónico o contraseña incorrectos.";
                    }
                } else {
                    $login_err = "Algo salió mal. Por favor intente más tarde.";
                }
                
                // Close statement
                unset($stmt);
            }
        } catch(PDOException $e) {
            $login_err = "Error: " . $e->getMessage();
        }
    }
    
    // Close connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de control de asistencia UMG - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0B84FF;
            --secondary-color: #f8f9fa;
            --text-color: #212529;
            --light-gray: #e9ecef;
            --border-color: #ced4da;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            background-color: white;
            color: var(--primary-color);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin: 0 auto 15px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
            outline: none;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(11, 132, 255, 0.2);
        }
        
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 40px;
            color: #6c757d;
            font-size: 18px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #6c757d;
            cursor: pointer;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 6px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 600;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .form-footer a, 
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .form-footer a:hover,
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 6px;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                max-width: 90%;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .form-control {
                padding: 12px 12px 12px 40px;
            }
        }
        
        /* Small devices adjustments */
        @media (max-width: 480px) {
            .container {
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
                height: 100%;
            }
            
            body {
                padding: 0;
                background-color: white;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            .header p {
                font-size: 12px;
            }
            
            .logo {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group .icon {
                top: 36px;
            }
            
            .password-toggle {
                top: 36px;
            }
            
            .form-control {
                padding: 10px 10px 10px 35px;
                font-size: 14px;
            }
            
            .btn {
                padding: 12px 15px;
            }
        }
        
        /* For very small screens */
        @media (max-width: 320px) {
            .header h1 {
                font-size: 16px;
            }
            
            .form-container {
                padding: 15px;
            }
            
            .form-control {
                font-size: 13px;
            }
            
            .btn {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">UMG</div>
            <h1>Sistema de control de asistencia UMG</h1>
            <p>Universidad Mariano Gálvez - Campus Escuintla</p>
        </div>
        
        <div class="form-container">
            <?php 
            if(!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }        
            ?>
            
            <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-group">
                    <label for="correo_electronico">Usuario</label>
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" id="correo_electronico" name="correo_electronico" class="form-control <?php echo (!empty($correo_electronico_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $correo_electronico; ?>" placeholder="Correo electrónico" required>
                    <?php if(!empty($correo_electronico_err)) { ?>
                        <div class="error-message" style="display: block;"><?php echo $correo_electronico_err; ?></div>
                    <?php } ?>
                </div>
                
                <div class="form-group">
                    <label for="contraseña">Contraseña</label>
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="contraseña" name="contraseña" class="form-control <?php echo (!empty($contraseña_err)) ? 'is-invalid' : ''; ?>" placeholder="Contraseña" required>
                    <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    <?php if(!empty($contraseña_err)) { ?>
                        <div class="error-message" style="display: block;"><?php echo $contraseña_err; ?></div>
                    <?php } ?>
                </div>
                
                <div class="forgot-password">
                    <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn">Iniciar Sesión</button>
                
                <div class="form-footer">
                    ¿No tienes una cuenta? <a href="persona.html">Registrarse</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordField = document.getElementById('contraseña');
            
            passwordToggle.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    passwordToggle.classList.remove('fa-eye');
                    passwordToggle.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    passwordToggle.classList.remove('fa-eye-slash');
                    passwordToggle.classList.add('fa-
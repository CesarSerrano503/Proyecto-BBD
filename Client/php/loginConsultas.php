<?php
require_once 'Database.php';

if (isset($_POST['login'])) {
    // Crear conexion db
    $db = new Database();
    // Obtener datos login
    $Carnet = strtoupper($_POST['carnet']);
    $Contra = $_POST['contra'];
    // Preparar la consulta SQL para buscar al usuario por su carnet
    $sql = "SELECT * FROM Usuarios WHERE IdUsuario = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param("s", $Carnet);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        // Verificar la contraseña
        if ($Contra == $usuario['Password']) {
            session_abort();
            //Guardar datos del usuario en el cache
            $id = $usuario['IdUsuario'];
            $usu = $usuario['Nombre'];
            $correo = $usuario['Correo'];
            $datosUsuario = array("Carnet" => $id,"Nombre" => $usu,"Correo" => $correo);
            //Iniciar session
            session_start();
            $_SESSION["Usuario"] = $datosUsuario;
            header("Location: index.php");
        } else {
            MensajeError("Carnet o contraseña incorrectos.");
        }
    } else {
        MensajeError("No se encontró el usuario");
    }
    // Cerrar la conexión
    $stmt->close();
    $db->close();
}

function MensajeError($mensaje){//Funcion para mostrar mensaje de error
    echo "<script>";
    echo "Swal.fire({
            icon: 'error',
            title: 'Aviso de inicio de sesión',
            text: '$mensaje',
            confirmButtonColor: '#d33'
        });";
    echo "</script>";
}

?>
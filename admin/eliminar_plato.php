<?php
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM platos WHERE id_plato = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?seccion=platos");
        exit();
    } else {
        echo "Error al eliminar el plato.";
    }
} else {
    echo "ID no válido.";
}
?>

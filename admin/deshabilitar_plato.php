<?php
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id = intval($_GET['id']);
    $estado = intval($_GET['estado']) === 1 ? 1 : 0;

    $stmt = $conn->prepare("UPDATE platos SET activo = ? WHERE id_plato = ?");
    $stmt->bind_param("ii", $estado, $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?seccion=platos");
        exit();
    } else {
        echo "Error al cambiar estado del plato.";
    }
} else {
    echo "Parámetros inválidos.";
}
?>

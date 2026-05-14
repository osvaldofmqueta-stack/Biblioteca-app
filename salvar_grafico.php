<?php
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['graficoLivros']) && !empty($data['graficoUsuarios'])) {
    file_put_contents('graficos/graficoLivros.png', base64_decode(explode(',', $data['graficoLivros'])[1]));
    file_put_contents('graficos/graficoUsuarios.png', base64_decode(explode(',', $data['graficoUsuarios'])[1]));
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
?>

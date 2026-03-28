<?php
header('Content-Type: application/json');
include_once('./_common.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "잘못된 요청 방식입니다. (POST 전용)"
    ]);
    exit;
}

$bo_table = isset($_POST['bo_table']) ? trim($_POST['bo_table']) : '';
$wr_id = isset($_POST['wr_id']) ? (int)$_POST['wr_id'] : 0;
$input_password = isset($_POST['wr_password']) ? trim($_POST['wr_password']) : '';

if (!$bo_table || !$wr_id || !$input_password) {
    echo json_encode([
        "success" => false,
        "message" => "필수 정보가 누락되었습니다."
    ]);
    exit;
}

$target_table = $g5['write_prefix'] . $bo_table;

$sql = "SELECT wr_secret, wr_password, wr_num FROM {$target_table} WHERE wr_id = '{$wr_id}'";
$row = sql_fetch($sql);

if (!$row) {
    echo json_encode([
        "success" => false,
        "message" => "글이 존재하지 않습니다."
    ]);
    exit;
}

$password_valid = false;

if (!empty($row['wr_secret'])) {
    if ($input_password === $row['wr_secret']) {
        $password_valid = true;
    }
} else {
    if (check_password($input_password, $row['wr_password'])) {
        $password_valid = true;
    }
}

if ($password_valid) {
    $ss_name = 'ss_secret_' . $bo_table . '_' . $row['wr_num'];
    set_session($ss_name, true);

    $sql = "SELECT wr_content FROM {$target_table} WHERE wr_id = '{$wr_id}'";
    $row = sql_fetch($sql);
    $post_content = isset($row['wr_content']) ? $row['wr_content'] : '';

    echo json_encode([
        "success" => true,
        "wr_id" => $wr_id,
        "message" => "비밀번호 검증 성공",
        "post_html" => nl2br($post_content)
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "비밀번호가 틀렸습니다."
    ]);
}
exit;
?>

<?php
// 출력 버퍼링 시작 (헤더 리다이렉트를 위해)
ob_start();

include_once('../../../_common.php');

if (!defined('_GNUBOARD_')) {
    exit;
}

$bo_table = isset($_REQUEST['bo_table']) ? preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['bo_table']) : '';
$wr_id = isset($_REQUEST['wr_id']) ? (int)$_REQUEST['wr_id'] : 0;
$write_type = isset($_REQUEST['write_type']) ? trim($_REQUEST['write_type']) : '';

if (!$bo_table || !$wr_id) {
    alert('잘못된 요청입니다.');
}

$board = get_board_db($bo_table, true);
$write_table = $g5['write_prefix'] . $bo_table;
$write = get_write($write_table, $wr_id);

if (!$board || !$write['wr_id']) {
    alert('존재하지 않는 게시글입니다.');
}

if ((int)$write['wr_parent'] === (int)$write['wr_id']) {
    alert('부모 게시글은 이 기능으로 삭제할 수 없습니다.');
}

if ($write_type) {
    $allowed_type = in_array($write_type, array('scena', 'pclist', 'tarae'), true);
    if (!$allowed_type || $write['wr_4'] !== $write_type) {
        alert('게시글 유형이 일치하지 않습니다.');
    }
}

$parent_wr_id = (int)$write['wr_parent'];

// 리다이렉트 URL 생성
if ($write_type === 'pclist') {

    if ($parent_wr_id > 0 && $parent_wr_id != $wr_id) {
        $redirect_url = G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id . '&sublist=pclist';
        $redirect_url = short_url_clean($redirect_url);
    } else {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table);
    }
} elseif ($write_type === 'tarae') {
    if ($parent_wr_id > 0) {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id . '&sublist=tarae');
    } else {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&sublist=tarae');
    }
} elseif ($write_type === 'scena') {
    if ($parent_wr_id > 0) {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id . '&write_type=' . $write_type);
    } else {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&write_type=' . $write_type);
    }
} else {
    if ($parent_wr_id > 0) {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id);
    } else {
        $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table);
    }
}

// 권한 확인 (관리자 또는 작성자)
$current_member_id = isset($member['mb_id']) ? $member['mb_id'] : '';
$can_delete = false;
if ($is_admin) {
    $can_delete = true;
} elseif ($current_member_id && $write['mb_id'] && $current_member_id === $write['mb_id']) {
    // 자신의 글인 경우, 게시판 쓰기 권한도 확인
    if ($member['mb_level'] >= $board['bo_write_level']) {
        $can_delete = true;
    }
}

if (!$can_delete) {
    alert('삭제 권한이 없습니다.');
}

// 첨부파일 삭제
$result = sql_query("SELECT * FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$wr_id}'");
while ($row = sql_fetch_array($result)) {
    $delete_file = run_replace('delete_file_path', G5_DATA_PATH . '/file/' . $bo_table . '/' . str_replace('../', '', $row['bf_file']), $row);
    if (file_exists($delete_file)) {
        @unlink($delete_file);
    }
    if (preg_match("/\.({$config['cf_image_extension']})$/i", $row['bf_file'])) {
        delete_board_thumbnail($bo_table, $row['bf_file']);
    }
}
sql_query("DELETE FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$wr_id}'");

// 에디터 썸네일 삭제
delete_editor_thumbnail($write['wr_content']);

// 본문 삭제
sql_query("DELETE FROM {$write_table} WHERE wr_id = '{$wr_id}' LIMIT 1");

// 관련 테이블 정리 (테이블 키가 정의되어 있을 때만 실행)
if (isset($g5['board_new_table'])) {
    sql_query("DELETE FROM {$g5['board_new_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$wr_id}'");
}
if (isset($g5['scrap_table'])) {
    sql_query("DELETE FROM {$g5['scrap_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$wr_id}'");
}

// 글 카운트 차감
if (isset($g5['board_table'])) {
    sql_query("UPDATE {$g5['board_table']} SET bo_count_write = IF(bo_count_write > 0, bo_count_write - 1, 0) WHERE bo_table = '{$bo_table}'");
}

delete_cache_latest($bo_table);

// 리다이렉트 URL 확인
error_log("delete.sublist.skin.php - write_type: " . $write_type . ", wr_id: " . $wr_id . ", parent_wr_id: " . $parent_wr_id . ", redirect_url: " . $redirect_url);

// 출력 버퍼 비우기
ob_end_clean();


$js_redirect_url = str_replace('&amp;', '&', $redirect_url);
$js_redirect_url = str_replace("'", "\\'", $js_redirect_url);
$js_redirect_url = str_replace("\\", "\\\\", $js_redirect_url);

// 리다이렉트 URL 확인
error_log("delete.sublist.skin.php - Final redirect_url: " . $redirect_url);
error_log("delete.sublist.skin.php - Final js_redirect_url: " . $js_redirect_url);

// 리다이렉트 시도 
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>삭제 중...</title></head><body>";
echo "<script>";
echo "alert('삭제되었습니다.');";
echo "console.log('Redirect URL: " . $js_redirect_url . "');";
echo "window.location.href = '" . $js_redirect_url . "';";
echo "</script>";
echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . "'></noscript>";
echo "</body></html>";
exit;


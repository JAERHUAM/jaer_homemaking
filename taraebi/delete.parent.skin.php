<?php
// 출력 버퍼링 시작 (헤더 리다이렉트를 위해)
ob_start();

include_once('../../../_common.php');

if (!defined('_GNUBOARD_')) {
    exit;
}

$bo_table = isset($_REQUEST['bo_table']) ? preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['bo_table']) : '';
$wr_id = isset($_REQUEST['wr_id']) ? (int)$_REQUEST['wr_id'] : 0;

if (!$bo_table || !$wr_id) {
    alert('잘못된 요청입니다.');
}

$board = get_board_db($bo_table, true);
$write_table = $g5['write_prefix'] . $bo_table;
$write = get_write($write_table, $wr_id);

if (!$board || !$write['wr_id']) {
    alert('존재하지 않는 게시글입니다.');
}

// 부모 게시글만 허용
if ((int)$write['wr_parent'] !== (int)$write['wr_id']) {
    alert('부모 게시글만 이 기능으로 삭제할 수 있습니다.');
}

// 권한 확인 (관리자 또는 작성자)
$current_member_id = isset($member['mb_id']) ? $member['mb_id'] : '';
$can_delete = false;
if ($is_admin) {
    $can_delete = true;
} elseif ($current_member_id && $write['mb_id'] && $current_member_id === $write['mb_id']) {
    if ($member['mb_level'] >= $board['bo_write_level']) {
        $can_delete = true;
    }
}

if (!$can_delete) {
    alert('삭제 권한이 없습니다.');
}

// 하위 글 포함 삭제 대상 수집
$delete_ids = array();
$stack = array((int)$wr_id);
while (!empty($stack)) {
    $current_id = array_pop($stack);
    if ($current_id <= 0) {
        continue;
    }
    if (in_array($current_id, $delete_ids, true)) {
        continue;
    }
    $delete_ids[] = $current_id;
    $child_result = sql_query("SELECT wr_id FROM {$write_table} WHERE wr_parent = '{$current_id}' AND wr_id != '{$current_id}'");
    while ($child_row = sql_fetch_array($child_result)) {
        $stack[] = (int)$child_row['wr_id'];
    }
}

if (empty($delete_ids)) {
    alert('삭제할 게시글을 찾지 못했습니다.');
}

$delete_ids_sql = implode(',', array_map('intval', $delete_ids));

// 첨부파일 삭제
$result = sql_query("SELECT * FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id IN ({$delete_ids_sql})");
while ($row = sql_fetch_array($result)) {
    $delete_file = run_replace('delete_file_path', G5_DATA_PATH . '/file/' . $bo_table . '/' . str_replace('../', '', $row['bf_file']), $row);
    if (file_exists($delete_file)) {
        @unlink($delete_file);
    }
    if (preg_match("/\.({$config['cf_image_extension']})$/i", $row['bf_file'])) {
        delete_board_thumbnail($bo_table, $row['bf_file']);
    }
}
sql_query("DELETE FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id IN ({$delete_ids_sql})");

// 에디터 썸네일 삭제
$content_result = sql_query("SELECT wr_content FROM {$write_table} WHERE wr_id IN ({$delete_ids_sql})");
while ($content_row = sql_fetch_array($content_result)) {
    if (isset($content_row['wr_content'])) {
        delete_editor_thumbnail($content_row['wr_content']);
    }
}

// 본문 삭제
sql_query("DELETE FROM {$write_table} WHERE wr_id IN ({$delete_ids_sql})");

// 관련 테이블 정리 (테이블 키가 정의되어 있을 때만 실행)
if (isset($g5['board_new_table'])) {
    sql_query("DELETE FROM {$g5['board_new_table']} WHERE bo_table = '{$bo_table}' AND wr_id IN ({$delete_ids_sql})");
}
if (isset($g5['scrap_table'])) {
    sql_query("DELETE FROM {$g5['scrap_table']} WHERE bo_table = '{$bo_table}' AND wr_id IN ({$delete_ids_sql})");
}

// 글 카운트 차감 (부모 글 포함, 댓글 제외)
if (isset($g5['board_table'])) {
    $count_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_id IN ({$delete_ids_sql}) AND wr_is_comment = 0");
    $delete_count = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
    if ($delete_count > 0) {
        sql_query("UPDATE {$g5['board_table']} SET bo_count_write = IF(bo_count_write >= {$delete_count}, bo_count_write - {$delete_count}, 0) WHERE bo_table = '{$bo_table}'");
    }
}

delete_cache_latest($bo_table);

// 리다이렉트 URL (목록으로)
$redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table);

ob_end_clean();

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>삭제 중...</title></head><body>";
echo "<script>";
echo "alert('삭제되었습니다.');";
echo "window.location.href = '" . str_replace("'", "\\'", $redirect_url) . "';";
echo "</script>";
echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . "'></noscript>";
echo "</body></html>";
exit;

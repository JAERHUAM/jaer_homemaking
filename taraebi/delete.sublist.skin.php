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
    $allowed_type = in_array($write_type, array('tarae'), true);
    if (!$allowed_type || $write['wr_4'] !== $write_type) {
        alert('게시글 유형이 일치하지 않습니다.');
    }
}

$parent_wr_id = (int)$write['wr_parent'];

// 리다이렉트 URL 생성 (가능하면 기존 화면으로 복귀)
$redirect_url = '';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $ref_parts = parse_url($referer);
    $base_parts = parse_url(G5_HTTP_BBS_URL);
    if ($ref_parts && $base_parts && isset($ref_parts['host']) && isset($base_parts['host']) && $ref_parts['host'] === $base_parts['host']) {
        $redirect_url = $referer;
    }
}
if (!$redirect_url) {
    if ($write_type === 'tarae') {
        if ($parent_wr_id > 0) {
            $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id . '&sublist=tarae&tarae_open=' . $parent_wr_id) . '#tarae_content_' . $parent_wr_id;
        } else {
            $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&sublist=tarae');
        }
    } else {
        if ($parent_wr_id > 0) {
            $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id);
        } else {
            $redirect_url = short_url_clean(G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table);
        }
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

// 글 카운트 차감
if (isset($g5['board_table'])) {
    $count_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_id IN ({$delete_ids_sql}) AND wr_is_comment = 0");
    $delete_count = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
    if ($delete_count > 0) {
        sql_query("UPDATE {$g5['board_table']} SET bo_count_write = IF(bo_count_write >= {$delete_count}, bo_count_write - {$delete_count}, 0) WHERE bo_table = '{$bo_table}'");
    }
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


<?php
if (!defined('_GNUBOARD_')) exit;

// 공지글 작성 시 기존 공지글을 삭제하도록 처리
if ($w == '' && isset($_POST['notice']) && $_POST['notice'] && $is_admin) {
    // 기존 공지글 찾기
    $notice_array = array_map('trim', explode(',', $board['bo_notice']));
    $notice_array = array_filter($notice_array);
    
    if (count($notice_array) > 0) {
        // 모든 기존 공지글 삭제
        foreach ($notice_array as $notice_id) {
            if ($notice_id) {
                // 공지글 삭제 (관리자 권한으로 삭제)
                sql_query("DELETE FROM {$write_table} WHERE wr_id = '{$notice_id}'");
                // 관련 댓글도 삭제
                sql_query("DELETE FROM {$write_table} WHERE wr_parent = '{$notice_id}'");
                // 최근게시물 삭제
                sql_query("DELETE FROM {$g5['board_new_table']} WHERE bo_table = '{$bo_table}' AND wr_parent = '{$notice_id}'");
                // 스크랩 삭제
                sql_query("DELETE FROM {$g5['scrap_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$notice_id}'");
            }
        }
        
        // bo_notice 초기화
        sql_query("UPDATE {$g5['board_table']} SET bo_notice = '' WHERE bo_table = '{$bo_table}'");
    }
}
?>


<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

// 게시판 쓰기 권한 체크
global $board, $member, $is_admin, $write;
if ($w == 'u') {
    // 글수정 권한 체크
    if ($member['mb_id'] && $write['mb_id'] === $member['mb_id']) {
        // 자신의 글은 통과
    } else if ($is_admin) {
        // 관리자는 통과
    } else if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 수정할 권한이 없습니다.');
        } else {
            alert('글을 수정할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.');
        }
    } else {
        // 권한은 있지만 자신의 글이 아니면 차단
        alert('자신이 작성한 글만 수정할 수 있습니다.');
    }
}

// tarae 전용 필드 업데이트
$tarae_subject = isset($_POST['tarae_subject']) ? trim($_POST['tarae_subject']) : '';
$tarae_summary = isset($_POST['tarae_summary']) ? trim($_POST['tarae_summary']) : '';

// wr_subject 업데이트 (tarae_subject에서 가져옴)
if ($tarae_subject !== '') {
    $tarae_subject = clean_xss_tags($tarae_subject, 1, 1);
    $tarae_subject = substr($tarae_subject, 0, 255);
    sql_query("UPDATE {$write_table} SET wr_subject = '".sql_real_escape_string($tarae_subject)."' WHERE wr_id = '{$wr_id}'", false);
}

// wr_3 업데이트 (부제)
$tarae_summary = clean_xss_tags($tarae_summary, 1, 1);
$tarae_summary = substr($tarae_summary, 0, 255);

// tarae 전용 이미지 저장 처리 (wr_39 ~ wr_42)
$tarae_image_urls = array();
for ($i = 1; $i <= 4; $i++) {
    $wr_field = 'wr_' . (38 + $i); // wr_39, wr_40, wr_41, wr_42
    $uploaded_file_url = '';
    
    // 파일 업로드 처리
    $file_key = 'tarae_image_file_' . $i;
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $tmp_file = $_FILES[$file_key]['tmp_name'];
        $filename = $_FILES[$file_key]['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if (in_array($ext, $allowed_ext)) {
            $upload_dir = G5_DATA_PATH . '/file/' . $bo_table;
            $upload_url = G5_DATA_URL  . '/file/' . $bo_table;
            
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, G5_DIR_PERMISSION, true);
                @chmod($upload_dir, G5_DIR_PERMISSION);
            }
            
            $new_filename = 'tarae_' . md5(uniqid(time(), true) . '_' . $i) . '.' . $ext;
            $dest_path = $upload_dir . '/' . $new_filename;
            
            if (move_uploaded_file($tmp_file, $dest_path)) {
                @chmod($dest_path, G5_FILE_PERMISSION);
                if (function_exists('resize_image')) {
                    resize_image($dest_path, 1200, 1200);
                }
                $uploaded_file_url = $upload_url . '/' . $new_filename;
            }
        }
    }
    
    // 기존 이미지 삭제 처리
    $delete_existing_image = false;
    if ($w == 'u' && isset($_POST[$wr_field.'_del']) && $_POST[$wr_field.'_del'] == '1') {
        $delete_existing_image = true;
        // 기존 이미지 파일 삭제
        $old_write = sql_fetch("SELECT {$wr_field} FROM {$write_table} WHERE wr_id = '{$wr_id}'");
        if ($old_write && !empty($old_write[$wr_field])) {
            $old_url = $old_write[$wr_field];
            if (strpos($old_url, G5_DATA_URL.'/file/') === 0) {
                $old_path = str_replace(G5_DATA_URL, G5_DATA_PATH, $old_url);
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }
        }
    }
    
    // 최종 이미지 URL 결정 (업로드 > URL 입력 > 기존 유지 > 삭제)
    $final_image_url = '';
    if ($uploaded_file_url) {
        $final_image_url = $uploaded_file_url;
    } elseif (isset($_POST[$wr_field]) && trim($_POST[$wr_field]) !== '') {
        $input_url = clean_xss_tags(trim($_POST[$wr_field]));
        if (filter_var($input_url, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $input_url) || strpos($input_url, G5_DATA_URL) === 0) {
            $final_image_url = $input_url;
        }
    } elseif ($w == 'u' && !$delete_existing_image) {
        // 기존 이미지 유지 (업데이트하지 않음)
        continue;
    }
    
    $tarae_image_urls[$wr_field] = $final_image_url;
}

// wr_3, wr_39 ~ wr_42 필드 업데이트
$update_fields = array(
    "wr_3 = '".sql_real_escape_string($tarae_summary)."'",
    "wr_4 = 'tarae'"
);

foreach ($tarae_image_urls as $field => $url) {
    $update_fields[] = "{$field} = '".sql_real_escape_string($url)."'";
}

if (!empty($update_fields)) {
    $update_sql = "UPDATE {$write_table} SET ".implode(', ', $update_fields)." WHERE wr_id = '{$wr_id}'";
    sql_query($update_sql, false);
}


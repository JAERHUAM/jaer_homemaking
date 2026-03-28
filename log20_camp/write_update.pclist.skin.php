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

// pclist 전용 필드 업데이트
// stripslashes()를 사용하여 ///"를 원래 쌍따옴표(")로 복원
$pclist_subtitle = isset($_POST['pclist_subtitle']) ? trim(stripslashes($_POST['pclist_subtitle'])) : '';
$pclist_oneline = isset($_POST['pclist_oneline']) ? trim(stripslashes($_POST['pclist_oneline'])) : '';
// 제목색: 텍스트 입력 필드 우선, 없으면 color picker 값 사용
$pclist_title_color = '';
if (isset($_POST['pclist_title_color_text']) && trim($_POST['pclist_title_color_text']) !== '') {
    $pclist_title_color = trim(stripslashes($_POST['pclist_title_color_text']));
} elseif (isset($_POST['pclist_title_color']) && trim($_POST['pclist_title_color']) !== '') {
    $pclist_title_color = trim(stripslashes($_POST['pclist_title_color']));
}
// 배경색: 텍스트 입력 필드 우선, 없으면 color picker 값 사용
$pclist_bg_color = '';
if (isset($_POST['pclist_bg_color_text']) && trim($_POST['pclist_bg_color_text']) !== '') {
    $pclist_bg_color = trim(stripslashes($_POST['pclist_bg_color_text']));
} elseif (isset($_POST['pclist_bg_color']) && trim($_POST['pclist_bg_color']) !== '') {
    $pclist_bg_color = trim(stripslashes($_POST['pclist_bg_color']));
}
// 추가색: 텍스트 입력 필드 우선, 없으면 color picker 값 사용
$pclist_add_color = '';
if (isset($_POST['pclist_add_color_text']) && trim($_POST['pclist_add_color_text']) !== '') {
    $pclist_add_color = trim(stripslashes($_POST['pclist_add_color_text']));
} elseif (isset($_POST['pclist_add_color']) && trim($_POST['pclist_add_color']) !== '') {
    $pclist_add_color = trim(stripslashes($_POST['pclist_add_color']));
}
$pclist_keyword1 = isset($_POST['pclist_keyword1']) ? trim(stripslashes($_POST['pclist_keyword1'])) : '';
$pclist_keyword2 = isset($_POST['pclist_keyword2']) ? trim(stripslashes($_POST['pclist_keyword2'])) : '';
$pclist_keyword3 = isset($_POST['pclist_keyword3']) ? trim(stripslashes($_POST['pclist_keyword3'])) : '';

// 기타 필드 (8개, 각각 주제와 내용)
$pclist_etc = array();
$content_field_map = array(34, 35, 36, 37, 40, 41, 42, 43);
for ($i = 1; $i <= 8; $i++) {
    $pclist_etc[$i] = array(
        'subject' => isset($_POST['pclist_etc_subject_' . $i]) ? trim(stripslashes($_POST['pclist_etc_subject_' . $i])) : '',
        'content' => isset($_POST['pclist_etc_content_' . $i]) ? trim(stripslashes($_POST['pclist_etc_content_' . $i])) : ''
    );
}

// 썸네일 이미지 처리
$thumbnail_url = '';
$has_file_upload = false;

// 파일 업로드 처리 (우선순위 1)
if (isset($_FILES['wr_7_file']) && $_FILES['wr_7_file']['error'] == 0) {
    $tmp_file = $_FILES['wr_7_file']['tmp_name'];
    $filename = $_FILES['wr_7_file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (in_array($ext, $allowed_ext)) {
        $upload_dir = G5_DATA_PATH . '/file/' . $bo_table;
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, G5_DIR_PERMISSION, true);
            @chmod($upload_dir, G5_DIR_PERMISSION);
        }
        
        $new_filename = 'pclist_' . $wr_id . '_' . md5(uniqid(time(), true)) . '.' . $ext;
        $dest_path = $upload_dir . '/' . $new_filename;
        
        if (move_uploaded_file($tmp_file, $dest_path)) {
            @chmod($dest_path, G5_FILE_PERMISSION);
            if (function_exists('resize_image')) {
                resize_image($dest_path, 800, 800);
            }
            $thumbnail_url = G5_DATA_URL . '/file/' . $bo_table . '/' . $new_filename;
            $has_file_upload = true;
        }
    }
}

// URL 입력 처리 (파일 업로드가 없을 때만)
if (!$has_file_upload) {
    if (isset($_POST['wr_7_del']) && $_POST['wr_7_del'] == '1') {
        $thumbnail_url = '';
    } elseif (isset($_POST['wr_7']) && trim($_POST['wr_7']) !== '') {
        $wr_7_url = clean_xss_tags(trim($_POST['wr_7']));
        if (filter_var($wr_7_url, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_7_url) || strpos($wr_7_url, G5_DATA_URL) === 0) {
            $thumbnail_url = $wr_7_url;
        }
    }
}

// 메인 이미지 처리 (wr_39)
$main_image_url = '';
$has_main_file_upload = false;

// 파일 업로드 처리 (우선순위 1)
if (isset($_FILES['wr_39_file']) && $_FILES['wr_39_file']['error'] == 0) {
    $tmp_file = $_FILES['wr_39_file']['tmp_name'];
    $filename = $_FILES['wr_39_file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (in_array($ext, $allowed_ext)) {
        $upload_dir = G5_DATA_PATH . '/file/' . $bo_table;
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, G5_DIR_PERMISSION, true);
            @chmod($upload_dir, G5_DIR_PERMISSION);
        }
        
        $new_filename = 'pclist_main_' . $wr_id . '_' . md5(uniqid(time(), true)) . '.' . $ext;
        $dest_path = $upload_dir . '/' . $new_filename;
        
        if (move_uploaded_file($tmp_file, $dest_path)) {
            @chmod($dest_path, G5_FILE_PERMISSION);
            if (function_exists('resize_image')) {
                resize_image($dest_path, 800, 800);
            }
            $main_image_url = G5_DATA_URL . '/file/' . $bo_table . '/' . $new_filename;
            $has_main_file_upload = true;
        }
    }
}

// URL 입력 처리 (파일 업로드가 없을 때만)
if (!$has_main_file_upload) {
    if (isset($_POST['wr_39_del']) && $_POST['wr_39_del'] == '1') {
        $main_image_url = '';
    } elseif (isset($_POST['wr_39']) && trim($_POST['wr_39']) !== '') {
        $wr_39_url = clean_xss_tags(trim($_POST['wr_39']));
        if (filter_var($wr_39_url, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_39_url) || strpos($wr_39_url, G5_DATA_URL) === 0) {
            $main_image_url = $wr_39_url;
        }
    }
}

// wr_21: pclist 제목색, wr_22: pclist 배경색
// wr_44: pclist 추가색
// wr_23, wr_24, wr_25: 키워드 3개
// wr_26~wr_33: 기타 주제 8개
// wr_34,wr_35,wr_36,wr_37,wr_40,wr_41,wr_42,wr_43: 기타 내용 8개
// wr_38: 한마디
// wr_39: 메인 이미지

$meta_updates = array(
    "wr_3 = '".sql_real_escape_string($pclist_subtitle)."'",
    "wr_4 = 'pclist'",
    "wr_7 = '".sql_real_escape_string($thumbnail_url)."'",
    "wr_21 = '".sql_real_escape_string($pclist_title_color)."'",
    "wr_22 = '".sql_real_escape_string($pclist_bg_color)."'",
    "wr_44 = '".sql_real_escape_string($pclist_add_color)."'",
    "wr_23 = '".sql_real_escape_string($pclist_keyword1)."'",
    "wr_24 = '".sql_real_escape_string($pclist_keyword2)."'",
    "wr_25 = '".sql_real_escape_string($pclist_keyword3)."'",
    "wr_38 = '".sql_real_escape_string($pclist_oneline)."'",
    "wr_39 = '".sql_real_escape_string($main_image_url)."'"
);

// 기타 필드 추가 (8개)
$content_field_map = array(34, 35, 36, 37, 40, 41, 42, 43);
for ($i = 1; $i <= 8; $i++) {
    $subject_field = 'wr_' . (25 + $i); // wr_26 ~ wr_33
    $content_field = 'wr_' . $content_field_map[$i - 1]; // wr_34,wr_35,wr_36,wr_37,wr_40,wr_41,wr_42,wr_43
    $meta_updates[] = "{$subject_field} = '".sql_real_escape_string($pclist_etc[$i]['subject'])."'";
    $meta_updates[] = "{$content_field} = '".sql_real_escape_string($pclist_etc[$i]['content'])."'";
}

$meta_sql = "UPDATE {$write_table} SET " . implode(', ', $meta_updates) . " WHERE wr_id = '{$wr_id}'";
sql_query($meta_sql, false);


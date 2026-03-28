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

// ----------------------------------------------------------
// 시나리오 메타 정보 저장
// ----------------------------------------------------------
$rule_value   = isset($_POST['wr_19']) ? trim($_POST['wr_19']) : '';
$gm_value     = isset($_POST['wr_6']) ? trim($_POST['wr_6']) : '';
$writer_value = isset($_POST['wr_writer']) ? trim($_POST['wr_writer']) : '';
$pc_count_raw = isset($_POST['wr_11']) ? (int)$_POST['wr_11'] : 0;
$pc_count     = max(0, min(6, $pc_count_raw));

// 시작/종료 날짜 저장
$start_date = isset($_POST['wr_datetime']) ? trim($_POST['wr_datetime']) : '';
if ($start_date !== '') {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        $start_date .= ' 00:00:00';
    }
    if (strtotime($start_date) !== false) {
        sql_query("UPDATE {$write_table} SET wr_datetime = '".sql_real_escape_string($start_date)."' WHERE wr_id = '{$wr_id}'", false);
    }
}

$end_date = isset($_POST['wr_5']) ? trim($_POST['wr_5']) : '';
if ($end_date !== '') {
    sql_query("UPDATE {$write_table} SET wr_5 = '".sql_real_escape_string($end_date)."' WHERE wr_id = '{$wr_id}'", false);
}

// 비밀글 조회 비밀번호 처리 (wr_secret)
$secret_password = '';
$existing_secret = '';
if ($w == 'u') {
    $secret_row = sql_fetch("SELECT wr_secret FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    if ($secret_row && isset($secret_row['wr_secret'])) {
        $existing_secret = $secret_row['wr_secret'];
    }
}

if (isset($_POST['secret']) && $_POST['secret'] == 'secret') {
    // wr_secret 필드 타입 확인 및 변경 (tinyint → varchar)
    $field_info = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_secret'");
    if ($field_info && strpos($field_info['Type'], 'tinyint') !== false) {
        sql_query("ALTER TABLE {$write_table} MODIFY COLUMN wr_secret varchar(255) NOT NULL DEFAULT ''", false);
    }
    
    if (isset($_POST['wr_secret']) && trim($_POST['wr_secret']) !== '') {
        // 새 비밀번호가 입력된 경우
        $secret_password = sql_real_escape_string(trim($_POST['wr_secret']));
    } elseif ($w == 'u' && $existing_secret !== '') {
        // 수정 모드이고 비밀번호가 비어있으면 기존 값 유지
        $secret_password = sql_real_escape_string($existing_secret);
    }
} elseif (isset($_POST['secret']) && $_POST['secret'] != 'secret') {
    // 비밀글이 아니면 wr_secret 초기화
    $secret_password = '';
}

$meta_updates = array(
    "wr_19 = '".sql_real_escape_string($rule_value)."'",
    "wr_6 = '".sql_real_escape_string($gm_value)."'",
    "wr_20 = '".sql_real_escape_string($writer_value)."'",
    "wr_11 = '{$pc_count}'",
    "wr_4 = 'scena'"
);

// wr_secret 저장
if ($secret_password !== '') {
    $meta_updates[] = "wr_secret = '{$secret_password}'";
} else {
    $meta_updates[] = "wr_secret = ''";
}

for ($i = 1; $i <= 6; $i++) {
    $field = 'wr_' . (11 + $i);
    $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    $meta_updates[] = "{$field} = '".sql_real_escape_string($value)."'";
}

$meta_sql = "UPDATE {$write_table} SET " . implode(', ', $meta_updates) . " WHERE wr_id = '{$wr_id}'";
sql_query($meta_sql, false);

// ----------------------------------------------------------
// 기본 이미지 사용 (부모 썸네일)
// ----------------------------------------------------------
$use_parent_thumb = isset($_POST['use_parent_thumb']) && $_POST['use_parent_thumb'] == '1';
$has_new_thumb_file = isset($_FILES['wr_7_file']) && $_FILES['wr_7_file']['error'] == UPLOAD_ERR_OK;
$is_deleting_thumb = isset($_POST['wr_7_del']) && $_POST['wr_7_del'] == '1';
if ($use_parent_thumb && !$has_new_thumb_file && !$is_deleting_thumb) {
    $parent_wr_id = isset($_POST['wr_parent']) ? (int)$_POST['wr_parent'] : 0;
    if ($parent_wr_id <= 0) {
        $parent_row = sql_fetch("SELECT wr_parent FROM {$write_table} WHERE wr_id = '{$wr_id}'");
        if ($parent_row && isset($parent_row['wr_parent'])) {
            $parent_wr_id = (int)$parent_row['wr_parent'];
        }
    }
    if ($parent_wr_id > 0) {
        $parent_thumb_row = sql_fetch("SELECT wr_7 FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
        $parent_thumb = ($parent_thumb_row && isset($parent_thumb_row['wr_7'])) ? trim($parent_thumb_row['wr_7']) : '';
        $thumb_sql = "UPDATE {$write_table} SET wr_7 = '".sql_real_escape_string($parent_thumb)."' WHERE wr_id = '{$wr_id}'";
        sql_query($thumb_sql, false);
    }
}

// ----------------------------------------------------------
// 본문 첨부파일(.txt / .html) 처리 - wr_21 에 파일 경로 저장
// ----------------------------------------------------------
// wr_21 컬럼이 없으면 추가 (본문 파일 경로용)
$wr21_column = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_21'");
if (!$wr21_column) {
    sql_query("ALTER TABLE {$write_table} ADD COLUMN `wr_21` VARCHAR(255) NOT NULL DEFAULT ''", false);
}

// 기존 값 조회 (수정 모드에서 사용)
$existing_body_path = '';
if ($w == 'u') {
    $body_row = sql_fetch("SELECT wr_21 FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    if ($body_row && isset($body_row['wr_21'])) {
        $existing_body_path = $body_row['wr_21'];
    }
}

$new_body_path = $existing_body_path;

// 새 txt/html 파일 업로드가 있다면 처리
if (isset($_FILES['scena_body_file']) && $_FILES['scena_body_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['scena_body_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_file = $_FILES['scena_body_file']['tmp_name'];
        $orig_name = $_FILES['scena_body_file']['name'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // txt와 html 파일 모두 허용
        if ($ext === 'txt' || $ext === 'html' || $ext === 'htm') {
            $upload_dir = G5_DATA_PATH . '/file/' . $bo_table . '/scena_body';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, G5_DIR_PERMISSION, true);
                @chmod($upload_dir, G5_DIR_PERMISSION);
            }

            // 원본 확장자 유지
            $new_filename = 'scena_' . $wr_id . '_' . md5(uniqid(time(), true)) . '.' . $ext;
            $dest_path = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($tmp_file, $dest_path)) {
                @chmod($dest_path, G5_FILE_PERMISSION);
                // G5_DATA_PATH 기준 상대 경로로 저장 (view 에서 G5_DATA_PATH 와 합쳐 사용)
                $relative_path = 'file/' . $bo_table . '/scena_body/' . $new_filename;
                $new_body_path = $relative_path;
            }
        }
    }
}

// 경로가 변경되었으면 wr_21 업데이트
if ($new_body_path !== $existing_body_path) {
    $escaped_path = sql_real_escape_string($new_body_path);
    sql_query("UPDATE {$write_table} SET wr_21 = '{$escaped_path}' WHERE wr_id = '{$wr_id}'", false);
}

// ----------------------------------------------------------
// PC 이미지 저장
// ----------------------------------------------------------
$pc_image_table = $write_table . '_pc_images';
$create_pc_table_sql = "CREATE TABLE IF NOT EXISTS `{$pc_image_table}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wr_id` INT UNSIGNED NOT NULL,
    `pc_index` TINYINT UNSIGNED NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `wr_pc_unique` (`wr_id`, `pc_index`),
    KEY `wr_id_idx` (`wr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
sql_query($create_pc_table_sql, false);

sql_query("DELETE FROM `{$pc_image_table}` WHERE wr_id = '{$wr_id}'");

$pc_upload_dir = G5_DATA_PATH . '/file/' . $bo_table . '/pc';
$pc_upload_url = G5_DATA_URL . '/file/' . $bo_table . '/pc';
if (!is_dir($pc_upload_dir)) {
    @mkdir($pc_upload_dir, G5_DIR_PERMISSION, true);
    @chmod($pc_upload_dir, G5_DIR_PERMISSION);
}

for ($i = 1; $i <= 6; $i++) {
    $image_url = '';
    $file_field = 'pc_' . $i . '_image_file';
    $url_field = 'pc_' . $i . '_image_url';

    if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] == 0) {
        $tmp_file = $_FILES[$file_field]['tmp_name'];
        $filename = $_FILES[$file_field]['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($ext, $allowed_ext)) {
            $new_filename = 'pc_' . $wr_id . '_' . $i . '_' . md5(uniqid(time(), true)) . '.' . $ext;
            $dest_path = $pc_upload_dir . '/' . $new_filename;

            if (move_uploaded_file($tmp_file, $dest_path)) {
                @chmod($dest_path, G5_FILE_PERMISSION);
                if (function_exists('resize_image')) {
                    resize_image($dest_path, 800, 800);
                }
                $image_url = $pc_upload_url . '/' . $new_filename;
            }
        }
    }

    if (!$image_url && isset($_POST[$url_field]) && trim($_POST[$url_field]) !== '') {
        $candidate = clean_xss_tags(trim($_POST[$url_field]));
        if ($candidate !== '') {
            if (filter_var($candidate, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $candidate) || strpos($candidate, G5_DATA_URL) === 0) {
                $image_url = $candidate;
            }
        }
    }

    if ($image_url) {
        $img_sql = "INSERT INTO `{$pc_image_table}` (wr_id, pc_index, image_url, created_at) VALUES ('{$wr_id}', '{$i}', '".sql_real_escape_string($image_url)."', NOW())";
        sql_query($img_sql);
    }
}

// ----------------------------------------------------------
// 핸드아웃 저장
// ----------------------------------------------------------
$handout_table = $write_table . '_handouts';
$create_handout_table_sql = "CREATE TABLE IF NOT EXISTS `{$handout_table}` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wr_id` INT UNSIGNED NOT NULL,
    `handout_index` TINYINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `content` MEDIUMTEXT NOT NULL,
    `content_front` MEDIUMTEXT NOT NULL,
    `content_back` MEDIUMTEXT NOT NULL,
    `image_url` VARCHAR(255) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `wr_handout_unique` (`wr_id`, `handout_index`),
    KEY `wr_id_idx` (`wr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
sql_query($create_handout_table_sql, false);

// 기존 테이블에 content_front, content_back 컬럼 추가 (없으면)
$check_front = sql_fetch("SHOW COLUMNS FROM `{$handout_table}` LIKE 'content_front'");
if (!$check_front) {
    sql_query("ALTER TABLE `{$handout_table}` ADD COLUMN `content_front` MEDIUMTEXT NOT NULL AFTER `content`", false);
}
$check_back = sql_fetch("SHOW COLUMNS FROM `{$handout_table}` LIKE 'content_back'");
if (!$check_back) {
    sql_query("ALTER TABLE `{$handout_table}` ADD COLUMN `content_back` MEDIUMTEXT NOT NULL AFTER `content_front`", false);
}

// 기존 핸드아웃 삭제 (수정 시 전체 교체)
sql_query("DELETE FROM `{$handout_table}` WHERE wr_id = '{$wr_id}'", false);

$handout_count = isset($_POST['handout_count']) ? (int)$_POST['handout_count'] : 0;
$handout_count = max(0, min(20, $handout_count));

$handout_upload_dir = G5_DATA_PATH . '/file/' . $bo_table . '/handout';
$handout_upload_url = G5_DATA_URL . '/file/' . $bo_table . '/handout';
if (!is_dir($handout_upload_dir)) {
    @mkdir($handout_upload_dir, G5_DIR_PERMISSION, true);
    @chmod($handout_upload_dir, G5_DIR_PERMISSION);
}

for ($i = 1; $i <= $handout_count; $i++) {
    $title = isset($_POST['handout_' . $i . '_title']) ? trim(stripslashes($_POST['handout_' . $i . '_title'])) : '';
    $content = isset($_POST['handout_' . $i . '_content']) ? trim(stripslashes($_POST['handout_' . $i . '_content'])) : '';
    $content_front = isset($_POST['handout_' . $i . '_content_front']) ? trim(stripslashes($_POST['handout_' . $i . '_content_front'])) : '';
    $content_back = isset($_POST['handout_' . $i . '_content_back']) ? trim(stripslashes($_POST['handout_' . $i . '_content_back'])) : '';
    $image_url = '';

    // 이미지 파일 업로드 처리
    $file_field = 'handout_' . $i . '_image_file';
    if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] == 0) {
        $tmp_file = $_FILES[$file_field]['tmp_name'];
        $filename = $_FILES[$file_field]['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($ext, $allowed_ext)) {
            $new_filename = 'handout_' . $wr_id . '_' . $i . '_' . md5(uniqid(time(), true)) . '.' . $ext;
            $dest_path = $handout_upload_dir . '/' . $new_filename;

            if (move_uploaded_file($tmp_file, $dest_path)) {
                @chmod($dest_path, G5_FILE_PERMISSION);
                if (function_exists('resize_image')) {
                    resize_image($dest_path, 800, 800);
                }
                $image_url = $handout_upload_url . '/' . $new_filename;
            }
        }
    }

    // 이미지 URL 처리 (파일 업로드가 없을 때만)
    if (!$image_url && isset($_POST['handout_' . $i . '_image_url']) && trim($_POST['handout_' . $i . '_image_url']) !== '') {
        $candidate = clean_xss_tags(trim($_POST['handout_' . $i . '_image_url']));
        if ($candidate !== '') {
            if (filter_var($candidate, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $candidate) || strpos($candidate, G5_DATA_URL) === 0) {
                $image_url = $candidate;
            }
        }
    }

    // 제목이나 본문이 하나라도 있으면 저장
    if ($title !== '' || $content !== '' || $content_front !== '' || $content_back !== '') {
        $title_escaped = sql_real_escape_string($title);
        $content_escaped = sql_real_escape_string($content);
        $content_front_escaped = sql_real_escape_string($content_front);
        $content_back_escaped = sql_real_escape_string($content_back);
        $image_url_escaped = sql_real_escape_string($image_url);
        
        $handout_sql = "INSERT INTO `{$handout_table}` (wr_id, handout_index, title, content, content_front, content_back, image_url, created_at) 
                        VALUES ('{$wr_id}', '{$i}', '{$title_escaped}', '{$content_escaped}', '{$content_front_escaped}', '{$content_back_escaped}', '{$image_url_escaped}', NOW())";
        sql_query($handout_sql, false);
    }
}


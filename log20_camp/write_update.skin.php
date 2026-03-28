<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$allowed_write_types = array('scena', 'pclist', 'tarae');
$current_write_type = '';

if (isset($_REQUEST['write_type']) && $_REQUEST['write_type']) {
    $current_write_type = trim($_REQUEST['write_type']);
} elseif (isset($_POST['wr_4']) && $_POST['wr_4']) {
    $current_write_type = trim($_POST['wr_4']);
}

if ((!$current_write_type || !in_array($current_write_type, $allowed_write_types, true)) && isset($wr_id) && $wr_id) {
    $current_write = sql_fetch("SELECT wr_4 FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    if ($current_write && isset($current_write['wr_4']) && in_array($current_write['wr_4'], $allowed_write_types, true)) {
        $current_write_type = $current_write['wr_4'];
    }
}

if ($current_write_type === 'scena') {
    $wr19_column = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_19'");
    if (!$wr19_column) {
        sql_query("ALTER TABLE {$write_table} ADD COLUMN `wr_19` VARCHAR(255) NOT NULL DEFAULT ''", false);
    }

    // PC 관련 필드 존재 여부 확인 (wr_11 ~ wr_17)
    $pc_columns = array(
        'wr_11' => "INT(11) NOT NULL DEFAULT '0'",
        'wr_12' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'wr_13' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'wr_14' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'wr_15' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'wr_16' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'wr_17' => "VARCHAR(255) NOT NULL DEFAULT ''"
    );

    foreach ($pc_columns as $col => $definition) {
        $col_exists = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE '{$col}'");
        if (!$col_exists) {
            sql_query("ALTER TABLE {$write_table} ADD COLUMN `{$col}` {$definition}", false);
        }
    }
}

// 기존 이미지 삭제 처리 (먼저 처리)
$delete_existing = false;
if ($w == 'u' && isset($_POST['wr_7_del']) && $_POST['wr_7_del'] == '1') {
    $delete_existing = true;
    // 기존 wr_7 값 가져오기
    $old_wr_7 = sql_fetch("SELECT wr_7, wr_parent FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    if ($old_wr_7 && $old_wr_7['wr_7']) {
        $is_parent_post = isset($old_wr_7['wr_parent']) && (int)$old_wr_7['wr_parent'] === (int)$wr_id;
        $old_url = $old_wr_7['wr_7'];
        // 로컬 파일인 경우 삭제
        if (strpos($old_url, G5_DATA_URL.'/file/') === 0) {
            $old_path = str_replace(G5_DATA_URL, G5_DATA_PATH, $old_url);
            $allow_delete_file = true;
            if (!$is_parent_post) {
                $old_url_safe = sql_real_escape_string($old_url);
                $ref_row = sql_fetch("SELECT COUNT(*) as cnt FROM {$write_table} WHERE wr_7 = '{$old_url_safe}' AND wr_id != '{$wr_id}'");
                if ($ref_row && isset($ref_row['cnt']) && (int)$ref_row['cnt'] > 0) {
                    $allow_delete_file = false;
                }
            }
            if ($allow_delete_file && file_exists($old_path)) {
                @unlink($old_path);
            }
        }
    }
}

// 파일 업로드 처리 (우선순위 1) - pclist 타입은 write_update.pclist.skin.php에서 처리
$has_file_upload = false;
if ($current_write_type !== 'pclist' && isset($_FILES['wr_7_file']) && $_FILES['wr_7_file']['error'] == 0) {
    $tmp_file = $_FILES['wr_7_file']['tmp_name'];
    $filename = $_FILES['wr_7_file']['name'];
    
    // 파일 확장자 체크
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (in_array($ext, $allowed_ext)) {
        // 업로드 디렉토리 설정
        $upload_dir = G5_DATA_PATH.'/file/'.$bo_table;
        $upload_url = G5_DATA_URL.'/file/'.$bo_table;
        
        // 디렉토리가 없으면 생성
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, G5_DIR_PERMISSION, true);
            @chmod($upload_dir, G5_DIR_PERMISSION);
        }
        
        // 파일명 생성 (중복 방지)
        $new_filename = 'thumb_'.md5(uniqid(time(), true)).'.'.$ext;
        $dest_path = $upload_dir.'/'.$new_filename;
        
        // 파일 이동
        if (move_uploaded_file($tmp_file, $dest_path)) {
            // 파일 권한 설정
            @chmod($dest_path, G5_FILE_PERMISSION);
            
            // 이미지 리사이징 (최대 800x800)
            if (function_exists('resize_image')) {
                resize_image($dest_path, 800, 800);
            }
            
            // URL 생성하여 wr_7에 저장
            $image_url = $upload_url.'/'.$new_filename;
            
            // wr_7 업데이트 (파일 업로드가 있으면 URL 입력 필드는 무시)
            $sql = "UPDATE {$write_table} SET wr_7 = '".sql_real_escape_string($image_url)."' WHERE wr_id = '{$wr_id}'";
            sql_query($sql);
            $has_file_upload = true;
        }
    }
}

// URL 입력 처리 (파일 업로드가 없을 때만) - pclist 타입은 write_update.pclist.skin.php에서 처리
if ($current_write_type !== 'pclist' && !$has_file_upload) {
    if (isset($_POST['wr_7']) && !empty(trim($_POST['wr_7']))) {
        $wr_7_url = clean_xss_tags(trim($_POST['wr_7']));
        if (filter_var($wr_7_url, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_7_url)) {
            // URL이 유효하면 저장
            $sql = "UPDATE {$write_table} SET wr_7 = '".sql_real_escape_string($wr_7_url)."' WHERE wr_id = '{$wr_id}'";
            sql_query($sql);
        }
    } elseif ($delete_existing) {
        // 기존 이미지 삭제만 체크되고 새 파일/URL이 없으면 wr_7 초기화
        $sql = "UPDATE {$write_table} SET wr_7 = '' WHERE wr_id = '{$wr_id}'";
        sql_query($sql);
    }
}

// PC 이미지 저장 처리 (시나리오 전용)
if ($current_write_type === 'scena') {
    include_once(__DIR__ . '/write_update.scena.skin.php');
}

// pclist 전용 필드 저장 처리
if ($current_write_type === 'pclist') {
    include_once(__DIR__ . '/write_update.pclist.skin.php');
}

// tarae 전용 필드 저장 처리
if ($current_write_type === 'tarae') {
    include_once(__DIR__ . '/write_update.tarae.skin.php');
}

// 일반 게시글(부모 게시물) 전용 필드 저장 처리
// scena, pclist, tarae가 아닌 경우에만 처리
if (!$current_write_type || !in_array($current_write_type, $allowed_write_types, true)) {
    // wr_order (게시글 순서) 저장
    if (isset($_POST['wr_order'])) {
        $wr_order = (int)$_POST['wr_order'];
        $sql = "UPDATE {$write_table} SET wr_order = '{$wr_order}' WHERE wr_id = '{$wr_id}'";
        sql_query($sql);
    }
    
    // wr_secret (비밀글 조회 비밀번호) 저장
    // process_secret_password 함수는 secret 필드만 확인하므로, set_secret도 처리하도록 수정
    if (isset($_POST['secret']) && $_POST['secret'] == 'secret' && isset($_POST['wr_secret'])) {
        $secret_password = sql_real_escape_string(trim($_POST['wr_secret']));
        // wr_secret 필드 타입 확인 및 변경 (tinyint → varchar)
        $field_info = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_secret'");
        if ($field_info && strpos($field_info['Type'], 'tinyint') !== false) {
            sql_query("ALTER TABLE {$write_table} MODIFY COLUMN wr_secret varchar(255) NOT NULL DEFAULT ''", false);
        }
        $sql = "UPDATE {$write_table} SET wr_secret = '{$secret_password}' WHERE wr_id = '{$wr_id}'";
        sql_query($sql);
    } elseif (isset($_POST['secret']) && $_POST['secret'] != 'secret') {
        // 비밀글이 아니면 wr_secret 초기화
        $field_info = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_secret'");
        if ($field_info) {
            $sql = "UPDATE {$write_table} SET wr_secret = '' WHERE wr_id = '{$wr_id}'";
            sql_query($sql);
        }
    }
}

// wr_parent 처리 (자식 게시물인 경우)
if ($w == '' && isset($_POST['wr_parent']) && (int)$_POST['wr_parent'] > 0) {
    $parent_wr_id = (int)$_POST['wr_parent'];
    // 부모 게시물이 존재하는지 확인
    $parent_check = sql_fetch("SELECT wr_id FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
    if ($parent_check && $parent_check['wr_id']) {
        // 자식 게시물의 wr_parent를 부모 게시물 ID로 설정
        sql_query("UPDATE {$write_table} SET wr_parent = '{$parent_wr_id}' WHERE wr_id = '{$wr_id}'");
    }
}

// 시나리오/PC/메모 전용 게시물은 커스텀 뷰로 이동하도록 리디렉션 주소를 필터링
if (!function_exists('tr_log_write_update_move_url')) {
    function tr_log_write_update_move_url($redirect_url, $board, $wr_id, $w, $qstr, $file_upload_msg)
    {
        global $g5;

        $allowed_types = array('scena', 'pclist', 'tarae');
        $write_type = isset($_REQUEST['write_type']) ? trim($_REQUEST['write_type']) : '';

        // write_type 파라미터가 없다면 wr_4 값을 확인
        if (!$write_type && isset($board['bo_table'])) {
            $write_table = $g5['write_prefix'] . $board['bo_table'];
            $current_write = get_write($write_table, $wr_id);
            if ($current_write && isset($current_write['wr_4'])) {
                $write_type = $current_write['wr_4'];
            }
        }

        if (!$write_type || !in_array($write_type, $allowed_types, true)) {
            return $redirect_url;
        }

        $bo_table = isset($board['bo_table']) ? $board['bo_table'] : '';
        if (!$bo_table) {
            return $redirect_url;
        }

        $target_wr_id = (int)$wr_id;

        if ($write_type === 'scena') {
            if (isset($_REQUEST['wr_parent']) && (int)$_REQUEST['wr_parent'] > 0) {
                $target_wr_id = (int)$_REQUEST['wr_parent'];
            } else {
                $write_table_name = $g5['write_prefix'] . $bo_table;
                $current_post = get_write($write_table_name, $wr_id);
                if ($current_post && !empty($current_post['wr_parent'])) {
                    $target_wr_id = (int)$current_post['wr_parent'];
                }
            }
            // 시나리오는 list.scena.skin.php로 리다이렉트
            $custom_url = G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $target_wr_id . '&sublist=scena';
            return short_url_clean($custom_url);
        }

        if ($write_type === 'pclist') {
            // pclist는 부모 게시물로 리다이렉트하여 list.pclist.skin.php가 표시되도록 함
            if (isset($_REQUEST['wr_parent']) && (int)$_REQUEST['wr_parent'] > 0) {
                $target_wr_id = (int)$_REQUEST['wr_parent'];
            } else {
                $write_table_name = $g5['write_prefix'] . $bo_table;
                $current_post = get_write($write_table_name, $wr_id);
                if ($current_post && !empty($current_post['wr_parent'])) {
                    $target_wr_id = (int)$current_post['wr_parent'];
                }
            }
            // pclist는 부모 게시물의 view.skin.php 내부에 list.pclist.skin.php가 표시됨
            $custom_url = G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $target_wr_id . '&sublist=pclist';
            return short_url_clean($custom_url);
        }

        if ($write_type === 'tarae') {
            // tarae는 부모 게시물로 리다이렉트하여 list.tarae.skin.php가 표시되도록 함
            if (isset($_REQUEST['wr_parent']) && (int)$_REQUEST['wr_parent'] > 0) {
                $target_wr_id = (int)$_REQUEST['wr_parent'];
            } else {
                $write_table_name = $g5['write_prefix'] . $bo_table;
                $current_post = get_write($write_table_name, $wr_id);
                if ($current_post && !empty($current_post['wr_parent'])) {
                    $target_wr_id = (int)$current_post['wr_parent'];
                }
            }
            // tarae는 부모 게시물의 view.skin.php 내부에 list.tarae.skin.php가 표시됨
            $custom_url = G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $target_wr_id . '&sublist=tarae';
            return short_url_clean($custom_url);
        }

        $custom_url = G5_HTTP_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $target_wr_id . '&write_type=' . $write_type;
        return short_url_clean($custom_url);
    }

    add_replace('write_update_move_url', 'tr_log_write_update_move_url', 10, 6);
}


<?php
if (!defined('_GNUBOARD_')) {
    $skin_path = dirname(__FILE__);
    $common_path = realpath($skin_path . '/../../../_common.php');
    if ($common_path && file_exists($common_path)) {
        include_once($common_path);
    } else {
        exit;
    }
}

global $g5, $member, $board, $bo_table, $board_skin_url, $is_admin;

// 게시판 테이블명 설정 (GET 우선, 없으면 bo_table/board에서 보정)
$current_bo_table = '';
if (isset($_GET['bo_table']) && $_GET['bo_table']) {
    $current_bo_table = preg_replace('/[^a-z0-9_]/i', '', $_GET['bo_table']);
} elseif (isset($bo_table) && $bo_table) {
    $current_bo_table = $bo_table;
} elseif (isset($board['bo_table']) && $board['bo_table']) {
    $current_bo_table = $board['bo_table'];
}
if (!$current_bo_table) {
    $current_bo_table = 'tr_log';
}

// Youtube URL -> ID 추출
if (!function_exists('tarae_extract_youtube_id')) {
    function tarae_extract_youtube_id($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        $parts = parse_url($url);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['v']) && preg_match('~^[A-Za-z0-9_-]{11}$~', $query['v'])) {
                return $query['v'];
            }
        }
        return '';
    }
}

// 게시판 정보 가져오기 (없으면)
if (!isset($board) || !$board) {
    $board = get_board_db($current_bo_table, true);
}
$write_table = $g5['write_prefix'] . $current_bo_table;

// 공유용 OG 메타 (og_image.tarae.php에서 유틸 함수 제공)
define('TARAE_OG_META_ONLY', true);
include_once(__DIR__ . '/og_image.tarae.php');
if (function_exists('tarae_apply_share_meta')) {
    tarae_apply_share_meta($write_table, $current_bo_table, $parent_wr_id, $board_skin_url);
}

// 부모 게시물 ID (GET 우선, 없으면 다른 후보로 재확인)
$resolved_parent_wr_id = 0;
if (isset($_GET['wr_id']) && (int)$_GET['wr_id'] > 0) {
    $resolved_parent_wr_id = (int)$_GET['wr_id'];
} elseif (isset($parent_wr_id) && (int)$parent_wr_id > 0) {
    $resolved_parent_wr_id = (int)$parent_wr_id;
} elseif (isset($view['wr_id']) && (int)$view['wr_id'] > 0) {
    $resolved_parent_wr_id = (int)$view['wr_id'];
} elseif (isset($wr_id) && (int)$wr_id > 0) {
    $resolved_parent_wr_id = (int)$wr_id;
}
$parent_wr_id = $resolved_parent_wr_id;

// 부모 게시물 색상 정보
$parent_wr_1 = isset($wr_1) ? $wr_1 : '';
$parent_wr_2 = isset($wr_2) ? $wr_2 : '';
if ($parent_wr_id > 0 && empty($parent_wr_1)) {
    $parent_write = sql_fetch("SELECT wr_1, wr_2 FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
    if ($parent_write) {
        $parent_wr_1 = isset($parent_write['wr_1']) ? $parent_write['wr_1'] : '';
        $parent_wr_2 = isset($parent_write['wr_2']) ? $parent_write['wr_2'] : '';
    }
}

// 페이지네이션 설정
$page = isset($_GET['tarae_page']) ? (int)$_GET['tarae_page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
// 타래(댓글) 첨부 최대 개수 (게시판 설정, 최대 16)
$max_reply_files = isset($board['bo_upload_count']) ? (int)$board['bo_upload_count'] : 0;
if ($max_reply_files > 16) {
    $max_reply_files = 16;
}
if ($max_reply_files < 0) {
    $max_reply_files = 0;
}

// 전체 메모 수 조회
$total_count = 0;
$total_page = 1;

$table_check = sql_query("SHOW TABLES LIKE '{$write_table}'", false);
if ($table_check && sql_num_rows($table_check) > 0) {
    $count_sql = "SELECT COUNT(*) as cnt 
                  FROM {$write_table} 
                  WHERE wr_parent = '{$parent_wr_id}' 
                  AND wr_is_comment IN (0, 1)
                  AND wr_id != wr_parent
                  AND wr_4 = 'tarae'";
    $count_result = sql_query($count_sql, false);
    if ($count_result) {
        $count_row = sql_fetch_array($count_result);
        $total_count = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
    }
    $total_page = $total_count > 0 ? ceil($total_count / $limit) : 1;
}

// 컬럼 존재 여부 확인 (wr_11~wr_26)
$max_display_images = 16;
$tarae_image_start_index = 11;
$tarae_columns = array();
for ($i = 1; $i <= $max_display_images; $i++) {
    $tarae_columns['wr_' . ($tarae_image_start_index - 1 + $i)] = false;
}
foreach ($tarae_columns as $col => $exists) {
    $col_check = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE '{$col}'");
    $tarae_columns[$col] = $col_check ? true : false;
}

// 메모 목록 조회
$list = array();
if ($parent_wr_id > 0) {
    $parent_wr_id_safe = (int)$parent_wr_id;
    $offset_safe = (int)$offset;
    $limit_safe = (int)$limit;
    
    $image_selects = array();
    for ($i = 1; $i <= $max_display_images; $i++) {
        $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
        $image_selects[] = $tarae_columns[$field] ? $field : "'' AS {$field}";
    }
    $image_select_sql = implode(', ', $image_selects);

    $sql = "SELECT wr_id, wr_subject, wr_name, wr_content, wr_6, {$image_select_sql}, wr_datetime, mb_id
            FROM {$write_table} 
            WHERE wr_parent = '{$parent_wr_id_safe}' 
            AND wr_is_comment IN (0, 1)
            AND wr_id != wr_parent
            AND wr_4 = 'tarae'
            ORDER BY wr_datetime DESC
            LIMIT {$offset_safe}, {$limit_safe}";
    
    $result = sql_query($sql, false);
    
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $wr_images = array();
            for ($i = 1; $i <= $max_display_images; $i++) {
                $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
                $wr_images[] = isset($row[$field]) ? $row[$field] : '';
            }
            $list[] = array(
                'wr_id' => $row['wr_id'],
                'wr_subject' => $row['wr_subject'],
                'wr_name' => $row['wr_name'],
                'wr_content' => $row['wr_content'],
                'wr_6' => isset($row['wr_6']) ? $row['wr_6'] : '',
                'wr_images' => $wr_images,
                'wr_datetime' => $row['wr_datetime'],
                'mb_id' => $row['mb_id']
            );
        }
    }
}

// 타래(댓글) 목록 조회 - 메모별로 묶어서 표시
$tarae_threads = array();
if (!empty($list)) {
    $memo_ids = array();
    foreach ($list as $item) {
        $memo_ids[] = (int)$item['wr_id'];
    }
    $memo_ids = array_unique(array_filter($memo_ids));
    if (!empty($memo_ids)) {
        $memo_ids_sql = implode(',', $memo_ids);
        $thread_sql = "SELECT wr_id, wr_parent, wr_subject, wr_content, wr_name, wr_6, {$image_select_sql}, wr_datetime, mb_id
                       FROM {$write_table}
                       WHERE wr_parent IN ({$memo_ids_sql})
                       AND wr_id != wr_parent
                       AND wr_4 = 'tarae'
                       ORDER BY wr_datetime ASC";
        $thread_result = sql_query($thread_sql, false);
        if ($thread_result) {
            while ($thread_row = sql_fetch_array($thread_result)) {
                $parent_id = (int)$thread_row['wr_parent'];
                if (!isset($tarae_threads[$parent_id])) {
                    $tarae_threads[$parent_id] = array();
                }
                $tarae_threads[$parent_id][] = $thread_row;
            }
        }
    }
}

$action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
$accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#f5f5f5';
?>

<div class="log20_sublist_area" id="log20_sublist_tarae"<?php if ($action_color || $accent_color) { ?> style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;"<?php } ?>>
    <style>
    .log20_tarae_toggle_btn--top {
        margin-bottom: 5px;
    }
    </style>
    <?php
    // 부모 게시물의 색상 정보 및 글쓰기 링크
    $action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
    $accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';

    // tarae 전용 쓰기 페이지로 이동 (기본 write 흐름 사용)
    $write_url = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&write_type=tarae';
    if ($parent_wr_id > 0) {
        $write_url .= '&wr_parent=' . (int)$parent_wr_id;
    }
    
    // 게시판 권한 체크 (읽기/쓰기)
    $member_level = isset($member['mb_level']) ? (int)$member['mb_level'] : 0;
    $write_level = isset($board['bo_write_level']) ? (int)$board['bo_write_level'] : 1;
    $read_level = isset($board['bo_read_level']) ? (int)$board['bo_read_level'] : 1;
    $has_write_permission = $is_admin || ($member_level >= $write_level);
    $has_read_permission = $is_admin || ($member_level >= $read_level) || !$is_member;
    ?>
    <div class="log20_sublist_header"<?php if ($action_color) { ?> style="border-bottom-color: <?php echo htmlspecialchars($action_color); ?>;"<?php } ?>>
        <?php if ($has_write_permission) { ?>
        <a href="<?php echo $write_url; ?>" class="log20_sublist_write_btn" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>; border:none;" onclick="return checkWritePermission(event, <?php echo $write_level; ?>, <?php echo $is_admin ? 'true' : 'false'; ?>, <?php echo $member_level; ?>);">
            <span class="log20_sublist_write_btn_text">타래 시작하기</span>
        </a>
        <?php } ?>
    </div>
    
    <!-- 검색창 -->
    <div class="log20_tarae_search_wrap"<?php if ($action_color || $accent_color) { ?> style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;"<?php } ?>>
        <div class="log20_tarae_search_input_row">
        <input type="text" id="log20_tarae_search_input" class="log20_tarae_search_input" placeholder="제목, 내용으로 검색..." autocomplete="off">
        </div>
        <div class="log20_tarae_search_button_row">
        <button type="button" id="log20_tarae_search_btn" class="log20_tarae_search_btn" title="찾기">찾기</button>
        <button type="button" id="log20_tarae_search_reset" class="log20_tarae_search_reset" title="초기화">초기화</button>
        </div>
    </div>
    
    <!-- 메모 목록 -->
    <?php if (count($list) > 0) { ?>
        <div class="log20_list_tarae_area" id="log20_list_tarae_area">
            <?php foreach ($list as $item) { 
                $content_id = 'tarae_content_' . (int)$item['wr_id'];
                $edit_url   = G5_BBS_URL . '/write.php?w=u&bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$item['wr_id'] . '&write_type=tarae';
                $del_url    = $board_skin_url . '/delete.sublist.skin.php?bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$item['wr_id'] . '&write_type=tarae';
                $display_name = $item['wr_name'] ? $item['wr_name'] : '익명';
                $member_photo = '';
                if (!empty($item['mb_id'])) {
                    $mem = get_member($item['mb_id'], 'mb_signature');
                    $member_photo = isset($mem['mb_signature']) ? $mem['mb_signature'] : '';
                }
                $images = array();
                if (!empty($item['wr_images']) && is_array($item['wr_images'])) {
                    foreach ($item['wr_images'] as $img_url) {
                        if (!empty($img_url)) {
                            $images[] = $img_url;
                        }
                    }
                }
                $max_display_images = 16;
                if (count($images) > $max_display_images) {
                    $images = array_slice($images, 0, $max_display_images);
                }
                $image_count = count($images);
                $body_class = $image_count > 0 ? 'log20_tarae_body has-images' : 'log20_tarae_body no-images';
            ?>
            <div class="log20_tarae_item" data-tarae-id="<?php echo (int)$item['wr_id']; ?>" data-search-text="<?php echo htmlspecialchars(strtolower(strip_tags($item['wr_subject'] . ' ' . $item['wr_content']))); ?>">
                <div class="log20_tarae_header">
                    <div class="log20_tarae_header_left">
                        <div class="log20_tarae_profile">
                            <span class="log20_tarae_profile_img">
                                <?php if ($member_photo) { ?>
                                    <img src="<?php echo htmlspecialchars($member_photo); ?>" alt="">
                                <?php } else { ?>
                                    <span class="log20_tarae_profile_fallback"><?php echo mb_substr(get_text($display_name), 0, 1); ?></span>
                                <?php } ?>
                            </span>
                            <span class="log20_tarae_profile_name"><?php echo get_text($display_name); ?></span>
                        </div>
                        <span class="log20_tarae_title"><?php echo get_text($item['wr_subject']); ?></span>
                    </div>
                    <span class="log20_tarae_date">
                        <?php echo date('Y.m.d', strtotime($item['wr_datetime'])); ?>
                        <?php if ($has_read_permission) { ?>
                        <span class="log20_tarae_share_wrap">
                            <button type="button" class="log20_tarae_btn log20_tarae_btn_share" data-share-subject="<?php echo htmlspecialchars($item['wr_subject']); ?>" data-share-parent="<?php echo (int)$item['wr_id']; ?>" data-share-id="<?php echo (int)$item['wr_id']; ?>">공유</button>
                            <span class="log20_tarae_share_toast" aria-hidden="true">복사되었습니다</span>
                        </span>
                        <?php } ?>
                        <?php if ($has_write_permission) { ?>
                        <a href="<?php echo $edit_url; ?>" class="log20_tarae_btn log20_tarae_btn_edit">수정</a>
                        <a href="<?php echo $del_url; ?>" class="log20_tarae_btn log20_tarae_btn_delete" onclick="del(this.href); return false;">삭제</a>
                        <?php } ?>
                    </span>
                </div>
                <div class="log20_tarae_content" id="<?php echo $content_id; ?>">
                    <div class="<?php echo $body_class; ?>">
                        <div class="log20_tarae_text">
                            <?php
                                $youtube_id = tarae_extract_youtube_id(isset($item['wr_6']) ? $item['wr_6'] : '');
                                if ($youtube_id) {
                                    $youtube_src = 'https://www.youtube.com/embed/' . $youtube_id;
                            ?>
                                <div class="log20_tarae_youtube">
                                    <iframe src="<?php echo htmlspecialchars($youtube_src); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            <?php } ?>
                            <?php
                            $item_content = stripslashes($item['wr_content']);
                            // 동일 본문 내 Buy Me a Coffee 등 외부 스크립트 중복 제거 (버튼 2개 연속 출력 방지)
                            if (preg_match_all('/<script\s[^>]*src="[^"]*buymeacoffee[^"]*"[^>]*><\/script>\s*/i', $item_content, $bmc_matches) && count($bmc_matches[0]) > 1) {
                                $item_content = preg_replace('/<script\s[^>]*src="[^"]*buymeacoffee[^"]*"[^>]*><\/script>\s*/i', "\x00BMC_PLACEHOLDER\x00", $item_content);
                                $item_content = preg_replace('/\x00BMC_PLACEHOLDER\x00/', $bmc_matches[0][0], $item_content, 1);
                                $item_content = str_replace("\x00BMC_PLACEHOLDER\x00", '', $item_content);
                            }
                            echo $item_content;
                            ?>
                        </div>
                        <?php if ($image_count > 0) { ?>
                        <?php
                        $row_sizes = array();
                        if ($image_count <= 3) {
                            $row_sizes[] = $image_count;
                        } elseif ($image_count === 4) {
                            $row_sizes = array(2, 2);
                        } elseif ($image_count === 5) {
                            $row_sizes = array(4, 2);
                        } elseif ($image_count === 6) {
                            $row_sizes = array(3, 3);
                        } elseif ($image_count === 7) {
                            $row_sizes = array(4, 3);
                        } elseif ($image_count === 8) {
                            $row_sizes = array(3, 3, 2);
                        } elseif ($image_count === 9) {
                            $row_sizes = array(3, 3, 3);
                        } elseif ($image_count === 10) {
                            $row_sizes = array(4, 4, 2);
                        } elseif ($image_count === 11) {
                            $row_sizes = array(4, 4, 3);
                        } elseif ($image_count === 12) {
                            $row_sizes = array(4, 4, 4);
                        } else {
                            $row_sizes = array(4, 4, 4);
                            $remaining = $image_count - 12;
                            if ($remaining > 0) {
                                $row_sizes[] = $remaining >= 4 ? 4 : $remaining;
                            }
                        }
                        ?>
                        <div class="log20_tarae_images_rows log20_tarae_images_rows--<?php echo (int)$image_count; ?>">
                            <?php
                            $img_index = 0;
                            $remaining = $image_count;
                            $base_columns = isset($row_sizes[0]) ? (int)$row_sizes[0] : 1;
                            foreach ($row_sizes as $size) {
                                if ($remaining <= 0) break;
                                $size = $size > $remaining ? $remaining : $size;
                                $remaining -= $size;
                                $row_columns = $base_columns;
                            ?>
                            <div class="log20_tarae_images_row" style="grid-template-columns: repeat(<?php echo (int)$row_columns; ?>, 1fr);">
                                <?php for ($j = 0; $j < $size; $j++) {
                                    $img_url = isset($images[$img_index]) ? $images[$img_index] : '';
                                    $img_index++;
                                ?>
                                <div class="log20_tarae_image_item">
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" alt="">
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="log20_tarae_thread" id="tarae-thread-<?php echo (int)$item['wr_id']; ?>" style="display:none;">
                    <div class="log20_tarae_thread_toggle_top">
                        <button type="button" class="log20_tarae_footer_btn log20_tarae_toggle_btn log20_tarae_toggle_btn--top" data-toggle="tarae-thread-<?php echo (int)$item['wr_id']; ?>">타래 접기</button>
                    </div>
                    <?php
                    $thread_items = isset($tarae_threads[(int)$item['wr_id']]) ? $tarae_threads[(int)$item['wr_id']] : array();
                    if (!empty($thread_items)) {
                        $thread_index = 0;
                        foreach ($thread_items as $thread_row) {
                            $thread_index++;
                            $thread_title_text = get_text($item['wr_subject']) . sprintf('%04d', $thread_index);
                    ?>
                    <?php
                        $thread_name = $thread_row['wr_name'] ? $thread_row['wr_name'] : '익명';
                        $thread_photo = '';
                        if (!empty($thread_row['mb_id'])) {
                            $thread_mem = get_member($thread_row['mb_id'], 'mb_signature');
                            $thread_photo = isset($thread_mem['mb_signature']) ? $thread_mem['mb_signature'] : '';
                        }
                    ?>
                    <div class="log20_tarae_thread_item" data-thread-id="<?php echo (int)$thread_row['wr_id']; ?>" data-thread-parent="<?php echo (int)$item['wr_id']; ?>">
                        <?php
                            $thread_edit_url = G5_BBS_URL . '/write.php?w=u&bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$thread_row['wr_id'] . '&write_type=tarae';
                            $thread_del_url  = $board_skin_url . '/delete.sublist.skin.php?bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$thread_row['wr_id'] . '&write_type=tarae';
                            $thread_files = get_file($current_bo_table, $thread_row['wr_id']);
                            $thread_file_entries = array();
                            if (is_array($thread_files) && !empty($thread_files)) {
                                foreach ($thread_files as $file_no => $thread_file) {
                                    if (!is_array($thread_file)) {
                                        continue;
                                    }
                                    if (!empty($thread_file['file'])) {
                                        $file_url = $thread_file['path'] . '/' . $thread_file['file'];
                                        if (preg_match('/\.(gif|jpe?g|png|webp)$/i', $thread_file['file'])) {
                                            $thread_file_entries[] = array(
                                                'no' => (int)$file_no,
                                                'url' => $file_url
                                            );
                                        }
                                    }
                                }
                            }
                        ?>
                        <div class="log20_tarae_thread_meta">
                            <span class="log20_tarae_thread_profile">
                                <?php if ($thread_photo) { ?>
                                    <img src="<?php echo htmlspecialchars($thread_photo); ?>" alt="">
                                <?php } else { ?>
                                    <span class="log20_tarae_thread_profile_fallback"><?php echo mb_substr(get_text($thread_name), 0, 1); ?></span>
                                <?php } ?>
                            </span>
                            <span class="log20_tarae_thread_name"><?php echo get_text($thread_name); ?></span>
                            <span class="log20_tarae_thread_title"><?php echo get_text(isset($thread_row['wr_subject']) ? $thread_row['wr_subject'] : ''); ?></span>
                            <span class="log20_tarae_thread_date"><?php echo date('Y.m.d', strtotime($thread_row['wr_datetime'])); ?></span>
                            <?php
                                $thread_order_values = array();
                                for ($i = 1; $i <= $max_display_images; $i++) {
                                    $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
                                    $val = isset($thread_row[$field]) ? trim($thread_row[$field]) : '';
                                    if ($val !== '') {
                                        $thread_order_values[] = $val;
                                    }
                                }
                            ?>
                            <?php if ($has_read_permission) { ?>
                            <span class="log20_tarae_share_wrap">
                                <button type="button" class="log20_tarae_thread_share log20_tarae_btn_share" data-share-subject="<?php echo htmlspecialchars(isset($thread_row['wr_subject']) ? $thread_row['wr_subject'] : ''); ?>" data-share-parent="<?php echo (int)$item['wr_id']; ?>" data-share-id="<?php echo (int)$thread_row['wr_id']; ?>">공유</button>
                                <span class="log20_tarae_share_toast log20_tarae_share_toast--bottom" aria-hidden="true">복사되었습니다</span>
                            </span>
                            <?php } ?>
                            <?php if ($has_write_permission) { ?>
                            <button type="button" class="log20_tarae_thread_edit" data-thread-id="<?php echo (int)$thread_row['wr_id']; ?>" data-thread-parent="<?php echo (int)$item['wr_id']; ?>" data-thread-content="<?php echo htmlspecialchars($thread_row['wr_content']); ?>" data-thread-subject="<?php echo htmlspecialchars(isset($thread_row['wr_subject']) ? $thread_row['wr_subject'] : ''); ?>" data-thread-files="<?php echo htmlspecialchars(json_encode($thread_file_entries), ENT_QUOTES, 'UTF-8'); ?>" data-thread-order="<?php echo htmlspecialchars(json_encode($thread_order_values), ENT_QUOTES, 'UTF-8'); ?>">수정</button>
                            <a href="<?php echo $thread_del_url; ?>" class="log20_tarae_thread_delete" onclick="del(this.href); return false;">삭제</a>
                            <?php } ?>
                        </div>
                        <?php
                        $file_urls = array();
                        if (is_array($thread_files) && !empty($thread_files)) {
                            foreach ($thread_files as $file_no => $thread_file) {
                                if (!is_array($thread_file)) {
                                    continue;
                                }
                                if (!empty($thread_file['file'])) {
                                    $file_url = $thread_file['path'] . '/' . $thread_file['file'];
                                    if (preg_match('/\.(gif|jpe?g|png|webp)$/i', $thread_file['file'])) {
                                        $file_urls[(int)$file_no] = $file_url;
                                    }
                                }
                            }
                        }
                        $file_urls_list = array_values($file_urls);

                        $ordered_images = array();
                        $used_urls = array();
                        $has_order = false;
                        for ($i = 1; $i <= $max_display_images; $i++) {
                            $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
                            $val = isset($thread_row[$field]) ? trim($thread_row[$field]) : '';
                            if ($val !== '') {
                                $has_order = true;
                                if (strpos($val, 'file:') === 0) {
                                    $file_idx = (int)substr($val, 5);
                                    if (isset($file_urls_list[$file_idx])) {
                                        $ordered_images[] = $file_urls_list[$file_idx];
                                        $used_urls[$file_urls_list[$file_idx]] = true;
                                    }
                                } else {
                                    $ordered_images[] = $val;
                                    $used_urls[$val] = true;
                                }
                            }
                        }

                        if ($has_order) {
                            $remaining_files = array();
                            foreach ($file_urls_list as $file_url) {
                                if (!isset($used_urls[$file_url])) {
                                    $remaining_files[] = $file_url;
                                }
                            }
                            $thread_images = array_merge($ordered_images, $remaining_files);
                        } else {
                            $thread_images = array_values($file_urls);
                        }
                        if (count($thread_images) > $max_reply_files) {
                            $thread_images = array_slice($thread_images, 0, $max_reply_files);
                        }
                        $thread_image_count = count($thread_images);
                        $thread_body_class = $thread_image_count > 0 ? 'log20_tarae_thread_body has-images' : 'log20_tarae_thread_body no-images';
                        ?>
                        <div class="<?php echo $thread_body_class; ?>">
                            <div class="log20_tarae_thread_text">
                                <?php
                                    $thread_youtube_id = tarae_extract_youtube_id(isset($thread_row['wr_6']) ? $thread_row['wr_6'] : '');
                                    if ($thread_youtube_id) {
                                        $thread_youtube_src = 'https://www.youtube.com/embed/' . $thread_youtube_id;
                                ?>
                                    <div class="log20_tarae_youtube">
                                        <iframe src="<?php echo htmlspecialchars($thread_youtube_src); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </div>
                                <?php } ?>
                                <?php echo nl2br(stripslashes($thread_row['wr_content'])); ?>
                            </div>
                            <?php if ($thread_image_count > 0) { ?>
                            <?php
                            $row_sizes = array();
                            if ($thread_image_count <= 3) {
                                $row_sizes[] = $thread_image_count;
                            } elseif ($thread_image_count === 4) {
                                $row_sizes = array(2, 2);
                            } elseif ($thread_image_count === 5) {
                                $row_sizes = array(4, 2);
                            } elseif ($thread_image_count === 6) {
                                $row_sizes = array(3, 3);
                            } elseif ($thread_image_count === 7) {
                                $row_sizes = array(4, 3);
                            } elseif ($thread_image_count === 8) {
                                $row_sizes = array(3, 3, 2);
                            } elseif ($thread_image_count === 9) {
                                $row_sizes = array(3, 3, 3);
                            } elseif ($thread_image_count === 10) {
                                $row_sizes = array(4, 4, 2);
                            } elseif ($thread_image_count === 11) {
                                $row_sizes = array(4, 4, 3);
                            } elseif ($thread_image_count === 12) {
                                $row_sizes = array(4, 4, 4);
                            } else {
                                $row_sizes = array(4, 4, 4);
                                $remaining = $thread_image_count - 12;
                                if ($remaining > 0) {
                                    $row_sizes[] = $remaining >= 4 ? 4 : $remaining;
                                }
                            }
                            ?>
                            <div class="log20_tarae_images_rows log20_tarae_images_rows--<?php echo (int)$thread_image_count; ?>" style="--tarae-image-max-height: 200px;">
                                <?php
                                $img_index = 0;
                                $remaining = $thread_image_count;
                                $base_columns = isset($row_sizes[0]) ? (int)$row_sizes[0] : 1;
                                foreach ($row_sizes as $size) {
                                    if ($remaining <= 0) break;
                                    $size = $size > $remaining ? $remaining : $size;
                                    $remaining -= $size;
                                    $row_columns = $base_columns;
                                ?>
                                <div class="log20_tarae_images_row" style="grid-template-columns: repeat(<?php echo (int)$row_columns; ?>, 1fr);">
                                    <?php for ($j = 0; $j < $size; $j++) {
                                        $img_url = isset($thread_images[$img_index]) ? $thread_images[$img_index] : '';
                                        $img_index++;
                                    ?>
                                    <div class="log20_tarae_image_item" data-image-index="<?php echo $img_index - 1; ?>" data-image-src="<?php echo htmlspecialchars($img_url); ?>">
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" alt="">
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                    ?>
                    <div class="log20_tarae_thread_empty">아직 타래가 없습니다.</div>
                    <?php } ?>
                </div>
                <div class="log20_tarae_footer">
                    <?php if ($has_write_permission) { ?>
                    <button type="button" class="log20_tarae_footer_btn log20_tarae_toggle_btn" data-toggle="tarae-reply-<?php echo (int)$item['wr_id']; ?>">타래 잇기</button>
                    <?php } ?>
                    <button type="button" class="log20_tarae_footer_btn log20_tarae_toggle_btn" data-toggle="tarae-thread-<?php echo (int)$item['wr_id']; ?>">타래 풀기</button>
                    <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $current_bo_table; ?>&wr_id=<?php echo (int)$parent_wr_id; ?>&sublist=tarae" class="log20_tarae_footer_btn log20_tarae_footer_btn--icon" title="새로고침">
                        <i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"></i>
                        <span class="sound_only">새로고침</span>
                    </a>
                </div>
                <div class="log20_tarae_reply" id="tarae-reply-<?php echo (int)$item['wr_id']; ?>" data-default-parent="log20_list_tarae_area" style="display:none;">
                    <form class="log20_tarae_reply_form" method="post" action="<?php echo G5_BBS_URL; ?>/write_update.php" enctype="multipart/form-data">
                        <input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($current_bo_table); ?>">
                        <input type="hidden" name="wr_parent" value="<?php echo (int)$item['wr_id']; ?>">
                        <input type="hidden" name="wr_4" value="tarae">
                        <input type="hidden" name="write_type" value="tarae">
                        <input type="hidden" name="wr_subject" value="<?php echo htmlspecialchars($item['wr_subject']); ?>">
                        <input type="hidden" name="w" value="">
                        <input type="hidden" name="wr_id" value="">
                        <?php if (!empty($board['bo_use_category'])) { ?>
                            <?php
                                $parent_ca_name = isset($item['ca_name']) ? $item['ca_name'] : '';
                                if ($parent_ca_name === '') {
                                    $parent_ca_row = sql_fetch("SELECT ca_name FROM {$write_table} WHERE wr_id = '".(int)$item['wr_id']."'");
                                    if ($parent_ca_row && isset($parent_ca_row['ca_name'])) {
                                        $parent_ca_name = $parent_ca_row['ca_name'];
                                    }
                                }
                            ?>
                            <input type="hidden" name="ca_name" value="<?php echo htmlspecialchars($parent_ca_name); ?>">
                        <?php } ?>
                        <?php if (empty($member['mb_id'])) { ?>
                        <div class="log20_tarae_reply_guest">
                            <input type="text" name="wr_name" class="log20_tarae_reply_input" placeholder="이름" required>
                            <input type="password" name="wr_password" class="log20_tarae_reply_input" placeholder="비밀번호" required>
                        </div>
                        <?php } ?>
                        <textarea name="wr_content" class="log20_tarae_reply_textarea" rows="4" placeholder="본문을 입력하세요."></textarea>
                        <div class="log20_tarae_reply_preview" aria-live="polite"></div>
                        <input type="file" name="tarae_attach_files[]" class="log20_tarae_reply_file" accept="image/*,image/gif" multiple data-max="<?php echo (int)$max_reply_files; ?>">
                        <div class="log20_tarae_reply_actions">
                            <button type="button" class="log20_tarae_reply_attach log20_tarae_reply_attach_file" aria-label="이미지">
                                <i class="fa-solid fa-images"></i>
                            </button>
                            <button type="button" class="log20_tarae_reply_attach log20_tarae_reply_url_toggle" aria-label="이미지 URL">
                                <i class="fa-solid fa-u"></i>
                            </button>
                            <button type="button" class="log20_tarae_reply_attach log20_tarae_reply_copy_toggle" aria-label="붙여넣기">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                            <button type="button" class="log20_tarae_reply_attach log20_tarae_reply_youtube_toggle" aria-label="Youtube">
                                <i class="fa-solid fa-circle-play"></i>
                            </button>
                            <span class="log20_tarae_reply_file_count">0 / <?php echo (int)$max_reply_files; ?></span>
                            <span class="log20_tarae_reply_actions_spacer" aria-hidden="true"></span>
                            <span class="log20_tarae_reply_uploading" aria-live="polite">업로드 중...</span>
                            <button type="button" class="log20_tarae_reply_cancel log20_tarae_footer_btn">작성 취소</button>
                            <button type="submit" class="log20_tarae_footer_btn">작성완료</button>
                        </div>
                        <div class="log20_tarae_reply_actions log20_tarae_reply_actions--secondary">
                            <div class="log20_tarae_reply_url" style="display:none;">
                                <input type="text" class="log20_tarae_reply_url_input" placeholder="이미지 URL (https://...)">
                                <button type="button" class="log20_tarae_reply_url_done">입력 완료</button>
                                <button type="button" class="log20_tarae_reply_url_cancel">입력 취소</button>
                            </div>
                            <div class="log20_tarae_reply_paste" style="display:none;">
                                <input type="text" class="log20_tarae_reply_paste_input" placeholder="여기에 Ctrl+V로 붙여넣기">
                                <button type="button" class="log20_tarae_reply_paste_cancel">입력 취소</button>
                            </div>
                            <div class="log20_tarae_reply_youtube" style="display:none;">
                                <input type="text" name="wr_6" class="log20_tarae_reply_youtube_input" placeholder="Youtube URL (https://...)">
                                <button type="button" class="log20_tarae_reply_youtube_cancel">입력 취소</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php } ?>
        </div>
        
        <?php if ($total_page > 1) { ?>
        <div class="paginate_wrap">
            <?php
            $paging_html = '';
            $start_page = max(1, $page - 2);
            $end_page = min($total_page, $page + 2);
            
            if ($page > 1) {
                $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'tarae\', ' . ($page - 1) . ')" class="pg_page">이전</a>';
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $page) {
                    $paging_html .= '<strong class="pg_current">' . $i . '</strong>';
                } else {
                    $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'tarae\', ' . $i . ')" class="pg_page">' . $i . '</a>';
                }
            }
            
            if ($page < $total_page) {
                $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'tarae\', ' . ($page + 1) . ')" class="pg_page">다음</a>';
            }
            
            echo $paging_html;
            ?>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="log20_empty">
            <p>아직 메모가 없습니다. 첫 메모를 남겨보세요!</p>
        </div>
    <?php } ?>
</div>

<!-- tarae 메인 이미지 확대 모달 -->
<div id="tarae_image_modal" class="tarae_image_modal" style="display:none;">
    <div class="tarae_image_modal_backdrop"></div>
    <div class="tarae_image_modal_content">
        <button type="button" class="tarae_image_modal_nav tarae_image_modal_prev" aria-label="이전 이미지">‹</button>
        <button type="button" class="tarae_image_modal_close" aria-label="닫기">×</button>
        <div class="tarae_image_modal_inner">
            <img id="tarae_image_modal_img" src="" alt="">
        </div>
        <button type="button" class="tarae_image_modal_nav tarae_image_modal_next" aria-label="다음 이미지">›</button>
    </div>
</div>

<script src="<?php echo $board_skin_url; ?>/tarae.media.js"></script>
<script src="<?php echo $board_skin_url; ?>/tarae.search.js"></script>
<script src="<?php echo $board_skin_url; ?>/tarae.embed.js"></script>

<script>
// 게시판 쓰기 권한 체크 함수
function checkWritePermission(event, writeLevel, isAdmin, memberLevel) {
    if (isAdmin) {
        return true; // 관리자는 통과
    }
    
    if (memberLevel < writeLevel) {
        event.preventDefault();
        event.stopPropagation();
        alert('작성 권한이 없습니다.');
        return false;
    }
    
    return true;
}
(function() {
    function adjustSublistHeightTarae() {
        var $sublist = jQuery('#log20_content_sublist');
        if ($sublist.length === 0) return;

        // 고정 높이를 제거하고 내용에 맞게 확장
        $sublist.css('height', 'auto');
    }

    function updateTaraeImageHeights() {
        var items = document.querySelectorAll('.log20_tarae_item');
        items.forEach(function(item) {
            var body = item.querySelector('.log20_tarae_body');
            if (!body) return;
            var text = body.querySelector('.log20_tarae_text');
            if (!text) return;
            var textHeight = text.offsetHeight || 0;
            var targetHeight = textHeight > 200 ? textHeight : 200;
            body.style.setProperty('--tarae-image-max-height', targetHeight + 'px');
        });

        var threadBodies = document.querySelectorAll('.log20_tarae_thread_body');
        threadBodies.forEach(function(body) {
            var text = body.querySelector('.log20_tarae_thread_text');
            if (!text) return;
            var textHeight = text.offsetHeight || 0;
            var targetHeight = textHeight > 200 ? textHeight : 200;
            body.style.setProperty('--tarae-image-max-height', targetHeight + 'px');
        });
    }

    document.addEventListener('click', function(e) {
        var toggleBtn = e.target.closest('[data-toggle^="tarae-"]');
        if (!toggleBtn) return;
        var targetId = toggleBtn.getAttribute('data-toggle');
        var target = document.getElementById(targetId);
        if (!target) return;
        var isHidden = target.style.display === 'none' || target.style.display === '';
        target.style.display = isHidden ? 'block' : 'none';
        if (targetId.indexOf('tarae-thread-') === 0) {
            var nextText = isHidden ? '타래 접기' : '타래 풀기';
            document.querySelectorAll('[data-toggle="' + targetId + '"]').forEach(function(btn) {
                btn.textContent = nextText;
            });
        }
        adjustSublistHeightTarae();
    });

    function applyExternalLinkTargets(scope) {
        var root = scope || document;
        var links = root.querySelectorAll('.log20_tarae_text a[href], .log20_tarae_thread_text a[href]');
        links.forEach(function(link) {
            var href = link.getAttribute('href') || '';
            if (!href) return;
            var isYoutube = /(^https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\//i.test(href);
            if (!isYoutube) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    }

    applyExternalLinkTargets();

    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.log20_tarae_thread_edit');
        if (!editBtn) return;
        var parentId = editBtn.getAttribute('data-thread-parent');
        var threadId = editBtn.getAttribute('data-thread-id');
        var content = editBtn.getAttribute('data-thread-content') || '';
        var subjectValue = editBtn.getAttribute('data-thread-subject') || '';
        var filesJson = editBtn.getAttribute('data-thread-files') || '[]';
        var orderJson = editBtn.getAttribute('data-thread-order') || '[]';
        var form = document.querySelector('#tarae-reply-' + parentId + ' .log20_tarae_reply_form');
        if (!form) return;
        var textarea = form.querySelector('.log20_tarae_reply_textarea');
        var wInput = form.querySelector('input[name="w"]');
        var idInput = form.querySelector('input[name="wr_id"]');
        var subjectInput = form.querySelector('input[name="wr_subject"]');
        if (textarea) textarea.value = content;
        if (wInput) wInput.value = 'u';
        if (idInput) idInput.value = threadId;
        if (subjectInput) subjectInput.value = subjectValue;
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (fileInput) fileInput.value = '';
        form.querySelectorAll('input[name^="bf_file_del["]').forEach(function(el) { el.remove(); });
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (preview) {
            preview.innerHTML = '';
            form.querySelectorAll('.log20_tarae_reply_url_hidden').forEach(function(el) { el.remove(); });
            try {
                var files = JSON.parse(filesJson);
                var orderValues = JSON.parse(orderJson);
                var fileMap = {};
                if (Array.isArray(files)) {
                    files.forEach(function(file) {
                        if (!file || typeof file.no === 'undefined' || !file.url) return;
                        fileMap[String(file.no)] = file.url;
                    });
                }
                if (!Array.isArray(orderValues) || orderValues.length === 0) {
                    orderValues = Object.keys(fileMap).map(function(key) { return 'file:' + key; });
                }
                var usedFileNos = {};
                var urlToFileNo = {};
                Object.keys(fileMap).forEach(function(fileNo) {
                    var fileUrl = fileMap[fileNo];
                    if (fileUrl) {
                        urlToFileNo[fileUrl] = fileNo;
                    }
                });
                orderValues.forEach(function(val) {
                    if (!val) return;
                    var item = document.createElement('div');
                    item.className = 'log20_tarae_reply_preview_item';
                    item.setAttribute('draggable', 'true');
                    item.setAttribute('data-existing', '1');
                    if (typeof val === 'string' && val.indexOf('file:') === 0) {
                        var fileNo = val.substring(5);
                        var url = fileMap[fileNo];
                        if (!url) return;
                        usedFileNos[fileNo] = true;
                        item.setAttribute('data-index', String(fileNo));
                        item.setAttribute('data-url', url);
                        item.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-no="' + fileNo + '" data-url="' + url + '" aria-label="첨부 취소">×</button>';
                    } else {
                        var urlVal = String(val);
                        if (urlToFileNo[urlVal]) {
                            var mappedNo = urlToFileNo[urlVal];
                            usedFileNos[mappedNo] = true;
                            item.setAttribute('data-index', String(mappedNo));
                            item.setAttribute('data-url', urlVal);
                            item.innerHTML = '<img src="' + urlVal + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-no="' + mappedNo + '" data-url="' + urlVal + '" aria-label="첨부 취소">×</button>';
                        } else {
                            item.setAttribute('data-url', urlVal);
                            item.innerHTML = '<img src="' + urlVal + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-url="' + urlVal + '" aria-label="첨부 취소">×</button>';
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'tarae_reply_url[]';
                            hidden.value = urlVal;
                            hidden.className = 'log20_tarae_reply_url_hidden';
                            form.appendChild(hidden);
                        }
                    }
                    preview.appendChild(item);
                });
                Object.keys(fileMap).forEach(function(fileNo) {
                    if (usedFileNos[fileNo]) return;
                    var url = fileMap[fileNo];
                    if (!url) return;
                    var item = document.createElement('div');
                    item.className = 'log20_tarae_reply_preview_item';
                    item.setAttribute('draggable', 'true');
                    item.setAttribute('data-existing', '1');
                    item.setAttribute('data-index', String(fileNo));
                    item.setAttribute('data-url', url);
                    item.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-no="' + fileNo + '" data-url="' + url + '" aria-label="첨부 취소">×</button>';
                    preview.appendChild(item);
                });
            } catch (err) {
                preview.innerHTML = '';
            }
        }
        if (window.taraeReplyMedia && window.taraeReplyMedia.updateReplyFileCount) {
            window.taraeReplyMedia.updateReplyFileCount(form);
        }
        var wrapper = document.getElementById('tarae-reply-' + parentId);
        if (wrapper) {
            // 수정창을 현재 댓글 바로 아래로 이동
            var threadItem = editBtn.closest('.log20_tarae_thread_item');
            if (threadItem && threadItem.parentNode) {
                if (!wrapper.hasAttribute('data-original-parent-id')) {
                    var originalParentId = wrapper.parentElement ? wrapper.parentElement.id : '';
                    wrapper.setAttribute('data-original-parent-id', originalParentId);
                }
                threadItem.parentNode.insertBefore(wrapper, threadItem.nextSibling);
            }
            wrapper.style.display = 'block';
        }
        var cancelBtn = form.querySelector('.log20_tarae_reply_cancel');
        if (cancelBtn) cancelBtn.style.display = '';
        adjustSublistHeightTarae();
    });

    document.addEventListener('click', function(e) {
        var cancelBtn = e.target.closest('.log20_tarae_reply_cancel');
        if (!cancelBtn) return;
        var form = cancelBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var textarea = form.querySelector('.log20_tarae_reply_textarea');
        var wInput = form.querySelector('input[name="w"]');
        var idInput = form.querySelector('input[name="wr_id"]');
        var subjectInput = form.querySelector('input[name="wr_subject"]');
        if (textarea) textarea.value = '';
        if (wInput) wInput.value = '';
        if (idInput) idInput.value = '';
        if (subjectInput) subjectInput.value = '';
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (fileInput) fileInput.value = '';
        form.querySelectorAll('input[name^="bf_file_del["]').forEach(function(el) { el.remove(); });
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (preview) preview.innerHTML = '';
        if (window.taraeReplyMedia && window.taraeReplyMedia.updateReplyFileCount) {
            window.taraeReplyMedia.updateReplyFileCount(form);
        }
        cancelBtn.style.display = 'none';
        var wrapper = form.closest('.log20_tarae_reply');
        if (wrapper) {
            var originalParentId = wrapper.getAttribute('data-original-parent-id');
            if (originalParentId) {
                var originalParent = document.getElementById(originalParentId);
                if (originalParent) {
                    originalParent.appendChild(wrapper);
                }
            }
            wrapper.style.display = 'none';
        }
    });

    document.addEventListener('submit', function(e) {
        var form = e.target.closest('.log20_tarae_reply_form');
        if (!form) return;
        var content = form.querySelector('.log20_tarae_reply_textarea');
        if (window.taraeReplyMedia && window.taraeReplyMedia.syncReplyOrderToFields) {
            window.taraeReplyMedia.syncReplyOrderToFields(form);
        }
        if (content && !content.value.trim()) {
            var urlHidden = form.querySelectorAll('.log20_tarae_reply_url_hidden');
            var fileInput = form.querySelector('.log20_tarae_reply_file');
            var fileCount = fileInput && fileInput.files ? fileInput.files.length : 0;
            if (urlHidden.length === 0 && fileCount === 0) {
                e.preventDefault();
                alert('본문을 입력해주세요.');
                content.focus();
                return;
            }
        }
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (fileInput) {
            var maxFiles = parseInt(fileInput.getAttribute('data-max'), 10);
            if (isNaN(maxFiles) || maxFiles < 0) maxFiles = 0;
            var existingCount = form.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length;
            var newCount = fileInput.files ? fileInput.files.length : 0;
            if (existingCount + newCount > maxFiles) {
                e.preventDefault();
                alert('첨부파일은 최대 ' + maxFiles + '개까지 업로드 가능합니다.');
                return;
            }
        }
        e.preventDefault();
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        var uploadingEl = form.querySelector('.log20_tarae_reply_uploading');
        if (uploadingEl) uploadingEl.classList.add('is-visible');

        var formData = new FormData(form);
        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.success) {
                var parentId = form.querySelector('input[name="wr_parent"]').value;
                var baseUrl = window.location.href.split('#')[0];
                var joiner = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                sessionStorage.setItem('tarae_scroll_latest_parent', String(parentId || ''));
                window.location.href = baseUrl + joiner + 'tarae_open=' + encodeURIComponent(parentId) + '#tarae-reply-' + encodeURIComponent(parentId);
            } else {
                alert('저장에 실패했습니다.');
            }
        })
        .catch(function() {
            alert('저장 중 오류가 발생했습니다.');
        })
        .finally(function() {
            if (submitBtn) submitBtn.disabled = false;
            if (uploadingEl) uploadingEl.classList.remove('is-visible');
        });
    });

    document.addEventListener('keydown', function(e) {
        var target = e.target;
        if (!target || !target.classList.contains('log20_tarae_reply_textarea')) return;
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            var form = target.closest('.log20_tarae_reply_form');
            if (form) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        }
    });

    // 초기 로드 시에도 한 번 높이 맞추기
    if (typeof jQuery !== 'undefined') {
        jQuery(function() {
            adjustSublistHeightTarae();
            updateTaraeImageHeights();
        });
    } else {
        adjustSublistHeightTarae();
        updateTaraeImageHeights();
    }

    window.addEventListener('load', function() {
        updateTaraeImageHeights();
    });

    var openThreadId = "<?php echo isset($_GET['tarae_open']) ? (int)$_GET['tarae_open'] : 0; ?>";
    var latestParentId = sessionStorage.getItem('tarae_scroll_latest_parent');
    var skipOpenScroll = !!latestParentId;

    function waitForImages(root, callback) {
        if (!root) {
            callback();
            return;
        }
        var images = root.querySelectorAll('img');
        var remaining = 0;
        images.forEach(function(img) {
            if (!img.complete) remaining++;
        });
        if (remaining === 0) {
            callback();
            return;
        }
        var done = false;
        var timer = setTimeout(function() {
            if (done) return;
            done = true;
            callback();
        }, 1500);
        images.forEach(function(img) {
            if (img.complete) return;
            var onDone = function() {
                if (done) return;
                remaining--;
                if (remaining <= 0) {
                    done = true;
                    clearTimeout(timer);
                    callback();
                }
            };
            img.addEventListener('load', onDone, { once: true });
            img.addEventListener('error', onDone, { once: true });
        });
    }

    if (openThreadId) {
        var threadEl = document.getElementById('tarae-thread-' + openThreadId);
        if (threadEl) {
            threadEl.style.display = 'block';
            if (!skipOpenScroll) {
                threadEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        var toggleBtns = document.querySelectorAll('[data-toggle="tarae-thread-' + openThreadId + '"]');
        if (toggleBtns && toggleBtns.length) {
            toggleBtns.forEach(function(btn) {
                btn.textContent = '타래 접기';
            });
        }
        adjustSublistHeightTarae();
    }

    if (latestParentId) {
        sessionStorage.removeItem('tarae_scroll_latest_parent');
        var latestIdNum = parseInt(latestParentId, 10);
        if (!isNaN(latestIdNum) && latestIdNum > 0) {
            if (!openThreadId) {
                openThreadId = String(latestIdNum);
            }
            var latestThreadEl = document.getElementById('tarae-thread-' + latestIdNum);
            if (latestThreadEl) {
                latestThreadEl.style.display = 'block';
                var latestItems = latestThreadEl.querySelectorAll('.log20_tarae_thread_item');
                var latestTarget = latestItems.length ? latestItems[latestItems.length - 1] : latestThreadEl;
                var scrollToLatest = function() {
                    latestTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                waitForImages(latestTarget, function() {
                    updateTaraeImageHeights();
                    if (window.requestAnimationFrame) {
                        requestAnimationFrame(scrollToLatest);
                    } else {
                        setTimeout(scrollToLatest, 0);
                    }
                });
            }
        }
    } else {
        var restoreScroll = sessionStorage.getItem('tarae_scroll_restore');
        if (restoreScroll) {
            sessionStorage.removeItem('tarae_scroll_restore');
            var y = parseInt(restoreScroll, 10);
            if (!isNaN(y)) {
                window.scrollTo(0, y);
            }
        }
    }

    // 검색 기능은 tarae.search.js로 이동
})();
</script>

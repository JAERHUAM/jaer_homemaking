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

global $member, $is_admin, $board_skin_url, $board, $g5;

// 게시판 정보 가져오기 (없으면)
if (!isset($board) || !$board) {
    $current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
    $board = get_board_db($current_bo_table, true);
}

$current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
$write_table = $g5['write_prefix'] . $current_bo_table;

$parent_wr_id = isset($parent_wr_id) ? (int)$parent_wr_id : (isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : (isset($view['wr_id']) ? (int)$view['wr_id'] : (isset($wr_id) ? (int)$wr_id : 0)));

// 모든 시나리오 게시물 조회
$all_scena_list = array();
if ($parent_wr_id > 0) {
    $wr19_column = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_19'");
    if (!$wr19_column) {
        sql_query("ALTER TABLE {$write_table} ADD COLUMN `wr_19` VARCHAR(255) NOT NULL DEFAULT ''", false);
    }

    $wr20_column = sql_fetch("SHOW COLUMNS FROM {$write_table} LIKE 'wr_20'");
    if (!$wr20_column) {
        sql_query("ALTER TABLE {$write_table} ADD COLUMN `wr_20` VARCHAR(255) NOT NULL DEFAULT ''", false);
    }

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

    $sql = "SELECT wr_id, wr_subject, wr_name, wr_datetime, wr_hit, wr_comment, wr_1, wr_2, wr_3, wr_4, wr_5, wr_6, wr_7, wr_8, wr_9, wr_10, wr_11, wr_12, wr_13, wr_14, wr_15, wr_16, wr_17, wr_19, wr_20, wr_content, mb_id
            FROM {$write_table} 
            WHERE wr_parent = '{$parent_wr_id}' 
            AND wr_is_comment = 0 
            AND wr_id != wr_parent
            AND wr_4 = 'scena'
            ORDER BY wr_datetime DESC";
    
    $result = sql_query($sql, false);
    
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $all_scena_list[] = array(
                'wr_id' => $row['wr_id'],
                'wr_subject' => $row['wr_subject'],
                'wr_name' => $row['wr_name'],
                'wr_datetime' => $row['wr_datetime'],
                'wr_hit' => $row['wr_hit'],
                'wr_comment' => $row['wr_comment'],
                'mb_id' => isset($row['mb_id']) ? $row['mb_id'] : '',
                'wr_1' => isset($row['wr_1']) ? $row['wr_1'] : '',
                'wr_2' => isset($row['wr_2']) ? $row['wr_2'] : '',
                'wr_3' => isset($row['wr_3']) ? $row['wr_3'] : '',
                'wr_4' => isset($row['wr_4']) ? $row['wr_4'] : '',
                'wr_5' => isset($row['wr_5']) ? $row['wr_5'] : '',
                'wr_6' => isset($row['wr_6']) ? $row['wr_6'] : '',
                'wr_7' => isset($row['wr_7']) ? $row['wr_7'] : '',
                'wr_8' => isset($row['wr_8']) ? $row['wr_8'] : '',
                'wr_9' => isset($row['wr_9']) ? $row['wr_9'] : '',
                'wr_10' => isset($row['wr_10']) ? $row['wr_10'] : '',
                'wr_11' => isset($row['wr_11']) ? $row['wr_11'] : '',
                'wr_12' => isset($row['wr_12']) ? $row['wr_12'] : '',
                'wr_13' => isset($row['wr_13']) ? $row['wr_13'] : '',
                'wr_14' => isset($row['wr_14']) ? $row['wr_14'] : '',
                'wr_15' => isset($row['wr_15']) ? $row['wr_15'] : '',
                'wr_16' => isset($row['wr_16']) ? $row['wr_16'] : '',
                'wr_17' => isset($row['wr_17']) ? $row['wr_17'] : '',
                'wr_19' => isset($row['wr_19']) ? $row['wr_19'] : '',
                'wr_20' => isset($row['wr_20']) ? $row['wr_20'] : '',
                'wr_content' => isset($row['wr_content']) ? $row['wr_content'] : '',
                'pc_images' => array()
            );
        }
    }
}

// PC 이미지 정보 로드
$pc_image_table = $write_table . '_pc_images';
$pc_images_map = array();
$pc_table_exists = sql_fetch("SHOW TABLES LIKE '{$pc_image_table}'");
if ($pc_table_exists && !empty($all_scena_list)) {
    $wr_ids = array_column($all_scena_list, 'wr_id');
    $wr_ids = array_map('intval', $wr_ids);
    $wr_ids = array_filter($wr_ids);
    if (!empty($wr_ids)) {
        $id_list = implode(',', $wr_ids);
        $pc_result = sql_query("SELECT wr_id, pc_index, image_url FROM `{$pc_image_table}` WHERE wr_id IN ({$id_list}) ORDER BY wr_id, pc_index ASC", false);
        if ($pc_result) {
            while ($pc_row = sql_fetch_array($pc_result)) {
                $pc_wr_id = (int)$pc_row['wr_id'];
                if (!isset($pc_images_map[$pc_wr_id])) {
                    $pc_images_map[$pc_wr_id] = array();
                }
                $pc_images_map[$pc_wr_id][] = $pc_row['image_url'];
            }
        }
    }
}

foreach ($all_scena_list as &$scene_row) {
    $scene_id = isset($scene_row['wr_id']) ? (int)$scene_row['wr_id'] : 0;
    $scene_row['pc_images'] = isset($pc_images_map[$scene_id]) ? $pc_images_map[$scene_id] : array();
}
unset($scene_row);

// 가장 최근 게시물 (첫 번째)
$latest_scena = !empty($all_scena_list) ? $all_scena_list[0] : null;

// 부모 게시물 색상/제목 정보
// 우선순위: 1) 전달된 $wr_1, $wr_2, 2) 부모 게시물에서 직접 조회
$parent_wr_1 = isset($wr_1) ? $wr_1 : '';
$parent_wr_2 = isset($wr_2) ? $wr_2 : '';
$parent_subject = '';
$parent_mb_id = '';

// 부모 게시물 정보 조회 (제목 포함)
if ($parent_wr_id > 0) {
    $parent_write = sql_fetch("SELECT wr_subject, wr_1, wr_2, mb_id FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
    if ($parent_write) {
        if (empty($parent_wr_1)) {
            $parent_wr_1 = isset($parent_write['wr_1']) ? $parent_write['wr_1'] : '';
        }
        if (empty($parent_wr_2)) {
            $parent_wr_2 = isset($parent_write['wr_2']) ? $parent_write['wr_2'] : '';
        }
        $parent_subject = isset($parent_write['wr_subject']) ? $parent_write['wr_subject'] : '';
        $parent_mb_id = isset($parent_write['mb_id']) ? $parent_write['mb_id'] : '';
    }
}

// 최종 색상 설정
$action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
$accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';

// 썸네일 이미지 가져오기 함수
function get_scena_thumbnail($item, $bo_table) {
    include_once(G5_LIB_PATH.'/thumbnail.lib.php');
    $thumb = get_list_thumbnail($bo_table, $item['wr_id'], 500, 500, false, true);
    $img_url = '';
    $img_type = '';
    $fa_icon = '';
    
    if ($thumb['src']) {
        $img_url = $thumb['src'];
        $img_type = 'file';
    } elseif ($item['wr_7'] && trim($item['wr_7']) !== '') {
        $wr_7_trimmed = trim($item['wr_7']);
        if (filter_var($wr_7_trimmed, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_7_trimmed) || strpos($wr_7_trimmed, G5_DATA_URL) === 0) {
            $img_url = $wr_7_trimmed;
            $img_type = 'url';
        } elseif ($item['wr_8']) {
            $img_type = 'fa';
            $fa_icon = $item['wr_8'];
        } else {
            $img_url = G5_IMG_URL.'/no_image.png';
            $img_type = 'file';
        }
    } elseif ($item['wr_8']) {
        $img_type = 'fa';
        $fa_icon = $item['wr_8'];
    } else {
        $img_url = G5_IMG_URL.'/no_image.png';
        $img_type = 'file';
    }
    
    return array('url' => $img_url, 'type' => $img_type, 'fa_icon' => $fa_icon);
}
?>

<?php
$back_view_url = G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table;
$list_scena_url = $parent_wr_id > 0
    ? (G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table . '&wr_id=' . (int)$parent_wr_id . '&sublist=scena')
    : (G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table . '&sublist=scena');
// 공유 버튼용: view.scena.skin.php로 이동하는 URL (최신 시나리오가 있으면 해당 시나리오, 없으면 목록)
$share_scena_url = ($latest_scena && isset($latest_scena['wr_id']))
    ? (G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table . '&wr_id=' . (int)$latest_scena['wr_id'] . '&write_type=scena')
    : $list_scena_url;

// 부모 게시물 색상 기준 버튼
$btn_bg_color   = !empty($parent_wr_2) ? $parent_wr_2 : '#333'; // 배경색
$btn_icon_color = !empty($parent_wr_1) ? $parent_wr_1 : '#fff'; // 제목색
?>

<div class="log20_scenalist_back_btn_wrap">
    <a href="<?php echo htmlspecialchars($back_view_url, ENT_QUOTES); ?>"
       class="btn_b01 btn"
       title="목록"
       style="background-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
              border-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
              color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
        <i class="fa fa-list" aria-hidden="true"
           style="color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
        <span class="sound_only">목록</span>
    </a>
    <?php
    $current_member_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    $can_edit_parent = false;
    if ($parent_wr_id > 0) {
        if ($is_admin) {
            $can_edit_parent = true;
        } elseif ($current_member_id && $parent_mb_id && $current_member_id === $parent_mb_id) {
            $can_edit_parent = true;
        }
    }
    if ($can_edit_parent) {
        $parent_edit_url = G5_BBS_URL . '/write.php?w=u&bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$parent_wr_id;
        $parent_delete_url = G5_BBS_URL . '/delete.php?bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$parent_wr_id;
    ?>
    <div class="log20_scenalist_action_btns" style="float:right; display:flex; gap:6px;">
        <span class="log20_scena_share_wrap">
            <button type="button"
                    class="btn_b01 btn log20_scena_share_btn"
                    title="공유"
                    data-share-url="<?php echo htmlspecialchars($share_scena_url, ENT_QUOTES); ?>"
                    style="background-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                           border-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                           color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                <i class="fa-solid fa-share-nodes" aria-hidden="true"
                   style="color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                <span class="sound_only">공유</span>
            </button>
            <span class="log20_scena_share_toast log20_scena_share_toast--bottom" aria-hidden="true">복사되었습니다</span>
        </span>
        <a href="<?php echo htmlspecialchars($parent_edit_url, ENT_QUOTES); ?>"
           class="btn_b01 btn"
           title="부모글 수정"
           style="background-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                  border-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                  color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
            <i class="fa-solid fa-pencil" aria-hidden="true"
               style="color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
            <span class="sound_only">수정</span>
        </a>
        <a href="<?php echo htmlspecialchars($parent_delete_url, ENT_QUOTES); ?>"
           class="btn_b01 btn"
           title="부모글 삭제"
           onclick="return confirm('부모글을 삭제하시겠습니까?');"
           style="background-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                  border-color:<?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                  color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
            <i class="fa-solid fa-eraser" aria-hidden="true"
               style="color:<?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
            <span class="sound_only">삭제</span>
        </a>
    </div>
    <?php } ?>
</div>

<div class="log20_sublist_scena_area" id="log20_sublist_scena" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;">
    <?php
    $write_url = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&w=&write_type=scena&wr_parent=' . $parent_wr_id;
    
    // 게시판 쓰기 권한 체크
    $member_level = isset($member['mb_level']) ? (int)$member['mb_level'] : 0;
    $write_level = isset($board['bo_write_level']) ? (int)$board['bo_write_level'] : 1;
    ?>
<?php
$log20_scena_embed = isset($log20_scena_embed) ? (bool)$log20_scena_embed : false;
$scena_title_style = '';
if ($action_color) {
    $scena_title_style .= 'background-color: ' . htmlspecialchars($action_color) . '; ';
    $scena_title_style .= 'border-color: ' . htmlspecialchars($action_color) . '; ';
}
$scena_sublist_style = 'background-color: #ffffff; ';
if ($action_color) {
    $scena_sublist_style .= 'border: 1px solid ' . htmlspecialchars($action_color) . '; ';
    $scena_sublist_style .= 'border-top: none; ';
}
?>
<?php if (!$log20_scena_embed) { ?>
<div class="log_content_sublist_scena_title"<?php if ($scena_title_style) { ?> style="<?php echo trim($scena_title_style); ?>"<?php } ?>>
    <span class="log_content_sublist_scena_title_text" style="color: <?php echo $accent_color ? htmlspecialchars($accent_color) : '#fff'; ?>;"><?php echo $parent_subject ? get_text($parent_subject) : '시나리오 목록'; ?></span>
</div>
<div class="log20_content_sublist_scena" id="log20_content_sublist"<?php if ($scena_sublist_style) { ?> style="<?php echo trim($scena_sublist_style); ?>"<?php } ?>>
<?php } ?>

    <div class="log20_sublist_header"<?php if ($action_color) { ?> style="border-bottom-color: <?php echo htmlspecialchars($action_color); ?>;"<?php } ?>>
        <a href="<?php echo $write_url; ?>" class="log20_sublist_write_btn" style="border-radius: 4px; --action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;" onclick="return checkWritePermission(event, <?php echo $write_level; ?>, <?php echo $is_admin ? 'true' : 'false'; ?>, <?php echo $member_level; ?>);">
            <span class="log20_sublist_write_btn_text">시나리오 등록하기</span>
        </a>
    </div>

<div class="log20_content_sublist_scena_wrapper">
    <div class="log20_content_sublist_newscena" id="log20_content_sublist_newscena">
        <?php if ($latest_scena) { 
            $thumb_data = get_scena_thumbnail($latest_scena, $current_bo_table);
            $wr_1 = isset($latest_scena['wr_1']) ? $latest_scena['wr_1'] : '';
            $wr_2 = isset($latest_scena['wr_2']) ? $latest_scena['wr_2'] : '';
            $wr_3 = isset($latest_scena['wr_3']) ? $latest_scena['wr_3'] : '';
            $wr_4 = isset($latest_scena['wr_4']) ? $latest_scena['wr_4'] : '';
            $wr_5 = $latest_scena['wr_5'];
            $wr_6 = $latest_scena['wr_6'];
            $wr_7 = $latest_scena['wr_7'];
            $wr_8 = $latest_scena['wr_8'];
            $wr_9 = $latest_scena['wr_9'];
            $wr_10 = $latest_scena['wr_10'];
            $wr_19 = isset($latest_scena['wr_19']) ? $latest_scena['wr_19'] : '';
            $latest_view_url = G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table . '&wr_id=' . $latest_scena['wr_id'] . '&write_type=scena';
        ?>
        <!-- 좌상단: 썸네일 이미지 -->
        <div class="log20_scena_newscena_thumbnail" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;" data-view-url="<?php echo htmlspecialchars($latest_view_url); ?>">
            <?php if ($thumb_data['type'] == 'fa' && $thumb_data['fa_icon']) { ?>
                <i class="fa-solid fa-<?php echo htmlspecialchars($thumb_data['fa_icon']) ?>" style="color: <?php echo $wr_9 ? htmlspecialchars($wr_9) : '#000000'; ?>;"></i>
            <?php } else { ?>
                <img src="<?php echo htmlspecialchars($thumb_data['url']) ?>" alt="<?php echo get_text($latest_scena['wr_subject']) ?>">
            <?php } ?>
        </div>
        
        <!-- 우상단: PC 포트레잇-->
        <?php
        $latest_pc_images = isset($latest_scena['pc_images']) ? $latest_scena['pc_images'] : array();
        $latest_pc_count = is_array($latest_pc_images) ? count($latest_pc_images) : 0;
        ?>
        <div class="log20_content_sublist_newscena_pcport" id="log20_content_sublist_newscena_pcport_<?php echo $latest_scena['wr_id']; ?>">
            <div class="log20_pc_port_grid" data-wr-id="<?php echo $latest_scena['wr_id']; ?>" data-count="<?php echo $latest_pc_count; ?>">
                <?php if ($latest_pc_count > 0) { ?>
                    <?php foreach ($latest_pc_images as $pc_index => $pc_image_url) { ?>
                        <?php if (!$pc_image_url) continue; ?>
                        <?php
                        $pc_name_field = 'wr_' . (12 + $pc_index);
                        $pc_name = isset($latest_scena[$pc_name_field]) ? get_text($latest_scena[$pc_name_field]) : '';
                        ?>
                        <div class="log20_pc_port_item">
                            <div class="log20_pc_port_item_inner">
                                <img src="<?php echo htmlspecialchars($pc_image_url); ?>" alt="PC 이미지 <?php echo $pc_index + 1; ?>">
                                <?php if ($pc_name) { ?>
                                <div class="log20_pc_port_item_overlay">
                                    <div class="log20_pc_port_item_name"><?php echo $pc_name; ?></div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="log20_pc_port_empty">등록된 PC 이미지가 없습니다.</div>
                <?php } ?>
            </div>
        </div>
        
        <div class="log20_scena_newscena_info">
            <?php
            $latest_update_base = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&w=u&write_type=scena&wr_id=';
            $latest_delete_base = $board_skin_url . '/delete.sublist.skin.php?bo_table=' . $current_bo_table . '&write_type=scena&wr_id=';
            
            // 권한 확인 (관리자 또는 작성자)
            $latest_item_mb_id = isset($latest_scena['mb_id']) ? $latest_scena['mb_id'] : '';
            $current_member_id = isset($member['mb_id']) ? $member['mb_id'] : '';
            $can_manage_latest = false;
            if ($is_admin) {
                $can_manage_latest = true;
            } elseif ($current_member_id && $latest_item_mb_id && $current_member_id === $latest_item_mb_id) {
                $can_manage_latest = true;
            }
            ?>
            <div class="log20_scena_rule_row">
                <div class="log20_scena_action_cell" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;" data-update-base="<?php echo htmlspecialchars($latest_update_base); ?>" data-delete-base="<?php echo htmlspecialchars($latest_delete_base); ?>">
                    <div class="log20_scena_action_row">
                        <button type="button" class="log20_subitem_btn log20_subitem_btn--icon log20_subitem_btn--share" title="공유" data-wr-id="<?php echo (int)$latest_scena['wr_id']; ?>">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                        <?php if ($can_manage_latest) { ?>
                        <a href="<?php echo $latest_update_base . $latest_scena['wr_id']; ?>" class="log20_subitem_btn log20_subitem_btn--icon log20_subitem_btn--edit" title="수정">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <a href="<?php echo $latest_delete_base . $latest_scena['wr_id']; ?>" class="log20_subitem_btn log20_subitem_btn--icon log20_subitem_btn--delete" onclick="return confirm('삭제하시겠습니까?');" title="삭제">
                            <i class="fa-solid fa-eraser"></i>
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="log20_scena_item_title_row">
                <div class="log20_scena_item_title" data-view-url="<?php echo htmlspecialchars($latest_view_url); ?>"><?php echo get_text($latest_scena['wr_subject']) ?></div>
                <div class="log20_scena_item_writer"<?php if (!$latest_scena['wr_20']) { ?> style="display:none;"<?php } ?>>
                    <strong>w.</strong> <?php echo $latest_scena['wr_20'] ? get_text($latest_scena['wr_20']) : ''; ?></div>
            </div>
            <?php if ($wr_9) { ?>
                <div class="log20_scena_item_trailer"><?php echo get_text($wr_9) ?></div>
            <?php } ?>
            <div class="log20_scena_item_meta">
                <span class="log20_scena_item_rule"<?php if (!$wr_19) { ?> style="display:none;"<?php } ?>>
                    <?php if ($wr_19) { ?>룰: <?php echo get_text($wr_19); ?><?php } ?>
                </span>
                <span class="log20_scena_item_gm"<?php if (!$wr_6) { ?> style="display:none;"<?php } ?>>
                    <?php if ($wr_6) { ?>GM: <?php echo get_text($wr_6); ?><?php } ?>
                </span>
                <?php
                $pc_names = array();
                $pc_count = isset($latest_scena['wr_11']) ? (int)$latest_scena['wr_11'] : 0;
                if ($pc_count < 0) $pc_count = 0;
                if ($pc_count > 6) $pc_count = 6;
                for ($i = 1; $i <= 6; $i++) {
                    $pc_field = 'wr_' . (11 + $i);
                    if (!empty($latest_scena[$pc_field])) {
                        $pc_names[] = get_text($latest_scena[$pc_field]);
                    }
                    if ($pc_count > 0 && count($pc_names) >= $pc_count) {
                        break;
                    }
                }
                $pc_line = implode(', ', $pc_names);
                ?>
                <span class="log20_scena_item_pc"<?php if (!$pc_line) { ?> style="display:none;"<?php } ?>>
                    <?php if ($pc_line) { ?>PC: <?php echo $pc_line; ?><?php } ?>
                </span>
            </div>
            <?php
            $start_text = '';
            if ($latest_scena['wr_datetime']) {
                $start_text = date('Y.m.d', strtotime($latest_scena['wr_datetime']));
            }
            $end_text = '';
            if ($wr_5) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $wr_5)) {
                    $end_text = date('Y.m.d', strtotime($wr_5));
                } elseif (strtotime($wr_5) !== false) {
                    $end_text = date('Y.m.d', strtotime($wr_5));
                } else {
                    $end_text = get_text($wr_5);
                }
            }
            if ($start_text || $end_text) {
            ?>
            <div class="log20_scena_list_item_dates">
                <span class="log20_scena_list_item_date_label">날짜:</span>
                <?php if ($start_text) { ?>
                <span class="log20_scena_list_item_startdate"><?php echo $start_text; ?></span>
                <?php } ?>
                <?php if ($start_text && $end_text) { ?>
                <span class="log20_scena_list_item_range_sep">~</span>
                <?php } ?>
                <?php if ($end_text) { ?>
                <span class="log20_scena_item_enddate"><?php echo $end_text; ?></span>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
        <?php if (!empty($wr_3) && trim($wr_3) !== '') { ?>
        <div class="log20_scena_newscena_subt">
            <div class="log20_item_subtitle">
                <?php echo nl2br(get_text($wr_3)); ?>
            </div>
        </div>
        <?php } ?>
        <?php } else { ?>
        <div class="log20_empty">
            <p>등록된 시나리오가 없습니다.</p>
        </div>
        <?php } ?>
    </div>

    </div>
</div>

<?php if (!$log20_scena_embed) { ?>
</div>

<?php if (!empty($all_scena_list)) { ?>
<div class="log20_content_scenalist" id="log20_content_scenalist" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;">
    <div class="log20_scenalist_thumbnails_wrapper">
        <button class="log20_scenalist_arrow log20_scenalist_arrow_left" id="scena_thumb_prev" style="display: <?php echo count($all_scena_list) > 4 ? 'flex' : 'none'; ?>;">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="log20_scenalist_thumbnails" id="scena_thumbnails">
            <?php
            $thumb_index = 0;
            foreach ($all_scena_list as $scene_item) {
                $thumb_item = get_scena_thumbnail($scene_item, $current_bo_table);
                $is_visible = ($thumb_index < 4);
            ?>
            <div class="log20_scenalist_thumb_item" data-wr-id="<?php echo $scene_item['wr_id']; ?>" data-index="<?php echo $thumb_index; ?>" style="display: <?php echo $is_visible ? 'flex' : 'none'; ?>;">
                <div class="log20_scenalist_thumb_item_image">
                    <?php if ($thumb_item['type'] == 'fa' && $thumb_item['fa_icon']) { ?>
                        <i class="fa-solid fa-<?php echo htmlspecialchars($thumb_item['fa_icon']); ?>"></i>
                    <?php } else { ?>
                        <img src="<?php echo htmlspecialchars($thumb_item['url']); ?>" alt="<?php echo get_text($scene_item['wr_subject']); ?>">
                    <?php } ?>
                </div>
                <div class="log20_scenalist_thumb_item_name"><?php echo get_text($scene_item['wr_subject']); ?></div>
            </div>
            <?php 
                $thumb_index++;
            } ?>
        </div>
        <button class="log20_scenalist_arrow log20_scenalist_arrow_right" id="scena_thumb_next" style="display: <?php echo count($all_scena_list) > 4 ? 'flex' : 'none'; ?>;">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
</div>
<?php } ?>
<?php } ?>

<div class="log20_pc_image_modal" id="log20_pc_image_modal">
    <div class="log20_pc_image_modal_inner">
        <img src="" alt="PC 이미지 확대">
    </div>
</div>

<?php
$GLOBALS['log20_scenalist_data'] = array(
    'items' => $all_scena_list,
    'parent_color' => $parent_wr_1,
    'current_bo_table' => $current_bo_table
);
?>

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

var shareBaseUrl = '<?php echo G5_BBS_URL; ?>/share_popup.php?bo_table=<?php echo $current_bo_table; ?>&wr_id=';
function openSharePopup(wrId) {
    if (!wrId) return;
    window.open(shareBaseUrl + wrId, 'share_popup', 'width=600,height=500,scrollbars=yes');
}

function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text);
    }
    return new Promise(function(resolve, reject) {
        try {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (ok) {
                resolve();
            } else {
                reject(new Error('copy failed'));
            }
        } catch (err) {
            reject(err);
        }
    });
}

function showScenaShareToast(btn) {
    var wrap = btn.closest('.log20_scena_share_wrap');
    if (!wrap) return;
    var toast = wrap.querySelector('.log20_scena_share_toast');
    if (!toast) return;
    if (toast._hideTimer) {
        clearTimeout(toast._hideTimer);
    }
    toast.classList.add('is-visible');
    toast._hideTimer = setTimeout(function() {
        toast.classList.remove('is-visible');
    }, 800);
}

$(function() {
    var scenaListPagination = {
        maxVisible: 4,
        currentPage: 0,
        totalThumbs: 0,
        totalPages: 1,
        $thumbItems: null,
        $container: null,
        $prevBtn: null,
        $nextBtn: null
    };
    
    scenaListPagination.$container = $('#log20_content_scenalist');
    if (!scenaListPagination.$container.length) return;
    
    scenaListPagination.$thumbItems = scenaListPagination.$container.find('.log20_scenalist_thumb_item');
    scenaListPagination.$prevBtn = scenaListPagination.$container.find('#scena_thumb_prev');
    scenaListPagination.$nextBtn = scenaListPagination.$container.find('#scena_thumb_next');
    
    scenaListPagination.totalThumbs = scenaListPagination.$thumbItems.length;
    scenaListPagination.totalPages = Math.max(1, Math.ceil(scenaListPagination.totalThumbs / scenaListPagination.maxVisible));
    
    function updatePaginationMeta() {
        scenaListPagination.totalThumbs = scenaListPagination.$thumbItems.length;
        scenaListPagination.totalPages = Math.max(1, Math.ceil(scenaListPagination.totalThumbs / scenaListPagination.maxVisible));
        if (scenaListPagination.currentPage >= scenaListPagination.totalPages) {
            scenaListPagination.currentPage = scenaListPagination.totalPages - 1;
        }
    }
    
    // 화살표 버튼 
    function updateArrows() {

        scenaListPagination.$prevBtn.show();
        scenaListPagination.$nextBtn.show();

        if (scenaListPagination.currentPage <= 0) {
            scenaListPagination.$prevBtn.css('opacity', '0.3').css('cursor', 'not-allowed').prop('disabled', true);
        } else {
            scenaListPagination.$prevBtn.css('opacity', '1').css('cursor', 'pointer').prop('disabled', false);
        }
        
        if (scenaListPagination.currentPage >= scenaListPagination.totalPages - 1) {
            scenaListPagination.$nextBtn.css('opacity', '0.3').css('cursor', 'not-allowed').prop('disabled', true);
        } else {
            scenaListPagination.$nextBtn.css('opacity', '1').css('cursor', 'pointer').prop('disabled', false);
        }
        
        if (scenaListPagination.totalThumbs <= scenaListPagination.maxVisible) {
            scenaListPagination.$prevBtn.hide();
            scenaListPagination.$nextBtn.hide();
        }
    }
    
    // 현재 페이지 항목 표시
    function showCurrentPage() {
        var startIndex = scenaListPagination.currentPage * scenaListPagination.maxVisible;
        var endIndex = startIndex + scenaListPagination.maxVisible;
        
        scenaListPagination.$thumbItems.each(function(idx) {
            var $item = $(this);
            var isVisible = idx >= startIndex && idx < endIndex;

            if (isVisible) {
                $item.css('display', 'flex');
            } else {
                $item.css('display', 'none');
            }
        });
        
        updateArrows();
    }
    
    // 썸네일 이동
    function moveThumbnails(direction) {
        if (scenaListPagination.$prevBtn.prop('disabled') && direction === 'prev') return;
        if (scenaListPagination.$nextBtn.prop('disabled') && direction === 'next') return;
        
        if (direction === 'next') {
            scenaListPagination.currentPage = Math.min(scenaListPagination.currentPage + 1, scenaListPagination.totalPages - 1);
        } else if (direction === 'prev') {
            scenaListPagination.currentPage = Math.max(scenaListPagination.currentPage - 1, 0);
        }
        
        showCurrentPage();
    }
    
    scenaListPagination.$nextBtn.on('click', function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled')) {
            moveThumbnails('next');
        }
    });
    
    scenaListPagination.$prevBtn.on('click', function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled')) {
            moveThumbnails('prev');
        }
    });
    
    updatePaginationMeta();
    showCurrentPage();
    
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            updatePaginationMeta();
            showCurrentPage();
        }, 100);
    });
    
    // 시나리오 교체
    scenaListPagination.$thumbItems.on('click', function() {
        var wrId = $(this).data('wr-id');
        loadScenaDetail(wrId);
    });
    
    var scenaData = <?php echo json_encode($all_scena_list); ?>;
    var viewBaseUrl = '<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $current_bo_table; ?>&wr_id=';
    function buildViewUrl(wrId) {
        if (!wrId) return '';
        return viewBaseUrl + wrId + '&write_type=scena';
    }

    // 시나리오 상세 정보 로드
    function loadScenaDetail(wrId) {
        var item = scenaData.find(function(s) { return s.wr_id == wrId; });
        
        if (item) {
            var thumbData = getThumbnailData(item);
            var itemData = {
                wr_id: item.wr_id,
                wr_subject: item.wr_subject,
                wr_3: item.wr_3 || '',
                wr_19: item.wr_19 || '',
                wr_20: item.wr_20 || '',
                wr_5: item.wr_5 || '',
                wr_6: item.wr_6 || '',
                wr_9: item.wr_9 || '',
                wr_11: item.wr_11 || '',
                wr_12: item.wr_12 || '',
                wr_13: item.wr_13 || '',
                wr_14: item.wr_14 || '',
                wr_15: item.wr_15 || '',
                wr_16: item.wr_16 || '',
                wr_17: item.wr_17 || '',
                wr_datetime: item.wr_datetime,
                wr_datetime_formatted: item.wr_datetime ? formatDateTime(item.wr_datetime) : '',
                wr_5_formatted: item.wr_5 ? formatDateTime(item.wr_5) : '',
                thumb_type: thumbData.type,
                thumb_url: thumbData.url,
                fa_icon: thumbData.fa_icon,
                pc_images: item.pc_images || []
            };
            updateNewscena(itemData);
        } else {
            $.ajax({
                url: '<?php echo G5_BBS_URL; ?>/board.php',
                type: 'GET',
                data: {
                    bo_table: '<?php echo $current_bo_table; ?>',
                    wr_id: wrId,
                    write_type: 'scena'
                },
                success: function(html) {
                    var $html = $(html);
                    window.location.href = '<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $current_bo_table; ?>&wr_id=' + wrId + '&write_type=scena';
                },
                error: function() {
                    alert('시나리오 정보를 불러올 수 없습니다.');
                }
            });
        }
    }
    
    // 썸네일 데이터
    function getThumbnailData(item) {
        if (item.wr_7 && item.wr_7.trim() !== '') {
            var url = item.wr_7.trim();
            if (url.match(/^https?:\/\//) || url.match(/^\//) || url.indexOf('<?php echo G5_DATA_URL; ?>') === 0) {
                return { type: 'url', url: url, fa_icon: '' };
            } else if (item.wr_8) {
                return { type: 'fa', url: '', fa_icon: item.wr_8 };
            }
        } else if (item.wr_8) {
            return { type: 'fa', url: '', fa_icon: item.wr_8 };
        }
        return { type: 'file', url: '<?php echo G5_IMG_URL; ?>/no_image.png', fa_icon: '' };
    }
    
    // 날짜 포맷 함수
    function formatDateTime(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '.' + month + '.' + day;
    }

    function renderPcPortfolio(item) {
        var $grid = $('.log20_pc_port_grid');
        if (!$grid.length) return;
        var html = '';
        var count = 0;
        var images = item.pc_images || [];
        var pcCount = parseInt(item.wr_11, 10) || 0;
        if (pcCount < 0) pcCount = 0;
        if (pcCount > 6) pcCount = 6;

        if (Array.isArray(images) && images.length > 0) {
            images.forEach(function(url, idx) {
                if (!url) return;
                if (idx >= pcCount && pcCount > 0) return;
                
                count++;
                var pcIndex = idx;
                var pcNameField = 'wr_' + (12 + pcIndex);
                var pcName = item[pcNameField] || '';
                var pcNameHtml = '';
                
                if (pcName) {
                    pcNameHtml = '<div class="log20_pc_port_item_overlay"><div class="log20_pc_port_item_name">' + pcName + '</div></div>';
                }
                
                html += '<div class="log20_pc_port_item"><div class="log20_pc_port_item_inner"><img src="' + url + '" alt="PC 이미지 ' + (pcIndex + 1) + '">' + pcNameHtml + '</div></div>';
            });
        }

        if (!count) {
            html = '<div class="log20_pc_port_empty">등록된 PC 이미지가 없습니다.</div>';
        }

        $grid.attr('data-count', count);
        $grid.html(html);
    }

    function updateActionLinks(item) {
        var $cell = $('.log20_scena_action_cell');
        if (!$cell.length) return;
        var updateBase = $cell.data('update-base') || '';
        var deleteBase = $cell.data('delete-base') || '';
        var $shareBtn = $cell.find('.log20_subitem_btn--share');
        var $editBtn = $cell.find('.log20_subitem_btn--edit');
        var $deleteBtn = $cell.find('.log20_subitem_btn--delete');

        if (item.wr_id) {
            if ($shareBtn.length) {
                $shareBtn.attr('data-wr-id', item.wr_id);
            }
            if ($editBtn.length && updateBase) {
                $editBtn.attr('href', updateBase + item.wr_id);
            }
            if ($deleteBtn.length && deleteBase) {
                $deleteBtn.attr('href', deleteBase + item.wr_id);
            }
            $cell.show();
        } else {
            $cell.hide();
        }
    }
    
    function alignSubtitleWithMeta() {
        var $info = $('.log20_scena_newscena_info');
        if ($info.length === 0) return;
        
        var $dates = $info.find('.log20_scena_list_item_dates');
        var $subtitle = $info.find('.log20_scena_item_subtitle');
        
        if ($subtitle.length > 0 && $subtitle.is(':visible')) {
            var startRow = 1;
            
            if ($dates.length > 0 && $dates.is(':visible')) {
                $info.children().each(function(index) {
                    if ($(this).is($dates)) {
                        startRow = index + 1;
                        return false;
                    }
                });
            } else {
                var $meta = $info.find('.log20_scena_item_meta');
                if ($meta.length > 0) {
                    $info.children().each(function(index) {
                        if ($(this).is($meta)) {
                            startRow = index + 1;
                            return false;
                        }
                    });
                }
            }
            
            $subtitle.css('grid-row', startRow + ' / -1');
        }
    }
    
    // newscena 영역 
    function updateNewscena(item) {
        var $thumb = $('.log20_scena_newscena_thumbnail');
        var thumbHtml = '';
        if (item.thumb_type == 'fa' && item.fa_icon) {
            thumbHtml = '<i class="fa-solid fa-' + item.fa_icon + '" style="color: ' + (item.wr_9 || '#000000') + ';"></i>';
        } else {
            thumbHtml = '<img src="' + item.thumb_url + '" alt="' + item.wr_subject + '">';
        }
        
        $thumb.html(thumbHtml);
        var viewUrl = buildViewUrl(item.wr_id);
        $thumb.attr('data-view-url', viewUrl);
        $('.log20_scena_newscena_info .log20_scena_item_title').attr('data-view-url', viewUrl);
        
        var $info = $('.log20_scena_newscena_info');
        var subtitleText = item.wr_3 || '';
        var subtitleHtml = subtitleText.replace(/\n/g, '<br>');
        $info.find('.log20_scena_item_subtitle').html(subtitleHtml).toggle(!!subtitleText);
        $info.find('.log20_scena_item_title').text(item.wr_subject || '');
        
        var $writerEl = $info.find('.log20_scena_item_writer');
        if ($writerEl.length === 0) {

            var $titleRow = $info.find('.log20_scena_item_title_row');
            if ($titleRow.length > 0) {
                $writerEl = $('<div class="log20_scena_item_writer"></div>');
                $titleRow.append($writerEl);
            }
        }
        if ($writerEl.length > 0) {
            var writerHtml = item.wr_20 ? '<strong>w.</strong> ' + item.wr_20 : '';
            $writerEl.html(writerHtml).toggle(!!item.wr_20);
        }
        $info.find('.log20_scena_item_trailer').text(item.wr_9 || '').toggle(!!item.wr_9);
        var $metaRow = $info.find('.log20_scena_item_meta');
        var ruleText = item.wr_19 ? '룰: ' + item.wr_19 : '';
        var $ruleEl = $metaRow.find('.log20_scena_item_rule');
        if (ruleText) {
            $ruleEl.text(ruleText).show();
        } else {
            $ruleEl.text('').hide();
        }
        var gmText = item.wr_6 ? 'GM: ' + item.wr_6 : '';
        var pcNames = [];
        var pcCount = parseInt(item.wr_11, 10);
        if (isNaN(pcCount) || pcCount < 0) pcCount = 0;
        if (pcCount > 6) pcCount = 6;
        for (var idx = 1; idx <= 6; idx++) {
            var field = 'wr_' + (11 + idx);
            if (item[field]) {
                pcNames.push(item[field]);
            }
            if (pcCount > 0 && pcNames.length >= pcCount) {
                break;
            }
        }
        var pcText = pcNames.length ? ('PC: ' + pcNames.join(', ')) : '';
        var hasMeta = false;
        var $gmEl = $metaRow.find('.log20_scena_item_gm');
        var $pcEl = $metaRow.find('.log20_scena_item_pc');
        if (gmText) {
            $gmEl.text(gmText).show();
            hasMeta = true;
        } else {
            $gmEl.text('').hide();
        }
        if (pcText) {
            $pcEl.text(pcText).show();
            hasMeta = true;
        } else {
            $pcEl.text('').hide();
        }
        $metaRow.toggle(hasMeta);
        
        // 날짜 업데이트
        var $dateLine = $info.find('.log20_scena_list_item_dates');
        var $startDate = $dateLine.find('.log20_scena_list_item_startdate');
        var $endDate = $dateLine.find('.log20_scena_item_enddate');
        var $sep = $dateLine.find('.log20_scena_list_item_range_sep');
        var hasStart = false;
        var hasEnd = false;

        if (item.wr_datetime) {
            $startDate.text(item.wr_datetime_formatted || item.wr_datetime);
            $startDate.show();
            hasStart = true;
        } else {
            $startDate.hide();
        }

        if (item.wr_5) {
            $endDate.text(item.wr_5_formatted || item.wr_5);
            $endDate.show();
            hasEnd = true;
        } else {
            $endDate.hide();
        }

        $sep.toggle(hasStart && hasEnd);
        $dateLine.toggle(hasStart || hasEnd);

        // 부제(wr_3) 업데이트
        var $subtitleArea = $('.log20_scena_newscena_subt');
        var $subtitleContent = $subtitleArea.find('.log20_item_subtitle');
        var subtitleText = item.wr_3 || '';
        if (subtitleText && subtitleText.trim() !== '') {
            var subtitleHtml = subtitleText.replace(/\n/g, '<br>');
            if ($subtitleContent.length > 0) {
                $subtitleContent.html(subtitleHtml);
            } else {
                $subtitleArea.html('<div class="log20_item_subtitle">' + subtitleHtml + '</div>');
            }
            $subtitleArea.show();
        } else {
            $subtitleArea.hide();
        }

        renderPcPortfolio(item);
        updateActionLinks(item);
        
        setTimeout(alignSubtitleWithMeta, 50);
    }
    
    var $pcImageModal = $('#log20_pc_image_modal');
    var $pcImageModalInner = $pcImageModal.find('.log20_pc_image_modal_inner');
    var $pcImageModalImg = $pcImageModal.find('img');

    function openPcImageModal(src) {
        if (!src) return;
        $pcImageModalImg.attr('src', src);
        $pcImageModal.addClass('is-active');
    }

    $(document).on('click', '.log20_pc_port_item_inner img', function(e) {
        e.preventDefault();
        openPcImageModal($(this).attr('src'));
    });

    $pcImageModal.on('click', function() {
        $pcImageModal.removeClass('is-active');
    });

    $pcImageModalInner.on('click', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', '.log20_scena_newscena_thumbnail, .log20_scena_newscena_info .log20_scena_item_title', function() {
        var targetUrl = $(this).data('view-url');
        if (targetUrl) {
            window.location.href = targetUrl;
        }
    });

    $(document).on('click', '.log20_subitem_btn--share', function(e) {
        e.preventDefault();
        var wrId = $(this).data('wr-id');
        openSharePopup(wrId);
    });

    $(document).on('click', '.log20_scena_share_btn', function() {
        var url = $(this).data('share-url') || '';
        if (!url) return;
        copyToClipboard(url)
            .then(function() {
                showScenaShareToast(this);
            }.bind(this))
            .catch(function() {
                showScenaShareToast(this);
            }.bind(this));
    });

    updateArrows();
    
    setTimeout(alignSubtitleWithMeta, 100);
    
    // 마우스 방향에 따른 hover 효과
    $('.log20_scena_item_title_row').on('mouseenter', function(e) {
        var $el = $(this);
        var el = this;
        var rect = el.getBoundingClientRect();
        var y = e.clientY - rect.top;
        var height = rect.height;
        
        var centerY = height / 2;
        
        var dy = y - centerY;
        
        var transformOrigin = 'top center';
        var transform = 'scaleY(0)';
        var transformHover = 'scaleY(1)';
        
        if (dy < 0) {
            transformOrigin = 'top center';
            transform = 'scaleY(0)';
            transformHover = 'scaleY(1)';
        } else {
            transformOrigin = 'bottom center';
            transform = 'scaleY(0)';
            transformHover = 'scaleY(1)';
        }
        $el.css({
            '--hover-origin': transformOrigin,
            '--hover-transform': transform,
            '--hover-transform-hover': transformHover
        });
    });
});
</script>
</div>

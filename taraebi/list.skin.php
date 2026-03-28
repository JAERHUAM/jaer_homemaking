<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';

// 게시판 갤러리 크기 설정 가져오기
$gallery_width = isset($board['bo_gallery_width']) && $board['bo_gallery_width'] > 0 ? (int)$board['bo_gallery_width'] : 300;
$gallery_height = isset($board['bo_gallery_height']) && $board['bo_gallery_height'] > 0 ? (int)$board['bo_gallery_height'] : 300;

// 분류 버튼 생성
$custom_sca = isset($sca) ? $sca : '';
$category_buttons = '';
$has_category_buttons = false;
if ($board['bo_use_category'] && isset($board['bo_category_list']) && trim($board['bo_category_list']) !== '') {
    $category_href = G5_BBS_URL.'/board.php?bo_table='.$bo_table;
    $categories = array_filter(array_map('trim', explode('|', $board['bo_category_list'])));

    $all_active = ($sca === '' || $sca === null);
    $all_class = $all_active ? 'is-active' : '';
    $category_buttons .= '<a href="'.$category_href.'" class="log20_category_btn '.$all_class.'">전체</a>';

    foreach ($categories as $category) {
        if ($category === '') {
            continue;
        }
        $is_active = ($category === $sca);
        $href = $is_active ? $category_href : $category_href.'&amp;sca='.urlencode($category);
        $class = $is_active ? 'is-active' : '';
        $category_buttons .= '<a href="'.$href.'" class="log20_category_btn '.$class.'">'.get_text($category).'</a>';
    }
    $has_category_buttons = true;
}
?>
<style>
.log20_list_item {
    width: <?php echo $gallery_width; ?>px !important;
    height: <?php echo $gallery_height; ?>px !important;
}

/* 분류 버튼이 외부 CSS에 덮이지 않도록 인라인으로 강제 지정 */
#bo_btn_top.log20_top_buttons {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 10px;
}
#bo_btn_top .log20_category_buttons {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    grid-column: 2;
    justify-self: center;
}
#bo_btn_top .btn_bo_user {
    justify-self: end;
    grid-column: 3;
}
#bo_btn_top a.log20_category_btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 30px;
    min-height: 30px;
    padding: 4px 10px;
    line-height: 1;
    margin: 0 4px;
    font-weight: 600;
    text-decoration: none;
    user-select: none;
    -webkit-user-select: none;
    caret-color: transparent;
    border: 1px solid var(--btn-secondary-bg);
    background: var(--btn-secondary-bg);
    color: var(--btn-secondary-text);
    border-radius: var(--btn-secondary-radius);
}
#bo_btn_top a.log20_category_btn.is-active {
    background: var(--btn-accent-bg);
    color: var(--btn-accent-text);
    border-color: var(--btn-accent-bg);
}
</style>
<?php
?>

<!-- 게시판 목록 시작 { -->
<div id="bo_list" style="width:90%;">

    <!-- 게시판 페이지 정보 및 버튼 시작 { -->
    <div id="bo_btn_top" class="log20_top_buttons">
        <?php if ($has_category_buttons) { ?>
            <div class="log20_category_buttons">
                <?php echo $category_buttons ?>
            </div>
        <?php } ?>
        <ul class="btn_bo_user">
            <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-fw" aria-hidden="true"></i><span class="sound_only">관리자</span></a></li><?php } ?>
            <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_admin btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
            <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_admin btn" title="글쓰기"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">글쓰기</span></a></li><?php } ?>
        </ul>
    </div>
    <!-- } 게시판 페이지 정보 및 버튼 끝 -->

    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

    <!-- log20_list_area 시작 { -->
    <div class="log20_list_area">
        <?php
        // 부모 게시글 수만 계산 (wr_4가 'tarae'가 아닌 게시물만)
        if (!isset($write_table)) {
            $write_table = $g5['write_prefix'] . $bo_table;
        }
        $category_where = '';
        if (isset($custom_sca) && $custom_sca !== '') {
            $category_where = " AND ca_name = '".sql_real_escape_string($custom_sca)."'";
        }
        $parent_count_sql = "SELECT COUNT(*) as cnt FROM {$write_table} WHERE wr_is_comment = 0 AND (wr_4 IS NULL OR wr_4 = '' OR wr_4 != 'tarae'){$category_where}";
        $parent_count_result = sql_query($parent_count_sql, false);
        $parent_total_count = $total_count; // 기본값은 전체 게시글 수
        if ($parent_count_result) {
            $parent_count_row = sql_fetch_array($parent_count_result);
            if ($parent_count_row) {
                $parent_total_count = isset($parent_count_row['cnt']) ? (int)$parent_count_row['cnt'] : 0;
            }
        }

        $subject_len = isset($subject_len) ? $subject_len : 60;
        $page_rows = isset($board['bo_page_rows']) ? (int)$board['bo_page_rows'] : 10;
        if ($page_rows < 1) {
            $page_rows = 10;
        }
        $parent_total_page = $parent_total_count > 0 ? (int)ceil($parent_total_count / $page_rows) : 1;
        if ($page > $parent_total_page) {
            $page = $parent_total_page;
        }
        $total_count = $parent_total_count;
        $total_page = $parent_total_page;
        $write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './board.php?bo_table='.$bo_table.$qstr.'&amp;page=');

        $parent_list = array();
        $notice_list = array();
        $notice_array = isset($notice_array) ? $notice_array : array();
        if (!empty($notice_array)) {
            $notice_ids = array();
            foreach ($notice_array as $notice_id) {
                $notice_id = (int)$notice_id;
                if ($notice_id > 0) {
                    $notice_ids[] = $notice_id;
                }
            }
            if (!empty($notice_ids)) {
                $notice_ids_sql = implode(',', $notice_ids);
                $notice_sql = "SELECT * FROM {$write_table} WHERE wr_id IN ({$notice_ids_sql}) AND wr_is_comment = 0 AND (wr_4 IS NULL OR wr_4 = '' OR wr_4 != 'tarae'){$category_where} ORDER BY FIELD(wr_id, {$notice_ids_sql})";
                $notice_result = sql_query($notice_sql, false);
                if ($notice_result) {
                    while ($row = sql_fetch_array($notice_result)) {
                        $notice_list[] = get_list($row, $board, $board_skin_url, $subject_len);
                    }
                }
            }
        }
        $offset = ($page - 1) * $page_rows;
        $parent_sql = "SELECT * FROM {$write_table} WHERE wr_is_comment = 0 AND (wr_4 IS NULL OR wr_4 = '' OR wr_4 != 'tarae'){$category_where} ORDER BY wr_order ASC, wr_id DESC LIMIT {$offset}, {$page_rows}";
        $parent_result = sql_query($parent_sql, false);
        if ($parent_result) {
            while ($row = sql_fetch_array($parent_result)) {
                $parent_list[] = get_list($row, $board, $board_skin_url, $subject_len);
            }
        }
        $list = array_merge($notice_list, $parent_list);
        
        // 자식 게시물 필터링 및 공지글 분리
        $filtered_list = array();
        $normal_list = array();
        
        // 공지 배열 확인 (list.php에서 전달됨)
        $notice_array = isset($notice_array) ? $notice_array : array();
        
        foreach ($list as $item) {
            $item_wr_4 = isset($item['wr_4']) ? trim($item['wr_4']) : '';
            // wr_4가 'tarae'가 아닌 게시물만 표시 (부모 게시물만)
            if ($item_wr_4 !== 'tarae') {
                $item_wr_id = isset($item['wr_id']) ? (int)$item['wr_id'] : 0;
                // 공지글인지 확인
                if (in_array($item_wr_id, $notice_array) || (isset($item['is_notice']) && $item['is_notice'])) {
                    $notice_list[] = $item;
                } else {
                    $normal_list[] = $item;
                }
            }
        }
        
        // wr_order 기준으로 정렬 (공지글은 그대로, 일반 게시글은 wr_order 순서대로)
        usort($normal_list, function($a, $b) {
            $order_a = isset($a['wr_order']) ? (int)$a['wr_order'] : 0;
            $order_b = isset($b['wr_order']) ? (int)$b['wr_order'] : 0;
            if ($order_a == $order_b) {
                // wr_order가 같으면 wr_id 내림차순
                $id_a = isset($a['wr_id']) ? (int)$a['wr_id'] : 0;
                $id_b = isset($b['wr_id']) ? (int)$b['wr_id'] : 0;
                return $id_b - $id_a;
            }
            return $order_a - $order_b; // wr_order 오름차순
        });
        
        // 공지글을 먼저, 그 다음 정렬된 일반 게시글 순서로 합치기
        $list = array_merge($notice_list, $normal_list);
        
        for ($i=0; $i<count($list); $i++) {
            // 게시물의 추가 필드에서 데이터 가져오기
            $wr_1 = isset($list[$i]['wr_1']) ? $list[$i]['wr_1'] : ''; // 색1
            $wr_2 = isset($list[$i]['wr_2']) ? $list[$i]['wr_2'] : ''; // 색2
            $wr_3 = isset($list[$i]['wr_3']) ? $list[$i]['wr_3'] : ''; // 부제
            $wr_5 = isset($list[$i]['wr_5']) ? $list[$i]['wr_5'] : ''; // GM
            $wr_7 = isset($list[$i]['wr_7']) ? $list[$i]['wr_7'] : ''; // 썸네일 이미지 URL
            $wr_8 = isset($list[$i]['wr_8']) ? $list[$i]['wr_8'] : ''; // FA 아이콘
            $wr_9 = isset($list[$i]['wr_9']) ? $list[$i]['wr_9'] : ''; // FA 색
            $wr_10 = isset($list[$i]['wr_10']) ? $list[$i]['wr_10'] : ''; // 배경 색

            // 썸네일 이미지 가져오기
            $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], 500, 500, false, true);
            $img_url = '';
            $img_type = '';
            $fa_icon = '';
            
            // 이미지 우선순위: 첨부파일 > URL > FA 아이콘
            if ($thumb['src']) {
                $img_url = $thumb['src'];
                $img_type = 'file';
            } elseif ($wr_7 && trim($wr_7) !== '') {
                // wr_7이 유효한 URL인지 확인
                $wr_7_trimmed = trim($wr_7);
                if (filter_var($wr_7_trimmed, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_7_trimmed) || strpos($wr_7_trimmed, G5_DATA_URL) === 0) {
                    $img_url = $wr_7_trimmed;
                    $img_type = 'url';
                } elseif ($wr_8) {
                    $img_type = 'fa';
                    $fa_icon = $wr_8;
                } else {
                    $img_url = G5_IMG_URL.'/no_image.png';
                    $img_type = 'file';
                }
            } elseif ($wr_8) {
                $img_type = 'fa';
                $fa_icon = $wr_8;
            } else {
                $img_url = G5_IMG_URL.'/no_image.png';
                $img_type = 'file';
            }
        ?>
        <div class="log20_list_item">
            <a href="<?php echo $list[$i]['href'] ?>" class="log20_item_link">
                <div class="log20_item_title" style="color: <?php echo $wr_1 ? htmlspecialchars($wr_1) : '#000'; ?>; background-color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : 'transparent'; ?>; --bg-color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : 'transparent'; ?>;">
                    <?php echo get_text(cut_str($list[$i]['wr_subject'], 50)) ?>
                </div>
                <div class="log20_item_image"<?php if ($img_type == 'fa' && $wr_10) { ?> style="background-color: <?php echo htmlspecialchars($wr_10); ?>; --bg-color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : 'transparent'; ?>;"<?php } else { ?> style="--bg-color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : 'transparent'; ?>;"<?php } ?>>
                    <?php if ($wr_3) { ?>
                        <div class="log20_item_subtitle" style="color: <?php echo $wr_1 ? htmlspecialchars($wr_1) : '#000'; ?>;">
                            <?php echo get_text($wr_3) ?>
                        </div>
                    <?php } ?>
                    <?php if ($img_type == 'fa' && $fa_icon) { ?>
                        <i class="fa-solid fa-<?php echo htmlspecialchars($fa_icon) ?>" style="color: <?php echo $wr_9 ? htmlspecialchars($wr_9) : '#000000'; ?>;"></i>
                    <?php } else { ?>
                        <img src="<?php echo $img_url ?>" alt="<?php echo get_text($list[$i]['wr_subject']) ?>">
                    <?php } ?>
                </div>
            </a>
            <span class="log20_order_badge" style="background-color: <?php echo $wr_1 ? htmlspecialchars($wr_1) : '#000'; ?>; color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : '#fff'; ?>;">
                <?php echo isset($list[$i]['wr_order']) ? (int)$list[$i]['wr_order'] : 0; ?>
            </span>
        </div>
        <?php } ?>
        <?php if (count($list) == 0) { ?>
        <div class="log20_empty">
            <p>게시물이 없습니다.</p>
        </div>
        <?php } ?>
    </div>
    <!-- } log20_list_area 끝 -->

    </form>

    <!-- 페이지 -->
    <div class="paginate_wrap">
        <?php echo $write_pages; ?>
    </div>
    <!-- 페이지 -->

</div>

<!-- } 게시판 목록 끝 -->


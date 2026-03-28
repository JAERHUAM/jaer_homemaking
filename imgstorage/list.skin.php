<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once(__DIR__.'/imgstorage.lib.php');

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';

// 갤러리 설정·권한 (lib)
$imgstorage_config = imgstorage_get_list_config($board, $member, $is_member, isset($subject_len) ? $subject_len : 60);
$gallery_width = $imgstorage_config['gallery_width'];
$gallery_height = $imgstorage_config['gallery_height'];
$subject_len = $imgstorage_config['subject_len'];
$imgstorage_can_download = $imgstorage_config['can_download'];
$imgstorage_can_write = $imgstorage_config['can_write'];

// 분류 버튼 (lib)
$imgstorage_category = imgstorage_get_category_buttons($board, $bo_table, isset($sca) ? $sca : '');
$imgstorage_category_buttons = $imgstorage_category['html'];
$imgstorage_has_category_buttons = $imgstorage_category['has_buttons'];
?>

<!-- 게시판 목록 시작 { -->
<div id="bo_list" class="imgstorage_list" data-can-download="<?php echo $imgstorage_can_download ? '1' : '0'; ?>" data-no-drag="<?php echo $is_admin ? '0' : '1'; ?>" style="--imgstorage-gallery-width: <?php echo $gallery_width; ?>px; --imgstorage-gallery-height: <?php echo $gallery_height; ?>px;">

    <!-- 최상단 우측: 설정 버튼 -->
    <?php if ($imgstorage_can_write) { ?>
    <div class="imgstorage_top_row">
        <?php if ($admin_href) { ?>
        <a href="<?php echo $admin_href; ?>" class="btn_admin btn" title="설정"><i class="fa fa-cog fa-fw" aria-hidden="true"></i> 설정</a>
        <?php } ?>
    </div>
    <?php } ?>

    <!-- uploadimg: 바로 글쓰기 영역 (좌우 2영역) + 분류 버튼 -->
    <div class="imgstorage_upload_wrap">
    <?php if ($imgstorage_can_write) { ?>
    <div class="imgstorage_uploadimg" id="imgstorage_uploadimg">
        <div class="imgstorage_uploadimg_left">
            <div class="imgstorage_title_row">
                <?php if (!empty($board['bo_use_category']) && !empty($board['bo_category_list'])) {
                    $cats = array_filter(array_map('trim', explode('|', $board['bo_category_list'])));
                    $ca_val = (isset($sca) && $sca !== '') ? $sca : (isset($cats[0]) ? $cats[0] : '');
                ?>
                <select id="imgstorage_ca_name" name="ca_name" class="imgstorage_category_select">
                    <?php foreach ($cats as $c) { ?>
                    <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($ca_val === $c) ? ' selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php } ?>
                </select>
                <?php } ?>
                <input type="text" class="imgstorage_input_title" id="imgstorage_title" placeholder="제목" maxlength="50">
            </div>
            <input type="text" class="imgstorage_input_subtitle" id="imgstorage_subtitle" placeholder="부제| 링크: [글자](url)" maxlength="280">
            <?php if (!$is_member) { ?>
            <input type="text" class="imgstorage_input_title" name="wr_name" id="imgstorage_wr_name" placeholder="이름" required>
            <input type="password" class="imgstorage_input_subtitle" name="wr_password" id="imgstorage_wr_password" placeholder="비밀번호" required>
            <?php } ?>
            <div class="imgstorage_submit_row imgstorage_submit_row--left">
                <label class="imgstorage_sensitive_option">
                    <input type="checkbox" name="wr_adult" id="imgstorage_wr_adult" value="1" class="imgstorage_wr_adult_check">
                    <span class="imgstorage_sensitive_label">민감한 콘텐츠</span>
                </label>
                <button type="button" class="imgstorage_btn_submit" id="imgstorage_btn_submit">올리기</button>
            </div>
        </div>
        <div class="imgstorage_uploadimg_right">
            <!-- 3개 버튼 + 미리보기(붙여넣기 버튼 옆) -->
            <div class="imgstorage_actions_row" id="imgstorage_actions_row">
                <button type="button" class="imgstorage_btn_attach imgstorage_attach_file" aria-label="파일첨부"><i class="fa-solid fa-images"></i></button>
                <button type="button" class="imgstorage_btn_attach imgstorage_url_toggle" aria-label="URL 입력"><i class="fa-solid fa-link"></i></button>
                <button type="button" class="imgstorage_btn_attach imgstorage_paste_toggle" aria-label="붙여넣기"><i class="fa-solid fa-paste"></i></button>
                <div class="imgstorage_preview_wrap" id="imgstorage_preview_wrap" style="display:none;">
                    <div class="imgstorage_preview" id="imgstorage_preview"></div>
                    <button type="button" class="imgstorage_preview_remove" id="imgstorage_preview_remove" aria-label="첨부 취소">×</button>
                </div>
            </div>
            <!-- 버튼 클릭 시 나타나는 입력칸 (세로 30px) -->
            <div class="imgstorage_secondary_row imgstorage_reply_url" id="imgstorage_reply_url">
                <input type="text" class="imgstorage_url_input" placeholder="이미지 URL (https://...)">
                <button type="button" class="imgstorage_btn_done imgstorage_url_done">입력 완료</button>
                <button type="button" class="imgstorage_btn_cancel imgstorage_url_cancel">입력 취소</button>
            </div>
            <div class="imgstorage_secondary_row imgstorage_reply_paste" id="imgstorage_reply_paste">
                <input type="text" class="imgstorage_paste_input" placeholder="여기에 Ctrl+V로 붙여넣기">
                <button type="button" class="imgstorage_btn_cancel imgstorage_paste_cancel">입력 취소</button>
            </div>
            <input type="file" id="imgstorage_file_input" class="imgstorage_file_input" accept="image/*,image/gif" style="display:none;">
        </div>
    </div>
    <?php } ?>
    <div class="imgstorage_category_search_area">
        <?php if ($imgstorage_has_category_buttons) { ?>
        <div class="imgstorage_category_buttons">
            <?php echo $imgstorage_category_buttons; ?>
        </div>
        <?php } ?>
        <div class="imgstorage_search_wrap">
            <form name="fsearch" method="get" action="<?php echo G5_BBS_URL; ?>/board.php" class="imgstorage_search_form">
                <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
                <input type="hidden" name="sca" value="<?php echo $sca; ?>">
                <input type="hidden" name="sfl" value="<?php echo htmlspecialchars(isset($sfl) ? $sfl : 'wr_subject||wr_content', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="stx" value="<?php echo htmlspecialchars(stripslashes(isset($stx) ? $stx : ''), ENT_QUOTES, 'UTF-8'); ?>" class="imgstorage_search_input" placeholder="검색어" maxlength="20">
                <button type="submit" class="imgstorage_search_btn">검색</button>
            </form>
        </div>
    </div>
    </div>

    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="spt" value="<?php echo $spt; ?>">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="sw" value="">

    <!-- list item 영역 -->
    <div class="imgstorage_list_area">
        <?php
        $list_count = isset($list) ? count($list) : 0;
        set_session('ss_delete_token', $delete_token = uniqid(time()));
        $creation_rank = array();
        if ($list_count > 0) {
            $write_table = $g5['write_prefix'] . $bo_table;
            $wr_ids = array();
            foreach ($list as $it) {
                $wr_ids[] = (int)$it['wr_id'];
            }
            $wr_ids_str = implode(',', array_unique($wr_ids));
            $sql_rank = "SELECT a.wr_id, (SELECT COUNT(*) FROM {$write_table} b WHERE b.wr_id <= a.wr_id AND b.wr_is_comment = 0) AS creation_rank FROM {$write_table} a WHERE a.wr_id IN ({$wr_ids_str}) AND a.wr_is_comment = 0";
            $res_rank = sql_query($sql_rank);
            while ($row_rank = sql_fetch_array($res_rank)) {
                $creation_rank[(int)$row_rank['wr_id']] = (int)$row_rank['creation_rank'];
            }
        }
        for ($i = 0; $i < $list_count; $i++) {
            $item = $list[$i];
            $wr_3 = isset($item['wr_3']) ? $item['wr_3'] : '';
            $wr_7 = isset($item['wr_7']) ? $item['wr_7'] : '';
            $wr_adult = isset($item['wr_adult']) ? (int)$item['wr_adult'] : 0;
            $thumb = get_list_thumbnail($board['bo_table'], $item['wr_id'], $gallery_width, $gallery_height, false, true);
            $img_url = '';
            if ($thumb['src']) {
                $img_url = $thumb['src'];
            } elseif ($wr_7 && trim($wr_7) !== '' && (filter_var(trim($wr_7), FILTER_VALIDATE_URL) || strpos(trim($wr_7), G5_DATA_URL) === 0)) {
                $img_url = trim($wr_7);
            } else {
                $img_url = G5_IMG_URL . '/no_image.png';
            }
            // 모달용 원본 이미지 URL: ori(첨부파일 원본) > wr_7(URL 입력) > 썸네일
            $img_url_original = '';
            if (!empty($thumb['ori'])) {
                $img_url_original = $thumb['ori'];
            } elseif ($wr_7 && trim($wr_7) !== '' && (filter_var(trim($wr_7), FILTER_VALIDATE_URL) || strpos(trim($wr_7), G5_DATA_URL) === 0)) {
                $img_url_original = trim($wr_7);
            } else {
                $img_url_original = $img_url;
            }
            $num = isset($creation_rank[(int)$item['wr_id']]) ? $creation_rank[(int)$item['wr_id']] : ($i + 1);
            $img_wrap_class = 'imgstorage_item_img_wrap' . ($wr_adult ? ' imgstorage_item_img_wrap--sensitive' : '');
            // 수정, 삭제 링크 (회원/관리자는 모달, 비회원글은 비밀번호 페이지)
            $item_update_href = $item_delete_href = '';
            $item_can_modal_edit = false;
            if (($member['mb_id'] && ($member['mb_id'] === $item['mb_id'])) || $is_admin) {
                $item_update_href = G5_BBS_URL.'/write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$item['wr_id'].'&amp;page='.$page.$qstr;
                $item_delete_href = G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&amp;wr_id='.$item['wr_id'].'&amp;token='.$delete_token.'&amp;page='.$page.urldecode($qstr);
                $item_can_modal_edit = true;
            } elseif (!$item['mb_id']) {
                $item_update_href = G5_BBS_URL.'/password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$item['wr_id'].'&amp;page='.$page.$qstr;
                $item_delete_href = G5_BBS_URL.'/password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$item['wr_id'].'&amp;page='.$page.$qstr;
            }
            $item_ca_name = isset($item['ca_name']) ? $item['ca_name'] : '';
        ?>
        <div class="imgstorage_list_item_wrap">
            <?php if ($item_update_href || $item_delete_href) { ?>
            <div class="imgstorage_item_actions">
                <?php if ($item_update_href) {
                    if ($item_can_modal_edit) { ?>
                <button type="button" class="imgstorage_btn_action imgstorage_btn_edit imgstorage_edit_trigger" data-wr-id="<?php echo (int)$item['wr_id']; ?>" data-ca-name="<?php echo htmlspecialchars($item_ca_name, ENT_QUOTES, 'UTF-8'); ?>" data-wr-subject="<?php echo htmlspecialchars($item['wr_subject'], ENT_QUOTES, 'UTF-8'); ?>" data-wr-3="<?php echo htmlspecialchars($wr_3, ENT_QUOTES, 'UTF-8'); ?>" data-wr-adult="<?php echo $wr_adult; ?>">수정</button>
                <?php } else { ?>
                <a href="<?php echo $item_update_href; ?>" class="imgstorage_btn_action imgstorage_btn_edit">수정</a>
                <?php } } ?>
                <?php if ($item_delete_href) { ?><a href="<?php echo $item_delete_href; ?>" class="imgstorage_btn_action imgstorage_btn_delete" onclick="del(this.href); return false;">삭제</a><?php } ?>
            </div>
            <?php } ?>
            <div class="imgstorage_list_item">
            <a href="<?php echo $item['href']; ?>" class="imgstorage_view_popup_link" data-img-src="<?php echo htmlspecialchars($img_url_original, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="<?php echo $img_wrap_class; ?>">
                    <span class="imgstorage_item_badge"><?php echo $num; ?></span>
                    <img src="<?php echo $img_url; ?>" alt="<?php echo get_text($item['wr_subject']); ?>"<?php echo imgstorage_drag_disabled_attr($is_admin); ?>>
                </div>
                <div class="imgstorage_item_explain">
                    <div class="imgstorage_explain_title"><?php echo get_text(cut_str($item['wr_subject'], $subject_len)); ?></div>
                    <?php if ($wr_3 !== '') { ?>
                    <div class="imgstorage_explain_subtitle"><?php echo imgstorage_parse_subtitle_links($wr_3); ?></div>
                    <?php } ?>
                    <div class="imgstorage_explain_meta"><?php echo get_text($item['wr_name']); ?> · <?php echo $item['datetime']; ?></div>
                </div>
            </a>
            </div>
        </div>
        <?php } ?>
        <?php if ($list_count == 0) { ?>
        <div class="imgstorage_empty">
            <p>게시물이 없습니다.</p>
        </div>
        <?php } ?>
    </div>
    </form>

    <!-- 페이지 -->
    <div class="paginate_wrap">
        <?php echo $write_pages; ?>
    </div>
</div>
<!-- } 게시판 목록 끝 -->

<!-- imgstorage 수정 모달 -->
<div id="imgstorage_edit_modal" class="imgstorage_modal imgstorage_edit_modal" aria-hidden="true">
    <div class="imgstorage_modal_backdrop imgstorage_edit_modal_backdrop"></div>
    <div class="imgstorage_edit_modal_box">
        <div class="imgstorage_edit_modal_header">
            <h3 class="imgstorage_edit_modal_title">수정</h3>
            <button type="button" class="imgstorage_edit_modal_close" aria-label="닫기">&times;</button>
        </div>
        <form id="imgstorage_edit_form" class="imgstorage_edit_form">
            <input type="hidden" name="wr_id" id="imgstorage_edit_wr_id" value="">
            <input type="hidden" name="w" value="u">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
            <input type="hidden" name="wr_content" id="imgstorage_edit_wr_content" value=" ">
            <input type="hidden" name="html" value="">
            <input type="hidden" name="secret" value="">
            <input type="hidden" name="notice" value="">
            <?php if (!empty($board['bo_use_category']) && !empty($board['bo_category_list'])) {
                $edit_cats = array_filter(array_map('trim', explode('|', $board['bo_category_list'])));
            ?>
            <div class="imgstorage_edit_row imgstorage_edit_row--category">
                <label for="imgstorage_edit_ca_name" class="imgstorage_edit_label">분류</label>
                <select name="ca_name" id="imgstorage_edit_ca_name" class="imgstorage_edit_select">
                    <?php foreach ($edit_cats as $ec) { ?>
                    <option value="<?php echo htmlspecialchars($ec, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ec); ?></option>
                    <?php } ?>
                </select>
            </div>
            <?php } else { ?>
            <input type="hidden" name="ca_name" value="">
            <?php } ?>
            <div class="imgstorage_edit_row">
                <label for="imgstorage_edit_title" class="imgstorage_edit_label">제목</label>
                <input type="text" name="wr_subject" id="imgstorage_edit_title" class="imgstorage_edit_input" maxlength="50" placeholder="제목">
            </div>
            <div class="imgstorage_edit_row">
                <label for="imgstorage_edit_subtitle" class="imgstorage_edit_label">부제</label>
                <input type="text" name="wr_3" id="imgstorage_edit_subtitle" class="imgstorage_edit_input" maxlength="280" placeholder="부제 링크:[글자](url)">
            </div>
            <div class="imgstorage_edit_row">
                <label class="imgstorage_edit_label">민감한 콘텐츠</label>
                <label class="imgstorage_edit_check_wrap">
                    <input type="hidden" name="wr_adult" value="0">
                    <input type="checkbox" name="wr_adult" id="imgstorage_edit_wr_adult" value="1" class="imgstorage_edit_check">
                    <span class="imgstorage_sensitive_label">민감한 콘텐츠</span>
                </label>
            </div>
            <div class="imgstorage_edit_actions">
                <button type="button" class="imgstorage_edit_btn_cancel">취소</button>
                <button type="submit" class="imgstorage_edit_btn_submit">저장</button>
            </div>
        </form>
    </div>
</div>

<!-- imgstorage 이미지 모달 -->
<div id="imgstorage_view_modal" class="imgstorage_modal<?php echo $imgstorage_can_download ? '' : ' imgstorage_modal--no-download'; ?><?php echo !$is_admin ? ' imgstorage_modal--no-drag' : ''; ?>" aria-hidden="true">
    <div class="imgstorage_modal_backdrop"></div>
    <div class="imgstorage_modal_wrap">
        <div class="imgstorage_modal_actions">
            <?php if ($imgstorage_can_download) { ?>
            <div class="imgstorage_modal_action_group">
                <span class="imgstorage_modal_msg" id="imgstorage_modal_msg_url"></span>
                <button type="button" class="imgstorage_modal_btn imgstorage_modal_btn_copy_url">URL 복사</button>
            </div>
            <div class="imgstorage_modal_action_group">
                <span class="imgstorage_modal_msg" id="imgstorage_modal_msg_img"></span>
                <button type="button" class="imgstorage_modal_btn imgstorage_modal_btn_copy_img">이미지 복사</button>
            </div>
            <?php } ?>
            <button type="button" class="imgstorage_modal_close" aria-label="닫기">&times;</button>
        </div>
        <div class="imgstorage_modal_box" id="imgstorage_modal_box">
            <img id="imgstorage_modal_img" src="" alt="" class="imgstorage_modal_img"<?php echo imgstorage_drag_disabled_attr($is_admin); ?>>
        </div>
    </div>
</div>

<script>
(function() {
    var TITLE_MAX = 50, SUBTITLE_MAX = 280;
    var uploadimg = document.getElementById('imgstorage_uploadimg');
    if (!uploadimg) return;

    var fileInput = document.getElementById('imgstorage_file_input');
    var urlRow = document.getElementById('imgstorage_reply_url');
    var pasteRow = document.getElementById('imgstorage_reply_paste');
    var urlInput = urlRow ? urlRow.querySelector('.imgstorage_url_input') : null;
    var pasteInput = pasteRow ? pasteRow.querySelector('.imgstorage_paste_input') : null;
    var previewWrap = document.getElementById('imgstorage_preview_wrap');
    var previewEl = document.getElementById('imgstorage_preview');
    var previewRemoveBtn = document.getElementById('imgstorage_preview_remove');
    var submitBtn = document.getElementById('imgstorage_btn_submit');

    // 이미지 1개: File 객체 또는 URL 문자열
    var currentImageFile = null;
    var currentImageUrl = null;

    function hideAllSecondary() {
        if (urlRow) urlRow.classList.remove('is-visible');
        if (pasteRow) pasteRow.classList.remove('is-visible');
    }

    function setPreviewFromFile(file) {
        currentImageFile = file;
        currentImageUrl = null;
        var url = URL.createObjectURL(file);
        previewEl.innerHTML = '<img src="' + url + '" alt="">';
        previewWrap.style.display = 'flex';
        if (fileInput) fileInput.value = '';
    }

    function setPreviewFromUrl(url) {
        currentImageUrl = url;
        currentImageFile = null;
        previewEl.innerHTML = '<img src="' + url + '" alt="">';
        previewWrap.style.display = 'flex';
    }

    function clearPreview() {
        if (previewEl && previewEl.querySelector('img')) {
            var src = previewEl.querySelector('img').src;
            if (src && src.indexOf('blob:') === 0) URL.revokeObjectURL(src);
        }
        previewEl.innerHTML = '';
        previewWrap.style.display = 'none';
        currentImageFile = null;
        currentImageUrl = null;
    }

    function hasImage() {
        return currentImageFile !== null || (currentImageUrl && currentImageUrl.trim() !== '');
    }

    // 파일첨부 버튼
    uploadimg.querySelector('.imgstorage_attach_file').addEventListener('click', function() {
        hideAllSecondary();
        if (fileInput) fileInput.click();
    });

    fileInput && fileInput.addEventListener('change', function() {
        var file = this.files && this.files[0];
        if (file && file.type && file.type.indexOf('image') === 0) {
            setPreviewFromFile(file);
        }
    });

    // URL 입력
    uploadimg.querySelector('.imgstorage_url_toggle').addEventListener('click', function() {
        if (pasteRow) pasteRow.classList.remove('is-visible');
        if (urlRow) {
            urlRow.classList.toggle('is-visible');
            if (urlRow.classList.contains('is-visible') && urlInput) urlInput.focus();
        }
    });
    if (urlRow) {
        urlRow.querySelector('.imgstorage_url_cancel').addEventListener('click', function() {
            if (urlInput) urlInput.value = '';
            urlRow.classList.remove('is-visible');
        });
        urlRow.querySelector('.imgstorage_url_done').addEventListener('click', function() {
            var url = urlInput ? urlInput.value.trim() : '';
            if (!url) {
                alert('이미지 URL을 입력해주세요.');
                return;
            }
            setPreviewFromUrl(url);
            if (urlInput) urlInput.value = '';
            urlRow.classList.remove('is-visible');
        });
    }

    // 붙여넣기 (taraebi 로직 참고: 클립보드 이미지 → File로 저장)
    uploadimg.querySelector('.imgstorage_paste_toggle').addEventListener('click', function() {
        if (urlRow) urlRow.classList.remove('is-visible');
        if (pasteRow) {
            pasteRow.classList.toggle('is-visible');
            if (pasteRow.classList.contains('is-visible') && pasteInput) pasteInput.focus();
        }
    });
    if (pasteRow) {
        pasteRow.querySelector('.imgstorage_paste_cancel').addEventListener('click', function() {
            if (pasteInput) pasteInput.value = '';
            pasteRow.classList.remove('is-visible');
        });
    }

    document.addEventListener('paste', function(e) {
        if (!pasteInput || !pasteRow || !pasteRow.classList.contains('is-visible')) return;
        var target = e.target;
        if (target !== pasteInput && !pasteInput.contains(target)) return;
        var items = (e.clipboardData || window.clipboardData).items || [];
        var imageFile = null;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image') === 0) {
                imageFile = items[i].getAsFile();
                break;
            }
        }
        if (imageFile) {
            e.preventDefault();
            var ext = 'png';
            if (imageFile.type === 'image/jpeg') ext = 'jpg';
            else if (imageFile.type === 'image/gif') ext = 'gif';
            else if (imageFile.type === 'image/webp') ext = 'webp';
            var name = 'paste_' + Date.now() + '.' + ext;
            var file = new File([imageFile], name, { type: imageFile.type });
            setPreviewFromFile(file);
            if (pasteInput) pasteInput.value = '';
            pasteRow.classList.remove('is-visible');
            return;
        }
        if (navigator.clipboard && navigator.clipboard.read) {
            e.preventDefault();
            navigator.clipboard.read().then(function(clipboardItems) {
                if (!clipboardItems || !clipboardItems.length) return;
                var item = clipboardItems[0];
                var imageType = '';
                if (item.types) {
                    for (var t = 0; t < item.types.length; t++) {
                        if (item.types[t].indexOf('image') === 0) {
                            imageType = item.types[t];
                            break;
                        }
                    }
                }
                if (!imageType) return;
                item.getType(imageType).then(function(blob) {
                    var ext = 'png';
                    if (imageType === 'image/jpeg') ext = 'jpg';
                    else if (imageType === 'image/gif') ext = 'gif';
                    else if (imageType === 'image/webp') ext = 'webp';
                    var name = 'paste_' + Date.now() + '.' + ext;
                    var file = new File([blob], name, { type: imageType });
                    setPreviewFromFile(file);
                    if (pasteInput) pasteInput.value = '';
                    pasteRow.classList.remove('is-visible');
                });
            }).catch(function() {});
        }
    });

    previewRemoveBtn && previewRemoveBtn.addEventListener('click', function() {
        clearPreview();
    });

    // 올리기: AJAX로 write_update.php 전송
    submitBtn.addEventListener('click', function() {
        var titleEl = document.getElementById('imgstorage_title');
        var subtitleEl = document.getElementById('imgstorage_subtitle');
        var title = titleEl ? titleEl.value.trim().substring(0, TITLE_MAX) : '';
        var subtitle = subtitleEl ? subtitleEl.value.trim().substring(0, SUBTITLE_MAX) : '';

        if (!title) {
            alert('제목을 입력해주세요.');
            if (titleEl) titleEl.focus();
            return;
        }
        if (!hasImage()) {
            alert('이미지를 1개 첨부해주세요. (파일첨부, URL 입력, 붙여넣기 중 하나)');
            return;
        }

        var formData = new FormData();
        formData.append('bo_table', '<?php echo $bo_table; ?>');
        formData.append('w', '');
        formData.append('wr_subject', title);
        formData.append('wr_3', subtitle);
        formData.append('wr_content', ' ');
        formData.append('html', '');
        formData.append('secret', '');
        formData.append('notice', '');
        var wrAdultEl = document.getElementById('imgstorage_wr_adult');
        formData.append('wr_adult', wrAdultEl && wrAdultEl.checked ? '1' : '0');
        if (currentImageUrl) formData.append('wr_7', currentImageUrl);
        if (currentImageFile) {
            formData.append('bf_file[]', currentImageFile);
            formData.append('bf_content[0]', '0');
        }

        <?php if (!$is_member) { ?>
        var wrName = document.getElementById('imgstorage_wr_name');
        var wrPass = document.getElementById('imgstorage_wr_password');
        if (wrName && !wrName.value.trim()) {
            alert('이름을 입력해주세요.');
            wrName.focus();
            return;
        }
        if (wrPass && !wrPass.value.trim()) {
            alert('비밀번호를 입력해주세요.');
            wrPass.focus();
            return;
        }
        if (wrName) formData.append('wr_name', wrName.value.trim());
        if (wrPass) formData.append('wr_password', wrPass.value);
        <?php } ?>

        <?php if (!empty($board['bo_use_category']) && !empty($board['bo_category_list'])) { ?>
        var caNameEl = document.getElementById('imgstorage_ca_name');
        formData.append('ca_name', caNameEl ? caNameEl.value : '');
        <?php } ?>

        submitBtn.disabled = true;
        submitBtn.textContent = '업로드 중...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo G5_BBS_URL; ?>/write_update.php');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = '올리기';
            if (xhr.status !== 200) {
                alert('업로드 중 오류가 났습니다.');
                return;
            }
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    clearPreview();
                    if (titleEl) titleEl.value = '';
                    if (subtitleEl) subtitleEl.value = '';
                    location.href = '<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>';
                } else {
                    alert(res.message || '저장 실패');
                }
            } catch (err) {
                alert('응답 처리 중 오류가 났습니다.');
            }
        };
        xhr.onerror = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = '올리기';
            alert('네트워크 오류가 났습니다.');
        };
        xhr.send(formData);
    });
})();

(function() {
    var boList = document.getElementById('bo_list');
    var canDownload = boList && boList.getAttribute('data-can-download') === '1';

    if (!canDownload) {
        var listArea = document.querySelector('.imgstorage_list_area');
        if (listArea) listArea.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    }

    var modal = document.getElementById('imgstorage_view_modal');
    var modalBox = document.getElementById('imgstorage_modal_box');
    var modalImg = document.getElementById('imgstorage_modal_img');
    var backdrop = modal ? modal.querySelector('.imgstorage_modal_backdrop') : null;
    var closeBtn = modal ? modal.querySelector('.imgstorage_modal_close') : null;

    if (!canDownload && modal) modal.addEventListener('contextmenu', function(e) { e.preventDefault(); });

    window.closeImgstorageModal = function() {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('imgstorage_modal--open');
        document.body.style.overflow = '';
        if (modalImg) modalImg.removeAttribute('src');
    };

    function openImgstorageModal(imgSrc) {
        if (!modal || !modalBox || !modalImg) return;
        modalImg.src = imgSrc;
        modalImg.onload = function() {
            var w = modalImg.naturalWidth + 20;
            var h = modalImg.naturalHeight + 20;
            var maxW = window.innerWidth - 40;
            var maxH = window.innerHeight - 40;
            if (w > maxW || h > maxH) {
                var scale = Math.min(maxW / w, maxH / h);
                w = Math.floor(w * scale);
                h = Math.floor(h * scale);
            }
            modalBox.style.width = w + 'px';
            modalBox.style.height = h + 'px';
            modalBox.style.maxWidth = '';
            modalBox.style.maxHeight = '';
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('imgstorage_modal--open');
            document.body.style.overflow = 'hidden';
        };
        modalImg.onerror = function() {
            modalImg.removeAttribute('src');
        };
    }

    var btnCopyUrl = canDownload && modal ? modal.querySelector('.imgstorage_modal_btn_copy_url') : null;
    var btnCopyImg = canDownload && modal ? modal.querySelector('.imgstorage_modal_btn_copy_img') : null;
    var msgUrl = document.getElementById('imgstorage_modal_msg_url');
    var msgImg = document.getElementById('imgstorage_modal_msg_img');

    function showMsg(el, text) {
        if (!el) return;
        el.textContent = text;
        el.classList.add('imgstorage_modal_msg--show');
        clearTimeout(el._msgTimer);
        el._msgTimer = setTimeout(function() {
            el.classList.remove('imgstorage_modal_msg--show');
            el.textContent = '';
        }, 2000);
    }

    if (btnCopyUrl) {
        btnCopyUrl.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!modalImg || !modalImg.src) return;
            var url = modalImg.src;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    showMsg(msgUrl, '이미지 URL이 복사되었습니다.');
                }).catch(function() {
                    showMsg(msgUrl, '복사에 실패했습니다.');
                });
            } else {
                showMsg(msgUrl, '복사에 실패했습니다.');
            }
        });
    }

    if (btnCopyImg) {
        btnCopyImg.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!modalImg || !modalImg.src) return;
            var src = modalImg.src;
            if (navigator.clipboard && navigator.clipboard.write) {
                fetch(src).then(function(res) { return res.blob(); }).then(function(blob) {
                    return navigator.clipboard.write([new ClipboardItem({ [blob.type]: blob })]);
                }).then(function() {
                    showMsg(msgImg, '이미지가 클립보드에 복사되었습니다.');
                }).catch(function() {
                    showMsg(msgImg, '이미지 복사에 실패했습니다.');
                });
            } else {
                showMsg(msgImg, '이미지 복사에 실패했습니다.');
            }
        });
    }

    if (backdrop) backdrop.addEventListener('click', window.closeImgstorageModal);
    if (closeBtn) closeBtn.addEventListener('click', window.closeImgstorageModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('imgstorage_modal--open')) {
            window.closeImgstorageModal();
        }
    });

    var listArea = document.querySelector('.imgstorage_list_area');
    if (listArea) {
        listArea.addEventListener('click', function(e) {
            if (e.target.closest('a.imgstorage_explain_subtitle_link')) return;
            var a = e.target.closest('a.imgstorage_view_popup_link');
            if (!a) return;
            var inImgWrap = e.target.closest('.imgstorage_item_img_wrap');
            var inTitle = e.target.closest('.imgstorage_explain_title');
            if (!inImgWrap && !inTitle) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            var imgSrc = a.getAttribute('data-img-src');
            if (imgSrc) openImgstorageModal(imgSrc);
        });
    }
})();

(function() {
    var editModal = document.getElementById('imgstorage_edit_modal');
    var editForm = document.getElementById('imgstorage_edit_form');
    var editBackdrop = editModal ? editModal.querySelector('.imgstorage_edit_modal_backdrop') : null;
    var editCloseBtn = editModal ? editModal.querySelector('.imgstorage_edit_modal_close') : null;
    var editCancelBtn = editModal ? editModal.querySelector('.imgstorage_edit_btn_cancel') : null;
    var editSubmitBtn = editForm ? editForm.querySelector('.imgstorage_edit_btn_submit') : null;

    function closeEditModal() {
        if (!editModal) return;
        editModal.setAttribute('aria-hidden', 'true');
        editModal.classList.remove('imgstorage_modal--open');
        document.body.style.overflow = '';
    }

    function openEditModal(btn) {
        if (!editModal || !editForm || !btn) return;
        var wrId = btn.getAttribute('data-wr-id');
        var caName = btn.getAttribute('data-ca-name') || '';
        var wrSubject = btn.getAttribute('data-wr-subject') || '';
        var wr3 = btn.getAttribute('data-wr-3') || '';
        var wrAdult = btn.getAttribute('data-wr-adult') === '1';

        var wrIdEl = document.getElementById('imgstorage_edit_wr_id');
        var caNameEl = document.getElementById('imgstorage_edit_ca_name');
        var titleEl = document.getElementById('imgstorage_edit_title');
        var subtitleEl = document.getElementById('imgstorage_edit_subtitle');
        var adultEl = document.getElementById('imgstorage_edit_wr_adult');

        if (wrIdEl) wrIdEl.value = wrId;
        if (caNameEl) caNameEl.value = caName;
        if (titleEl) titleEl.value = wrSubject;
        if (subtitleEl) subtitleEl.value = wr3;
        if (adultEl) adultEl.checked = wrAdult;

        editModal.setAttribute('aria-hidden', 'false');
        editModal.classList.add('imgstorage_modal--open');
        document.body.style.overflow = 'hidden';
        if (titleEl) titleEl.focus();
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.imgstorage_edit_trigger');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            openEditModal(btn);
        }
    });

    if (editBackdrop) editBackdrop.addEventListener('click', closeEditModal);
    if (editCloseBtn) editCloseBtn.addEventListener('click', closeEditModal);
    if (editCancelBtn) editCancelBtn.addEventListener('click', closeEditModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && editModal && editModal.classList.contains('imgstorage_modal--open')) {
            closeEditModal();
        }
    });

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var titleEl = document.getElementById('imgstorage_edit_title');
            var title = titleEl ? titleEl.value.trim().substring(0, 50) : '';
            if (!title) {
                alert('제목을 입력해주세요.');
                if (titleEl) titleEl.focus();
                return;
            }

            if (editSubmitBtn) {
                editSubmitBtn.disabled = true;
                editSubmitBtn.textContent = '저장 중...';
            }

            var formData = new FormData(editForm);
            formData.set('wr_subject', title);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo G5_BBS_URL; ?>/write_update.php');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                if (editSubmitBtn) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.textContent = '저장';
                }
                if (xhr.status !== 200) {
                    alert('저장 중 오류가 났습니다.');
                    return;
                }
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        closeEditModal();
                        location.reload();
                    } else {
                        alert(res.message || '저장 실패');
                    }
                } catch (err) {
                    alert('응답 처리 중 오류가 났습니다.');
                }
            };
            xhr.onerror = function() {
                if (editSubmitBtn) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.textContent = '저장';
                }
                alert('네트워크 오류가 났습니다.');
            };
            xhr.send(formData);
        });
    }
})();
</script>
<?php imgstorage_print_drag_protection_script($is_admin, ['.imgstorage_list_area', '#imgstorage_view_modal'], false); ?>

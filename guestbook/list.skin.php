<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$write_table = $g5['write_prefix'] . $bo_table;
$default_css_path = G5_PATH . '/css/default.css.php';
$default_css_ver = is_file($default_css_path) ? filemtime($default_css_path) : time();
$skin_css_path = $board_skin_path . '/style.css';
$skin_css_ver = is_file($skin_css_path) ? filemtime($skin_css_path) : time();
echo '<link rel="stylesheet" href="'.G5_URL.'/css/default.css.php?v='.$default_css_ver.'">';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v='.$skin_css_ver.'">';
?>

<!-- 게시판 시작 { -->
<div class="webclap-container">
    <!-- 상단 이미지 영역 -->
    <div class="image_webclap">
        <?php if ($is_admin) { ?>
            <a href="<?php echo $board_skin_url ?>/admin_images.php?bo_table=<?php echo $bo_table ?>" class="btn_image_admin" title="이미지 관리">
                <i class="fa-solid fa-gears"></i>
            </a>
        <?php } ?>
        <div class="image-container">
            <?php
            // webclap_2 스킨의 images 폴더에서 이미지 가져오기
            $images_dir = $board_skin_path . '/images';
            $images_url = $board_skin_url . '/images';
            $images = array();
            
            // 이미지 메모 데이터 로드
            $memos_file = $images_dir . '/image_memos.json';
            $image_memos = array();
            if (file_exists($memos_file)) {
                $memos_json = file_get_contents($memos_file);
                $image_memos = json_decode($memos_json, true);
                if (!is_array($image_memos)) {
                    $image_memos = array();
                }
            }
            
            // images 폴더가 존재하고 읽을 수 있는지 확인
            if (is_dir($images_dir) && is_readable($images_dir)) {
                // 허용된 이미지 확장자
                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                
                // 디렉토리 스캔
                $files = scandir($images_dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $file_path = $images_dir . '/' . $file;
                    $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    
                    // 이미지 파일이고 실제 파일인지 확인
                    if (in_array($file_extension, $allowed_extensions) && is_file($file_path)) {
                        $images[] = array(
                            'url' => $images_url . '/' . $file,
                            'filename' => $file
                        );
                    }
                }
            }
            
            // 이미지가 있으면 랜덤으로 하나 선택
            if (count($images) > 0) {
                $random_image = $images[array_rand($images)];
                $image_memo = isset($image_memos[$random_image['filename']]) ? $image_memos[$random_image['filename']] : '';
                if (!empty($image_memo)) {
                    echo '<div class="image-with-tooltip">';
                    echo '<img src="' . htmlspecialchars($random_image['url']) . '" alt="랜덤 이미지" class="random-image">';
                    echo '<div class="image-tooltip">' . htmlspecialchars($image_memo) . '</div>';
                    echo '</div>';
                } else {
                    echo '<img src="' . htmlspecialchars($random_image['url']) . '" alt="랜덤 이미지" class="random-image">';
                }
            }
            ?>
        </div>
    </div>

    <!-- 공지글 영역 -->
    <div class="notice_write_webclap">
        <?php
        foreach ($list as $item) {
            if ($item['is_notice']) {
        ?>
            <div class="notice-item-wrapper">
                <div class="notice-item">
                    <div class="notice-content"><?php echo nl2br($item['wr_content']) ?></div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>

    <!-- 하단 글쓰기 영역 -->
    <div class="write_webclap">
        <!-- 일반 글쓰기 영역 -->
        <div class="guestbook_write_webclap">
            <form name="fwrite" id="fwrite" action="<?php echo G5_BBS_URL ?>/write_update.php" method="post" enctype="multipart/form-data" autocomplete="off" style="width:100%">
                <input type="hidden" name="uid" value="<?php echo get_uniqid() ?>">
                <input type="hidden" name="w" value="">
                <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
                <input type="hidden" name="wr_id" value="0">
                <input type="hidden" name="sca" value="<?php echo $sca ?>">
                <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
                <input type="hidden" name="stx" value="<?php echo $stx ?>">
                <input type="hidden" name="spt" value="<?php echo $spt ?>">
                <input type="hidden" name="page" value="<?php echo $page ?>">
                <input type="hidden" name="wr_subject" value="방명록 글">
                <div class="write-form-wrapper">
                    <div class="write-fields">
                        <?php if (!$is_member) { ?>
                            <div class="guest-info">
                                <input type="text" name="wr_name" id="wr_name" required class="frm_input" placeholder="이름">
                                <input type="password" name="wr_password" id="wr_password" required class="frm_input" placeholder="비밀번호">
                            </div>
                        <?php } ?>
                        <div class="write-input-wrapper">
                            <textarea name="wr_content" id="wr_content" required class="frm_input" placeholder="방명록을 남기고 가주세요."></textarea>
                            <button type="submit" class="btn_submit" style="background-color: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">작성하기</button>
                        </div>
                        <div class="write-options">
                            <label class="secret-checkbox">
                                <input type="checkbox" name="secret" id="wr_secret" value="secret">
                                <span>비밀글 쓰기</span>
                            </label>
                            <?php if ($is_admin) { ?>
                                <label class="notice-checkbox">
                                    <input type="checkbox" name="notice" id="wr_notice" value="1">
                                    <span>공지</span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 작성된 글 목록 -->
    <div class="guestbook_list_webclap">
            <?php
            $has_normal_posts = false;
            foreach ($list as $item) {
                if ($item['is_notice']) continue;
                $has_normal_posts = true;
                
                $delete_href = $item['del_href'] ?? (isset($item['wr_id']) ? G5_BBS_URL.'/delete.php?bo_table='.$bo_table.'&amp;wr_id='.$item['wr_id'] : '');
                $is_secret_post = isset($item['wr_option']) && strpos($item['wr_option'], 'secret') !== false;
                $can_view_secret = $is_admin || (isset($member['mb_id'], $item['mb_id']) && $member['mb_id'] && $item['mb_id'] && $member['mb_id'] === $item['mb_id']);
                if (!$can_view_secret && $is_secret_post) {
                    $session_key = 'ss_secret_'.$bo_table.'_'.$item['wr_num'];
                    $can_view_secret = get_session($session_key);
                }
                $show_secret = $is_secret_post && !$can_view_secret;
                
                $display_content = $item['wr_content'] ?? '';
                if (!$show_secret && $is_secret_post && trim(strip_tags((string)$display_content)) === '') {
                    $secret_row = sql_fetch("SELECT wr_content FROM {$write_table} WHERE wr_id = '{$item['wr_id']}'");
                    $display_content = $secret_row['wr_content'] ?? '';
                }
                
                $can_manage = $is_admin || (isset($member['mb_id'], $item['mb_id']) && $member['mb_id'] && $item['mb_id'] && $member['mb_id'] === $item['mb_id']);
            ?>
                <div class="guestbook-item">
                    <div class="guestbook-meta-item">
                        <div class="guestbook-meta">
                            <div class="guestbook-author">
                                <span class="guestbook-name"><?php echo get_text($item['wr_name']) ?></span>
                                <span class="guestbook-date"><?php echo date('Y-m-d H:i', strtotime($item['wr_datetime'])) ?></span>
                            </div>
                            <?php if ($can_manage && $delete_href) { ?>
                                <a href="<?php echo $delete_href ?>" class="guestbook-delete-btn" onclick="del(this.href); return false;">삭제</a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="guestbook-content-item">
                        <div class="guestbook-content" data-wr-id="<?php echo $item['wr_id'] ?>" data-bo-table="<?php echo $bo_table ?>">
                            <?php if ($show_secret) { ?>
                                <div class="guestbook-secret-row">
                                    <span class="guestbook-secret-text">비밀글입니다.</span>
                                    <?php if (empty($item['mb_id'])) { ?>
                                        <button type="button" class="guestbook-delete-btn guestbook-secret-view-btn">보기</button>
                                        <span class="guestbook-secret-controls" hidden>
                                            <input type="password" class="guestbook-secret-input" placeholder="비밀번호 입력" autocomplete="off">
                                            <button type="button" class="guestbook-delete-btn guestbook-secret-confirm-btn">확인</button>
                                            <button type="button" class="guestbook-delete-btn guestbook-secret-cancel-btn">취소</button>
                                        </span>
                                        <span class="guestbook-secret-message" aria-live="polite"></span>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <?php echo nl2br($display_content) ?>
                            <?php } ?>
                        </div>
                        <div class="guestbook-comment-area">
                            <?php
                            $comment_count_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_is_comment = 1 AND wr_parent = '{$item['wr_id']}'");
                            $comment_count = isset($comment_count_row['cnt']) ? (int)$comment_count_row['cnt'] : 0;
                            ?>
                            <button type="button" class="guestbook-comment-toggle-btn">댓글</button>
                            <span class="guestbook-comment-count"><b><?php echo $comment_count; ?></b></span>
                            <?php
                            if ($comment_count > 0) {
                            ?>
                                <div class="guestbook-comment-list" hidden>
                                    <?php
                                    $is_post_author = $is_admin || (isset($member['mb_id'], $item['mb_id']) && $member['mb_id'] && $item['mb_id'] && $member['mb_id'] === $item['mb_id']);
                                    $can_view_parent_comments = !$is_secret_post || $can_view_secret;
                                    $comment_rows = array();
                                    $comment_sql = "SELECT wr_id, wr_parent, wr_name, wr_content, wr_datetime, mb_id, wr_option, wr_num FROM {$write_table} WHERE wr_is_comment = 1 AND wr_parent = '{$item['wr_id']}' ORDER BY wr_datetime ASC";
                                    $comment_result = sql_query($comment_sql);
                                    while ($comment_row = sql_fetch_array($comment_result)) {
                                        $comment_rows[] = $comment_row;
                                    }
                                    $visible_comments = 0;
                                    foreach ($comment_rows as $comment_row) {
                                        $is_secret_comment = isset($comment_row['wr_option']) && strpos($comment_row['wr_option'], 'secret') !== false;
                                        $is_comment_author = isset($member['mb_id'], $comment_row['mb_id']) && $member['mb_id'] && $comment_row['mb_id'] && $member['mb_id'] === $comment_row['mb_id'];
                                        $can_view_comment = $can_view_parent_comments || $is_admin || $is_post_author || $is_comment_author;
                                        if (!$can_view_comment && $is_secret_comment) {
                                            $session_key = 'ss_secret_'.$bo_table.'_'.$comment_row['wr_num'];
                                            $can_view_comment = get_session($session_key);
                                        }
                                        if (!$can_view_comment) {
                                            continue;
                                        }
                                        $can_delete_comment = false;
                                        $comment_delete_link = '';
                                        if (!empty($member['mb_id'])) {
                                            if ($is_admin || $is_comment_author) {
                                                set_session('ss_delete_comment_'.$comment_row['wr_id'].'_token', $token = uniqid(time()));
                                                $comment_delete_link = G5_BBS_URL.'/delete_comment.php?bo_table='.$bo_table.'&amp;comment_id='.$comment_row['wr_id'].'&amp;token='.$token.'&amp;page='.$page;
                                                $can_delete_comment = true;
                                            }
                                        } else {
                                            if (empty($comment_row['mb_id'])) {
                                                $comment_delete_link = G5_BBS_URL.'/password.php?w=x&amp;bo_table='.$bo_table.'&amp;comment_id='.$comment_row['wr_id'].'&amp;page='.$page;
                                                $can_delete_comment = true;
                                            }
                                        }
                                        $visible_comments++;
                                        $comment_content = nl2br(get_text($comment_row['wr_content']));
                                    ?>
                                            <div class="guestbook-comment-item">
                                                <div class="guestbook-comment-meta">
                                                    <div class="guestbook-comment-meta-left">
                                                        <span class="guestbook-comment-name"><?php echo get_text($comment_row['wr_name']) ?></span>
                                                        <span class="guestbook-comment-date"><?php echo date('Y-m-d H:i', strtotime($comment_row['wr_datetime'])) ?></span>
                                                    </div>
                                                    <?php if ($can_delete_comment && $comment_delete_link) { ?>
                                                        <a href="<?php echo $comment_delete_link ?>" class="guestbook-comment-delete-btn" onclick="del(this.href); return false;">삭제</a>
                                                    <?php } ?>
                                                </div>
                                                <div class="guestbook-comment-content"><?php echo $comment_content ?></div>
                                            </div>
                                    <?php
                                    }
                                    if ($visible_comments === 0) {
                                        echo '<div class="guestbook-comment-empty">작성자만 볼 수 있습니다.</div>';
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                            <form name="fcomment_<?php echo $item['wr_id'] ?>" class="guestbook-comment-form" action="<?php echo G5_BBS_URL ?>/write_comment_update.php" method="post" autocomplete="off" hidden>
                                <input type="hidden" name="w" value="c">
                                <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
                                <input type="hidden" name="wr_id" value="<?php echo $item['wr_id'] ?>">
                                <input type="hidden" name="comment_id" value="">
                                <input type="hidden" name="sca" value="<?php echo $sca ?>">
                                <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
                                <input type="hidden" name="stx" value="<?php echo $stx ?>">
                                <input type="hidden" name="spt" value="<?php echo $spt ?>">
                                <input type="hidden" name="page" value="<?php echo $page ?>">
                                <?php if (!$is_member) { ?>
                                    <div class="guestbook-comment-guest">
                                        <input type="text" name="wr_name" value="<?php echo get_cookie("ck_wr_name"); ?>" id="wr_name_<?php echo $item['wr_id'] ?>" required class="frm_input" placeholder="이름">
                                        <input type="password" name="wr_password" id="wr_password_<?php echo $item['wr_id'] ?>" required class="frm_input" placeholder="비밀번호">
                                    </div>
                                <?php } ?>
                                <div class="guestbook-comment-input">
                                    <textarea name="wr_content" id="wr_content_<?php echo $item['wr_id'] ?>" required class="frm_input" placeholder="댓글을 입력하세요."></textarea>
                                    <button type="submit" class="guestbook-comment-submit">작성하기</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php
            }
            if (!$has_normal_posts) {
            ?>
                <div class="empty-list">작성된 글이 없습니다.</div>
            <?php } ?>
    </div>
</div>
<!-- } 게시판 끝 -->

<script>
(() => {
    const listWrap = document.querySelector('.guestbook_list_webclap');
    if (!listWrap) return;

    const endpoint = "<?php echo $board_skin_url ?>/check_secret.php";

    listWrap.addEventListener('click', async (event) => {
        const viewBtn = event.target.closest('.guestbook-secret-view-btn');
        const confirmBtn = event.target.closest('.guestbook-secret-confirm-btn');
        const cancelBtn = event.target.closest('.guestbook-secret-cancel-btn');
        const commentToggleBtn = event.target.closest('.guestbook-comment-toggle-btn');

        if (commentToggleBtn) {
            const area = commentToggleBtn.closest('.guestbook-comment-area');
            const form = area ? area.querySelector('.guestbook-comment-form') : null;
            const list = area ? area.querySelector('.guestbook-comment-list') : null;
            if (form) form.hidden = !form.hidden;
            if (list) list.hidden = !list.hidden;
            return;
        }

        if (viewBtn) {
            const contentEl = viewBtn.closest('.guestbook-content');
            if (!contentEl) return;
            const controls = contentEl.querySelector('.guestbook-secret-controls');
            if (controls) {
                controls.hidden = false;
                viewBtn.style.display = 'none';
                const input = controls.querySelector('.guestbook-secret-input');
                if (input) input.focus();
            }
            return;
        }

        if (cancelBtn) {
            const contentEl = cancelBtn.closest('.guestbook-content');
            if (!contentEl) return;

            const controls = contentEl.querySelector('.guestbook-secret-controls');
            const input = controls ? controls.querySelector('.guestbook-secret-input') : null;
            const message = contentEl.querySelector('.guestbook-secret-message');
            const view = contentEl.querySelector('.guestbook-secret-view-btn');

            if (controls) controls.hidden = true;
            if (view) view.style.display = '';
            if (input) input.value = '';
            if (message) message.textContent = '';
            return;
        }

        if (confirmBtn) {
            const contentEl = confirmBtn.closest('.guestbook-content');
            if (!contentEl) return;

            const controls = contentEl.querySelector('.guestbook-secret-controls');
            const input = controls ? controls.querySelector('.guestbook-secret-input') : null;
            const message = contentEl.querySelector('.guestbook-secret-message');
            const wrId = contentEl.dataset.wrId;
            const boTable = contentEl.dataset.boTable;
            const password = input ? input.value.trim() : '';

            if (!password) {
                if (message) message.textContent = '비밀번호를 입력해주세요.';
                if (input) input.focus();
                return;
            }

            if (message) message.textContent = '';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({
                        bo_table: boTable || '',
                        wr_id: wrId || '',
                        wr_password: password
                    })
                });

                const data = await response.json();
                if (data && data.success) {
                    contentEl.innerHTML = data.post_html || '';
                } else {
                    if (message) message.textContent = (data && data.message) ? data.message : '비밀번호 확인에 실패했습니다.';
                }
            } catch (error) {
                if (message) message.textContent = '요청 처리 중 오류가 발생했습니다.';
            }
        }
    });
})();
</script>

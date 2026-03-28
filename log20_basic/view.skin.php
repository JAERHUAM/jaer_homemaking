<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// write_type 파라미터에 따라 다른 스킨 파일 로드
$write_type = isset($_GET['write_type']) ? $_GET['write_type'] : '';
    if ($write_type === 'scena') {
        include_once(G5_LIB_PATH.'/thumbnail.lib.php');
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
        echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';

    // ----------------------------------------------------------
    // 본문 첨부파일(.txt)에서 내용을 읽어와 본문으로 사용
    // (wr_21 에 G5_DATA_PATH 기준 상대 경로가 저장되어 있음)
    // ----------------------------------------------------------
    global $g5, $bo_table, $view;

    $write_table = $g5['write_prefix'] . $bo_table;
    $wr_id = isset($view['wr_id']) ? (int)$view['wr_id'] : 0;
    $log20_full_content = '';

    if ($wr_id > 0) {
        // wr_21 (본문 파일 경로)와 wr_content 를 가져온다.
        $row_full = sql_fetch("SELECT wr_21, wr_content FROM {$write_table} WHERE wr_id = '{$wr_id}'");

        if ($row_full) {
            // 1순위: wr_21 에 지정된 파일에서 내용 읽기
            if (!empty($row_full['wr_21'])) {
                $relative_path = $row_full['wr_21'];
                // 이미 절대경로가 저장된 경우를 대비
                if (strpos($relative_path, G5_DATA_PATH) === 0) {
                    $file_path = $relative_path;
                } else {
                    $file_path = rtrim(G5_DATA_PATH, '/').'/'.ltrim($relative_path, '/');
                }

                if (is_file($file_path) && is_readable($file_path)) {
                    $file_content = @file_get_contents($file_path);
                    if ($file_content !== false) {
                        $log20_full_content = $file_content;
                    }
                }
            }

            // 2순위: 파일을 못 읽었을 경우, 기존 wr_content 사용 (호환용)
            if ($log20_full_content === '' && isset($row_full['wr_content'])) {
                $log20_full_content = $row_full['wr_content'];
            }
        }
    }

    // 3순위: 그래도 비어 있으면 기존 $view['content'] 사용
    if ($log20_full_content === '' && isset($view['content'])) {
        $log20_full_content = $view['content'];
    }

    // rulecss 는 페이지 전체가 아닌 "본문" 안에서만 적용되도록
    // 별도의 iframe 안에서만 사용한다.
    $log20_rulecss_head_html = '';
    $log20_rulecss_path  = $board_skin_path . '/rulecss';
    $log20_rulecss_url   = $board_skin_url . '/rulecss';

    if (is_dir($log20_rulecss_path)) {
        if ($dh = opendir($log20_rulecss_path)) {
            while (($entry = readdir($dh)) !== false) {
                if (preg_match('/\.css$/i', $entry)) {
                    $href = $log20_rulecss_url . '/' . $entry;
                    $log20_rulecss_head_html .= '<link rel="stylesheet" href="'.$href.'">' . "\n";
                }
            }
            closedir($dh);
        }
    }

    // iframe 안에 넣을 전체 HTML (rulecss만 사용)
    $log20_iframe_html  = '<!doctype html><html><head><meta charset="utf-8">';
    $log20_iframe_html .= $log20_rulecss_head_html;
    $log20_iframe_html .= '<style>body, body * { user-select: text ; -webkit-user-select: text ; -moz-user-select: text ; -ms-user-select: text !important; }</style>';
    $log20_iframe_html .= '</head><body>';
    $log20_iframe_html .= $log20_full_content;
    $log20_iframe_html .= '</body></html>';

    // 부모 게시물의 색상 정보
    // view.scena.skin.php는 시나리오 게시물 자체를 보는 페이지이므로, 부모 게시물의 색상을 가져와야 함
    $parent_wr_id = 0;
    if (isset($view['wr_parent'])) {
        $parent_wr_id = (int)$view['wr_parent'];
    } elseif ($wr_id > 0) {
        // wr_parent가 없으면 현재 게시물에서 조회
        $current_write = sql_fetch("SELECT wr_parent FROM {$write_table} WHERE wr_id = '{$wr_id}'");
        if ($current_write && isset($current_write['wr_parent'])) {
            $parent_wr_id = (int)$current_write['wr_parent'];
        }
    }

    $parent_wr_1 = '';
    $parent_wr_2 = '';

    // 부모 게시물 ID가 있고, 부모 게시물이 자신과 다른 경우 (자식 게시물인 경우)
    if ($parent_wr_id > 0 && $parent_wr_id != $wr_id) {
        $parent_write = sql_fetch("SELECT wr_1, wr_2 FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
        if ($parent_write) {
            $parent_wr_1 = isset($parent_write['wr_1']) ? $parent_write['wr_1'] : '';
            $parent_wr_2 = isset($parent_write['wr_2']) ? $parent_write['wr_2'] : '';
        }
    } else {
        // 부모 게시물이 없거나 자신인 경우 (부모 게시물인 경우), 현재 게시물의 색상 사용
        $parent_wr_1 = isset($view['wr_1']) ? $view['wr_1'] : '';
        $parent_wr_2 = isset($view['wr_2']) ? $view['wr_2'] : '';
    }

    $action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
    $accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';

    // ----------------------------------------------------------
    // 핸드아웃 데이터 로드 (별도 handouts 테이블)
    // ----------------------------------------------------------
    $log20_handouts = array();
    $handout_table = $write_table . '_handouts';

        $ht_check = sql_query("SHOW TABLES LIKE '{$handout_table}'", false);
        if ($ht_check && sql_num_rows($ht_check) > 0 && $wr_id > 0) {
            $wr_id_safe = (int)$wr_id;
            $handout_sql = "
                SELECT handout_index, title, content, content_front, content_back, image_url
                FROM {$handout_table}
                WHERE wr_id = '{$wr_id_safe}'
                ORDER BY handout_index ASC
            ";
            $handout_result = sql_query($handout_sql, false);
            if ($handout_result) {
                while ($row = sql_fetch_array($handout_result)) {
                    $idx = (int)$row['handout_index'];
                    if ($idx < 1 || $idx > 20) continue;
                    $content_front = isset($row['content_front']) ? $row['content_front'] : '';
                    $content_back = isset($row['content_back']) ? $row['content_back'] : '';
                    // 기존 content가 있고 front/back이 없으면 content를 front로 사용 (호환성)
                    if (empty($content_front) && empty($content_back) && isset($row['content'])) {
                        $content_front = $row['content'];
                    }
                    $log20_handouts[] = array(
                        'index' => $idx,
                        'title' => isset($row['title']) ? $row['title'] : '',
                        'content' => isset($row['content']) ? $row['content'] : '',
                        'content_front' => $content_front,
                        'content_back' => $content_back,
                        'image_url' => isset($row['image_url']) ? $row['image_url'] : ''
                    );
                }
            }
        }
?>

<?php
// 돌아가기 버튼 URL 생성
// 항상 시나리오 목록(list.scena.skin.php)으로 이동하도록 구성
$back_url = '';
if ($parent_wr_id > 0 && $parent_wr_id != $wr_id) {
    // 부모 게시물이 있으면 해당 부모 기준 시나리오 목록으로 이동
    $back_url = G5_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $parent_wr_id . '&sublist=scena';
} else {
    // 부모 게시물이 없으면 현재 게시글 기준으로 시나리오 목록으로 이동
    $back_wr_id = $wr_id > 0 ? $wr_id : 0;
    $back_url = G5_BBS_URL . '/board.php?bo_table=' . $bo_table . ($back_wr_id ? '&wr_id=' . $back_wr_id : '') . '&sublist=scena';
}
?>
<div class="log20_scena_view_back_btn_wrap">
    <a href="<?php echo htmlspecialchars($back_url, ENT_QUOTES); ?>" class="log20_scena_view_back_btn" style="background-color: <?php echo htmlspecialchars($accent_color, ENT_QUOTES); ?>; color: <?php echo htmlspecialchars($action_color, ENT_QUOTES); ?>;">
        <i class="fa-solid fa-angle-left"></i>
    </a>
</div>
<article id="bo_v" class="log20_scena_view" style="width:<?php echo $width; ?>; border-color: <?php echo htmlspecialchars($action_color, ENT_QUOTES); ?>;">
    <header class="log20_scena_view_title" style="padding-bottom:10px; margin-bottom:0; border-bottom:1px solid <?php echo htmlspecialchars($action_color, ENT_QUOTES); ?>;">
        <?php echo get_text($view['wr_subject']); ?>
    </header>
    <div class="log20_scena_view_body">
        <div class="log20_scena_view_body_logpart">
            <iframe
                class="log20_scena_iframe"
                srcdoc=""
                style="width:100%; border:0; overflow:auto;"
            ></iframe>
        </div>
        <aside class="log20_scena_view_body_handout" aria-label="핸드아웃 목록">
            <div class="log20_scena_view_handout_header_box" style="background-color: <?php echo htmlspecialchars($accent_color, ENT_QUOTES); ?>; color: <?php echo htmlspecialchars($action_color, ENT_QUOTES); ?>;">
                핸드아웃
            </div>
            <?php if (!empty($log20_handouts)) { ?>
            <div class="log20_scena_view_handout_list">
                <?php foreach ($log20_handouts as $handout) { 
                    $hid = 'log20_scena_handout_' . (int)$handout['index'];
                    $title_raw = trim($handout['title']) !== '' ? $handout['title'] : ('핸드아웃 ' . (int)$handout['index']);
                    // 쌍따옴표 복원: stripslashes와 HTML 엔티티 디코딩
                    $title = html_entity_decode(stripslashes($title_raw), ENT_QUOTES, 'UTF-8');
                    $image_url = trim($handout['image_url']);
                ?>
                <div class="log20_scena_view_handout_item" data-handout-index="<?php echo (int)$handout['index']; ?>">
                    <div class="log20_scena_view_handout_header" data-handout-toggle="<?php echo $hid; ?>">
                        <div class="log20_scena_view_handout_thumb">
                            <?php if ($image_url !== '') { ?>
                                <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES); ?>" alt="">
                            <?php } else { ?>
                                <span style="font-size:11px; color:#aaa;">H</span>
                            <?php } ?>
                        </div>
                        <div class="log20_scena_view_handout_title" title="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($title, ENT_QUOTES); ?>
                        </div>
                        <div class="log20_scena_view_handout_toggle">▶</div>
                    </div>
                    <div class="log20_scena_view_handout_detail" id="<?php echo $hid; ?>">
                        <div class="log20_scena_view_handout_detail_inner">
                            <?php if ($image_url !== '') { ?>
                            <div class="log20_scena_view_handout_detail_image">
                                <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>" class="scena_handout_image_clickable">
                            </div>
                            <?php } ?>
                            <div class="log20_scena_view_handout_detail_body">
                                <?php 
                                $content_front_raw = isset($handout['content_front']) ? $handout['content_front'] : '';
                                $content_back_raw = isset($handout['content_back']) ? $handout['content_back'] : '';
                                // 기존 content가 있고 front/back이 없으면 content를 front로 사용 (호환성)
                                if (empty($content_front_raw) && empty($content_back_raw) && isset($handout['content'])) {
                                    $content_front_raw = $handout['content'];
                                }
                                
                                // 쌍따옴표 복원: stripslashes와 HTML 엔티티 디코딩
                                $content_front = html_entity_decode(stripslashes($content_front_raw), ENT_QUOTES, 'UTF-8');
                                $content_back = html_entity_decode(stripslashes($content_back_raw), ENT_QUOTES, 'UTF-8');
                                
                                if (!empty($content_front) || !empty($content_back)) {
                                    if (!empty($content_front)) {
                                        echo '<b>앞면</b><br>';
                                        echo nl2br($content_front);
                                        if (!empty($content_back)) {
                                            echo '<br><br>';
                                        }
                                    }
                                    if (!empty($content_back)) {
                                        echo '<b>뒷면</b><br>';
                                        echo nl2br($content_back);
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </aside>
    </div>
</article>

<!-- 핸드아웃 이미지 모달 -->
<div id="scena_handout_image_modal" class="scena_handout_image_modal" style="display:none;">
    <div class="scena_handout_image_modal_backdrop"></div>
    <div class="scena_handout_image_modal_content">
        <button type="button" class="scena_handout_image_modal_close" aria-label="닫기">×</button>
        <div class="scena_handout_image_modal_inner">
            <img id="scena_handout_image_modal_img" src="" alt="">
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var iframe = document.querySelector(".log20_scena_iframe");
    if (!iframe) return;

    // PHP 에서 만든 iframe HTML을 주입
    var html = <?php echo json_encode($log20_iframe_html, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    iframe.srcdoc = html;

    function resizeIframe() {
        try {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            if (!doc || !doc.body) return;
            var h = doc.body.scrollHeight;
            if (h && h > 0) {
                iframe.style.height = h + "px";
            }
        } catch (e) {
            // 접근 불가 시 무시
        }
    }

    iframe.addEventListener("load", resizeIframe);
    // 혹시 로딩이 늦게 끝나는 경우 대비
    setTimeout(resizeIframe, 1000);

    // ------------------------------------------------------
    // 핸드아웃 sticky 작동을 위한 overflow 조정 (#container_wr의 overflow-y: auto 우회)
    // ------------------------------------------------------
    var handoutAside = document.querySelector('.log20_scena_view_body_handout');
    if (handoutAside) {
        // 핸드아웃의 부모 요소들을 찾아서 overflow: visible 강제 적용
        var currentElement = handoutAside.parentElement;
        var elementsToFix = [];
        
        // #container_wr까지 올라가면서 overflow 속성이 있는 요소 찾기
        while (currentElement && currentElement.id !== 'container_wr') {
            var computedStyle = window.getComputedStyle(currentElement);
            var overflow = computedStyle.overflow || computedStyle.overflowY || computedStyle.overflowX;
            if (overflow && overflow !== 'visible') {
                elementsToFix.push({
                    element: currentElement,
                    originalOverflow: currentElement.style.overflow || '',
                    originalOverflowY: currentElement.style.overflowY || ''
                });
                // overflow: visible 강제 적용
                currentElement.style.overflow = 'visible';
                currentElement.style.overflowY = 'visible';
            }
            currentElement = currentElement.parentElement;
        }
        
        // #container_wr의 overflow도 임시로 조정 (다른 곳에 영향을 주지 않도록 주의)
        var containerWr = document.getElementById('container_wr');
        if (containerWr) {
            var originalContainerOverflow = containerWr.style.overflowY || '';
            // 핸드아웃이 있는 경우에만 overflow 조정
            containerWr.style.overflowY = 'visible';
            
            // 페이지 언로드 시 원래대로 복원
            window.addEventListener('beforeunload', function() {
                containerWr.style.overflowY = originalContainerOverflow;
                elementsToFix.forEach(function(item) {
                    item.element.style.overflow = item.originalOverflow;
                    item.element.style.overflowY = item.originalOverflowY;
                });
            });
        }
    }

    // ------------------------------------------------------
    // 핸드아웃 토글
    // ------------------------------------------------------
    var handoutItems = document.querySelectorAll('.log20_scena_view_handout_item');
    if (handoutItems.length) {
        handoutItems.forEach(function(item) {
            var header = item.querySelector('.log20_scena_view_handout_header');
            var toggleIcon = item.querySelector('.log20_scena_view_handout_toggle');
            if (!header) return;

            header.addEventListener('click', function() {
                var targetId = header.getAttribute('data-handout-toggle');
                var detail = targetId ? document.getElementById(targetId) : null;
                if (!detail) return;

                var isOpen = item.classList.contains('is-open');
                if (isOpen) {
                    item.classList.remove('is-open');
                    if (toggleIcon) toggleIcon.textContent = '▶';
                } else {
                    item.classList.add('is-open');
                    if (toggleIcon) toggleIcon.textContent = '▼';
                }
            });
        });
    }

    // ------------------------------------------------------
    // 핸드아웃 이미지 모달
    // ------------------------------------------------------
    var handoutModal = document.getElementById('scena_handout_image_modal');
    var handoutModalImg = document.getElementById('scena_handout_image_modal_img');
    var handoutModalBackdrop = handoutModal ? handoutModal.querySelector('.scena_handout_image_modal_backdrop') : null;
    var handoutModalClose = handoutModal ? handoutModal.querySelector('.scena_handout_image_modal_close') : null;
    var scenaScrollTop = 0;
    var scenaScrollLockHandler = null;

    function openHandoutModal(src, alt) {
        if (!handoutModal || !handoutModalImg) return;
        
        // 현재 스크롤 위치 저장
        scenaScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        
        // 스크롤 이벤트를 막는 핸들러 등록
        scenaScrollLockHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, scenaScrollTop);
            document.documentElement.scrollTop = scenaScrollTop;
            document.body.scrollTop = scenaScrollTop;
            return false;
        };
        
        // 스크롤 이벤트 막기
        window.addEventListener('scroll', scenaScrollLockHandler, { passive: false, capture: true });
        window.addEventListener('wheel', scenaScrollLockHandler, { passive: false, capture: true });
        window.addEventListener('touchmove', scenaScrollLockHandler, { passive: false, capture: true });
        
        // 스크롤을 먼저 막기
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        
        // body에 top 스타일을 먼저 추가하여 스크롤 위치 고정
        document.body.style.top = '-' + scenaScrollTop + 'px';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.left = '0';
        document.body.style.right = '0';
        
        // 모달 표시
        handoutModalImg.src = src;
        handoutModalImg.alt = alt || '';
        handoutModal.style.display = 'flex';
        
        // 클래스 추가
        document.body.classList.add('scena_handout_modal_open');
        document.documentElement.classList.add('scena_handout_modal_open');
        
        // 스크롤 위치가 변경되지 않도록 여러 번 확인
        var checkScroll = function() {
            var currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            if (Math.abs(currentScroll - scenaScrollTop) > 1) {
                window.scrollTo(0, scenaScrollTop);
                document.documentElement.scrollTop = scenaScrollTop;
                document.body.scrollTop = scenaScrollTop;
            }
        };
        
        // 즉시 확인
        checkScroll();
        
        // requestAnimationFrame으로 확인
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function() {
                checkScroll();
                requestAnimationFrame(function() {
                    checkScroll();
                });
            });
        }
    }

    function closeHandoutModal() {
        if (!handoutModal || !handoutModalImg) return;
        
        var savedScrollTop = scenaScrollTop || 0;
        
        // 스크롤 이벤트 차단 해제
        if (scenaScrollLockHandler) {
            window.removeEventListener('scroll', scenaScrollLockHandler, { capture: true });
            window.removeEventListener('wheel', scenaScrollLockHandler, { capture: true });
            window.removeEventListener('touchmove', scenaScrollLockHandler, { capture: true });
            scenaScrollLockHandler = null;
        }
        
        // body 스타일 제거
        document.body.style.top = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        
        // 클래스 제거
        document.body.classList.remove('scena_handout_modal_open');
        document.documentElement.classList.remove('scena_handout_modal_open');
        
        // 모달 숨기기
        handoutModal.style.display = 'none';
        handoutModalImg.src = '';
        handoutModalImg.alt = '';
        
        // 원래 스크롤 위치로 복원
        var restoreScroll = function() {
            window.scrollTo(0, savedScrollTop);
            document.documentElement.scrollTop = savedScrollTop;
            document.body.scrollTop = savedScrollTop;
        };
        
        // 즉시 복원 시도
        restoreScroll();
        
        // requestAnimationFrame으로 레이아웃 업데이트 후 복원
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function() {
                restoreScroll();
                requestAnimationFrame(function() {
                    restoreScroll();
                });
            });
        } else {
            setTimeout(restoreScroll, 0);
            setTimeout(restoreScroll, 10);
        }
    }

    // 이미지 클릭 이벤트 (이벤트 위임 사용)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('click', '.scena_handout_image_clickable', function(e) {
            e.preventDefault();
            openHandoutModal(this.src, this.getAttribute('alt') || '');
        });
    } else {
        document.addEventListener('click', function(e) {
            var target = e.target;
            if (target && target.matches('.scena_handout_image_clickable')) {
                e.preventDefault();
                openHandoutModal(target.src, target.getAttribute('alt') || '');
            }
        });
    }

    // 모달 닫기 이벤트
    if (handoutModalBackdrop) {
        handoutModalBackdrop.addEventListener('click', closeHandoutModal);
    }
    if (handoutModalClose) {
        handoutModalClose.addEventListener('click', closeHandoutModal);
    }

    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && handoutModal && handoutModal.style.display === 'flex') {
            closeHandoutModal();
        }
    });
});
</script>

<?php
    exit;
} else {
    // write_type이 없지만 해당 게시물이 시나리오(wr_4='scena')라면
    // 시나리오 전용 뷰로 강제 이동 (비밀번호 입력 후에도 유지되도록)
    global $view, $bo_table, $wr_id, $g5;
    if (isset($view['wr_4']) && $view['wr_4'] === 'scena' && $bo_table && $wr_id) {
        $parent_wr_id = isset($view['wr_parent']) ? (int)$view['wr_parent'] : 0;
        if ($parent_wr_id <= 0) {
            $write_table = $g5['write_prefix'] . $bo_table;
            $current_write = sql_fetch("SELECT wr_parent FROM {$write_table} WHERE wr_id = '{$wr_id}'");
            if ($current_write && isset($current_write['wr_parent'])) {
                $parent_wr_id = (int)$current_write['wr_parent'];
            }
        }
        $target_wr_id = $parent_wr_id > 0 ? $parent_wr_id : (int)$wr_id;
        $redirect_url = G5_BBS_URL . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $target_wr_id . '&sublist=scena';
        goto_url($redirect_url);
        exit;
    }
}

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// 시나리오 목록 전용 페이지
$sublist = isset($_GET['sublist']) ? trim($_GET['sublist']) : '';
if ($sublist === 'scena') {
    echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';
    $parent_wr_id = isset($wr_id) ? (int)$wr_id : 0;
    include($board_skin_path . '/list.scena.skin.php');
    exit;
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';
?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<!-- 게시물 읽기 시작 { -->

<?php
// 상단 버튼 색상: 부모 게시물의 제목색(wr_1) / 배경색(wr_2)을 기준으로 설정
$btn_bg_color   = isset($view['wr_2']) && $view['wr_2'] ? $view['wr_2'] : '#333';
$btn_icon_color = isset($view['wr_1']) && $view['wr_1'] ? $view['wr_1'] : '#fff';
?>

<article id="bo_v" class="view_article" style="width:<?php echo $width; ?>">
    <!-- log20_content 영역 시작 { -->
    <!-- 게시물 버튼 -->
    <div id="bo_v_top" class="view_buttons_inline">
        <ul class="btn_bo_user">
            <li>
                <a href="<?php echo $list_href ?>"
                   class="btn_b01 btn"
                   title="목록"
                   style="background-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          border-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                    <i class="fa fa-list" aria-hidden="true"
                       style="color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                    <span class="sound_only">목록</span>
                </a>
            </li>
        </ul>
        <ul class="btn_bo_user bo_v_com">
            <li>
                <a href="<?php echo G5_BBS_URL; ?>/share_popup.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>"
                   class="btn_b01 btn"
                   title="공유"
                   onclick="window.open(this.href, 'share_popup', 'width=600,height=500,scrollbars=yes'); return false;"
                   style="background-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          border-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                    <i class="fa fa-share-alt" aria-hidden="true"
                       style="color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                    <span class="sound_only">공유</span>
                </a>
            </li>
            <?php if ($update_href) { ?>
            <li>
                <a href="<?php echo $update_href ?>"
                   class="btn_b01 btn"
                   title="수정"
                   style="background-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          border-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                    <i class="fa fa-pencil" aria-hidden="true"
                       style="color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                    <span class="sound_only">수정</span>
                </a>
            </li>
            <?php } ?>
            <?php if ($delete_href) { ?>
            <li>
                <a href="<?php echo $delete_href ?>"
                   class="btn_b01 btn"
                   title="삭제"
                   onclick="del(this.href); return false;"
                   style="background-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          border-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                    <i class="fa fa-trash" aria-hidden="true"
                       style="color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                    <span class="sound_only">삭제</span>
                </a>
            </li>
            <?php } ?>
        </ul>
    </div>
    <div class="log20_content">
        <?php
        // 게시물의 추가 필드에서 데이터 가져오기
        $wr_1 = isset($view['wr_1']) ? $view['wr_1'] : ''; // 제목색
        $wr_2 = isset($view['wr_2']) ? $view['wr_2'] : ''; // 제목 배경 색
        $wr_3 = isset($view['wr_3']) ? $view['wr_3'] : ''; // 부제
        $wr_7 = isset($view['wr_7']) ? $view['wr_7'] : ''; // 썸네일 이미지 URL
        $wr_8 = isset($view['wr_8']) ? $view['wr_8'] : ''; // FA 아이콘
        $wr_9 = isset($view['wr_9']) ? $view['wr_9'] : ''; // FA 색
        $wr_10 = isset($view['wr_10']) ? $view['wr_10'] : ''; // 배경 색

        // 썸네일 이미지 가져오기
        $img_url = '';
        $img_type = '';
        $fa_icon = '';

        // 이미지 우선순위: 첨부파일 > URL > FA 아이콘
        if (isset($view['file']) && count($view['file']) > 0) {
            // 첨부파일에서 첫 번째 이미지 찾기
            foreach ($view['file'] as $file) {
                if (isset($file['view']) && $file['view']) {
                    $img_url = $file['view'];
                    $img_type = 'file';
                    break;
                }
            }
        }
        
        if (!$img_url && $wr_7 && trim($wr_7) !== '') {
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
        } elseif (!$img_url && $wr_8) {
            $img_type = 'fa';
            $fa_icon = $wr_8;
        } else {
            $img_url = G5_IMG_URL.'/no_image.png';
            $img_type = 'file';
        }

        // log20_content_title 배경색 설정
        $title_bg_color = '';
        if ($img_type == 'fa' && $wr_10) {
            $title_bg_color = $wr_10;
        } elseif ($img_type != 'fa' && $wr_2) {
            $title_bg_color = $wr_2;
        }
        
        // log20_content_title과 log20_content_menu 스타일 설정
        $title_style = '';
        if ($title_bg_color) {
            $title_style .= 'background-color: ' . htmlspecialchars($title_bg_color) . '; ';
        }
        if ($wr_1) {
            $title_style .= 'border-color: ' . htmlspecialchars($wr_1) . '; ';
        }
        ?>
        <!-- log20_content_title 영역 시작 { -->
        <div class="log20_content_title"<?php if ($title_style) { ?> style="<?php echo trim($title_style); ?>"<?php } ?>>

            <!-- 좌측 썸네일 영역 -->
            <div class="log20_thumbnail"<?php 
                $thumbnail_style = '';
                if ($img_type == 'fa' && $wr_10) {
                    $thumbnail_style .= 'background-color: ' . htmlspecialchars($wr_10) . '; ';
                }
                if ($img_type == 'fa') {
                    $thumbnail_style .= 'width: 300px; ';
                } else {
                    // 이미지 사용 시 width를 auto로 설정하여 이미지 비율에 맞춤
                    $thumbnail_style .= 'width: auto; ';
                }
                if ($thumbnail_style) {
                    echo ' style="' . trim($thumbnail_style) . '"';
                }
            ?>>
                <?php if ($img_type == 'fa' && $fa_icon) { ?>
                    <i class="fa-solid fa-<?php echo htmlspecialchars($fa_icon) ?>" style="color: <?php echo $wr_9 ? htmlspecialchars($wr_9) : '#000000'; ?>;"></i>
                <?php } else { ?>
                    <img src="<?php echo $img_url ?>" alt="<?php echo get_text($view['wr_subject']) ?>">
                <?php } ?>
            </div>

            <!-- 우측 정보 영역 -->
            <div class="log20_info"<?php if ($wr_1) { ?> style="color: <?php echo htmlspecialchars($wr_1); ?>;"<?php } ?>>
                <?php if ($wr_3) { ?>
                <div class="log20_info_item_subtitle">
                    <?php echo get_text($wr_3) ?>
                </div>
                <?php } ?>
                <div class="log20_info_item_title">
                    <?php echo get_text($view['wr_subject']) ?>
                </div>
                <div class="log20_info_bottom"></div>
            </div>
        </div>
        <!-- } log20_content_title 영역 끝 -->

        <!-- log20_content_menu 영역 시작 { -->
        <?php
        // log20_content_menu 스타일 설정
        $menu_style = '';
        if ($title_bg_color) {
            $menu_style .= 'background-color: ' . htmlspecialchars($title_bg_color) . '; ';
        }
        if ($wr_1) {
            $menu_style .= 'border-color: ' . htmlspecialchars($wr_1) . '; ';
        }
        ?>
        <div class="log20_content_menu"<?php if ($menu_style) { ?> style="<?php echo trim($menu_style); ?>"<?php } ?>>
            <div class="log20_content_menu_scena">
                <div class="log_content_menu_btn">
                    <div class="log_content_menu_icon" style="background-color: <?php echo $wr_1 ? htmlspecialchars($wr_1) : '#000'; ?>;">
                        <i class="fa-solid fa-scroll" style="color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : '#fff'; ?>;"></i>
                    </div>
                    <span class="log_content_menu_text" style="color: <?php echo $wr_1 ? htmlspecialchars($wr_1) : '#000'; ?>;">시나리오 목록</span>
                </div>
            </div>
        </div>
        <!-- } log20_content_menu 영역 끝 -->
    </div>
    <!-- } log20_content 영역 끝 -->

    <?php
    $cnt = 0;
    if ($view['file']['count']) {
        for ($i=0; $i<count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view'])
                $cnt++;
        }
    }
    ?>

    <?php if($cnt) { ?>
    <!-- 첨부파일 시작 { -->
    <section id="bo_v_file">
        <h2>첨부파일</h2>
        <ul>
        <?php
        // 가변 파일
        for ($i=0; $i<count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view']) {
         ?>
            <li>
                <i class="fa fa-folder-open" aria-hidden="true"></i>
                <a href="<?php echo $view['file'][$i]['href'];  ?>" class="view_file_download">
                    <strong><?php echo $view['file'][$i]['source'] ?></strong> <?php echo $view['file'][$i]['content'] ?> (<?php echo $view['file'][$i]['size'] ?>)
                </a>
                <br>
                <span class="bo_v_file_cnt"><?php echo $view['file'][$i]['download'] ?>회 다운로드 | DATE : <?php echo $view['file'][$i]['datetime'] ?></span>
            </li>
        <?php
            }
        }
         ?>
        </ul>
    </section>
    <!-- } 첨부파일 끝 -->
    <?php } ?>

    <?php if(isset($view['link']) && array_filter($view['link'])) { ?>
    <!-- 관련링크 시작 { -->
    <section id="bo_v_link">
        <h2>관련링크</h2>
        <ul>
        <?php
        // 링크
        $cnt = 0;
        for ($i=1; $i<=count($view['link']); $i++) {
            if ($view['link'][$i]) {
                $cnt++;
                $link = cut_str($view['link'][$i], 70);
            ?>
            <li>
                <i class="fa fa-link" aria-hidden="true"></i>
                <a href="<?php echo $view['link_href'][$i] ?>" target="_blank">
                    <strong><?php echo $link ?></strong>
                </a>
                <br>
                <span class="bo_v_link_cnt"><?php echo $view['link_hit'][$i] ?>회 연결</span>
            </li>
            <?php
            }
        }
        ?>
        </ul>
    </section>
    <!-- } 관련링크 끝 -->
    <?php } ?>
</article>
<!-- } 게시판 읽기 끝 -->

<script>
<?php if ($board['bo_download_point'] < 0) { ?>
$(function() {
    $("a.view_file_download").click(function() {
        if(!g5_is_member) {
            alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오.");
            return false;
        }

        var msg = "파일을 다운로드 하시면 포인트가 차감(<?php echo number_format($board['bo_download_point']) ?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n그래도 다운로드 하시겠습니까?";

        if(confirm(msg)) {
            var href = $(this).attr("href")+"&js=on";
            $(this).attr("href", href);

            return true;
        } else {
            return false;
        }
    });
});
<?php } ?>

function board_move(href)
{
    window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
}
</script>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

    // 이미지 리사이즈
    $("#bo_v_atc").viewimageresize();
    
});
</script>
<!-- } 게시글 읽기 끝 -->


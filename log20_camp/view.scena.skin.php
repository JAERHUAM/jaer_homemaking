<?php
if (!defined("_GNUBOARD_")) exit;

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



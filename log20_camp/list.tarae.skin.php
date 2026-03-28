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

// 게시판 정보 가져오기 (없으면)
if (!isset($board) || !$board) {
    $current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
    $board = get_board_db($current_bo_table, true);
}

// 게시판 테이블명 설정
$current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
$write_table = $g5['write_prefix'] . $current_bo_table;

// 부모 게시물 ID
$parent_wr_id = isset($parent_wr_id) ? (int)$parent_wr_id : (isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : (isset($view['wr_id']) ? (int)$view['wr_id'] : (isset($wr_id) ? (int)$wr_id : 0)));

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

// 전체 메모 수 조회
$total_count = 0;
$total_page = 1;

$table_check = sql_query("SHOW TABLES LIKE '{$write_table}'", false);
if ($table_check && sql_num_rows($table_check) > 0) {
    $count_sql = "SELECT COUNT(*) as cnt 
                  FROM {$write_table} 
                  WHERE wr_parent = '{$parent_wr_id}' 
                  AND wr_is_comment = 0 
                  AND wr_id != wr_parent
                  AND wr_4 = 'tarae'";
    $count_result = sql_query($count_sql, false);
    if ($count_result) {
        $count_row = sql_fetch_array($count_result);
        $total_count = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
    }
    $total_page = $total_count > 0 ? ceil($total_count / $limit) : 1;
}

// 메모 목록 조회
$list = array();
if ($parent_wr_id > 0) {
    $parent_wr_id_safe = (int)$parent_wr_id;
    $offset_safe = (int)$offset;
    $limit_safe = (int)$limit;
    
    $sql = "SELECT wr_id, wr_subject, wr_name, wr_content, wr_3, wr_39, wr_40, wr_41, wr_42, wr_datetime, mb_id
            FROM {$write_table} 
            WHERE wr_parent = '{$parent_wr_id_safe}' 
            AND wr_is_comment = 0 
            AND wr_id != wr_parent
            AND wr_4 = 'tarae'
            ORDER BY wr_datetime DESC
            LIMIT {$offset_safe}, {$limit_safe}";
    
    $result = sql_query($sql, false);
    
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $list[] = array(
                'wr_id' => $row['wr_id'],
                'wr_subject' => $row['wr_subject'],
                'wr_name' => $row['wr_name'],
                'wr_content' => $row['wr_content'],
                'wr_subtitle' => isset($row['wr_3']) ? $row['wr_3'] : '',
                'wr_images' => array(
                    isset($row['wr_39']) ? $row['wr_39'] : '',
                    isset($row['wr_40']) ? $row['wr_40'] : '',
                    isset($row['wr_41']) ? $row['wr_41'] : '',
                    isset($row['wr_42']) ? $row['wr_42'] : ''
                ),
                'wr_datetime' => $row['wr_datetime'],
                'mb_id' => $row['mb_id']
            );
        }
    }
}

$action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
$accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#f5f5f5';
?>

<div class="log20_sublist_area" id="log20_sublist_tarae">
    <?php
    // 부모 게시물의 색상 정보 및 글쓰기 링크
    $action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
    $accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';

    // tarae 전용 쓰기 페이지로 이동 (기본 write 흐름 사용)
    $write_url = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&write_type=tarae';
    if ($parent_wr_id > 0) {
        $write_url .= '&wr_parent=' . (int)$parent_wr_id;
    }
    
    // 게시판 쓰기 권한 체크
    $member_level = isset($member['mb_level']) ? (int)$member['mb_level'] : 0;
    $write_level = isset($board['bo_write_level']) ? (int)$board['bo_write_level'] : 1;
    ?>
    <div class="log20_sublist_header"<?php if ($action_color) { ?> style="border-bottom-color: <?php echo htmlspecialchars($action_color); ?>;"<?php } ?>>
        <a href="<?php echo $write_url; ?>" class="log20_sublist_write_btn" style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>; border:none;" onclick="return checkWritePermission(event, <?php echo $write_level; ?>, <?php echo $is_admin ? 'true' : 'false'; ?>, <?php echo $member_level; ?>);">
            <span class="log20_sublist_write_btn_text">메모 남기기</span>
        </a>
    </div>
    
    <!-- 검색창 -->
    <div class="log20_tarae_search_wrap"<?php if ($action_color || $accent_color) { ?> style="--action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;"<?php } ?>>
        <input type="text" id="log20_tarae_search_input" class="log20_tarae_search_input" placeholder="제목, 부제, 내용으로 검색..." autocomplete="off">
        <button type="button" id="log20_tarae_search_btn" class="log20_tarae_search_btn" title="찾기">찾기</button>
        <button type="button" id="log20_tarae_search_reset" class="log20_tarae_search_reset" title="초기화">초기화</button>
    </div>
    
    <!-- 메모 목록 -->
    <?php if (count($list) > 0) { ?>
        <div class="log20_list_tarae_area" id="log20_list_tarae_area">
            <?php foreach ($list as $item) { 
                $content_id = 'tarae_content_' . (int)$item['wr_id'];
                $edit_url   = G5_BBS_URL . '/write.php?w=u&bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$item['wr_id'] . '&write_type=tarae';
                $del_url    = $board_skin_url . '/delete.sublist.skin.php?bo_table=' . urlencode($current_bo_table) . '&wr_id=' . (int)$item['wr_id'] . '&write_type=tarae';
                $images = array();
                if (!empty($item['wr_images'][0])) $images[] = $item['wr_images'][0];
                if (!empty($item['wr_images'][1])) $images[] = $item['wr_images'][1];
                if (!empty($item['wr_images'][2])) $images[] = $item['wr_images'][2];
                if (!empty($item['wr_images'][3])) $images[] = $item['wr_images'][3];
                $image_count = count($images);
                $body_class = $image_count > 0 ? 'log20_tarae_body has-images' : 'log20_tarae_body no-images';
            ?>
            <div class="log20_tarae_item" data-search-text="<?php echo htmlspecialchars(strtolower(strip_tags($item['wr_subject'] . ' ' . $item['wr_subtitle'] . ' ' . $item['wr_content']))); ?>">
                <div class="log20_tarae_header">
                    <span class="log20_tarae_title"><?php echo get_text($item['wr_subject']); ?></span>
                    <span class="log20_tarae_date">
                        <?php echo date('Y.m.d', strtotime($item['wr_datetime'])); ?>
                        <a href="<?php echo $edit_url; ?>" class="log20_tarae_btn log20_tarae_btn_edit">수정</a>
                        <a href="<?php echo $del_url; ?>" class="log20_tarae_btn log20_tarae_btn_delete" onclick="del(this.href); return false;">삭제</a>
                    </span>
                </div>
                <?php if (!empty($item['wr_subtitle'])) { ?>
                <div class="log20_tarae_subtitle">
                    <?php echo get_text($item['wr_subtitle']); ?>
                </div>
                <?php } ?>
                <button type="button" class="log20_tarae_toggle" data-target="<?php echo $content_id; ?>">▶ 펼치기</button>
                <div class="log20_tarae_content" id="<?php echo $content_id; ?>" style="display:none;">
                    <div class="<?php echo $body_class; ?>">
                        <?php if ($image_count > 0) { ?>
                        <div class="log20_tarae_images log20_tarae_images--<?php echo $image_count; ?>">
                            <?php foreach ($images as $img_url) { ?>
                            <div class="log20_tarae_image_item">
                                <img src="<?php echo htmlspecialchars($img_url); ?>" alt="">
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <div class="log20_tarae_text">
                            <?php echo stripslashes($item['wr_content']); ?>
                        </div>
                    </div>
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
        <button type="button" class="tarae_image_modal_close" aria-label="닫기">×</button>
        <div class="tarae_image_modal_inner">
            <img id="tarae_image_modal_img" src="" alt="">
        </div>
    </div>
</div>

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
// tarae 펼치기/접기 토글 + log20_content_sublist 높이 조정
// 이벤트 위임을 사용하므로 중복 실행되어도 문제 없음
(function() {
    function adjustSublistHeightTarae() {
        var $sublist = jQuery('#log20_content_sublist');
        if ($sublist.length === 0) return;

        // 현재 height를 auto로 두고 실제 컨텐츠 높이 측정
        var originalHeight = $sublist.css('height');
        $sublist.css('height', 'auto');

        var actualContentHeight = $sublist[0].scrollHeight;
        var minHeight = parseInt($sublist.css('min-height')) || 300;

        // 여유 20px 추가
        var targetHeight = actualContentHeight + 20;
        var finalHeight = Math.max(minHeight, targetHeight);

        $sublist.css('height', finalHeight + 'px');
    }

    // 펼치기/접기 토글 이벤트 (이벤트 위임 사용 - 동적 로딩 대응)
    function handleToggleClick(e) {
        var btn, targetId, content;
        
        // jQuery를 사용하는 경우
        if (typeof jQuery !== 'undefined') {
            var $btn = jQuery(e.target).closest('.log20_tarae_toggle');
            if ($btn.length === 0) return;
            
            targetId = $btn.attr('data-target');
            var $content = jQuery('#' + targetId);
            if ($content.length === 0) return;
            
            if ($content.is(':hidden') || $content.css('display') === 'none') {
                $content.show();
                $btn.text('▼ 접기');
            } else {
                $content.hide();
                $btn.text('▶ 펼치기');
            }
            
            adjustSublistHeightTarae();
            return;
        }
        
        // 순수 JS를 사용하는 경우 - closest 사용
        if (e.target.closest) {
            btn = e.target.closest('.log20_tarae_toggle');
        } else {
            // closest를 지원하지 않는 브라우저를 위한 폴백
            btn = e.target;
            while (btn && btn !== document.body) {
                if (btn.classList && btn.classList.contains('log20_tarae_toggle')) {
                    break;
                }
                btn = btn.parentElement;
            }
            if (!btn || !btn.classList || !btn.classList.contains('log20_tarae_toggle')) {
                btn = null;
            }
        }
        
        if (!btn) return;
        
        targetId = btn.getAttribute('data-target');
        content = document.getElementById(targetId);
        if (!content) return;

        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            btn.textContent = '▼ 접기';
        } else {
            content.style.display = 'none';
            btn.textContent = '▶ 펼치기';
        }

        adjustSublistHeightTarae();
    }
    
    // 이벤트 위임으로 문서 레벨에서 이벤트 바인딩 (중복 바인딩 방지)
    if (typeof jQuery !== 'undefined') {
        // 기존 이벤트가 있으면 제거 후 재바인딩 (중복 방지)
        jQuery(document).off('click', '.log20_tarae_toggle');
        jQuery(document).on('click', '.log20_tarae_toggle', handleToggleClick);
    } else {
        // 순수 JS는 이벤트 위임을 위해 document에 한 번만 바인딩
        if (!window.taraeToggleHandlerBound) {
            document.addEventListener('click', handleToggleClick, true);
            window.taraeToggleHandlerBound = true;
        }
    }

    // 이미지 클릭 시 모달 표시 (동적 로딩 대응: 이벤트 위임 사용)
    var modal = document.getElementById('tarae_image_modal');
    var modalImg = document.getElementById('tarae_image_modal_img');
    var modalBackdrop = modal ? modal.querySelector('.tarae_image_modal_backdrop') : null;
    var modalClose = modal ? modal.querySelector('.tarae_image_modal_close') : null;
    var taraeScrollTop = 0;
    var scrollLockHandler = null;

    function openModal(src, alt) {
        if (!modal || !modalImg) return;
        
        // 현재 스크롤 위치 저장 (모달을 열기 전에, 가장 먼저)
        taraeScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        
        // 스크롤 이벤트를 막는 핸들러 등록
        scrollLockHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, taraeScrollTop);
            document.documentElement.scrollTop = taraeScrollTop;
            document.body.scrollTop = taraeScrollTop;
            return false;
        };
        
        // 스크롤 이벤트 막기 (여러 이벤트 타입에 대해)
        window.addEventListener('scroll', scrollLockHandler, { passive: false, capture: true });
        window.addEventListener('wheel', scrollLockHandler, { passive: false, capture: true });
        window.addEventListener('touchmove', scrollLockHandler, { passive: false, capture: true });
        
        // 스크롤을 먼저 막기
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        
        // body에 top 스타일을 먼저 추가하여 스크롤 위치 고정
        // position: fixed를 적용하기 전에 스크롤 위치를 고정
        document.body.style.top = '-' + taraeScrollTop + 'px';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.left = '0';
        document.body.style.right = '0';
        
        // 모달 표시
        modalImg.src = src;
        modalImg.alt = alt || '';
        modal.style.display = 'flex';
        
        // 클래스 추가
        document.body.classList.add('tarae_modal_open');
        document.documentElement.classList.add('tarae_modal_open');
        
        // 스크롤 위치가 변경되지 않도록 여러 번 확인
        var checkScroll = function() {
            var currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            if (Math.abs(currentScroll - taraeScrollTop) > 1) {
                window.scrollTo(0, taraeScrollTop);
                document.documentElement.scrollTop = taraeScrollTop;
                document.body.scrollTop = taraeScrollTop;
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

    function closeModal() {
        if (!modal || !modalImg) return;
        
        modal.style.display = 'none';
        modalImg.src = '';
        modalImg.alt = '';
        
        // 클래스를 제거하기 전에 스크롤 위치 복원 준비
        var savedScrollTop = taraeScrollTop || 0;
        
        // 스크롤 이벤트 핸들러 제거
        if (scrollLockHandler) {
            window.removeEventListener('scroll', scrollLockHandler, { capture: true });
            window.removeEventListener('wheel', scrollLockHandler, { capture: true });
            window.removeEventListener('touchmove', scrollLockHandler, { capture: true });
            scrollLockHandler = null;
        }
        
        // body 스타일 제거 (클래스 제거 전에)
        document.body.style.top = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        
        // 클래스 제거
        document.body.classList.remove('tarae_modal_open');
        document.documentElement.classList.remove('tarae_modal_open');
        
        // 원래 스크롤 위치로 복원하는 함수
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
                // 한 프레임 더 기다려서 확실하게 복원
                requestAnimationFrame(function() {
                    restoreScroll();
                });
            });
        } else {
            // requestAnimationFrame을 지원하지 않는 경우
            setTimeout(restoreScroll, 0);
            setTimeout(restoreScroll, 10);
        }
    }

    // jQuery가 있으면 jQuery 이벤트 위임 사용
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('click', '.log20_tarae_image_item img', function(e) {
            e.preventDefault();
            openModal(this.src, this.getAttribute('alt') || '');
        });
    } else {
        // 순수 JS 이벤트 위임
        document.addEventListener('click', function(e) {
            var target = e.target;
            if (target && target.matches('.log20_tarae_image_item img')) {
                e.preventDefault();
                openModal(target.src, target.getAttribute('alt') || '');
            }
        });
    }

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', closeModal);
    }
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // 초기 로드 시에도 한 번 높이 맞추기
    if (typeof jQuery !== 'undefined') {
        jQuery(function() {
            adjustSublistHeightTarae();
        });
    } else {
        adjustSublistHeightTarae();
    }

    // 검색 기능
    var searchInput = document.getElementById('log20_tarae_search_input');
    var searchBtn = document.getElementById('log20_tarae_search_btn');
    var searchReset = document.getElementById('log20_tarae_search_reset');
    var taraeItems = document.querySelectorAll('.log20_tarae_item');
    var taraeArea = document.getElementById('log20_list_tarae_area');
    var emptyMessage = document.querySelector('.log20_empty');

    function performSearch() {
        var searchTerm = searchInput.value.trim().toLowerCase();
        var hasVisibleItems = false;
        var visibleCount = 0;

        if (searchTerm === '') {
            // 검색어가 없으면 모든 항목 표시
            taraeItems.forEach(function(item) {
                item.style.display = '';
                visibleCount++;
            });
            hasVisibleItems = visibleCount > 0;
            
            // 기존 빈 메시지 표시/숨김 처리
            if (taraeArea && emptyMessage) {
                if (hasVisibleItems) {
                    taraeArea.style.display = '';
                    emptyMessage.style.display = 'none';
                } else {
                    taraeArea.style.display = 'none';
                    emptyMessage.style.display = '';
                }
            }
            
            // 검색 결과 없음 메시지 제거
            var noResultsMsg = document.getElementById('log20_tarae_no_results');
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        } else {
            // 검색어가 있으면 필터링
            taraeItems.forEach(function(item) {
                var searchText = item.getAttribute('data-search-text') || '';
                if (searchText.indexOf(searchTerm) !== -1) {
                    item.style.display = '';
                    visibleCount++;
                    hasVisibleItems = true;
                } else {
                    item.style.display = 'none';
                }
            });

            // 검색 결과가 없을 때 메시지 표시
            if (taraeArea) {
                if (hasVisibleItems) {
                    taraeArea.style.display = '';
                    // 검색 결과 없음 메시지 제거
                    var noResultsMsg = document.getElementById('log20_tarae_no_results');
                    if (noResultsMsg) {
                        noResultsMsg.remove();
                    }
                } else {
                    taraeArea.style.display = 'none';
                    // 검색 결과 없음 메시지 추가
                    var noResultsMsg = document.getElementById('log20_tarae_no_results');
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'log20_tarae_no_results';
                        noResultsMsg.className = 'log20_empty';
                        noResultsMsg.innerHTML = '<p>검색 결과가 없습니다.</p>';
                        if (taraeArea.parentNode) {
                            taraeArea.parentNode.insertBefore(noResultsMsg, taraeArea.nextSibling);
                        }
                    }
                }
            }
        }

        // 높이 재조정
        adjustSublistHeightTarae();
    }

    // 찾기 버튼 클릭
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            performSearch();
        });
    }

    // 초기화 버튼 클릭
    if (searchReset) {
        searchReset.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
        });
    }

    // Enter 키로 검색 (찾기 버튼과 동일)
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
})();
</script>

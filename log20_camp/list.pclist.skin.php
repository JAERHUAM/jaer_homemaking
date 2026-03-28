<?php
if (!defined('_GNUBOARD_')) {
    $skin_path = dirname(__FILE__);
    $common_path = realpath($skin_path . '/../../../_common.php');
    if ($common_path && file_exists($common_path)) {
        include_once($common_path);
    } else {
        exit; // 개별 페이지 접근 불가
    }
}

global $member, $is_admin, $board_skin_url, $board, $g5;

// 게시판 정보 가져오기 (없으면)
if (!isset($board) || !$board) {
    $current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
    $board = get_board_db($current_bo_table, true);
}

// 게시판 테이블명 설정
$current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
$write_table = $g5['write_prefix'] . $current_bo_table;

// 현재 게시물의 wr_id 가져오기 (부모 게시물)
$parent_wr_id = isset($parent_wr_id) ? (int)$parent_wr_id : (isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : (isset($view['wr_id']) ? (int)$view['wr_id'] : (isset($wr_id) ? (int)$wr_id : 0)));

// 페이지네이션 설정
$page = isset($_GET['pclist_page']) ? (int)$_GET['pclist_page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;
$total_count = 0;
$total_page = 1;

// 테이블 존재 확인
$table_check = sql_query("SHOW TABLES LIKE '{$write_table}'", false);
if ($table_check && sql_num_rows($table_check) > 0) {
    $count_sql = "SELECT COUNT(*) as cnt 
                  FROM {$write_table} 
                  WHERE wr_parent = '{$parent_wr_id}' 
                  AND wr_is_comment = 0 
                  AND wr_id != wr_parent
                  AND wr_4 = 'pclist'";
    $count_result = sql_query($count_sql, false);
    if ($count_result) {
        $count_row = sql_fetch_array($count_result);
        $total_count = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
    }
    $total_page = $total_count > 0 ? ceil($total_count / $limit) : 1;
}

// 게시물 목록 조회
$list = array();
if ($total_count > 0 && $parent_wr_id > 0) {
    $parent_wr_id = (int)$parent_wr_id;
    $offset = (int)$offset;
    $limit = (int)$limit;
    
    $sql = "SELECT wr_id, wr_subject, wr_name, wr_datetime, wr_hit, wr_comment, wr_1, wr_2, wr_3, wr_7, wr_8, wr_9, wr_10, wr_21, wr_22, wr_44, wr_content, mb_id
            FROM {$write_table} 
            WHERE wr_parent = '{$parent_wr_id}' 
            AND wr_is_comment = 0 
            AND wr_id != wr_parent
            AND wr_4 = 'pclist'
            ORDER BY wr_num DESC, wr_reply ASC
            LIMIT {$offset}, {$limit}";
    
    $result = sql_query($sql, false);
    
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
            $href = G5_BBS_URL . '/board.php?bo_table=' . $current_bo_table . '&wr_id=' . $row['wr_id'] . '&write_type=pclist';
            
            $list[] = array(
                'wr_id' => $row['wr_id'],
                'wr_subject' => $row['wr_subject'],
                'wr_name' => $row['wr_name'],
                'wr_datetime' => $row['wr_datetime'],
                'wr_hit' => $row['wr_hit'],
                'wr_comment' => $row['wr_comment'],
                'mb_id' => isset($row['mb_id']) ? $row['mb_id'] : '',
                'href' => $href,
                'wr_1' => isset($row['wr_1']) ? $row['wr_1'] : '',
                'wr_2' => isset($row['wr_2']) ? $row['wr_2'] : '',
                'wr_3' => isset($row['wr_3']) ? $row['wr_3'] : '',
                'wr_7' => isset($row['wr_7']) ? $row['wr_7'] : '',
                'wr_8' => isset($row['wr_8']) ? $row['wr_8'] : '',
                'wr_9' => isset($row['wr_9']) ? $row['wr_9'] : '',
                'wr_10' => isset($row['wr_10']) ? $row['wr_10'] : '',
                'wr_21' => isset($row['wr_21']) ? $row['wr_21'] : '',
                'wr_22' => isset($row['wr_22']) ? $row['wr_22'] : '',
                'wr_44' => isset($row['wr_44']) ? $row['wr_44'] : '',
                'wr_content' => isset($row['wr_content']) ? $row['wr_content'] : ''
            );
        }
    }
}
?>

<div class="log20_sublist_area" id="log20_sublist_pclist">
    <?php
    // 부모 게시물의 색상 정보
    $parent_wr_1 = isset($wr_1) ? $wr_1 : '';
    $parent_wr_2 = isset($wr_2) ? $wr_2 : '';
    $action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
    $accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';
    
    // 글쓰기 링크 생성
    $current_bo_table = isset($bo_table) ? $bo_table : 'tr_log';
    $write_url = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&w=&write_type=pclist&wr_parent=' . $parent_wr_id;
    if ($parent_wr_id > 0) {
        $write_url .= '&wr_parent=' . $parent_wr_id;
    }
    
    // 게시판 쓰기 권한 체크
    $can_write = false;
    $member_level = isset($member['mb_level']) ? (int)$member['mb_level'] : 0;
    $write_level = isset($board['bo_write_level']) ? (int)$board['bo_write_level'] : 1;
    
    if ($is_admin) {
        $can_write = true;
    } elseif ($member_level >= $write_level) {
        $can_write = true;
    }
    ?>
    <div class="log20_sublist_header"<?php if ($action_color) { ?> style="border-bottom-color: <?php echo htmlspecialchars($action_color); ?>;"<?php } ?>>
        <a href="<?php echo $write_url; ?>" class="log20_sublist_write_btn" style="border-radius: 4px; --action-color: <?php echo htmlspecialchars($action_color); ?>; --accent-color: <?php echo htmlspecialchars($accent_color); ?>;" onclick="return checkWritePermission(event, <?php echo $write_level; ?>, <?php echo $is_admin ? 'true' : 'false'; ?>, <?php echo $member_level; ?>);">
            <span class="log20_sublist_write_btn_text">캐릭터 등록하기</span>
        </a>
    </div>
    <?php if (count($list) > 0) { ?>
        <div class="log20_list_pclist_area" data-item-count="<?php echo count($list); ?>">
            <?php
            $item_index = 0;
            foreach ($list as $item) {
                $item_index++;
                $pclist_title_color = $item['wr_21']; // pclist 제목색
                $pclist_bg_color = $item['wr_22']; // pclist 배경색
                $pclist_add_color = isset($item['wr_44']) ? $item['wr_44'] : ''; // pclist 추가색
                $pclist_subtitle = $item['wr_3']; // pclist 부제 (별도 변수)
                $wr_7 = $item['wr_7'];
                $wr_8 = $item['wr_8'];
                $wr_9 = $item['wr_9'];
                $wr_10 = $item['wr_10'];
                
                // 썸네일 이미지 가져오기
                include_once(G5_LIB_PATH.'/thumbnail.lib.php');
                $thumb = get_list_thumbnail($current_bo_table, $item['wr_id'], 500, 500, false, true);
                $img_url = '';
                $img_type = '';
                $fa_icon = '';
                
                // 이미지 우선순위: 첨부파일 > URL 
                if ($thumb['src']) {
                    $img_url = $thumb['src'];
                    $img_type = 'file';
                } elseif ($wr_7 && trim($wr_7) !== '') {
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
            <?php
                $item_mb_id = isset($item['mb_id']) ? $item['mb_id'] : '';
                $current_member_id = isset($member['mb_id']) ? $member['mb_id'] : '';
                $can_manage_item = false;
                if ($is_admin) {
                    $can_manage_item = true;
                } elseif ($current_member_id && $item_mb_id && $current_member_id === $item_mb_id) {
                    $can_manage_item = true;
                }
                $update_url = G5_BBS_URL . '/write.php?bo_table=' . $current_bo_table . '&w=u&wr_id=' . $item['wr_id'] . '&write_type=pclist';
                if ($parent_wr_id) {
                    $update_url .= '&wr_parent=' . $parent_wr_id;
                }
                $delete_url = $board_skin_url . '/delete.sublist.skin.php?bo_table=' . $current_bo_table . '&wr_id=' . $item['wr_id'] . '&write_type=pclist';
                if ($parent_wr_id) {
                    $delete_url .= '&wr_parent=' . $parent_wr_id;
                }
            ?>
            <div class="log20_list_pclist_item" data-wr-id="<?php echo $item['wr_id']; ?>" data-item-index="<?php echo $item_index; ?>" data-mb-id="<?php echo htmlspecialchars($item['mb_id']); ?>" style="--bg-color: <?php echo $pclist_bg_color ? htmlspecialchars($pclist_bg_color) : 'transparent'; ?>; --pclist-title-color: <?php echo htmlspecialchars($pclist_title_color ?: '#000000'); ?>;">
                <div class="log20_list_pclist_link" style="cursor: pointer;">
                    <div class="log20_list_pclist_title" style="color: <?php echo $pclist_title_color ? htmlspecialchars($pclist_title_color) : '#000'; ?>; background-color: <?php echo $pclist_bg_color ? htmlspecialchars($pclist_bg_color) : 'transparent'; ?>;">
                        <?php echo get_text(cut_str($item['wr_subject'], 50)) ?>
                    </div>
                    <div class="log20_list_pclist_image"<?php 
                        $image_bg_style = '';
                        if ($pclist_add_color && trim($pclist_add_color) !== '') {
                            $image_bg_style = 'background-color: ' . htmlspecialchars($pclist_add_color) . ';';
                        } elseif ($img_type == 'fa' && $wr_10) {
                            $image_bg_style = 'background-color: ' . htmlspecialchars($wr_10) . ';';
                        }
                        if ($image_bg_style) {
                            echo ' style="' . $image_bg_style . '"';
                        }
                    ?>>
                        <?php if ($pclist_subtitle) { ?>
                            <div class="log20_list_pclist_subtitle" style="color: <?php echo $pclist_title_color ? htmlspecialchars($pclist_title_color) : '#000'; ?>;">
                                <?php echo get_text($pclist_subtitle) ?>
                            </div>
                        <?php } ?>
                        <?php if ($img_type == 'fa' && $fa_icon) { ?>
                            <i class="fa-solid fa-<?php echo htmlspecialchars($fa_icon) ?>" style="color: <?php echo $wr_9 ? htmlspecialchars($wr_9) : '#000000'; ?>;"></i>
                        <?php } else { ?>
                            <img src="<?php echo $img_url ?>" alt="<?php echo get_text($item['wr_subject']) ?>">
                        <?php } ?>
                    </div>
                </div>
                <?php if ($can_manage_item) { ?>
                <div class="log20_subitem_actions">
                    <a href="<?php echo $update_url; ?>" class="log20_subitem_btn">수정</a>
                    <a href="<?php echo $delete_url; ?>" class="log20_subitem_btn log20_subitem_btn--delete" onclick="return confirm('정말로 삭제하시겠습니까?');">삭제</a>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
            <!-- 동적 뷰 영역 -->
            <div class="log20_pclist_view_container"></div>
        </div>
        
        <?php if ($total_page > 1) { ?>
        <div class="paginate_wrap">
            <?php
            $paging_html = '';
            $start_page = max(1, $page - 2);
            $end_page = min($total_page, $page + 2);
            
            if ($page > 1) {
                $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'pclist\', ' . ($page - 1) . ')" class="pg_page">이전</a>';
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $page) {
                    $paging_html .= '<strong class="pg_current">' . $i . '</strong>';
                } else {
                    $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'pclist\', ' . $i . ')" class="pg_page">' . $i . '</a>';
                }
            }
            
            if ($page < $total_page) {
                $paging_html .= '<a href="javascript:void(0);" onclick="loadSublist(\'pclist\', ' . ($page + 1) . ')" class="pg_page">다음</a>';
            }
            
            echo $paging_html;
            ?>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="log20_empty">
            <p>게시물이 없습니다.</p>
        </div>
    <?php } ?>
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

jQuery(document).ready(function($) {
    var currentBoTable = '<?php echo $current_bo_table; ?>';
    var activeViewWrId = null;
    var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
    var currentMemberId = '<?php echo isset($member['mb_id']) ? htmlspecialchars($member['mb_id'], ENT_QUOTES) : ''; ?>';
    
    // pclist 아이템 클릭 이벤트
    $(document).on('click', '.log20_list_pclist_link', function(e) {
        e.preventDefault();
        var $item = $(this).closest('.log20_list_pclist_item');
        var wrId = $item.data('wr-id');
        var itemIndex = $item.data('item-index');
        var $area = $item.closest('.log20_list_pclist_area');
        var itemCount = parseInt($area.data('item-count')) || 1;
        var $container = $area.find('.log20_pclist_view_container');
        var $existingView = $container.find('.log20_pclist_view_item[data-wr-id="' + wrId + '"]');
        
        // 같은 아이템을 다시 클릭하면 닫기 
        if ($existingView.length > 0) {
            cleanupContentBodyObservers($existingView);
            $existingView.remove();
            if (activeViewWrId === wrId) {
                activeViewWrId = null;
            }
            updateViewLayout($area, itemCount);
            adjustSublistHeight();
            return;
        }
        
        // 다른 아이템을 클릭했을 때는 기존 뷰 유지하고 새 뷰 추가
        activeViewWrId = wrId;
        
        // 로딩 표시
        var $viewItem = $('<div class="log20_pclist_view_item" data-wr-id="' + wrId + '" data-item-index="' + itemIndex + '"><div class="log20_pclist_view_loading">로딩 중...</div></div>');
        $container.append($viewItem);
        updateViewLayout($area, itemCount);
        
        // AJAX로 뷰 로드
        $.ajax({
            url: '<?php echo G5_BBS_URL; ?>/board.php',
            type: 'GET',
            data: {
                bo_table: currentBoTable,
                wr_id: wrId,
                write_type: 'pclist'
            },
            dataType: 'html',
            success: function(response) {
                var $html = $('<div>').html(response);
                var $viewContent = $html.find('#bo_v').first();
                if ($viewContent.length === 0) {
                    $viewContent = $html.find('.view_article').first();
                }
                if ($viewContent.length === 0) {
                    $viewContent = $html.find('.pclist_view_content').first();
                }
                if ($viewContent.length === 0) {
                    $viewContent = $html.find('article').first();
                }
                if ($viewContent.length === 0) {
                    var $body = $html.find('body');
                    if ($body.length > 0) {
                        $viewContent = $body.find('#bo_v').first();
                        if ($viewContent.length === 0) {
                            $viewContent = $body.find('.view_article').first();
                        }
                        if ($viewContent.length === 0) {
                            $viewContent = $body.find('.pclist_view_content').first();
                        }
                        if ($viewContent.length === 0) {
                            $viewContent = $body.find('article').first();
                        }
                    }
                }
                // 전체 HTML에서 직접 찾기
                if ($viewContent.length === 0) {
                    var htmlStr = response;
                    var boVMatch = htmlStr.match(/<article[^>]*id=["']bo_v["'][^>]*>[\s\S]*?<\/article>/i);
                    if (boVMatch) {
                        $viewContent = $(boVMatch[0]);
                    }
                }
                
                if ($viewContent.length > 0) {
                    var $buttonContainer = $('<div class="log20_pclist_view_actions"></div>');
                    var parentWrId = <?php echo $parent_wr_id; ?>;
                    
                    // 작성자 정보 가져오기
                    var itemMbId = $item.data('mb-id') || '';
                    var canManage = false;
                    
                    // 관리자이거나 작성자 본인인 경우에만 수정/삭제 버튼 표시
                    if (isAdmin) {
                        canManage = true;
                    } else if (currentMemberId && itemMbId && currentMemberId === itemMbId) {
                        canManage = true;
                    }
                    
                    // 수정/삭제 버튼은 관리자 또는 작성자에게만 표시
                    if (canManage) {
                        // 수정 버튼 생성
                        var updateUrl = '<?php echo G5_BBS_URL; ?>/write.php?bo_table=' + currentBoTable + '&w=u&wr_id=' + wrId + '&write_type=pclist';
                        if (parentWrId > 0) {
                            updateUrl += '&wr_parent=' + parentWrId;
                        }
                        var $updateBtn = $('<a href="' + updateUrl + '" class="log20_pclist_view_action_text" title="수정">수정</a>');
                        $buttonContainer.append($updateBtn);
                        
                        // 삭제 버튼 생성
                        var deleteUrl = '<?php echo $board_skin_url; ?>/delete.sublist.skin.php?bo_table=' + currentBoTable + '&wr_id=' + wrId + '&write_type=pclist';
                        if (parentWrId > 0) {
                            deleteUrl += '&wr_parent=' + parentWrId;
                        }
                        var $deleteBtn = $('<a href="' + deleteUrl + '" class="log20_pclist_view_action_text" title="삭제" onclick="return confirm(\'정말로 삭제하시겠습니까? 삭제 후에는 복구할 수 없습니다.\');">삭제</a>');
                        $buttonContainer.append($deleteBtn);
                    }
                    
                    // 끄기 버튼 생성 (모든 사용자에게 표시)
                    var $closeBtn = $('<a href="javascript:void(0);" class="log20_pclist_view_action_text" title="끄기">끄기</a>');
                    $closeBtn.on('click', function(e) {
                        e.preventDefault();
                        cleanupContentBodyObservers($viewItem);
                        $viewItem.remove();
                        if (activeViewWrId === wrId) {
                            activeViewWrId = null;
                        }
                        updateViewLayout($area, itemCount);
                        adjustSublistHeight();
                    });
                    $buttonContainer.append($closeBtn);
                    
                    // 버튼 컨테이너와 콘텐츠 추가
                    $viewItem.html($buttonContainer).append($viewContent);
                } else {
                    $viewItem.html('<div class="log20_pclist_view_error">내용을 불러올 수 없습니다.</div>');
                }
                
                updateViewLayout($area, itemCount);
                // 2명이 아닐 때 pclist_view_etc 레이아웃 조정
                if (itemCount !== 2) {
                    adjustPclistViewLayout($viewItem);
                }
                // 높이 조절을 위해 약간의 지연 후 실행
                setTimeout(function() {
                    adjustSublistHeight();
                    // pclist_view_content_body의 높이 변화 감지 시작
                    observeContentBodyHeight($viewItem);
                }, 100);
                
                // 이미지 로드 완료 후에도 높이 재계산
                $viewItem.find('img').on('load', function() {
                    adjustSublistHeight();
                });
            },
            error: function(xhr, status, error) {
                $viewItem.html('<div class="log20_pclist_view_error">오류가 발생했습니다: ' + error + '</div>');
                adjustSublistHeight();
            }
        });
    });
    
    // 뷰 레이아웃 업데이트 함수
    function updateViewLayout($area, itemCount) {
        var $container = $area.find('.log20_pclist_view_container');
        var $viewItems = $container.find('.log20_pclist_view_item');
        
        $viewItems.each(function(index) {
            var $item = $(this);
            var itemIndex = parseInt($item.data('item-index')) || (index + 1);

            $item.removeClass('log20_pclist_view_left log20_pclist_view_right');
            
            if (itemCount === 2) {
                if (itemIndex === 1) {
                    $item.addClass('log20_pclist_view_left');
                } else if (itemIndex === 2) {
                    $item.addClass('log20_pclist_view_right');
                }
            } else {
                // 다른 개수일 때는 width 100%, 중앙 정렬
                $item.css({
                    'width': '100%',
                    'max-width': '100%',
                    'margin-left': 'auto',
                    'margin-right': 'auto'
                });
            }
        });
    }
    
    // 2명이 아닐 때 pclist_view_etc 레이아웃 조정 함수
    function adjustPclistViewLayout($viewItem) {
        var $titleArea = $viewItem.find('.pclist_view_title_area');
        var $colorBoxes = $viewItem.find('.pclist_view_color_boxes');
        var $etc = $viewItem.find('.pclist_view_etc');
        
        if ($titleArea.length > 0 && $colorBoxes.length > 0 && $etc.length > 0) {
            $etc.detach();
            $colorBoxes.after($etc);
        }
    }
    
    // log20_content_sublist 높이 조절 함수
    function adjustSublistHeight() {
        var $sublist = $('#log20_content_sublist');
        if ($sublist.length > 0) {
            var originalHeight = $sublist.css('height');
            $sublist.css('height', 'auto');
            
            var actualContentHeight = $sublist[0].scrollHeight;
            var paddingTop = parseInt($sublist.css('padding-top')) || 5;
            var paddingBottom = parseInt($sublist.css('padding-bottom')) || 20;
            var totalPadding = paddingTop + paddingBottom;
            var maxContainerBottom = 0;
            
            $sublist.find('.log20_pclist_view_container').each(function() {
                var $container = $(this);
                if ($container.is(':visible')) {
                    var containerHeight = $container[0].offsetHeight || $container[0].scrollHeight;
                    var marginTop = parseInt($container.css('margin-top')) || 0;
                    var marginBottom = parseInt($container.css('margin-bottom')) || 0;
                    var containerAbsoluteTop = $container[0].offsetTop;
                    var sublistAbsoluteTop = $sublist[0].offsetTop;
                    var containerRelativeTop = containerAbsoluteTop - sublistAbsoluteTop;
                    var containerBottom = containerRelativeTop + containerHeight + marginBottom;
                    if (containerBottom > maxContainerBottom) {
                        maxContainerBottom = containerBottom;
                    }
                }
            });
            

            var minHeight = parseInt($sublist.css('min-height')) || 300;

            var targetHeight;
            if (maxContainerBottom > 0) {
                targetHeight = maxContainerBottom + paddingBottom + 15;
            } else {
                targetHeight = actualContentHeight;
            }
            

            var finalHeight = Math.max(minHeight, targetHeight);
            if (actualContentHeight > finalHeight) {
                finalHeight = actualContentHeight;
            }
            
            $sublist.css('height', finalHeight + 'px');
        }
    }
    
    // pclist_view_content_body의 높이 변화 감지 및 log20_content_sublist 높이 조절
    var contentBodyObservers = []; 
    
    function observeContentBodyHeight($viewItem) {
        var $contentBody = $viewItem.find('.pclist_view_content_body');
        var $container = $viewItem.closest('.log20_pclist_view_container');
        
        if ($contentBody.length === 0 && $container.length === 0) {
            return;
        }
        
        var observerData = {
            viewItem: $viewItem,
            contentBody: $contentBody.length > 0 ? $contentBody[0] : null,
            container: $container.length > 0 ? $container[0] : null
        };
        
        if (typeof ResizeObserver !== 'undefined') {
            if ($contentBody.length > 0) {
                var resizeObserver = new ResizeObserver(function(entries) {
                    adjustSublistHeight();
                });
                
                resizeObserver.observe($contentBody[0]);
                observerData.resizeObserver = resizeObserver;
            }
            
            if ($container.length > 0) {
                var containerResizeObserver = new ResizeObserver(function(entries) {
                    adjustSublistHeight();
                });
                
                containerResizeObserver.observe($container[0]);
                observerData.containerResizeObserver = containerResizeObserver;
            }
        }

        if ($contentBody.length > 0) {
            var mutationObserver = new MutationObserver(function(mutations) {
                setTimeout(function() {
                    adjustSublistHeight();
                }, 50);
            });
            
            mutationObserver.observe($contentBody[0], {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true,
                characterData: true
            });
            observerData.mutationObserver = mutationObserver;
        }
        

        if ($container.length > 0) {
            var containerMutationObserver = new MutationObserver(function(mutations) {
                setTimeout(function() {
                    adjustSublistHeight();
                }, 50);
            });
            
            containerMutationObserver.observe($container[0], {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true,
                characterData: true
            });
            observerData.containerMutationObserver = containerMutationObserver;
        }
        
        // observer 정보 저장
        contentBodyObservers.push(observerData);
        
        // 접기/펼치기 버튼 클릭 이벤트 감지
        if ($contentBody.length > 0) {
            $contentBody.on('click.pclistHeight', '[class*="collapse"], [class*="expand"], [class*="toggle"], [id*="collapse"], [id*="expand"]', function() {
                setTimeout(function() {
                    adjustSublistHeight();
                }, 200);
            });

            $contentBody.on('click.pclistHeight', 'a, button', function() {
                var $target = $(this);
                var href = $target.attr('href') || '';
                var onclick = $target.attr('onclick') || '';

                if (href.indexOf('collapse') !== -1 || href.indexOf('expand') !== -1 || 
                    onclick.indexOf('collapse') !== -1 || onclick.indexOf('expand') !== -1 ||
                    $target.hasClass('btn_collapse') || $target.hasClass('btn_expand')) {
                    setTimeout(function() {
                        adjustSublistHeight();
                    }, 200);
                }
            });
        }
    }
    
    // 뷰가 제거될 때 observer 정리
    function cleanupContentBodyObservers($removedViewItem) {
        contentBodyObservers = contentBodyObservers.filter(function(observerData) {
            var shouldKeep = !$removedViewItem.is(observerData.viewItem) && 
                            !$removedViewItem.find(observerData.viewItem).length;
            
            if (!shouldKeep) {
                if (observerData.resizeObserver) {
                    observerData.resizeObserver.disconnect();
                }
                if (observerData.containerResizeObserver) {
                    observerData.containerResizeObserver.disconnect();
                }
                if (observerData.mutationObserver) {
                    observerData.mutationObserver.disconnect();
                }
                if (observerData.containerMutationObserver) {
                    observerData.containerMutationObserver.disconnect();
                }
                if (observerData.contentBody) {
                    $(observerData.contentBody).off('click.pclistHeight');
                }
            }
            
            return shouldKeep;
        });
    }
    
    // 전역 함수로 등록
    window.updatePclistViewLayout = function() {
        $('.log20_list_pclist_area').each(function() {
            var $area = $(this);
            var itemCount = parseInt($area.data('item-count')) || 1;
            updateViewLayout($area, itemCount);
        });
    };
});
</script>


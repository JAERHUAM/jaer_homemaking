<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';
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
            <li>
                <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&sublist=tarae"
                   class="btn_b01 btn"
                   title="새로고침"
                   style="background-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          border-color: <?php echo htmlspecialchars($btn_bg_color, ENT_QUOTES); ?>;
                          color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;">
                    <i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"
                       style="color: <?php echo htmlspecialchars($btn_icon_color, ENT_QUOTES); ?>;"></i>
                    <span class="sound_only">새로고침</span>
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
                <a href="<?php echo $board_skin_url; ?>/delete.parent.skin.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>"
                   class="btn_b01 btn"
                   title="삭제"
                   onclick="if(!confirm('모든 타래가 삭제됩니다. 확인하셨나요?')) return false; del(this.href); return false;"
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
        $wr_5 = isset($view['wr_5']) ? $view['wr_5'] : ''; // GM
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
        <!-- log20_content_sublist_title 영역 시작 { -->
        <?php
        // log20_content_sublist_title 스타일 설정
        $sublist_title_style = '';
        if ($wr_1) {
            $sublist_title_style .= 'background-color: ' . htmlspecialchars($wr_1) . '; ';
            $sublist_title_style .= 'border-color: ' . htmlspecialchars($wr_1) . '; ';
        }
        ?>
        <div class="log20_content_sublist_title"<?php if ($sublist_title_style) { ?> style="<?php echo trim($sublist_title_style); ?>"<?php } ?>>
            <span class="log20_content_sublist_title_text" style="color: <?php echo $wr_2 ? htmlspecialchars($wr_2) : '#fff'; ?>;"><?php echo get_text($view['wr_subject']); ?></span>
        </div>
        <!-- } log20_content_sublist_title 영역 끝 -->
        
        <!-- log20_content_sublist 영역 시작 { -->
        <?php
        // 현재 선택된 서브리스트 타입 (기본값: tarae)
        $sublist_type = isset($_GET['sublist']) ? clean_xss_tags($_GET['sublist']) : 'tarae';
        if ($sublist_type !== 'tarae') {
            $sublist_type = 'tarae';
        }
        
        // log20_content_sublist 스타일 설정
        $sublist_style = 'background-color: #ffffff; ';
        if ($wr_1) {
            $sublist_style .= 'border: 1px solid ' . htmlspecialchars($wr_1) . '; ';
            $sublist_style .= 'border-top: none; ';
        }
        ?>
        <div class="log20_content_sublist" id="log20_content_sublist"<?php if ($sublist_style) { ?> style="<?php echo trim($sublist_style); ?>"<?php } ?>>
            <?php
            
            // 스킨 파일 선택
        $skin_file = $board_skin_path . '/list.tarae.skin.php';
            
            if (file_exists($skin_file)) {
                // 부모 게시물 ID 설정 (자식 게시물 필터링용)
                $parent_wr_id = isset($wr_id) ? $wr_id : 0;
                include($skin_file);
            } else {
                echo '<div class="log20_empty"><p>게시판을 불러올 수 없습니다.</p></div>';
            }
            ?>
        </div>
        <!-- } log20_content_sublist 영역 끝 -->
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
    
    // 메뉴 버튼 클릭 시 텍스트 변경 및 게시판 로드
    $(".log_content_menu_btn").on("click", function() {
        var menuType = $(this).data("menu");
        
        var titleText = "";
        var sublistType = "";
        
        switch(menuType) {
            case "memo":
                titleText = "메모";
                sublistType = "tarae";
                break;
            default:
                titleText = "메모";
                sublistType = "tarae";
        }
        
        $(".log20_content_sublist_title_text").text(titleText);
        
        // 게시판 로드
        loadSublist(sublistType, 1);
    });
    
    // 게시판 로드 함수
    function loadSublist(sublistType, page) {
        var pageParam = "";
        
        pageParam = "tarae_page";
        
        // AJAX로 게시판 로드
        var url = "<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&sublist=" + sublistType + "&" + pageParam + "=" + page;
        
        $.ajax({
            url: url,
            type: "GET",
            dataType: "html",
            beforeSend: function() {
                $("#log20_content_sublist").html('<div class="log20_loading">로딩 중...</div>');
            },
            success: function(data) {
                // 응답에서 log20_content_sublist 영역만 추출
                var $response = $('<div>').html(data);
                var sublistContent = $response.find("#log20_content_sublist").html();
                if (sublistContent) {
                    // jQuery .html()은 script 태그를 제거하므로, 스크립트를 플레이스홀더로 치환 후 원래 위치에 복원
                    var scriptTags = [];
                    sublistContent = sublistContent.replace(/<script\s[^>]*>[\s\S]*?<\/script>\s*/gi, function(match) {
                        scriptTags.push(match);
                        return '<!--RA0_SCRIPT_' + (scriptTags.length - 1) + '-->';
                    });
                    sublistContent = sublistContent.replace(/<script>[\s\S]*?<\/script>\s*/gi, function(match) {
                        scriptTags.push(match);
                        return '<!--RA0_SCRIPT_' + (scriptTags.length - 1) + '-->';
                    });
                    $("#log20_content_sublist").html(sublistContent);

                    // 플레이스홀더를 실제 script 노드로 교체 (위치 유지 → Buy Me a Coffee 등 버튼이 본문 옆에 표시)
                    var container = document.getElementById('log20_content_sublist');
                    if (container) {
                        var walker = document.createTreeWalker(container, NodeFilter.SHOW_COMMENT, null, false);
                        var comments = [];
                        while (walker.nextNode()) comments.push(walker.currentNode);
                        comments.forEach(function(comment) {
                            var m = comment.textContent.match(/^RA0_SCRIPT_(\d+)$/);
                            if (m) {
                                var idx = parseInt(m[1], 10);
                                var scriptHtml = scriptTags[idx];
                                if (scriptHtml) {
                                    var temp = document.createElement('div');
                                    temp.innerHTML = scriptHtml;
                                    var scriptEl = temp.querySelector('script');
                                    if (scriptEl) {
                                        comment.parentNode.insertBefore(scriptEl, comment);
                                        comment.parentNode.removeChild(comment);
                                    }
                                }
                            }
                        });
                    }
                } else {
                    $("#log20_content_sublist").html('<div class="log20_empty"><p>게시판을 불러올 수 없습니다.</p></div>');
                }
                
                // URL 업데이트 (히스토리 추가, 페이지 새로고침 없이)
                var newUrl = window.location.pathname + "?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&sublist=" + sublistType + "&" + pageParam + "=" + page;
                window.history.pushState({sublist: sublistType, page: page}, '', newUrl);
            },
            error: function(xhr, status, error) {
                $("#log20_content_sublist").html('<div class="log20_empty"><p>게시판을 불러오는 중 오류가 발생했습니다.</p></div>');
            }
        });
    }
});
</script>
<!-- } 게시글 읽기 끝 -->


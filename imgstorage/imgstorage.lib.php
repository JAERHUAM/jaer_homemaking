<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 관리자 외 이미지 드래그 방지용 속성 (draggable + ondragstart)
 * @param bool $is_admin 관리자 여부
 * @return string ' draggable="false" ondragstart="return false;"' 또는 ''
 */
function imgstorage_drag_disabled_attr($is_admin) {
    return $is_admin ? '' : ' draggable="false" ondragstart="return false;"';
}

/**
 * (관리자 외) 이미지 우클릭·드래그·드롭 방지 스크립트 출력
 * - dragstart: 이미지 드래그 시작 차단
 * - drop/dragover: 입력 필드에 이미지 드롭 시 URL 유출 차단
 * @param bool $is_admin 관리자 여부 (gnuboard $is_admin)
 * @param string|array $selectors 적용 대상: 'body' 또는 선택자 배열 ['.imgstorage_list_area','#imgstorage_view_modal']
 * @param bool $also_contextmenu 우클릭 차단 여부 (view=true, list=false)
 */
function imgstorage_print_drag_protection_script($is_admin, $selectors = 'body', $also_contextmenu = false) {
    if ($is_admin) return;
    $sel = is_array($selectors) ? $selectors : [$selectors];
    $sel_js = json_encode($sel);
    $ctx = $also_contextmenu ? 'true' : 'false';
    $scope = json_encode(['#bo_list','.imgstor_container','#imgstorage_edit_modal']);
    echo '<script>(function(){'.
        'var s='.$sel_js.',c='.$ctx.',scope='.$scope.';'.
        'function pr(e){e.preventDefault();e.stopPropagation();}'.
        's.forEach(function(q){var el=(q==="body")?document.body:document.querySelector(q);'.
        'if(el){el.addEventListener("dragstart",pr,true);if(c)el.addEventListener("contextmenu",pr);}});'.
        'function inScope(el){if(!el)return false;for(var i=0;i<scope.length;i++){var c=document.querySelector(scope[i]);if(c&&c.contains(el))return true;}return false;}'.
        '["input","textarea"].forEach(function(tag){'.
        'document.addEventListener("dragover",function(e){var t=e.target;if(t&&t.tagName===tag.toUpperCase()&&inScope(t))pr(e);},true);'.
        'document.addEventListener("drop",function(e){var t=e.target;if(t&&t.tagName===tag.toUpperCase()&&inScope(t))pr(e);},true);'.
        '});'.
    '})();</script>';
}

/**
 * imgstorage 목록용 설정·권한 반환
 * @param array $board 게시판 설정
 * @param array $member 회원 정보
 * @param bool $is_member 로그인 여부
 * @param int $subject_len 제목 길이 (기본 60)
 * @return array gallery_width, gallery_height, subject_len, can_download, can_write
 */
function imgstorage_get_list_config($board, $member, $is_member, $subject_len = 60) {
    $gallery_width = isset($board['bo_gallery_width']) && $board['bo_gallery_width'] > 0 ? (int)$board['bo_gallery_width'] : 300;
    $gallery_height = isset($board['bo_gallery_height']) && $board['bo_gallery_height'] > 0 ? (int)$board['bo_gallery_height'] : 300;
    $can_download = $is_member && (int)$member['mb_level'] >= (int)$board['bo_download_level'];
    $can_write = (int)($member['mb_level'] ?? 0) >= (int)$board['bo_write_level'];
    return [
        'gallery_width' => $gallery_width,
        'gallery_height' => $gallery_height,
        'subject_len' => (int)$subject_len,
        'can_download' => $can_download,
        'can_write' => $can_write,
    ];
}

/**
 * imgstorage 분류 버튼 HTML 생성 (taraebi 방식)
 * @param array $board 게시판 설정
 * @param string $bo_table 게시판 테이블명
 * @param string $sca 현재 선택 분류
 * @return array html, has_buttons
 */
function imgstorage_get_category_buttons($board, $bo_table, $sca) {
    $html = '';
    $has_buttons = false;
    if (empty($board['bo_use_category']) || empty($board['bo_category_list']) || trim($board['bo_category_list']) === '') {
        return ['html' => $html, 'has_buttons' => $has_buttons];
    }
    $category_href = G5_BBS_URL . '/board.php?bo_table=' . $bo_table;
    $categories = array_filter(array_map('trim', explode('|', $board['bo_category_list'])));
    $all_active = (!isset($sca) || $sca === '');
    $all_class = $all_active ? 'is-active' : '';
    $html .= '<a href="' . $category_href . '" class="imgstorage_category_btn ' . $all_class . '">전체</a>';
    foreach ($categories as $category) {
        if ($category === '') continue;
        $is_active = (isset($sca) && $category === $sca);
        $href = $is_active ? $category_href : $category_href . '&amp;sca=' . urlencode($category);
        $class = $is_active ? 'is-active' : '';
        $html .= '<a href="' . $href . '" class="imgstorage_category_btn ' . $class . '">' . get_text($category) . '</a>';
    }
    $has_buttons = true;
    return ['html' => $html, 'has_buttons' => $has_buttons];
}

/**
 * imgstorage 게시판: 제목 50자, 부제 280자 제한
 */
function imgstorage_truncate_write_fields(&$wr_subject, &$wr_3) {
    $wr_subject = mb_substr(trim($wr_subject), 0, 50);
    $wr_3 = mb_substr(trim($wr_3), 0, 280);
}

/**
 * 부제 [글자](url) 형식을 하이퍼링크로 변환
 */
function imgstorage_parse_subtitle_links($text) {
    if (trim($text) === '') return '';
    $text = get_text($text);
    $html = preg_replace_callback(
        '/\[([^\]]*)\]\(([^)\s]+)\)/u',
        function($m) {
            $link_text = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $url = trim($m[2]);
            if (preg_match('#^(https?://|/)#i', $url)) {
                $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                return '<a href="'.$url.'" class="imgstorage_explain_subtitle_link" target="_blank" rel="noopener noreferrer">'.$link_text.'</a>';
            }
            return htmlspecialchars($m[0], ENT_QUOTES, 'UTF-8');
        },
        $text
    );
    return $html;
}

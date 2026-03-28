<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 목록 페이지로 리다이렉트
$list_url = G5_BBS_URL.'/board.php?bo_table='.$bo_table;
if (isset($sca) && $sca) {
    $list_url .= '&sca='.urlencode($sca);
}
if (isset($page) && $page > 1) {
    $list_url .= '&page='.$page;
}

goto_url($list_url);
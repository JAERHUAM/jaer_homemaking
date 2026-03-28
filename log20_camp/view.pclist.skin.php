<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';

$parent_wr_1 = isset($wr_1) ? $wr_1 : '';
$parent_wr_2 = isset($wr_2) ? $wr_2 : '';
$action_color = !empty($parent_wr_1) ? $parent_wr_1 : '#333';
$accent_color = !empty($parent_wr_2) ? $parent_wr_2 : '#666';
?>

<!-- 게시물 읽기 시작 { -->
<article id="bo_v" class="view_article" style="width:<?php echo $width; ?>">
    <div class="pclist_view_content">
        <?php
        // 게시물의 추가 필드에서 데이터 가져오기
        $wr_3 = isset($view['wr_3']) ? $view['wr_3'] : ''; // 진명/코드네임 등
        $wr_7 = isset($view['wr_7']) ? $view['wr_7'] : ''; // 썸네일 이미지 URL
        $wr_8 = isset($view['wr_8']) ? $view['wr_8'] : ''; // FA 아이콘
        $wr_9 = isset($view['wr_9']) ? $view['wr_9'] : ''; // FA 색
        $wr_10 = isset($view['wr_10']) ? $view['wr_10'] : ''; // 배경 색
        $wr_21 = isset($view['wr_21']) ? $view['wr_21'] : ''; // 제목색
        $wr_22 = isset($view['wr_22']) ? $view['wr_22'] : ''; // 배경색
        $wr_44 = isset($view['wr_44']) ? $view['wr_44'] : ''; // 추가색
        $wr_23 = isset($view['wr_23']) ? $view['wr_23'] : ''; // 키워드1
        $wr_24 = isset($view['wr_24']) ? $view['wr_24'] : ''; // 키워드2
        $wr_25 = isset($view['wr_25']) ? $view['wr_25'] : ''; // 키워드3
        $wr_26 = isset($view['wr_26']) ? $view['wr_26'] : ''; // 기타 주제1
        $wr_27 = isset($view['wr_27']) ? $view['wr_27'] : ''; // 기타 주제2
        $wr_28 = isset($view['wr_28']) ? $view['wr_28'] : ''; // 기타 주제3
        $wr_29 = isset($view['wr_29']) ? $view['wr_29'] : ''; // 기타 주제4
        $wr_30 = isset($view['wr_30']) ? $view['wr_30'] : ''; // 기타 주제5
        $wr_31 = isset($view['wr_31']) ? $view['wr_31'] : ''; // 기타 주제6
        $wr_32 = isset($view['wr_32']) ? $view['wr_32'] : ''; // 기타 주제7
        $wr_33 = isset($view['wr_33']) ? $view['wr_33'] : ''; // 기타 주제8
        $wr_34 = isset($view['wr_34']) ? $view['wr_34'] : ''; // 기타 내용1
        $wr_35 = isset($view['wr_35']) ? $view['wr_35'] : ''; // 기타 내용2
        $wr_36 = isset($view['wr_36']) ? $view['wr_36'] : ''; // 기타 내용3
        $wr_37 = isset($view['wr_37']) ? $view['wr_37'] : ''; // 기타 내용4
        $wr_40 = isset($view['wr_40']) ? $view['wr_40'] : ''; // 기타 내용5
        $wr_41 = isset($view['wr_41']) ? $view['wr_41'] : ''; // 기타 내용6
        $wr_42 = isset($view['wr_42']) ? $view['wr_42'] : ''; // 기타 내용7
        $wr_43 = isset($view['wr_43']) ? $view['wr_43'] : ''; // 기타 내용8
        $wr_38 = isset($view['wr_38']) ? $view['wr_38'] : ''; // 한마디
        $wr_39 = isset($view['wr_39']) ? $view['wr_39'] : ''; // 메인 이미지

        // 메인 이미지 가져오기
        $main_img_url = '';
        if ($wr_39 && trim($wr_39) !== '') {
            $wr_39_trimmed = trim($wr_39);
            if (filter_var($wr_39_trimmed, FILTER_VALIDATE_URL) || preg_match('/^\/[^\/]/', $wr_39_trimmed) || strpos($wr_39_trimmed, G5_DATA_URL) === 0) {
                $main_img_url = $wr_39_trimmed;
            }
        }
        ?>

        <!-- 최상단: 한마디 -->
        <?php if ($wr_38) { ?>
        <div class="pclist_view_oneline">
            "<?php echo get_text($wr_38) ?>"
        </div>
        <?php } ?>

        <!-- 상단 영역: 메인 이미지와 제목 -->
        <div class="pclist_view_header">
            <!-- 좌측: 메인 이미지 -->
            <div class="pclist_view_main_image">
                <div class="pclist_view_main_image_wrapper">
                    <?php if ($main_img_url) { ?>
                        <img src="<?php echo htmlspecialchars($main_img_url) ?>" alt="<?php echo get_text($view['wr_subject']) ?>" class="pclist_view_main_image_img">
                    <?php } else { ?>
                        <div class="pclist_view_main_image_empty">메인 이미지 없음</div>
                    <?php } ?>
                </div>
            </div>

            <!-- 우측: 제목 영역 -->
            <div class="pclist_view_title_area">
                <?php if ($wr_3) { ?>
                    <div class="pclist_view_subtitle">
                        <?php echo get_text($wr_3) ?>
                    </div>
                <?php } ?>
                <h2 class="pclist_view_title"<?php if ($wr_21) { ?> style="border-top-color: <?php echo htmlspecialchars($wr_21); ?>; border-bottom-color: <?php echo htmlspecialchars($wr_21); ?>;"<?php } ?>>
                    <?php echo get_text($view['wr_subject']) ?>
                </h2>
                <!-- 키워드 -->
                <?php if ($wr_23 || $wr_24 || $wr_25) { 
                    $keyword_style = '';
                    if ($wr_22) {
                        $keyword_style .= 'background-color: ' . htmlspecialchars($wr_22) . '; ';
                    }
                    if ($wr_21) {
                        $keyword_style .= 'color: ' . htmlspecialchars($wr_21) . '; ';
                    }
                ?>
                <div class="pclist_view_keywords">
                    <?php if ($wr_23) { ?>
                        <div class="pclist_view_keyword"<?php if ($keyword_style) { ?> style="<?php echo trim($keyword_style); ?>"<?php } ?>><?php echo get_text($wr_23) ?></div>
                    <?php } ?>
                    <?php if ($wr_24) { ?>
                        <div class="pclist_view_keyword"<?php if ($keyword_style) { ?> style="<?php echo trim($keyword_style); ?>"<?php } ?>><?php echo get_text($wr_24) ?></div>
                    <?php } ?>
                    <?php if ($wr_25) { ?>
                        <div class="pclist_view_keyword"<?php if ($keyword_style) { ?> style="<?php echo trim($keyword_style); ?>"<?php } ?>><?php echo get_text($wr_25) ?></div>
                    <?php } ?>
                </div>
                <!-- 색상 사각형 -->
                <div class="pclist_view_color_boxes">
                    <div class="pclist_view_color_box"<?php if ($wr_21) { ?> style="background-color: <?php echo htmlspecialchars($wr_21); ?>;"<?php } ?>></div>
                    <div class="pclist_view_color_box"<?php if ($wr_22) { ?> style="background-color: <?php echo htmlspecialchars($wr_22); ?>;"<?php } ?>></div>
                    <div class="pclist_view_color_box"<?php if ($wr_44) { ?> style="background-color: <?php echo htmlspecialchars($wr_44); ?>;"<?php } ?>></div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- 기타 필드: 주제와 내용 -->
        <?php 
        // 기타 필드가 하나라도 있는지 확인
        $has_etc = false;
        $content_field_map = array(34, 35, 36, 37, 40, 41, 42, 43);
        for ($i = 1; $i <= 8; $i++) {
            $subject_field = 'wr_' . (25 + $i);
            $content_field = 'wr_' . $content_field_map[$i - 1];
            if (isset($view[$subject_field]) && $view[$subject_field] || isset($view[$content_field]) && $view[$content_field]) {
                $has_etc = true;
                break;
            }
        }
        if ($has_etc) { 
        ?>
        <div class="pclist_view_etc">
            <?php 
            $content_field_map = array(34, 35, 36, 37, 40, 41, 42, 43);
            for ($i = 1; $i <= 8; $i++) {
                $subject_field = 'wr_' . (25 + $i); // wr_26 ~ wr_33
                $content_field = 'wr_' . $content_field_map[$i - 1]; // wr_34,wr_35,wr_36,wr_37,wr_40,wr_41,wr_42,wr_43
                $subject_value = isset($view[$subject_field]) ? $view[$subject_field] : '';
                $content_value = isset($view[$content_field]) ? $view[$content_field] : '';
                
                // 2개씩 묶어서 한 줄에 표시
                if ($i % 2 == 1) {
                    echo '<div class="pclist_view_etc_row_pair">';
                }
                
                if ($subject_value || $content_value) {
                    $subject_style = '';
                    if ($wr_22) {
                        $subject_style .= 'background-color: ' . htmlspecialchars($wr_22) . '; ';
                    }
                    if ($wr_21) {
                        $subject_style .= 'color: ' . htmlspecialchars($wr_21) . '; ';
                    }
            ?>
            <div class="pclist_view_etc_row">
                <div class="pclist_view_etc_subject"<?php if ($subject_style) { ?> style="<?php echo trim($subject_style); ?>"<?php } ?>>
                    <?php echo get_text($subject_value) ?: '&nbsp;'; ?>
                </div>
                <div class="pclist_view_etc_content">
                    <?php echo get_text($content_value) ?: '&nbsp;'; ?>
                </div>
            </div>
            <?php 
                } else {
                    // 값이 없어도 레이아웃 유지를 위해 빈 div 추가
                    echo '<div class="pclist_view_etc_row" style="visibility: hidden;"><div class="pclist_view_etc_subject">&nbsp;</div><div class="pclist_view_etc_content">&nbsp;</div></div>';
                }
                
                if ($i % 2 == 0 || $i == 8) {
                    echo '</div>';
                }
            } 
            ?>
        </div>
        <?php } ?>

        <!-- 본문 -->
        <div class="pclist_view_content_body">
            <div id="bo_v_atc" class="view_content">
                <?php
                // 본문 내용 출력 (HTML 허용)
                // wr_content를 직접 가져와서 처리하여 불필요한 wrapper 제거
                global $g5, $bo_table;
                $write_table = $g5['write_prefix'] . $bo_table;
                $wr_id = isset($view['wr_id']) ? (int)$view['wr_id'] : 0;
                $pclist_content = '';
                
                if ($wr_id > 0) {
                    $content_row = sql_fetch("SELECT wr_content FROM {$write_table} WHERE wr_id = '{$wr_id}'");
                    if ($content_row && isset($content_row['wr_content'])) {
                        $pclist_content = $content_row['wr_content'];
                    }
                }
                
                // wr_content가 없으면 $view['content'] 사용
                if (empty($pclist_content) && isset($view['content'])) {
                    $pclist_content = $view['content'];
                }
                
                // HTML 엔티티 디코딩 (&#034; -> ")
                $pclist_content = html_entity_decode($pclist_content, ENT_QUOTES, 'UTF-8');
                
                // <div class="ra0-content"> 같은 wrapper 제거하고 내부 내용만 추출
                // 여러 패턴 시도: class="ra0-content", class='ra0-content', class=ra0-content 등
                if (preg_match('/<div[^>]*class\s*=\s*["\']?[^"\']*ra0-content[^"\']*["\']?[^>]*>(.*?)<\/div>/is', $pclist_content, $matches)) {
                    $pclist_content = $matches[1];
                } elseif (preg_match('/<div[^>]*ra0-content[^>]*>(.*?)<\/div>/is', $pclist_content, $matches)) {
                    $pclist_content = $matches[1];
                }
                
                // 여전히 wrapper가 남아있으면 제거 시도
                $pclist_content = preg_replace('/<div[^>]*class\s*=\s*["\']?[^"\']*ra0-content[^"\']*["\']?[^>]*>/i', '', $pclist_content);
                $pclist_content = preg_replace('/<\/div>\s*$/', '', trim($pclist_content)); // 마지막 닫는 div 제거
                
                // stripslashes 적용 (이전에 추가한 쌍따옴표 처리)
                $pclist_content = stripslashes($pclist_content);
                
                echo $pclist_content;
                ?>
            </div>
        </div>
    </div>
</article>
<!-- } 게시물 읽기 끝 -->

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>
<script>
$(function() {
    $("#bo_v_atc").viewimageresize();
});
</script>


<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// design.lib.php include
include_once(G5_LIB_PATH.'/design.lib.php');

// 게시판 쓰기 권한 체크
global $board, $member, $is_admin;
if ($w == '') {
    // 글쓰기 권한 체크
    if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 쓸 권한이 없습니다.');
        } else {
            alert("글을 쓸 권한이 없습니다.\\n회원이시라면 로그인 후 이용해 보십시오.", G5_BBS_URL.'/login.php?'.(isset($qstr) ? $qstr : '').'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    }
} else if ($w == 'u') {
    // 글수정 권한 체크
    global $write;
    if ($member['mb_id'] && $write['mb_id'] === $member['mb_id']) {
        // 자신의 글은 통과
    } else if ($is_admin) {
        // 관리자는 통과
    } else if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 수정할 권한이 없습니다.');
        } else {
            alert('글을 수정할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.', G5_BBS_URL.'/login.php?'.(isset($qstr) ? $qstr : '').'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    } else {
        // 권한은 있지만 자신의 글이 아니면 차단
        alert('자신이 작성한 글만 수정할 수 있습니다.');
    }
}

// 활성화된 폰트 목록 가져오기
$font_result = get_active_fonts();
$font_options = [];
while($font_row = sql_fetch_array($font_result)) {
    $font_options[] = $font_row;
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';
?>

<section id="bo_w">
    <h2 class="sound_only"><?php echo $g5['title'] ?></h2>

    <!-- 게시물 작성/수정 시작 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="wr_4" value="pclist">
    <?php
    // wr_parent 설정 (부모 게시물 ID)
    $wr_parent = isset($_GET['wr_parent']) ? (int)$_GET['wr_parent'] : 0;
    if ($wr_parent > 0) {
        echo '<input type="hidden" name="wr_parent" value="'.$wr_parent.'">';
    }
    
    // pclist에서는 공지/비밀글 설정 숨김
    $option = '';
    $option_hidden = '';
    if ($is_html) {
        if ($is_dhtml_editor) {
            $option_hidden .= '<input type="hidden" value="html1" name="html">';
        } else {
            $option = '';
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" class="selec_chk" value="'.$html_value.'" '.$html_checked.'>'.PHP_EOL.'<label for="html"><span></span>html</label></li>';
        }
    }
    // 공지와 비밀글은 항상 전체공개로 설정
    $option_hidden .= '<input type="hidden" name="notice" value="">';
    $option_hidden .= '<input type="hidden" name="secret" value="">';
    echo $option_hidden;
    
    // pclist 전용 필드 컬럼 확인 및 추가
    $write_table_name = $g5['write_prefix'] . $bo_table;
    $pclist_columns = array(
        'wr_21' => "VARCHAR(255) NOT NULL DEFAULT ''", // 제목색
        'wr_22' => "VARCHAR(255) NOT NULL DEFAULT ''", // 배경색
        'wr_23' => "VARCHAR(255) NOT NULL DEFAULT ''", // 키워드1
        'wr_24' => "VARCHAR(255) NOT NULL DEFAULT ''", // 키워드2
        'wr_25' => "VARCHAR(255) NOT NULL DEFAULT ''", // 키워드3
        'wr_26' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타1 주제
        'wr_27' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타2 주제
        'wr_28' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타3 주제
        'wr_29' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타4 주제
        'wr_30' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타5 주제
        'wr_31' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타6 주제
        'wr_32' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타7 주제
        'wr_33' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타8 주제
        'wr_34' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타1 내용
        'wr_35' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타2 내용
        'wr_36' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타3 내용
        'wr_37' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타4 내용
        'wr_40' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타5 내용
        'wr_41' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타6 내용
        'wr_42' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타7 내용
        'wr_43' => "VARCHAR(255) NOT NULL DEFAULT ''", // 기타8 내용
        'wr_38' => "VARCHAR(255) NOT NULL DEFAULT ''", // 한마디
        'wr_39' => "VARCHAR(255) NOT NULL DEFAULT ''", // 메인 이미지
        'wr_44' => "VARCHAR(255) NOT NULL DEFAULT ''"  // 추가색
    );
    
    foreach ($pclist_columns as $col => $definition) {
        $col_exists = sql_fetch("SHOW COLUMNS FROM {$write_table_name} LIKE '{$col}'");
        if (!$col_exists) {
            sql_query("ALTER TABLE {$write_table_name} ADD COLUMN `{$col}` {$definition}", false);
        }
    }
    ?>

    <?php if ($is_category) { ?>
    <div class="bo_w_select write_div">
        <label for="ca_name" class="sound_only">분류<strong>필수</strong></label>
        <select name="ca_name" id="ca_name" required>
            <option value="">분류를 선택하세요</option>
            <?php echo $category_option ?>
        </select>
    </div>
    <?php } ?>

    <div class="bo_w_info write_div">
        <?php if ($is_name) { ?>
            <label for="wr_name" class="sound_only">이름<strong>필수</strong></label>
            <input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input half_input required" placeholder="이름">
        <?php } ?>
    
        <?php if ($is_password) { ?>
            <label for="wr_password" class="sound_only">비밀번호<strong>필수</strong></label>
            <input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input half_input <?php echo $password_required ?>" placeholder="비밀번호">
        <?php } ?>

    
        <?php if ($is_email) { ?>
            <label for="wr_email" class="sound_only">이메일</label>
            <input type="text" name="wr_email" value="<?php echo $email ?>" id="wr_email" class="frm_input half_input email " placeholder="이메일">
        <?php } ?>
    </div>
    
    <?php if ($option) { ?>
    <div class="write_div">
        <span class="sound_only">옵션</span>
        <ul class="bo_v_option">
        <?php echo $option ?>
        </ul>
    </div>
    <?php } ?>

    <!-- PC목록 전용 필드 시작 { -->
    <div class="log20_write_form">
        <dl>
            <dt><label for="wr_subject">캐릭터 명<strong>필수</strong></label></dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block flex-1">
                        <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input full_input required" size="50" maxlength="255" placeholder="캐릭터 명을 입력하세요">
                    </div>
                    <div class="log20_field_block flex-1">
                        <?php
                        $pclist_subtitle = '';
                        if ($w == 'u' && isset($write['wr_3'])) {
                            $pclist_subtitle = $write['wr_3'];
                        } elseif (isset($_POST['pclist_subtitle'])) {
                            $pclist_subtitle = $_POST['pclist_subtitle'];
                        }
                        ?>
                        <input type="text" name="pclist_subtitle" value="<?php echo htmlspecialchars($pclist_subtitle); ?>" id="pclist_subtitle" class="frm_input full_input" size="50" maxlength="255" placeholder="진명/코드네임 등을 입력하세요">
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label for="pclist_oneline">한마디</label></dt>
            <dd>
                <?php
                $pclist_oneline = '';
                if ($w == 'u' && isset($write['wr_38'])) {
                    $pclist_oneline = $write['wr_38'];
                } elseif (isset($_POST['pclist_oneline'])) {
                    $pclist_oneline = $_POST['pclist_oneline'];
                }
                ?>
                <input type="text" name="pclist_oneline" value="<?php echo htmlspecialchars($pclist_oneline); ?>" id="pclist_oneline" class="frm_input full_input" size="50" maxlength="255" placeholder="한마디를 입력하세요">
            </dd>
        </dl>

        <dl>
            <dt><label>색</label></dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="pclist_title_color">제목색</label>
                        <?php
                        $pclist_title_color = '';
                        if ($w == 'u' && isset($write['wr_21'])) {
                            $pclist_title_color = $write['wr_21'];
                        } elseif (isset($_POST['pclist_title_color'])) {
                            $pclist_title_color = $_POST['pclist_title_color'];
                        }
                        ?>
                        <div class="log20_field_inline">
                            <input type="color" name="pclist_title_color" value="<?php echo htmlspecialchars($pclist_title_color ?: '#000000'); ?>" id="pclist_title_color" class="frm_input">
                            <input type="text" name="pclist_title_color_text" value="<?php echo htmlspecialchars($pclist_title_color); ?>" id="pclist_title_color_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                    <div class="log20_field_block">
                        <label for="pclist_bg_color">제목배경색</label>
                        <?php
                        $pclist_bg_color = '';
                        if ($w == 'u' && isset($write['wr_22'])) {
                            $pclist_bg_color = $write['wr_22'];
                        } elseif (isset($_POST['pclist_bg_color'])) {
                            $pclist_bg_color = $_POST['pclist_bg_color'];
                        }
                        ?>
                        <div class="log20_field_inline">
                            <input type="color" name="pclist_bg_color" value="<?php echo htmlspecialchars($pclist_bg_color ?: '#000000'); ?>" id="pclist_bg_color" class="frm_input">
                            <input type="text" name="pclist_bg_color_text" value="<?php echo htmlspecialchars($pclist_bg_color); ?>" id="pclist_bg_color_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                    <div class="log20_field_block">
                        <label for="pclist_add_color">추가색</label>
                        <?php
                        $pclist_add_color = '';
                        if ($w == 'u' && isset($write['wr_44'])) {
                            $pclist_add_color = $write['wr_44'];
                        } elseif (isset($_POST['pclist_add_color'])) {
                            $pclist_add_color = $_POST['pclist_add_color'];
                        }
                        ?>
                        <div class="log20_field_inline">
                            <input type="color" name="pclist_add_color" value="<?php echo htmlspecialchars($pclist_add_color ?: '#000000'); ?>" id="pclist_add_color" class="frm_input">
                            <input type="text" name="pclist_add_color_text" value="<?php echo htmlspecialchars($pclist_add_color); ?>" id="pclist_add_color_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>키워드</label></dt>
            <dd>
                <div class="log20_keyword_container">
                    <?php
                    for ($i = 1; $i <= 3; $i++) {
                        $keyword_field = 'wr_' . (22 + $i); // wr_23, wr_24, wr_25
                        $keyword_value = '';
                        if ($w == 'u' && isset($write[$keyword_field])) {
                            $keyword_value = $write[$keyword_field];
                        } elseif (isset($_POST['pclist_keyword' . $i])) {
                            $keyword_value = $_POST['pclist_keyword' . $i];
                        }
                    ?>
                    <div class="log20_keyword_block">
                        <label for="pclist_keyword<?php echo $i; ?>" class="sound_only">키워드 <?php echo $i; ?></label>
                        <input type="text" name="pclist_keyword<?php echo $i; ?>" value="<?php echo htmlspecialchars($keyword_value); ?>" id="pclist_keyword<?php echo $i; ?>" class="frm_input full_input" maxlength="100" placeholder="키워드 <?php echo $i; ?>">
                    </div>
                    <?php } ?>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>기타</label></dt>
            <dd>
                <div class="log20_etc_container">
                    <?php
                    // 기타 필드 매핑: 주제는 wr_26~wr_33, 내용은 wr_34,wr_35,wr_36,wr_37,wr_40,wr_41,wr_42,wr_43
                    $content_field_map = array(34, 35, 36, 37, 40, 41, 42, 43);
                    for ($i = 1; $i <= 8; $i++) {
                        $subject_field = 'wr_' . (25 + $i); // wr_26 ~ wr_33
                        $content_field = 'wr_' . $content_field_map[$i - 1]; // wr_34,wr_35,wr_36,wr_37,wr_40,wr_41,wr_42,wr_43
                        $etc_subject_value = '';
                        $etc_content_value = '';
                        if ($w == 'u') {
                            if (isset($write[$subject_field])) {
                                $etc_subject_value = $write[$subject_field];
                            }
                            if (isset($write[$content_field])) {
                                $etc_content_value = $write[$content_field];
                            }
                        } elseif (isset($_POST['pclist_etc_subject_' . $i])) {
                            $etc_subject_value = $_POST['pclist_etc_subject_' . $i];
                        } elseif (isset($_POST['pclist_etc_content_' . $i])) {
                            $etc_content_value = $_POST['pclist_etc_content_' . $i];
                        }
                        
                        if ($i % 2 == 1) {
                            echo '<div class="log20_etc_row_pair">';
                        }
                    ?>
                    <div class="log20_etc_row">
                        <div class="log20_etc_inner">
                            <div class="log20_etc_subject_block">
                                <label for="pclist_etc_subject_<?php echo $i; ?>" class="sound_only">기타 <?php echo $i; ?> 주제</label>
                                <input type="text" name="pclist_etc_subject_<?php echo $i; ?>" value="<?php echo htmlspecialchars($etc_subject_value); ?>" id="pclist_etc_subject_<?php echo $i; ?>" class="frm_input full_input" maxlength="100" placeholder="주제 <?php echo $i; ?>">
                            </div>
                            <div class="log20_etc_content_block">
                                <label for="pclist_etc_content_<?php echo $i; ?>" class="sound_only">기타 <?php echo $i; ?> 내용</label>
                                <input type="text" name="pclist_etc_content_<?php echo $i; ?>" value="<?php echo htmlspecialchars($etc_content_value); ?>" id="pclist_etc_content_<?php echo $i; ?>" class="frm_input full_input" maxlength="255" placeholder="내용 <?php echo $i; ?>">
                            </div>
                        </div>
                    </div>
                    <?php 
                        if ($i % 2 == 0 || $i == 8) {
                            echo '</div>';
                        }
                    } ?>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>썸네일 이미지</label></dt>
            <dd>
                <?php if ($w == 'u' && isset($write['wr_7']) && !empty($write['wr_7'])) { ?>
                <div class="log20_existing_image">
                    <label>기존 이미지</label>
                    <div class="log20_existing_image_preview">
                        <img src="<?php echo htmlspecialchars($write['wr_7']) ?>" alt="기존 이미지">
                        <div>
                            <a href="<?php echo htmlspecialchars($write['wr_7']) ?>" target="_blank" class="log20_existing_image_link">이미지 크게 보기</a>
                            <label class="log20_existing_image_label">
                                <input type="checkbox" name="wr_7_del" value="1">
                                <span>기존 이미지 삭제</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="wr_7_file">이미지 파일 업로드</label>
                        <input type="file" name="wr_7_file" id="wr_7_file" class="frm_input full_input" accept="image/*">
                        <small class="log20_field_small">새 이미지를 업로드하면 기존 이미지가 교체됩니다.</small>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_7">이미지 URL</label>
                        <input type="url" name="wr_7" value="<?php echo isset($write['wr_7']) ? htmlspecialchars($write['wr_7']) : '' ?>" id="wr_7" class="frm_input full_input" size="50" placeholder="https://example.com/image.jpg">
                        <small class="log20_field_small">또는 이미지 URL을 입력하세요.</small>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>메인 이미지</label></dt>
            <dd>
                <?php if ($w == 'u' && isset($write['wr_39']) && !empty($write['wr_39'])) { ?>
                <div class="log20_existing_image">
                    <label>기존 이미지</label>
                    <div class="log20_existing_image_preview">
                        <img src="<?php echo htmlspecialchars($write['wr_39']) ?>" alt="기존 메인 이미지">
                        <div>
                            <a href="<?php echo htmlspecialchars($write['wr_39']) ?>" target="_blank" class="log20_existing_image_link">이미지 크게 보기</a>
                            <label class="log20_existing_image_label">
                                <input type="checkbox" name="wr_39_del" value="1">
                                <span>기존 이미지 삭제</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="wr_39_file">이미지 파일 업로드</label>
                        <input type="file" name="wr_39_file" id="wr_39_file" class="frm_input full_input" accept="image/*">
                        <small class="log20_field_small">새 이미지를 업로드하면 기존 이미지가 교체됩니다.</small>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_39">이미지 URL</label>
                        <input type="url" name="wr_39" value="<?php echo isset($write['wr_39']) ? htmlspecialchars($write['wr_39']) : '' ?>" id="wr_39" class="frm_input full_input" size="50" placeholder="https://example.com/image.jpg">
                        <small class="log20_field_small">또는 이미지 URL을 입력하세요.</small>
                    </div>
                </div>
            </dd>
        </dl>
    </div>
    <!-- } PC목록 전용 필드 끝 -->

    <!-- 본문 내용 시작 { -->
    <?php if ($write_min || $write_max) { ?>
    <div class="bo_w_msg write_div">
        <?php echo $write_min ? '<span class="sound_only">최소</span><strong>최소 '.number_format($write_min).'자</strong> 이상 입력하세요.' : '' ?>
        <?php echo $write_max ? '<span class="sound_only">최대</span><strong>최대 '.number_format($write_max).'자</strong>까지 입력 가능합니다.' : '' ?>
    </div>
    <?php } ?>
    <div class="bo_w_msg write_div">
        <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
    </div>
    <!-- } 본문 내용 끝 -->

    <?php if ($is_use_captcha) { //자동등록방지  ?>
    <div class="write_div">
        <?php echo $captcha_html ?>
    </div>
    <?php } ?>

    <div class="btn_confirm write_div">
        <a href="<?php echo get_pretty_url($bo_table); ?>" class="btn_cancel btn">취소</a>
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">작성완료</button>
    </div>
    </form>

    <script>
    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
            if (result)
                obj.value = "html2";
            else
                obj.value = "html1";
        }
        else
            obj.value = "";
    }

    // pclist에서는 비밀글 기능 사용 안 함

        // pclist 제목색 동기화
        $('#pclist_title_color').on('change', function() {
            $('#pclist_title_color_text').val($(this).val());
        });
        $('#pclist_title_color_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#pclist_title_color').val(val);
            }
        });

        // pclist 배경색 동기화
        $('#pclist_bg_color').on('change', function() {
            $('#pclist_bg_color_text').val($(this).val());
        });
        $('#pclist_bg_color_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#pclist_bg_color').val(val);
            }
        });

        // pclist 추가색 동기화
        $('#pclist_add_color').on('change', function() {
            $('#pclist_add_color_text').val($(this).val());
        });
        $('#pclist_add_color_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#pclist_add_color').val(val);
            }
        });
    });

    function fwrite_submit(f)
    {

        var subject = "";
        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.wr_subject.value,
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
            }
        });

        if (subject) {
            alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
            f.wr_subject.focus();
            return false;
        }

        // pclist 제목색/배경색 동기화
        if (f.pclist_title_color && f.pclist_title_color_text) {
            if (f.pclist_title_color_text.value && /^#[0-9A-F]{6}$/i.test(f.pclist_title_color_text.value)) {
                f.pclist_title_color.value = f.pclist_title_color_text.value;
            } else if (!f.pclist_title_color_text.value && f.pclist_title_color.value) {
                f.pclist_title_color_text.value = f.pclist_title_color.value;
            }
        }
        if (f.pclist_bg_color && f.pclist_bg_color_text) {
            if (f.pclist_bg_color_text.value && /^#[0-9A-F]{6}$/i.test(f.pclist_bg_color_text.value)) {
                f.pclist_bg_color.value = f.pclist_bg_color_text.value;
            } else if (!f.pclist_bg_color_text.value && f.pclist_bg_color.value) {
                f.pclist_bg_color_text.value = f.pclist_bg_color.value;
            }
        }
        // pclist 추가색 동기화
        if (f.pclist_add_color && f.pclist_add_color_text) {
            if (f.pclist_add_color_text.value && /^#[0-9A-F]{6}$/i.test(f.pclist_add_color_text.value)) {
                f.pclist_add_color.value = f.pclist_add_color_text.value;
            } else if (!f.pclist_add_color_text.value && f.pclist_add_color.value) {
                f.pclist_add_color_text.value = f.pclist_add_color.value;
            }
        }

        <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</section>
<!-- } 게시물 작성/수정 끝 -->


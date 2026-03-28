<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once(__DIR__.'/imgstorage.lib.php');

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';

$view_wr_3 = isset($view['wr_3']) ? $view['wr_3'] : '';
$view_wr_7 = isset($view['wr_7']) ? $view['wr_7'] : '';

$thumb = get_list_thumbnail($board['bo_table'], $view['wr_id'], 800, 800, false, true);
$img_url = '';
if ($thumb['src']) {
    $img_url = $thumb['src'];
} elseif ($view_wr_7 && trim($view_wr_7) !== '' && (filter_var(trim($view_wr_7), FILTER_VALIDATE_URL) || strpos(trim($view_wr_7), G5_DATA_URL) === 0)) {
    $img_url = trim($view_wr_7);
} else {
    $img_url = G5_IMG_URL . '/no_image.png';
}

$is_popup = isset($_GET['popup']) && $_GET['popup'];
?>
<?php if ($is_popup) { ?>
<div class="imgstor_container imgstor_container--popup">
<?php } else { ?>
<article id="bo_v" class="imgstorage_view">
    <div class="imgstor_container">
<?php } ?>
    <div class="imgstorage_view_buttons" id="imgstorage_view_buttons">
        <?php if ($is_popup) { ?>
        <a href="javascript:void(0)" onclick="if(window.parent.closeImgstorageModal){window.parent.closeImgstorageModal();} return false;"><i class="fa fa-list" aria-hidden="true"></i> 목록</a>
        <?php } else { ?>
        <a href="<?php echo htmlspecialchars($list_href, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-list" aria-hidden="true"></i> 목록</a>
        <?php } ?>
        <?php if ($update_href) { ?><a href="<?php echo htmlspecialchars($update_href, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-pencil" aria-hidden="true"></i> 수정</a><?php } ?>
        <?php if ($delete_href) { ?><a href="<?php echo htmlspecialchars($delete_href, ENT_QUOTES, 'UTF-8'); ?>" onclick="del(this.href); return false;"><i class="fa fa-trash" aria-hidden="true"></i> 삭제</a><?php } ?>
    </div>
    <h1 class="imgstor_container_title"><?php echo get_text($view['subject']); ?></h1>
    <?php if ($view_wr_3 !== '') { ?>
    <p class="imgstor_container_subtitle"><?php echo imgstorage_parse_subtitle_links($view_wr_3); ?></p>
    <?php } ?>
    <div class="imgstor_container_img_wrap<?php echo $is_admin ? '' : ' imgstorage-no-drag'; ?>">
        <img src="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo get_text($view['subject']); ?>"<?php echo imgstorage_drag_disabled_attr($is_admin); ?>>
    </div>
</div>
<?php if (!$is_popup) { ?>
</article>
<?php } ?>
<?php imgstorage_print_drag_protection_script($is_admin, 'body', true); ?>

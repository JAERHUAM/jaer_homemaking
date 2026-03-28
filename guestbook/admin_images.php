<?php
// common.php 포함
include_once(__DIR__ . '/../../../common.php');

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 관리자 권한 확인
if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.', G5_URL);
}

$bo_table = isset($_GET['bo_table']) ? clean_xss_tags($_GET['bo_table']) : '';

if (empty($bo_table)) {
    alert('게시판 정보가 없습니다.', G5_URL);
}

// 게시판 정보 확인
$board = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '" . sql_real_escape_string($bo_table) . "'");
if (!$board) {
    alert('존재하지 않는 게시판입니다.', G5_URL);
}

// 현재 스킨 폴더 이름 가져오기
$current_skin_name = basename(__DIR__);

// 스킨이 현재 폴더의 스킨인지 확인
if ($board['bo_skin'] !== $current_skin_name) {
    alert('이 스킨 게시판만 사용할 수 있습니다.', G5_URL);
}

// 스킨 경로 설정
$board_skin_path = get_skin_path('board', $current_skin_name);
$board_skin_url = get_skin_url('board', $current_skin_name);

// 이미지 디렉토리 경로
$images_dir = $board_skin_path . '/images';
$images_url = $board_skin_url . '/images';

// images 폴더가 없으면 생성
if (!is_dir($images_dir)) {
    @mkdir($images_dir, 0755, true);
}

// 현재 업로드된 이미지 목록 가져오기
$images = array();
if (is_dir($images_dir) && is_readable($images_dir)) {
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $files = scandir($images_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $file_path = $images_dir . '/' . $file;
        $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_extensions) && is_file($file_path)) {
            $file_size = filesize($file_path);
            $images[] = array(
                'name' => $file,
                'url' => $images_url . '/' . $file,
                'size' => $file_size,
                'date' => filemtime($file_path)
            );
        }
    }
    
    // 날짜순 정렬 (최신순)
    usort($images, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// 이미지 메모 데이터 로드
$memos_file = $images_dir . '/image_memos.json';
$image_memos = array();
if (file_exists($memos_file)) {
    $memos_json = file_get_contents($memos_file);
    $image_memos = json_decode($memos_json, true);
    if (!is_array($image_memos)) {
        $image_memos = array();
    }
}

// 파일 크기 포맷 함수
function format_file_size($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>방명록 이미지 관리</title>
    <link rel="stylesheet" href="<?php echo G5_CSS_URL ?>/default.css.php?ver=<?php echo G5_CSS_VER ?>">
    <link rel="stylesheet" href="<?php echo $board_skin_url ?>/style.css">
    <style>
        body {
            font-family: var(--content-font-family);
            padding: 20px;
            background-color: whitesmoke;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--container-bg-color);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            margin-bottom: 30px;
            color: var(--content-font-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            font-family: var(--title-font-family);
            font-size: var(--title-font-size);
        }
        .upload-section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .upload-section h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: var(--content-font-size);
            color: var(--content-font-color);
            font-family: var(--content-font-family);
        }
        .upload-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .upload-form input[type="file"] {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .upload-form button {
            padding: 8px 20px;
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: var(--content-font-size);
            font-family: var(--content-font-family);
        }
        .upload-form button:hover {
            background: var(--btn-secondary-bg);
            color: var(--btn-secondary-text);
        }
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .image-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        .image-info {
            padding: 10px;
            font-size: var(--content-font-size);
            color: var(--content-font-color);
            font-family: var(--content-font-family);
        }
        .image-name {
            font-weight: bold;
            margin-bottom: 5px;
            word-break: break-all;
        }
        .image-meta {
            color: var(--content-font-color);
            font-size: var(--content-font-size);
            font-family: var(--content-font-family);
        }
        .image-actions {
            padding: 10px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 5px;
        }
        .btn-delete {
            flex: 1;
            padding: 6px 12px;
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: var(--content-font-size);
            font-family: var(--content-font-family);
        }
        .btn-delete:hover {
            background: var(--btn-secondary-bg);
        }
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: var(--btn-secondary-bg);
            color: var(--btn-secondary-text);
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-back:hover {
            background: var(--btn-secondary-bg);
        }
        .empty-message {
            text-align: center;
            padding: 40px;
            color: var(--content-font-color);
            font-family: var(--content-font-family);
        }
        .upload-progress {
            margin-top: 10px;
            display: none;
        }
        .upload-progress.active {
            display: block;
        }
        .image-memo-input {
            margin-top: 8px;
        }
        .image-memo-input .memo-input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: var(--content-font-size);
            font-family: var(--content-font-family);
            color: var(--content-font-color);
            box-sizing: border-box;
        }
        .image-memo-input .memo-input:focus {
            outline: none;
            border-color: var(--btn-primary-bg);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="<?php echo G5_BBS_URL ?>/board.php?bo_table=<?php echo $bo_table ?>" class="btn-back">← 게시판으로 돌아가기</a>
        
        <h1>방명록 이미지 관리</h1>
        
        <div class="upload-section">
            <h2>이미지 업로드</h2>
            <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" required>
                <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
                <button type="submit">업로드</button>
            </form>
            <div class="upload-progress" id="uploadProgress">
                업로드 중...
            </div>
        </div>
        
        <div class="images-section">
            <h2>업로드된 이미지 (<?php echo count($images) ?>개)</h2>
            <?php if (count($images) > 0) { ?>
                <div class="images-grid">
                    <?php foreach ($images as $image) { 
                        $memo = isset($image_memos[$image['name']]) ? $image_memos[$image['name']] : '';
                    ?>
                        <div class="image-item" data-filename="<?php echo htmlspecialchars($image['name']) ?>">
                            <img src="<?php echo htmlspecialchars($image['url']) ?>" alt="<?php echo htmlspecialchars($image['name']) ?>">
                            <div class="image-info">
                                <div class="image-name"><?php echo htmlspecialchars($image['name']) ?></div>
                                <div class="image-meta">
                                    <?php echo format_file_size($image['size']) ?> · <?php echo date('Y-m-d H:i', $image['date']) ?>
                                </div>
                                <div class="image-memo-input">
                                    <input type="text" 
                                           class="memo-input" 
                                           placeholder="메모 입력..." 
                                           value="<?php echo htmlspecialchars($memo) ?>"
                                           data-filename="<?php echo htmlspecialchars($image['name']) ?>"
                                           onblur="saveMemo('<?php echo htmlspecialchars($image['name']) ?>', this.value)">
                                </div>
                            </div>
                            <div class="image-actions">
                                <button class="btn-delete" onclick="deleteImage('<?php echo htmlspecialchars($image['name']) ?>')">삭제</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-message">
                    업로드된 이미지가 없습니다.
                </div>
            <?php } ?>
        </div>
    </div>
    
    <script>
        // 이미지 업로드
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressDiv = document.getElementById('uploadProgress');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            progressDiv.classList.add('active');
            submitBtn.disabled = true;
            submitBtn.textContent = '업로드 중...';
            
            fetch('<?php echo $board_skin_url ?>/ajax.upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressDiv.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.textContent = '업로드';
                
                if (data.success) {
                    alert('이미지가 성공적으로 업로드되었습니다.');
                    location.reload();
                } else {
                    alert('업로드 실패: ' + (data.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                progressDiv.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.textContent = '업로드';
                alert('업로드 중 오류가 발생했습니다: ' + error);
            });
        });
        
        // 이미지 삭제
        function deleteImage(filename) {
            if (!confirm('이 이미지를 삭제하시겠습니까?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('filename', filename);
            formData.append('bo_table', '<?php echo $bo_table ?>');
            
            fetch('<?php echo $board_skin_url ?>/ajax.delete_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('이미지가 삭제되었습니다.');
                    location.reload();
                } else {
                    alert('삭제 실패: ' + (data.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                alert('삭제 중 오류가 발생했습니다: ' + error);
            });
        }
        
        // 메모 저장
        function saveMemo(filename, memo) {
            const formData = new FormData();
            formData.append('filename', filename);
            formData.append('memo', memo);
            formData.append('bo_table', '<?php echo $bo_table ?>');
            
            fetch('<?php echo $board_skin_url ?>/ajax.save_image_memo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('메모 저장 실패: ' + (data.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('메모 저장 중 오류:', error);
            });
        }
    </script>
</body>
</html>


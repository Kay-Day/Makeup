<?php
// test_upload.php - Kiểm tra upload ảnh trực tiếp
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <h1>Test File Upload</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kiểm tra xem có file được upload không
        if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === 0) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/assets/uploads/test/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['test_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ddd;'>";
            echo "Upload path: $target_file<br>";
            echo "Temp file: " . $_FILES['test_image']['tmp_name'] . "<br>";
            echo "File size: " . $_FILES['test_image']['size'] . " bytes<br>";
            echo "File type: " . $_FILES['test_image']['type'] . "<br>";
            
            // Thử upload file
            if (move_uploaded_file($_FILES['test_image']['tmp_name'], $target_file)) {
                echo "<p style='color: green;'>File uploaded successfully!</p>";
                echo "<img src='/beautyclick/assets/uploads/test/$file_name' style='max-width: 300px;'>";
            } else {
                echo "<p style='color: red;'>Upload failed!</p>";
                echo "Error: " . error_get_last()['message'];
            }
            echo "</div>";
        } else {
            echo "<p style='color: red;'>Error: " . $_FILES['test_image']['error'] . "</p>";
        }
    }
    ?>
    
    <form action="" method="POST" enctype="multipart/form-data">
        <p>
            <label for="test_image">Select image to upload:</label>
            <input type="file" name="test_image" id="test_image" accept="image/*">
        </p>
        <p>
            <button type="submit">Upload Image</button>
        </p>
    </form>
    
    <h2>Server Information</h2>
    <pre>
    Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
    Script Path: <?php echo __FILE__; ?>
    PHP Version: <?php echo PHP_VERSION; ?>
    Upload Max Filesize: <?php echo ini_get('upload_max_filesize'); ?>
    </pre>
</body>
</html>
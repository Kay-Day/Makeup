<?php
// artists.php - Display all makeup artists

// Set page title
$page_title = "Makeup Artists";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get filter parameters
$search = sanitize_input($conn, $_GET['search'] ?? '');
$rating = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$location = sanitize_input($conn, $_GET['location'] ?? '');
$sort = sanitize_input($conn, $_GET['sort'] ?? 'rating_desc');

// Build SQL query
$sql = "SELECT u.user_id, u.full_name, u.avatar, u.address, ap.studio_address, ap.bio, 
               ap.skills, ap.avg_rating, ap.total_bookings, ap.is_verified
        FROM users u
        JOIN artist_profiles ap ON u.user_id = ap.user_id
        WHERE u.role_id = 2 AND u.status = 'active'";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE '%$search%' OR ap.bio LIKE '%$search%' OR ap.skills LIKE '%$search%')";
}

if ($rating > 0) {
    $sql .= " AND ap.avg_rating >= $rating";
}

if (!empty($location)) {
    $sql .= " AND (u.address LIKE '%$location%' OR ap.studio_address LIKE '%$location%')";
}

// Apply sorting
switch ($sort) {
    case 'rating_desc':
        $sql .= " ORDER BY ap.avg_rating DESC, ap.total_bookings DESC";
        break;
    case 'bookings_desc':
        $sql .= " ORDER BY ap.total_bookings DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY u.full_name ASC";
        break;
    default:
        $sql .= " ORDER BY ap.avg_rating DESC";
}

// Get artists
$artists = get_records($conn, $sql);

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<style>
    /* Style for artist cards */
    .artist-card {
        position: relative;
        transition: transform 0.3s ease;
        border-radius: 15px;
        overflow: visible;
        /* Thay đổi từ hidden sang visible */
    }

    .artist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    }

    .artist-cover {
        height: 100px;
        background-image: linear-gradient(45deg, #ff6b6b, #4ecdc4);
        position: relative;
        border-radius: 15px 15px 0 0;
        /* Thêm border radius cho phần trên */
    }

    /* Fix for avatar positioning and sizing */
    .artist-avatar-container {
        position: absolute;
        /* Thay đổi từ relative sang absolute */
        top: 60px;
        /* Điều chỉnh vị trí từ trên xuống */
        left: 50%;
        transform: translateX(-50%);
        z-index: 5;
    }

    .artist-avatar {
        display: inline-block;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        object-fit: cover;
    }

    /* Additional styles for better layout */
    .artist-info {
        padding: 50px 15px 15px;
        /* Tăng padding-top để tạo không gian cho avatar */
        position: relative;
        z-index: 1;
    }

    .artist-info h5 {
        margin-top: 10px;
        font-size: 1.1rem;
    }

    .artist-stats {
        display: flex;
        justify-content: center;
        margin-bottom: 15px;
    }

    .artist-stats .col-6 {
        text-align: center;
    }

    .rating-stars i {
        font-size: 14px;
    }

    .filter-section {
        position: relative;
        z-index: 20;
    }

    .page-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/beautyclick/assets/images/makeup-bg.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 60px 0;
        margin-bottom: 30px;
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="h2 mb-2">Makeup Artists</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php" class="text-white">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Makeup Artists</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <!-- Search & Filter Section -->
    <div class="card border-0 shadow-sm mb-4 filter-section">
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" id="filter-form">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="search" class="form-label">Tìm kiếm theo tên hoặc kỹ năng</label>
                        <input type="text" class="form-control" id="search" name="search"
                            placeholder="Nhập tên hoặc kỹ năng" value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="rating" class="form-label">Đánh giá cao nhất</label>
                        <select class="form-select" id="rating" name="rating">
                            <option value="0" <?php echo $rating == 0 ? 'selected' : ''; ?>>Bất kỳ đánh giá nào</option>
                            <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>4+ Sao</option>
                            <option value="4.5" <?php echo $rating == 4.5 ? 'selected' : ''; ?>>4.5+ Sao</option>
                            <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>5 Sao</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="location" class="form-label">Tất cả các quận</label>
                        <select class="form-select" id="location" name="location">
                            <option value="">Tất cả các quận</option>
                            <option value="Hai Chau" <?php echo $location == 'Hai Chau' ? 'selected' : ''; ?>>Hải Châu
                            </option>
                            <option value="Thanh Khe" <?php echo $location == 'Thanh Khe' ? 'selected' : ''; ?>>Thanh Khê
                            </option>
                            <option value="Son Tra" <?php echo $location == 'Son Tra' ? 'selected' : ''; ?>>Sơn Trà
                            </option>
                            <option value="Ngu Hanh Son" <?php echo $location == 'Ngu Hanh Son' ? 'selected' : ''; ?>>Ngũ
                                Hành Sơn</option>
                            <option value="Lien Chieu" <?php echo $location == 'Lien Chieu' ? 'selected' : ''; ?>>Liên
                                Chiểu</option>
                            <option value="Cam Le" <?php echo $location == 'Cam Le' ? 'selected' : ''; ?>>Cẩm Lệ</option>
                            <option value="Hoa Vang" <?php echo $location == 'Hoa Vang' ? 'selected' : ''; ?>>Hòa Vang
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <label for="sort" class="form-label">Sắp xếp theo</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Đánh giá
                                cao nhất</option>
                            <option value="bookings_desc" <?php echo $sort == 'bookings_desc' ? 'selected' : ''; ?>>Nhiều
                                đặt chỗ nhất</option>
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Áp dụng bộ lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Artists Grid -->
    <div class="row">
        <?php if (count($artists) > 0): ?>
            <?php foreach ($artists as $artist): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card artist-card border-0 shadow-sm h-100">
                        <div class="card-body p-0">
                            <!-- Artist Header -->
                            <div class="artist-cover"></div>

                            <!-- Artist Avatar Container -->
                            <div class="artist-avatar-container">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>"
                                    alt="<?php echo $artist['full_name']; ?>" class="artist-avatar">
                            </div>

                            <!-- Artist Info - Thêm padding-top để tạo không gian cho avatar -->
                            <div class="artist-info text-center">
                                <h5 class="mb-1">
                                    <?php echo $artist['full_name']; ?>
                                    <?php if ($artist['is_verified']): ?>
                                        <span class="badge bg-primary ms-1" title="Verified Makeup Artist">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </h5>

                                <!-- Rating -->
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= round($artist['avg_rating'])): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="ms-1">(<?php echo number_format($artist['avg_rating'], 1); ?>)</span>
                                </div>

                                <!-- Stats -->
                                <div class="row artist-stats mb-3">
                                    <div class="col-6 border-end">
                                        <div class="fw-bold text-primary"><?php echo $artist['total_bookings']; ?></div>
                                        <div class="small text-muted">Đặt chỗ</div>
                                    </div>
                                    <div class="col-6">
                                        <?php
                                        $services_count = count_records($conn, 'services', "artist_id = {$artist['user_id']} AND is_available = 1");
                                        ?>
                                        <div class="fw-bold text-primary"><?php echo $services_count; ?></div>
                                        <div class="small text-muted">Dịch vụ</div>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <?php if (!empty($artist['skills'])): ?>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-magic me-1"></i>
                                        <?php echo substr($artist['skills'], 0, 100) . (strlen($artist['skills']) > 100 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Location -->
                                <p class="small mb-3">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <?php echo substr($artist['studio_address'], 0, 80) . (strlen($artist['studio_address']) > 80 ? '...' : ''); ?>
                                </p>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-center p-3">
                            <a href="/beautyclick/artists/profile.php?id=<?php echo $artist['user_id']; ?>"
                                class="btn btn-primary">
                                <i class="fas fa-user me-2"></i>Xem Hồ sơ
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>Không tìm thấy thợ makeup</h4>
                    <p>Chúng tôi không tìm thấy thợ makeup nào phù hợp với tiêu chí của bạn. Hãy thử điều chỉnh bộ lọc.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-submit form when select fields change
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('rating').addEventListener('change', function () {
            document.getElementById('filter-form').submit();
        });

        document.getElementById('location').addEventListener('change', function () {
            document.getElementById('filter-form').submit();
        });

        document.getElementById('sort').addEventListener('change', function () {
            document.getElementById('filter-form').submit();
        });
    });
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>
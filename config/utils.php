<?php
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateMD5($string) {
    return md5($string);
}

function uploadImage($file, $uploadDir = '../assets/images/products/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return false;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return false;
}

function deleteImage($filename, $uploadDir = '../assets/images/products/') {
    if (empty($filename)) {
        return false;
    }

    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }

    return false;
}

function getImagePath($filename) {
    if (empty($filename)) {
        return '/ecommerce/assets/images/default-product.png';
    }
    return '/ecommerce/assets/images/products/' . $filename;
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

function showNotification($type, $message) {
    $_SESSION['notification'] = [
        'type' => $type,
        'message' => $message
    ];
}

function displayNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);

        $alertClass = '';
        switch ($notification['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
            default:
                $alertClass = 'alert-info';
        }

        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                <i class='fas fa-check-circle me-2'></i>
                {$notification['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

function paginate($totalItems, $itemsPerPage, $currentPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);

    if ($totalPages <= 1) return '';

    $pagination = '<nav><ul class="pagination justify-content-center">';

    // Previous
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }

    // Page numbers
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    }

    $pagination .= '</ul></nav>';

    return $pagination;
}

function handleError($message, $redirect = null) {
    error_log($message);
    showNotification('error', 'An error occurred. Please try again.');
    if ($redirect) {
        header("Location: $redirect");
        exit();
    }
}
?>
<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';

if ($action === 'fetch_all') {
    try {
        $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'fetch_one') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $content = $_POST['content'] ?? '';
        $rating = $_POST['rating'] ?? 5;
        $photo_url = $_POST['existing_photo'] ?? '';

        // Handle Image Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $target_dir = "../../assets/uploads/testimonials/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_url = "assets/uploads/testimonials/" . $file_name;
            }
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE testimonials SET name = ?, content = ?, rating = ?, photo_url = ? WHERE id = ?");
                $stmt->execute([$name, $content, $rating, $photo_url, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO testimonials (name, content, rating, photo_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $content, $rating, $photo_url]);
            }
            echo json_encode(['success' => true, 'message' => 'Testimonial saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Testimonial deleted']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    exit();
}

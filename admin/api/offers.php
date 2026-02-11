<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
}

switch($action) {
    case 'fetch_all':
        fetchOffers($pdo);
        break;
    case 'fetch_one':
        fetchOneOffer($pdo);
        break;
    case 'create':
        createOffer($pdo);
        break;
    case 'update':
        updateOffer($pdo);
        break;
    case 'delete':
        deleteOffer($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchOffers($pdo) {
    $stmt = $pdo->query("SELECT * FROM offer_codes ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function fetchOneOffer($pdo) {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM offer_codes WHERE id = ?");
    $stmt->execute([$id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $offer]);
}

function createOffer($pdo) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = $_POST['discount_value'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    if(empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Code is required']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO offer_codes (code, discount_type, discount_value, description, status) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$code, $type, $value, $desc, $status]);
        echo json_encode(['success' => true, 'message' => 'Offer created successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Offer code already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}

function updateOffer($pdo) {
    $id = $_POST['id'];
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = $_POST['discount_value'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE offer_codes SET code=?, discount_type=?, discount_value=?, description=?, status=? WHERE id=?");
    try {
        $stmt->execute([$code, $type, $value, $desc, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Offer updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteOffer($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM offer_codes WHERE id=?");
    if($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Offer deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete']);
    }
}
?>

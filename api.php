<?php
session_start();
header("Content-Type: application/json");

// Conexão
$conn = new mysqli('localhost', 'root', '', 'cms_db');
if ($conn->connect_error && $conn->connect_error != 'Unknown database \'cms_db\'') {
    die(json_encode(['error' => $conn->connect_error]));
}

// Auto-Setup Simplificado
if ($conn->connect_error) {
    $conn = new mysqli('localhost', 'root', '');
    $conn->query("CREATE DATABASE IF NOT EXISTS cms_db");
    $conn->select_db("cms_db");
    
    // Schema
    $conn->query("CREATE TABLE IF NOT EXISTS articles (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), author VARCHAR(255), description TEXT, category VARCHAR(50), youtube_url VARCHAR(255), pdf_path VARCHAR(255), completion_date DATE, discipline VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS article_images (id INT AUTO_INCREMENT PRIMARY KEY, article_id INT, image_path VARCHAR(255), FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE)");
    
    $conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL)");
    if ($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] == 0) {
        $pass = password_hash('admin', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, password) VALUES ('admin', '$pass')");
    }

    foreach (['uploads/images', 'uploads/pdfs'] as $d) if (!is_dir($d)) mkdir($d, 0777, true);
}


function json($data) { echo json_encode($data); exit; }
function upload($file, $dir) {
    if (empty($file['name'])) return null;
    $path = $dir . time() . '_' . rand(100,999) . '_' . basename($file['name']);
    return move_uploaded_file($file['tmp_name'], $path) ? $path : null;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar Artigos
if ($method === 'GET') {
    $sql = "SELECT a.*, GROUP_CONCAT(CONCAT(ai.id, '::', ai.image_path) SEPARATOR '||') as imgs 
            FROM articles a LEFT JOIN article_images ai ON a.id = ai.article_id 
            GROUP BY a.id ORDER BY a.created_at DESC";
    $list = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    
    foreach ($list as &$item) {
        $item['images'] = [];
        if ($item['imgs']) {
            foreach (explode('||', $item['imgs']) as $img) {
                list($id, $path) = explode('::', $img);
                $item['images'][] = ['id' => $id, 'path' => $path];
            } 
        }
        $item['cover'] = $item['images'][0]['path'] ?? 'https://via.placeholder.com/800x600?text=Sem+Imagem';
        unset($item['imgs']);
    }
    json($list);
}

// POST: Criar/Editar
if ($method === 'POST') {
    if (empty($_SESSION['user'])) { http_response_code(401); json(['error' => 'Unauthorized']); }
    $id = $_POST['id'] ?? '';
    $pdf = upload($_FILES['pdfFile'] ?? [], 'uploads/pdfs/');

    $completion_date = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $discipline = $_POST['discipline'] ?? '';

    $author = '';
    if (isset($_POST['authors']) && is_array($_POST['authors'])) {
        $authors = array_filter($_POST['authors'], function($a) { return trim($a) !== ''; });
        $author = implode(', ', $authors);
    } elseif (isset($_POST['author'])) {
        $author = $_POST['author'];
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE articles SET title=?, author=?, description=?, category=?, youtube_url=?, completion_date=?, discipline=? WHERE id=?");
        $stmt->bind_param("sssssssi", $_POST['title'], $author, $_POST['description'], $_POST['category'], $_POST['youtubeUrl'], $completion_date, $discipline, $id);
        $stmt->execute();
        if ($pdf) $conn->query("UPDATE articles SET pdf_path='$pdf' WHERE id=$id");
    } else {
        $stmt = $conn->prepare("INSERT INTO articles (title, author, description, category, youtube_url, pdf_path, completion_date, discipline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $_POST['title'], $author, $_POST['description'], $_POST['category'], $_POST['youtubeUrl'], $pdf, $completion_date, $discipline);
        $stmt->execute();
        $id = $stmt->insert_id;
    }

    // Processar Imagens
    if (!empty($_FILES['images']['name'][0])) {
        $stmt = $conn->prepare("INSERT INTO article_images (article_id, image_path) VALUES (?, ?)");
        foreach ($_FILES['images']['name'] as $i => $name) {
            $file = ['name' => $name, 'tmp_name' => $_FILES['images']['tmp_name'][$i]];
            if ($path = upload($file, 'uploads/images/')) {
                $stmt->bind_param("is", $id, $path);
                $stmt->execute();
            }
        }
    }
    json(['success' => true]);
}

// DELETE: Remover
if ($method === 'DELETE') {
    if (empty($_SESSION['user'])) { http_response_code(401); json(['error' => 'Unauthorized']); }
    $id = (int)$_GET['id'];
    $type = $_GET['type'] ?? '';
    
    if ($type === 'image') {
        $conn->query("DELETE FROM article_images WHERE id=$id");
    } elseif ($type === 'pdf') {
        $conn->query("UPDATE articles SET pdf_path=NULL WHERE id=$id");
    } else {
        $conn->query("DELETE FROM articles WHERE id=$id");
    }
    json(['success' => true]);
}
?>

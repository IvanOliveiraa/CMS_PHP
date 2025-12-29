<?php
session_start();
header("Content-Type: application/json");
mysqli_report(MYSQLI_REPORT_OFF);

// Tenta ligar à base de dados
$conn = new mysqli('localhost', 'root', '', 'cms_db');

if ($conn->connect_error) {
    // Erro 1049: Base de dados desconhecida (Unknown database)
    if ($conn->connect_errno == 1049) {
        // Liga sem selecionar a base de dados para poder criar
        $conn = new mysqli('localhost', 'root', '');
        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'Falha na ligação inicial: ' . $conn->connect_error]));
        }
        
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS cms_db")) {
            http_response_code(500);
            die(json_encode(['error' => 'Erro ao criar base de dados: ' . $conn->error]));
        }
        
        $conn->select_db("cms_db");
    } else {
        http_response_code(500);
        die(json_encode(['error' => 'Erro de ligação: ' . $conn->connect_error]));
    }
}

// Criação de Tabelas (Se não existirem)
$conn->query("CREATE TABLE IF NOT EXISTS articles (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), author VARCHAR(255), description TEXT, category VARCHAR(50), youtube_url VARCHAR(255), pdf_path VARCHAR(255), completion_date DATE, discipline VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS article_images (id INT AUTO_INCREMENT PRIMARY KEY, article_id INT, image_path VARCHAR(255), FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE)");
$conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL)");

// Migração de Esquema (Garante que as colunas existam se a tabela já existir)
$cols = $conn->query("SHOW COLUMNS FROM articles");
$existing_cols = [];
if ($cols) { while($row = $cols->fetch_assoc()) $existing_cols[] = $row['Field']; }

if (!in_array('discipline', $existing_cols)) $conn->query("ALTER TABLE articles ADD COLUMN discipline VARCHAR(255)");
if (!in_array('completion_date', $existing_cols)) $conn->query("ALTER TABLE articles ADD COLUMN completion_date DATE");
if (!in_array('youtube_url', $existing_cols)) $conn->query("ALTER TABLE articles ADD COLUMN youtube_url VARCHAR(255)");
if (!in_array('pdf_path', $existing_cols)) $conn->query("ALTER TABLE articles ADD COLUMN pdf_path VARCHAR(255)");

// Popular Dados (se articles estiver vazio)
try {
    if ($conn->query("SELECT COUNT(*) FROM articles")->fetch_row()[0] == 0) {
        // Insere Artigos
        $res = $conn->query("INSERT INTO `articles` (`id`, `title`, `author`, `description`, `category`, `youtube_url`, `pdf_path`, `created_at`, `completion_date`, `discipline`) VALUES
            (6, 'Carrossel', 'Bia Peixe n.º 15391', 'Carrossel sobre curiosidades de uma ex-aluna da ESCS', 'Audiovisual e Multimédia', '', 'uploads/pdfs/1766770386_938_Dosseir Documental.pdf', '2025-12-27 14:16:02', '2025-12-05', 'Laboratório de Produção de Conteúdos'),
            (8, 'Apresentação sobre Surrealismo', 'Bia Peixe n.º 15391, André Ventura n.º 15348, Ricardo Costa n.º 15366, Tomás Pereira n.º 14647', 'Explicação sobre uma obra surrealista e desenvolvimento de uma experiência sobre desenhar sonhos.', 'Audiovisual e Multimédia', '', 'uploads/pdfs/1766841268_801_Arte e Comunicação - grupo.pdf', '2025-12-27 14:17:20', '2025-12-10', 'Arte e Comunicação'),
            (10, 'Documentário ', 'Bia Peixe n.º 15391', 'Documentário sobre a ilha do Porto Santo e a realidade de como é sair da ilha para os ilhéus', 'Audiovisual e Multimédia', 'https://youtu.be/ruo_m0PgY70?si=HzFH3XlvOBva4eog', NULL, '2025-12-29 12:00:35', '2025-12-05', 'Documentário Transmedia'),
            (11, 'Vídeo para Youtube', 'Bia Peixe n.º 15391, Bruna Pereira n.º 14017, Ana Costa n.º 15356', 'Vídeo para explicar o percurso acadêmico de uma ex-aluna da ESCS', 'Audiovisual e Multimédia', 'https://www.youtube.com/watch?v=UEW5QnmfW-o', NULL, '2025-12-27 14:22:10', '2025-11-06', 'Laboratório de Produção de Conteúdos'),
            (12, 'Dossiê Documental', 'Bia Peixe n.º 15391', 'Explicação em formato PDF sobre o documentário', 'Audiovisual e Multimédia', '', 'uploads/pdfs/1766843817_514_Dosseir Documental.pdf', '2025-12-27 14:16:52', '2025-12-05', 'Documentário Transmedia'),
            (13, 'Reel ', 'Bia Peixe n.º 15391', 'Reel para antecipação do vídeo completo sobre uma ex-aluna da ESCS', 'Audiovisual e Multimédia', '', NULL, '2025-12-27 14:16:13', '2025-12-05', 'Laboratório de Produção de Conteúdos')");
        
        if ($res) {
            // Insere Imagens apenas se os artigos foram inseridos com sucesso
            $conn->query("INSERT INTO `article_images` (`id`, `article_id`, `image_path`) VALUES
                (15, 6, 'uploads/images/1766770716_830_15391_BiaPeixe_Carrossel1.png'),
                (16, 6, 'uploads/images/1766807785_308_15391_BiaPeixe_Carrossel1.png'),
                (17, 6, 'uploads/images/1766807785_416_15391_BiaPeixe_Carrossel2.png'),
                (18, 6, 'uploads/images/1766807785_544_15391_BiaPeixe_Carrossel3.png'),
                (19, 6, 'uploads/images/1766807785_155_BiaPeixe_15391_CarrosselMockup .png'),
                (22, 8, 'uploads/images/1766842832_466_Captura_de_ecra_2025-12-27_as_13.39.58.png'),
                (23, 10, 'uploads/images/1766843289_346_Captura_de_ecra_2025-12-27_as_13.46.03.png'),
                (24, 11, 'uploads/images/1766843564_896_Captura_de_ecra_2025-12-27_as_13.52.22.png'),
                (25, 12, 'uploads/images/1766843817_911_Captura_de_ecra_2025-12-27_as_13.54.07.png'),
                (28, 13, 'uploads/images/1766844027_193_Captura_de_ecra_2025-12-27_as_13.59.34.png')");
        }
    }
} catch (Exception $e) {
    // Ignorar erro de entrada duplicada ou semelhante na população automática
}

// Popular Utilizadores (se vazio)
if ($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] == 0) {
    $conn->query("INSERT INTO `users` (`id`, `username`, `password`) VALUES (1, 'admin', '$2y$10$YrYKiEdwkFVRoxVOpWYLq.zsWbIAM9tHkkPiY.8PP/OFwvivpfAt2')");
}

// Diretórios
foreach (['uploads/images', 'uploads/pdfs'] as $d) if (!is_dir($d)) mkdir($d, 0777, true);


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
        $item['cover'] = $item['images'][0]['path'] ?? 'https://placehold.co/800x600?text=' . urlencode($item['title']);
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

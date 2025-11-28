<?php
// Dil seçimi yönetimi
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'tr'; // Varsayılan Türkçe
}

// URL veya form tarafından dil değiştirilmesi
if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    // AJAX isteğiyse JSON döndür
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'language' => $_SESSION['language']]);
        exit;
    }
}

// Dil dosyası çağırma
$language = $_SESSION['language'];
$lang_file = dirname(__FILE__) . '/languages/' . $language . '.php';

if (file_exists($lang_file)) {
    include $lang_file;
} else {
    // Türkçe varsayılan
    include dirname(__FILE__) . '/languages/tr.php';
}
?>

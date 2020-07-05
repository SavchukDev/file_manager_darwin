<?php

function sortByFilename($a, $b)
{
    return ($a['filename'] > $b['filename']);
}

function sortBySize($a, $b)
{
    return $a['size'] > $b['size'];
}

function sortByDate($a, $b)
{
    return strtotime($a["modify_date"]) - strtotime($b["modify_date"]);
}

function redirect(string $path)
{
    header('Location:' . $path);
}


function pathToBack($path)
{
    $parts = explode('/', $path);
    array_pop($parts);
    $path = implode('/', $parts);
    return $path;
}

//URI
$uri = explode('/', $_SERVER['REQUEST_URI']);
array_shift($uri); // пропускаем пустй элемент
$method = array_shift($uri);
$file_name = explode('?', urldecode(implode('/', $uri)))[0];
$file_permission = substr(sprintf('%o', fileperms($file_name)), -4);

//delete
if (isset($_GET['delete'])) {
    $dir = $_GET['delete'];
    if (is_dir($_GET['delete'])) {
        system("rm -rf " . escapeshellarg($dir));
    } else {
        unlink($_GET['delete']);
    }
    redirect($_SERVER['HTTP_REFERER']);
}

//set current path
if (!empty($file_name)) {
    $currentPath = $file_name;
} else {
    $currentPath = '.';
}

$currentFileList = scandir($currentPath);
$prepareFiles = [];
foreach ($currentFileList as $file) {
    if (in_array($file, ['.', '..'])) continue;
    $fullFilePath = $currentPath . '/' . $file;
    $prepareFiles[] = [
        'filename' => $file,
        'type' => is_dir($fullFilePath) ? 'Dir' : 'File',
        'size' => filesize($fullFilePath),
        'permission' => substr(sprintf('%o', fileperms($fullFilePath)), -4),
       // 'owner' => posix_getpwuid(fileowner($fullFilePath))['name'],
        'modify_date' => date("Y-m-d H:i:s", filemtime($fullFilePath)),
    ];
}

//apply sorting
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'filename':
            uasort($prepareFiles, 'sortByFilename');
            break;
        case 'size':
            uasort($prepareFiles, 'sortBySize');
            break;
        case 'modify_date':
            uasort($prepareFiles, 'sortByDate');
            break;
    }
}

//apply sorting
if (isset($_POST['edit'])) {
    file_put_contents($_POST['old_filepath'], $_POST['content']);
    rename($_POST['old_filepath'], $_POST['new_filepath']);
    redirect('/edit/' . pathinfo($_POST['new_filepath'], PATHINFO_DIRNAME));
}

//save new permissions
if (isset($_POST['edit_permission'])) {
    $r = octdec($_POST['mode']);
    chmod($_POST['filepath'], octdec($_POST['mode']));
    redirect('/edit/' . pathinfo($_POST['filepath'], PATHINFO_DIRNAME));
}

//save new file to current dir
if (isset($_FILES['file'])) {
    $uploadfile = $currentPath . '/' . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
    redirect('/edit/' . $currentPath);
}

//create new folder to current dir
if (isset($_POST['create_folder'])) {
    mkdir($currentPath . '/' . $_POST['create_folder']);
    redirect('/edit/' . $currentPath);
}
?>
<html>
<head>
    <link href="/main.css" rel="stylesheet">
</head>
<body>
<a href="/">To main page</a>
<a href="/edit/<?= pathToBack($currentPath); ?>">Back</a>
<?php if ($method == 'edit' and is_file($file_name)) : ?>
    <h2>File to edit: <?= $file_name; ?></h2>
    <form method="post" action="">
        <textarea rows="20" name="content"><?= file_get_contents($file_name) ?></textarea>
        <input type="hidden" value="<?= $file_name ?>" name="old_filepath">
        <input type="text" value="<?= $file_name ?>" name="new_filepath">
        <input type="submit" name="edit" value="send">
    </form>
<?php elseif ($method == 'permission'): ?>
    <h2>File to edit permission: <?= $file_name; ?> (<?= $file_permission; ?>)</h2>
    <form method="post" action="">
        <input type="text" name="mode">
        <input type="hidden" value="<?= $file_name ?>" name="filepath">
        <input type="submit" name="edit_permission" value="send">
    </form>
<?php else: ?>
    <h2>Current path: <?= $currentPath; ?></h2>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" value="send">
    </form>
    <form method="post" action="">
        <label for="exampleInputPassword1">Create folder</label>
        <input id="create_folder" type="text" name="create_folder">
        <input type="submit" value="send">
    </form>
    <table class="table">
        <thead>
        <tr>
            <th><a href="?sort=filename&path=<?= $currentPath; ?>">Filename</a></th>
            <th>Type</th>
            <th><a href="?sort=size&path=<?= $currentPath; ?>">Size</a></th>
            <th>Permission</th>
            <th>Owner</th>
            <th><a href="?sort=modify_date&path=<?= $currentPath; ?>">Modify date</a></th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($prepareFiles as $fileInfo): ?>
            <tr>
                <td>
                    <a href="/edit/<?= $currentPath . '/' . $fileInfo['filename'] ?>"><?= $fileInfo['filename'] ?></a>
                </td>
                <td><?= $fileInfo['type'] ?></td>
                <td><?= $fileInfo['size'] ?></td>
                <td>
                    <a href="/permission/<?= $currentPath . '/' . $fileInfo['filename'] ?>"><?= $fileInfo['permission'] ?></a>
                </td>
                <td><?= $fileInfo['owner'] ?></td>
                <td><?= $fileInfo['modify_date'] ?></td>
                <td><a href="?delete=<?= $currentPath . '/' . $fileInfo['filename'] ?>">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
<html>

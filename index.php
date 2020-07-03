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

function pathToBack($path){
    $parts  = explode('/', $path);
    array_pop($parts);
    $path = implode('/', $parts);
    return $path;
}

//delete
if (isset($_GET['delete'])) {
    if (is_dir($_GET['delete'])) {
        rmdir($_GET['delete']);
    } else {
        unlink($_GET['delete']);
    }
    redirect($_SERVER['HTTP_REFERER']);
}

if (isset($_GET['path'])) {
    $currentPath = $_GET['path'];
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
        'owner' => posix_getpwuid(fileowner($fullFilePath))['name'],
        'modify_date' => date("Y-m-d H:i:s", filemtime($fullFilePath)),
    ];
}

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

if (isset($_POST['edit'])) {
    file_put_contents($_POST['old_filepath'], $_POST['content']);
    rename($_POST['old_filepath'], $_POST['new_filepath']);
    redirect('?path=' . pathinfo($_POST['new_filepath'], PATHINFO_DIRNAME));
}
if (isset($_POST['edit_permission'])) {
    $r = octdec($_POST['mode']);
    chmod($_POST['filepath'], octdec($_POST['mode']));
    redirect('?path=' . pathinfo($_POST['filepath'], PATHINFO_DIRNAME));
}

if (isset($_FILES['file'])) {
    $uploadfile = $currentPath . '/' . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
    redirect('?path=' .$currentPath);
}

if (isset($_POST['create_folder'])) {
    mkdir($currentPath . '/' . $_POST['create_folder']);
    redirect($_SERVER['PHP_SELF']);
}
?>
<html>
<head>
    <link href="main.css" rel="stylesheet">
</head>
<body>
<a href="/">To main page</a>
<a href="?path=<?= pathToBack($currentPath); ?>">Back</a>
<?php if (isset($_GET['edit'])): ?>
    <h2>File to edit: <?= $_GET['edit']; ?></h2>
    <form method="post" action="">
        <textarea rows="20" name="content"><?= file_get_contents($_GET['edit']) ?></textarea>
        <input type="hidden" value="<?= $_GET['edit'] ?>" name="old_filepath">
        <input type="text" value="<?= $_GET['edit'] ?>" name="new_filepath">
        <input type="submit" name="edit" value="send">
    </form>
<?php elseif (isset($_GET['permission'])): ?>
    <h2>File to edit permission: <?= $_GET['permission']; ?> (<?= $_GET['current_mode']; ?>)</h2>
    <form method="post" action="">
        <input type="text" name="mode">
        <input type="hidden" value="<?= $_GET['permission'] ?>" name="filepath">
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
                    <?php $action = $fileInfo['type'] == 'Dir' ? 'path' : 'edit'; ?>
                    <a href="?<?= $action . '=' . $currentPath . '/' . $fileInfo['filename'] ?>"><?= $fileInfo['filename'] ?></a>
                </td>
                <td><?= $fileInfo['type'] ?></td>
                <td><?= $fileInfo['size'] ?></td>
                <td>
                    <a href="?current_mode=<?= $fileInfo['permission'] ?>&&permission=<?= $currentPath . '/' . $fileInfo['filename'] ?>"><?= $fileInfo['permission'] ?></a>
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
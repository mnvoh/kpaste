<?php 
//1ZlY8f^vR)qGXYz$
session_start();
error_reporting(E_ALL);
ini_set('display_errors', true);
if(isset($_POST['password']))
{
    if(sha1($_POST['password']) == 'a9b6429a7738aaea1f6bfbeb0a78c8fc48d6b28c')
    {
        $_SESSION['granted'] = true;
    }
}

if(isset($_FILES['kpastetargz']) && $_FILES['kpastetargz'] && $_SESSION['granted'])
{
    if($_FILES['kpastetargz']['error'] <= 0)
    {
        if($_FILES['kpastetargz']['type'] == 'application/x-gzip')
        {
            if($_FILES['kpastetargz']['name'] == 'kpaste.tar.gz')
            {
                if(!file_exists(__DIR__ . '/../kpaste.tar.gz'))
                {
                    if(move_uploaded_file($_FILES['kpastetargz']['tmp_name'],
                            __DIR__ . '/../kpaste.tar.gz'))
                    {
                        chdir(dirname(__DIR__));
                        exec('tar xvzf kpaste.tar.gz');
                        $_SESSION['noti'] = 'Update Successful!';
                        unlink(__DIR__ . '/../kpaste.tar.gz');
                    }
                    else
                    {
                        $_SESSION['error'] = 'There was a problem moving the uploaded file!';
                    }
                }
                else
                {
                    unlink(__DIR__ . '/../kpaste.tar.gz');
                    if(move_uploaded_file($_FILES['kpastetargz']['tmp_name'],
                            __DIR__ . '/../kpaste.tar.gz'))
                    {
                        chdir(dirname(__DIR__));
                        exec('tar xvzf kpaste.tar.gz');
                        $_SESSION['noti'] = 'Update Successful!';
                    }
                    else
                    {
                        $_SESSION['error'] = 'There was a problem moving the uploaded file!';
                    }
                }
            }
            else
            {
                $_SESSION['error'] = 'Invalid File Name! "kpaste.tar.gz" expected, ' . 
                        $_FILES['kpastetargz']['name'] . ' given.';
            }
        }
        else
        {
            $_SESSION['error'] = 'Invalid File Type! application/x-gzip expected, ' . 
                    $_FILES['kpastetargz']['type'] . ' given.';
        }
    }
    else
    {
        $_SESSION['error'] = 'Upload failed! Error: ' . $_FILES['kpastetargz']['error'];
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Update Kpaste</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <?php if(isset($_SESSION['error'])): ?>
        <div class='error'>
            <p>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </p>
        </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['noti'])): ?>
        <div class='noti'>
            <p>
                <?php echo $_SESSION['noti']; unset($_SESSION['noti']); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['granted'])): ?>
        <form action="" method="post" enctype="multipart/form-data">
            Select 'kpaste.tar.gz':
            <input type="file" name="kpastetargz" id="kpastetargz"><br />
            <input type="submit" name="submit" value="Update">
        </form>
        <?php else: ?>
        <form action="" method="post">
            <label>
                Password:
                <input type="password" name="password" />
            </label>
            <input type="submit" value="Authenticate" />
        </form>
        <?php endif; ?>
    </body>
</html>

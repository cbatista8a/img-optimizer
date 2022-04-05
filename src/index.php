<!doctype html>
<html lang="en" class="h-100">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Image Optimizer</title>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
    <style>
        .link-download{
            position: fixed;
            top: 50px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center h-100 d-flex pt-3 pb-3 justify-content-center">
<div class="container d-flex">
    <form class="form w-100 m-auto align-middle" action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="optimize" value="1">
        <div class="row mb-3">
            <div class="col-6 form-group">
                <label for="upload" class="sr-only">Chose a folder</label>
                <input id="upload" class="form-control" name="upload[]" type="file" webkitdirectory = "true" multiple/>
                <input class="form-input" id="json-files" name="files" type="hidden" value=""/>
            </div>
            <div class="col-6 form-group">
                <label for="quality" class="sr-only">Select Quality</label>
                <input id="quality" class="form-control form-range" name="quality" type="range" min="1" max="100" step="1" value="75" placeholder="Quality" oninput="this.nextElementSibling.value = this.value"/>
                <output>75</output>
            </div>
        </div>
        <div class="row">
            <button id="btn-submit" class="btn btn-primary btn-block" type="submit">Optimize</button>
        </div>
    </form>
</div>
<script>
    document.getElementById("upload").addEventListener("change", function(event) {
        let data = {};
        let files = event.target.files;
        for (let i=0; i<files.length; i++) {
            data[files[i].name] = {'filename' : files[i].name,'remote_path' : files[i].webkitRelativePath};
        }
        document.getElementById("json-files").value = JSON.stringify(data);
    }, false);
</script>

</body>
</html>




<?php
if (empty($_POST['optimize'])){
    return;
}

use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\Finder\Finder;

require_once 'vendor/autoload.php';

const SEPARATOR = DIRECTORY_SEPARATOR;

$quality = (int)$_POST['quality'] ?: 75;
$path = __DIR__.SEPARATOR.'optimized';
$optimizer = OptimizerChainFactory::create(['quality' => $quality]);
if (!verifyOutputDir($path)){
    throw new Exception("Verify permissions on directory: ". $path);
}

uploadFiles($path);
$finder = new Finder();
$finder->files()->exclude(['vendor','node_modules'])->name(['*.jpg', '*.jpeg', '*.png'])->in($path);
$files = $finder->getIterator();
/** @var SplFileInfo $file */
foreach ($files as $file) {
    $optimizer->optimize($file->getRealPath());
}

if (!file_exists($path)){
    return;
}
exec("rm optimized.tar.gz");
Compressor::compress($path);
exec("rm -rf {$path}");
if (file_exists("optimized.tar.gz")){
    echo "<a class='link-download' href='/optimized.tar.gz' download='optimized.tar.gz'>Download</a>";
}



function verifyOutputDir($dir): bool
{
    if (!file_exists($dir)){
       return mkdir($dir,0775,true);
    }
    return true;
}

function uploadFiles($path){
    $data = json_decode($_POST['files'],true);
    foreach ($_FILES['upload']['name'] as $key => $file){
        $base_path = $path.SEPARATOR.$data[$file]['remote_path'];
        if (verifyOutputDir(dirname($base_path))){
            move_uploaded_file($_FILES['upload']['tmp_name'][$key], $base_path);
        }
    }
}

class Compressor
{
    public static function compress($path){
        try
        {
            $tar = new PharData('optimized.tar');

            // ADD FILES TO archive.tar FILE
            $exclude = '/^(?!(.*optimized.tar|var\/cache.*))(.*)$/i';
            $tar->buildFromDirectory($path,$exclude);

            // COMPRESS optimized.tar FILE. COMPRESSED FILE WILL BE optimized.tar.gz
            $tar->compress(Phar::GZ);

            // NOTE THAT BOTH FILES WILL EXISTS. SO IF YOU WANT YOU CAN UNLINK optimized.tar
            exec('rm optimized.tar');
        }
        catch (Exception $e)
        {
            echo "Exception : " . $e;
        }
    }

}
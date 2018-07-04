<?php namespace palma;

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Authors: Alexander Wagner, Stefan Weil

// Test whether the script was called directly (used for unit test).
if (!isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = (sizeof(get_included_files()) == 1);



class FehHandler extends FileHandler
{
    public function getControls()
    {
        return FileHandler::CURSOR | FileHandler::ZOOM;
    }
    public function show($path)
    {
    }
}

class LibreOfficeHandler extends FileHandler
{
    public function getControls()
    {
        return FileHandler::CURSOR | FileHandler::ZOOM;
    }
    public function show($path)
    {
    }
}

class MidoriHandler extends FileHandler
{
    public function getControls()
    {
        return FileHandler::CURSOR | FileHandler::ZOOM;
    }
    public function show($path)
    {
    }
}

class VlcHandler extends FileHandler
{
    public function getControls()
    {
        return FileHandler::CURSOR | FileHandler::ZOOM;
    }
    public function show($path)
    {
    }
}

class ZathuraHandler extends FileHandler
{
    public function getControls()
    {
        return FileHandler::CURSOR | FileHandler::ZOOM |
               FileHandler::HOME | FileHandler::END |
               FileHandler::PRIOR | FileHandler::NEXT |
               FileHandler::DOWNLOAD;
    }
    public function show($path)
    {
    }
}

abstract class FileHandler
{

    // Constants for allowed controls.
    const UP = 1;
    const DOWN = 2;
    const LEFT = 4;
    const RIGHT = 8;
    const ZOOMIN = 16;
    const ZOOMOUT = 32;
    const HOME = 64;
    const END = 128;
    const PRIOR = 256;
    const NEXT = 512;
    const DOWNLOAD = 1024;
    const COUNTERCLOCKWISE = 2048;
    const CLOCKWISE = 4096;

    // Shortcuts for combinations of controls.
    const CURSOR = 15; // UP | DOWN | LEFT | RIGHT
    const ZOOM = 48;   // ZOOMIN | ZOOMOUT
    const ALL = 2047;

    // up down left right zoomin zoomout home end prior next download

    // protected $FILES = array();
    // protected $UPLOAD_PATH;

    abstract protected function getControls();
    abstract protected function show($path);

    public static function getFileHandler($file)
    {

        // Get get directory, name and file extension
        $pathParts = pathinfo($file);
        $ftype = strtolower($pathParts['extension']);
        $fdir = $pathParts['dirname'];
        $fname = $pathParts['filename'];
        $fhandler = "";

        // Define filehandlers
        $pdfHandler = '/usr/bin/zathura';
        $imageHandler = '/usr/bin/feh --scale-down';
        $webHandler = '/usr/bin/midori -p';
        $avHandler = '/usr/bin/cvlc --no-audio';
        $officeApp = "";

        // $params;
        // echo $ftype;
        if ($ftype === 'pdf') {
            $fhandler=$pdfHandler;
        } elseif ($ftype === 'gif' || $ftype === 'jpg' || $ftype === 'png') {
            $fhandler=$imageHandler;
        } elseif ($ftype === 'html' || $ftype === 'url') {
            $fhandler=$webHandler;
        } elseif ($ftype === 'mpg' || $ftype === 'mpeg' || $ftype === 'avi' ||
                  $ftype === 'mp3' || $ftype === 'mp4') {
            $fhandler=$avHandler;
        } else {
            if ($ftype === 'doc' || $ftype === 'docx' || $ftype === 'odt' || $ftype === 'txt') {
                $officeApp = "writer";
            } elseif ($ftype === 'ppt' || $ftype === 'pptx' || $ftype === 'pps' || $ftype === 'ppsx' || $ftype === 'odp') {
                $officeApp = "impress";
            } elseif ($ftype === 'xls' || $ftype === 'xlsx' || $ftype === 'ods') {
                $officeApp = "calc";
            }
            $convertedFile = convertOffice($file, $officeApp, $fdir, $fname);
            if ($convertedFile) {
                $file = $convertedFile;
                $fhandler = $pdfHandler;
            } else {
                $fhandler = "/usr/bin/libreoffice --'$officeApp' --nologo --norestore -o";
            }
        }

        /*
        alternatively with mime-types

            // $ftype = mime_content_type($this->UPLOAD_PATH.$file);
            // if($ftype=='application/pdf')
            // if($ftype=='image/gif' || $ftype=='image/jpg' || $ftype=='image/png' )
            // if($ftype=='html' || $ftype=='url' || $ftype="text/plain")
            // (...)

        */

        return array($fhandler, $file);
    }
}

function convertOffice($inputFile, $office, $outputDir, $fileName)
{
    shell_exec("/usr/bin/libreoffice --headless --convert-to pdf:'$office'_pdf_Export --outdir '$outputDir' '$inputFile' >/dev/null 2>&1");
    $newFile=$outputDir . '/' . $fileName . '.pdf';
    if (file_exists($newFile)) {
        return $newFile;
    } else {
        return false;
    }
}

if ($unittest[__FILE__]) {
    // Run unit test.
    $midoriHandler = new MidoriHandler;
    $zathuraHandler = new ZathuraHandler;
    echo("DOWNLOAD   =" . FileHandler::DOWNLOAD . "\n");
    echo("filehandler=" . FileHandler::getFileHandler("test.txt") . "\n");
    $handler = ${'midori' . 'Handler'};
    echo("controls   =" . $handler->getControls() . "\n");
}

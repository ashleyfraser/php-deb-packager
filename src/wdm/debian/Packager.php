<?php 
namespace wdm\debian;

use wdm\debian\control\StandardFile;

/**
 * 
 * Main packager
 *
 * @author Walter Dal Mut
 * @package 
 * @license MIT
 *
 * Copyright (C) 2012 Corley S.R.L.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
class Packager 
    implements IPackager
{
    private $_control;
    private $_mountPoints = array();
    private $_outputPath;
    
    public function setControl(StandardFile $control)
    {
        $this->_control = $control;
        return $this;
    }
    
    public function mount($sourcePath, $destinationPath)
    {
        $this->_mountPoints[$sourcePath] = $destinationPath;
        return $this;
    }
    
    public function setOutputPath($path)
    {
        $this->_outputPath = $path;
        return $this;
    }
    
    public function run()
    {
        if (file_exists($this->_outputPath)) {
            $iterator = new \DirectoryIterator($this->_outputPath);
            foreach ($iterator as $path) {
                if ($path != '.' || $path != '..'); {
                    echo "OUTPUT DIRECTORY MUST BE EMPTY! Something exists, exit immediately!" . PHP_EOL;
                    exit();
                }
            }
        }
        
        foreach ($this->_mountPoints as $path => $dest) {
            $this->_pathToPath($path, $this->_outputPath . DIRECTORY_SEPARATOR . $dest);
        }
        
        mkdir($this->_outputPath . "/DEBIAN", 0777);
        file_put_contents($this->_outputPath . "/DEBIAN/control", (string)$this->_control);
        
        return $this;
    }
    
    private function _pathToPath($path, $dest) 
    {
        if (is_dir($path)) {
            $iterator = new \DirectoryIterator($path);
            foreach ($iterator as $element) {
                if ($element != '.' && $element != '..') {
                    $fullPath = $path . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($fullPath)) {
                        $this->_pathToPath($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    } else {
                        $this->_copy($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    }
                }
            }
        } else if (is_file($path)) {
            $this->_copy($path, $dest);
        }
    }
    
    private function _copy($source, $dest) 
    {
        $destFolder = dirname($dest);
        if (!file_exists($destFolder)) {
            mkdir($destFolder, 0777, true);
        }
        copy($source, $dest);
    }
    
    public function build($debPackageName = false)
    {
        if (!$debPackageName) {
            $debPackageName = basename($this->_outputPath . ".deb");
        }
        
        $command = "dpkg -b {$this->_outputPath} {$debPackageName}" . PHP_EOL;
        
        echo $command;
    }
}
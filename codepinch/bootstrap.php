<?php

/**
  Copyright (c) 2016 VASYLTECH.COM

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
 */

if (defined('ABSPATH') && !defined('CODEPINCH_LOADED')) {
    require dirname(__FILE__) . '/Core.php';

    //bootstrap CodePinch core
    CodePinch_Core::bootstrap();
    
    //Define autoskip
    CodePinch_Core::set('autoskip', array(
        '/^require\(\)[^:]*: Unable to allocate memory for pool\.$/',
        '/^include\(\)[^:]*: Unable to allocate memory for pool\.$/',
        '/^require_once\(\)[^:]*: Unable to allocate memory for pool\.$/',
        '/^include_once\(\)[^:]*: Unable to allocate memory for pool\.$/',
        '/^Out of memory \(allocated [\d]+\) \(tried to allocate [\d]+ bytes\)$/',
        '/^Maximum execution time of [\d]+ seconds exceeded$/',
        '/^Allowed memory size of [\d]+ bytes exhausted.*$/',
        '/^Cannot call overloaded function for non-object$/',
        '/^include_once\([^)]+\): failed to open stream:.*$/',
        '/^opendir\([^)]+\): failed to open dir: Too many open files in system$/',
        '/^require_once\([^)]+\): failed to open stream: Too many open files in system$/',
        '/^require_once\([^)]+\): failed to open stream: Interrupted system call$/',
    ));
    
    //error fix can be loaded only once
    define('CODEPINCH_LOADED', true);
}
rewriter
========

Generate Apache Rewrite Rules from a given list of source / destination URLs. 


Usage
=====

Clone the repository from Github

    $ git clone git://github.com/owenbush/rewriter.git
    
Include the Rewriter class in your PHP script

    require_once('/path/to/Rewriter.php');
    
Create a new instance of the Rewriter class, specifying the following parameters

* $includeIfModuleCheck   (default true) - Wrap your rewrite rules in an <IfModule> check
* $includeTurnOnEngine    (default true) - Add a RewriteEngine on line to your mod_rewrite rules
* $http301                (default true) - Specify rewrites as 301 redirects, false will perform 302

Example

    $rewriter = new Rewriter(true, true, true);
    
Pass URL pairs to the class as either an array or a string

    $urls = array(
      '/mySourceURL.html' => '/myDestinationURL.html',
      '/somePage.php?action=doSave' => '/something/action/save',
      '/subdir/script.js' => '/newscript.js'    
    );
    $rewriteRules = $rewriter->generateRewritesFromArray($urls);
    
String - You can specify the separator (default space) and linebreak character (default "\n")

    $urls = "/mySourceURL.html /myDestinationURL.html
    /somePage.php?action=doSave /something/action/save
    /subdir/script.js /newscript.js";
    $rewriteRules = $rewriter->generateRewritesFromString($urls, " ", "\n");
    
    
You will receive back a string from the class like the following

    <IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteRule ^mySourceURL\.html$ /myDestinationURL.html? [R=301,L,NC]
    RewriteCond %{QUERY_STRING} ^action\=doSave$
    RewriteRule ^somePage\.php$ /something/action/save? [R=301,L,NC]
    RewriteRule ^subdir/script\.js$ /newscript.js? [R=301,L,NC]
    </IfModule>
    
You can then use this in your virtualhost declaration or in your .htaccess file.


License
========
(The MIT License)

Copyright (c) 2013 Owen Bush <owen@obush.co.uk>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


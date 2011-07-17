History
-------

After spending several days looking for a decent PHP framework, and after spending spending countless hours playing
around with Symfony, Yii, CodeIgniter, Kohana and Cake I decided that Rasmus was probably right with his
[No-Framework MVC] (http://toys.lerdorf.com/archives/38-The-no-framework-PHP-MVC-framework.html) so I decided to roll out my own.
My problems with these frameworks varied:

1. Not built on OO concepts, lots of files of functions.
2. The number of config files you had to edit (and use of strange markups like Yaml).
3. The steep learning curve required to be locked into one framework
4. The fact that in PHPStorm most of them look like an explosion of Red and Yellow errors in the trough (seeing a nice green square at the top of makes me happy).
5. Documented in a way that intellisense and goto/function wasn't working.
6. Didn't use PHPUnit for testing (want to be able to run unit tests in PHPStorm).
7. The amount of magic required.

Basically I wanted something that would work very well with [PHPStorm] (http://www.jetbrains.com/phpstorm/) and looked
and felt the way I felt a MVC framework should feel like.  Half-way through starting I was researching the easiest way to do
Routing I happened upon [kissmvc] (http://kissmvc.com/), and I thought it was probably the simplest of all the MVC Framworks I looked at.
I started using it, and then started getting annoyed by little things that I am opinionated about, and decided to just
fork it and make my own version.  I decided to release it, in cause anyone else in the world had similar opinions and
wanted to use it as a basis of their own very simple MVC.  The word KrisMVC is a play on kissmvc as my name is Kris...

About KrisMVC
-------------

Basically the MVC is 5 classes, index.php a bunch of .htaccess files and a directory structure.   Eventually I hope to
add Model generation, Authentication, Multilingual support, a better ORM and caching, but for now it is very very simple.

 
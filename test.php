<?php
    require_once './vendor/autoload.php';
    use hypergo\user\User;
    use hypergo\utils\Database;
    use hypergo\utils\Code;
    $u = new User("123456","12345678");
    var_dump($u->register());
    var_dump($u->login());
    var_dump(User::checkLogin());
    var_dump(Code::getRandomCode(2, 5));
    var_dump((json_decode($_SESSION["login_info"]))->username);
    $a = [];
    $a["ss"] = "fff";
    $a[] = "aaa";
    var_dump(ini_get("session.gc_maxlifetime"));
    
    // $loader = new \Twig\Loader\FilesystemLoader('./templates');
    // $twig = new \Twig\Environment($loader, [
    //     "cache" => "./cache",
    //     "auto_reload" => true
    // ]);
    
    // $template = $twig->load('index.html');
    // //echo $template->render(['content' => 'Twig is a modern template engine for PHP.']);
    // $template->display(['content' => 'Twig is a modern template engine for PHP.']);


// $memcache = new Memcache;

// $memcache->connect('127.0.0.1',11211) or die('shit');

// //$memcache->set('key','hello memcache!');

// $out = $memcache->get('key');

// echo $out;

// ini_set("session.save_handler", "memcache");
// ini_set("session.save_path", "tcp://127.0.0.1:11211");

// session_start();
// $_SESSION["test"] = "ttttttttttttttt";
// //echo $_SESSION["test"];
// echo $memcache->get('0sad9p5et9s0e1vp50j3r2t3mp');
// phpinfo();

// echo session_create_id()."<br>";
// echo session_create_id()."<br>";
// echo session_create_id()."<br>";
// echo session_create_id()."<br>";
?>
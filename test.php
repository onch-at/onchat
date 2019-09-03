<?php
    require_once './vendor/autoload.php';
    use hypergo\user\User;
    use hypergo\utils\Database;
    use hypergo\utils\Code;
    use hypergo\utils\Session;

    //echo date("Y-n-j H:i", time());
    var_dump((5 > 1)?"0":"1");

    
    // Session::start();
    // var_dump(empty($_SESSION["login_info"]));
    // var_dump(json_decode("json_encode()"));
    
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

// $memcache->set('key','hello memcache!');

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

<div class="f" style="height: 3rem; overflow:hidden;background: #448aff">
    <div id="ddd" style="background: #ffab40; height: 0">11111111 11111111111 11111111111111 1111111 1111111 11111111 1111</div>
    <div style="background: #f50057">11111111 11111111111 11111111111111 1111111 1111111 11111111 1111</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>
            $(".f").mouseover(
                function() {
                    $("#ddd",this).animate({height:"3rem"});
                });
            $(".f").mouseleave(
                function() {
                    $("#ddd",this).animate({height:"0"});
                });
        </script>

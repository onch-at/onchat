<?php
namespace hypergo\user;

use hypergo\utils\Config;
use hypergo\utils\Session;
use hypergo\utils\Database;
use Medoo\Medoo;

class User {
    private $uid; //UserID
    private $username; //用户名
    private $password; //用户密码(明文)
    
    private $database; //数据库(Medoo)
    
    private $errorMsg = ""; //错误消息
    
    const PASSWORD_COST = 11; //这里配置bcrypt算法的代价，值越大越安全同时也越慢，根据需要来随时升级
    const PASSWORD_ALGO = PASSWORD_BCRYPT; //默认使用（现在也只能用）bcrypt
    
    const USER_NAME_MIN_LENGTH = 5; //用户名最小长度
    const USER_NAME_MAX_LENGTH = 20; //用户名最大长度
    const USER_PASSWORD_MIN_LENGTH = 8; //用户密码最小长度
    const USER_PASSWORD_MAX_LENGTH = 50; //用户密码最大长度
    
    const STATUS_SUCCESS = 0; //成功
    const STATUS_UNKNOWN_ERROR = 1; //未知错误
    const STATUS_USER_REPEAT = 2; //用户已存在
    const STATUS_USER_NOT_EXIST = 3; //用户不存在
    const STATUS_USER_PASSWORD_ERROR = 4; //用户密码错误
    const STATUS_USER_NAME_SHORT = 5; //用户名过短
    const STATUS_USER_NAME_LONG = 6; //用户名过长
    const STATUS_USER_PASSWORD_SHORT = 7; //用户密码过短
    const STATUS_USER_PASSWORD_LONG = 8; //用户密码过长

    /**
     * 实例化User
     *
     * @param string $username 用户名
     * @param string $password 用户密码（明文）
     */
    public function __construct(string $username, string $password) {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setDatabase(Database::getInstance());
    }

    /**
     * 获取用户UID
     *
     * @return integer
     */
    public function getUid():int {
        return $this->uid;
    }

    /**
     * 设置用户UID
     *
     * @param integer $uid
     * @return void
     */
    public function setUid(int $uid) {
        $this->uid = $uid;
    }

    /**
     * 获取用户名
     *
     * @return string
     */
    public function getUsername():string {
        return $this->username;
    }

    /**
     * 设置用户名
     *
     * @param string $username 用户名
     * @return void
     */
    public function setUsername(string $username) {
        $this->username = str_replace(" ", "", $username);
    }

    /**
     * 获取用户密码（明文）
     *
     * @return string
     */
    public function getPassword():string {
        return $this->password;
    }
    
    /**
     * 设置用户密码
     *
     * @param string $password 用户密码
     * @return void
     */
    public function setPassword(string $password) {
        $this->password = $password;
    }
    
    /**
     * 获取Medoo数据库连接实例
     *
     * @return Medoo
     */
    public function getDatabase():Medoo {
        return $this->database;
    }

    /**
     * 进行数据库连接
     *
     * @param Database $database 数据库实例
     * @return void
     */
    public function setDatabase(Database $database) {
        $this->database = $database;
    }

    /**
     * 获取错误消息
     *
     * @return string
     */
    public function getErrorMessage():string {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误消息
     *
     * @param string $msg 错误消息
     * @return void
     */
    public function setErrorMessage(string $msg) {
        $this->errorMsg = $msg;
    }

    /**
     * 用户登录
     *
     * @return mixed
     */
    public function login() {
        $database = $this->getDatabase();

        $check = $this->checkUsername();
        //如果用户名长度不符合
        if ($check !== self::STATUS_SUCCESS) return $check;
      
        $check = $this->checkPassword();
        //如果密码长度不符合
        if ($check !== self::STATUS_SUCCESS) return $check;

        $data = $database->select("account", ["uid", "username", "password"], [
            "username" => $this->getUsername(),
            "LIMIT" => 1
        ]);
            
        if (empty($data)) return self::STATUS_USER_NOT_EXIST; //查询不到该用户

        $password = $data[0]["password"]; //密文
        
        //如果密码错误
        if (!User::verify($this->getPassword(), $password)) return self::STATUS_USER_PASSWORD_ERROR;
        
        $this->setUid($data[0]["uid"]);

        Session::start();
        
        $data = [
            "uid" => (int) $data[0]["uid"],
            "username" => $data[0]["username"],
            "password" => $password, //密文
            "expire" => time() + 86400, //1天后清除登录缓存
        ];
        $_SESSION["login_info"] = json_encode($data);

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            $data["expire"],
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
        
        return self::STATUS_SUCCESS;
    }

    /**
     * 用户注册
     *
     * @return mixed
     */
    public function register() {
        $database = $this->getDatabase();

        $check = $this->checkUsername();
        //如果用户名长度不符合
        if ($check !== self::STATUS_SUCCESS) return $check;
      
        $check = $this->checkPassword();
        //如果密码长度不符合
        if ($check !== self::STATUS_SUCCESS) return $check;

        //查询该用户是否已存在
        $data = $database->select("account", "uid", [
            "username" => $this->getUsername(),
            "LIMIT" => 1
        ]);

        //如果不为空，则代表当前用户已被注册
        if (!empty($data)) return self::STATUS_USER_REPEAT;
        
        $password = User::encode($this->getPassword());

        //插入用户数据到数据库
        $database->insert("account", [ 
            "username" => $this->getUsername(),
            "password" => $password
        ]);

        $timestamp = time();
        $birthday = getdate($timestamp);

        $database->insert("user_info", [ 
            "uid" => User::getUidByUsername($this->getUsername()),
            "nickname" => $this->getUsername(),
            "birthday" => date("Y-m-d", $timestamp),
            "age" => 0,
            "constellation" => User::getConstellation($birthday["mon"], $birthday["mday"])
        ]);
        
        //0 => SQL STATE
        //1 => 错误码
        //2 => 错误消息
        $info = $database->error();
        if ($info[0] !== "00000") { //如果SQLSTATE不为00000，即代表有错误
            $this->setErrorMessage($info[2]);
            return self::STATUS_UNKNOWN_ERROR;
        }

        Session::start();
        
        $data = [
            "uid" => (int) User::getUidByUsername($this->getUsername()),
            "username" => $this->getUsername(),
            "password" => $password, //密文
            "expire" => time() + 86400, //1天后清除登录缓存
        ];
        $_SESSION["login_info"] = json_encode($data);

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            $data["expire"],
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
        
        return self::STATUS_SUCCESS;
    }

    /**
     * 用户登出
     *
     * @return void
     */
    public static function logout() {
        Session::start();

        if (!empty($_SESSION["login_info"])) unset($_SESSION["login_info"]);
    }
    /**
     * 检查用户是否已经登录
     * 验证方式: 检查session，检查过期时间，检查是否有这个用户，检查密码
     *
     * @return boolean
     */
    public static function checkLogin():bool {
        Session::start();

        //如果服务器上没有对应的session
        if(empty($_SESSION["login_info"])) return false;
        
        $obj = json_decode($_SESSION["login_info"]);
          
        if ($obj->expire < time()) return false; //如果缓存时间过期

        $database = Database::getInstance();

        $data = $database->select("account", "password", [
            "username" => $obj->username,
            "LIMIT" => 1
        ]);

        if (empty($data) or $obj->password !== $data[0]) return false; //查询不到该用户//密码错误
    
        return true;
    }

    /**
     * 检测用户名是否符合要求
     *
     * @param string $username 用户名
     * @return integer
     */
    public function checkUsername(string $username = ""):int {
        if (empty($username)) $username = $this->getUsername();
        $length = mb_strlen($username, "utf-8");
        
        if ($length < self::USER_NAME_MIN_LENGTH) {
            return self::STATUS_USER_NAME_SHORT;
        } elseif ($length > self::USER_NAME_MAX_LENGTH) {
            return self::STATUS_USER_NAME_LONG;
        } else {
            return self::STATUS_SUCCESS;
        }
    }
        
    /**
     * 检测用户密码是否符合要求
     *
     * @param string $password 用户密码
     * @return integer
     */
    public function checkPassword(string $password = ""):int {
        if (empty($password)) $password = $this->getPassword();
        $length = mb_strlen($password, "utf-8");
        
        if ($length < self::USER_PASSWORD_MIN_LENGTH) {
            return self::STATUS_USER_PASSWORD_SHORT;
        } elseif ($length > self::USER_PASSWORD_MAX_LENGTH) {
            return self::STATUS_USER_PASSWORD_LONG;
        } else {
            return self::STATUS_SUCCESS;
        }
    }
        
    /**
     * 加密字符串
     *
     * @param string $str 待加密的字符串
     * @return string
     */
    public static function encode(string $str):string {
        return password_hash($str, self::PASSWORD_ALGO, [
            'cost' => self::PASSWORD_COST
        ]);
    }
    
    /**
     * 验证明文是否与加密后的密文匹配
     *
     * @param string $str 明文
     * @param string $hash 密文
     * @return boolean
     */
    public static function verify(string $str, string $hash):bool {
        return password_verify($str, $hash);
    }

    /**
     * 通过用户UID获取用户名
     *
     * @param integer $uid
     * @return mixed
     */
    public static function getUsernameByUid(int $uid) {
        $database = Database::getInstance();
        $data = $database->select("account", "username", [
            "uid" => $uid,
            "LIMIT" => 1
        ]);

        return (empty($data)) ? false : $data[0];
    }

    /**
     * 通过用户名获取用户UID
     *
     * @param string $username
     * @return mixed
     */
    public static function getUidByUsername(string $username) {
        $database = Database::getInstance();
        $data = $database->select("account", "uid", [
            "username" => $username,
            "LIMIT" => 1
        ]);

        return (empty($data)) ? false : $data[0];
    }

    public static function setUserInfo(int $uid, array $data) {
        $database = Database::getInstance();
        $database->update("user_info", $data, [
            "uid" => $uid,
            "LIMIT" => 1
        ]);
    }

    public static function getUserInfo(int $uid, array $data) {
        $database = Database::getInstance();
        $data = $database->select("user_info", $data, [
            "uid" => $uid,
            "LIMIT" => 1
        ]);

        return $data[0];
    }

    public static function getAge(int $bYear, int $bMonth, int $bDay):int {
        // $birthday = getdate($timestamp);
        // $bYear = $birthday["year"];
        // $bMonth = $birthday["mon"];
        // $bDay = $birthday["mday"];
        $today = getdate();
        $tYear = $today["year"];
        $tMonth = $today["mon"];
        $tDay = $today["mday"];

        $age = $tYear -$bYear; //获得岁数(未考虑月，日)

        //如果当月还没到生日月 or 如果当月就是生日月，且当天仍未到生日
        if (($tMonth < $bMonth) or ($tMonth == $bMonth && $tDay < $bDay)) return --$age;

        return $age;
    }

    public static function getConstellation(int $month, int $date):int {
        // $constellations = [
        //     "水瓶座", "双鱼座", "白羊座", "金牛座", "双子座", "巨蟹座", 
        //     "狮子座", "处女座", "天秤座", "天蝎座", "射手座", "摩羯座"
        // ];
        $constellations = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    
        if ($date <= 22) {
            if ($month !== 1) {
                return $constellations[$month - 2];
            } else {
                return $constellations[11];
            }
    
        } else {
            return $constellations[$month - 1];
        }
    }
}
?>
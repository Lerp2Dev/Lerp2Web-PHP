<?php
/**
 * Created by PhpStorm.
 * User: Álvaro
 * Date: 09/08/2017
 * Time: 18:49
 */

//namespace Lerp2PHP;

include 'Loader.php';

class AppAjax extends Core
{
    public static function getAction()
    {
        AppLogger::$CurLogger->SetEventId(@$_REQUEST['action']);
        $req = $_SERVER['REQUEST_METHOD'];
        switch ($req) {
            case 'GET':
                if(isset($_GET["action"]))
                {
                    switch ($_GET["action"])
                    {
                        case "get-profile":
                            $username = @$_GET['username'];
                            $data = UserUtils::getStatsBy("username", $username);
                            if(!$data)
                                AppLogger::$CurLogger->AddError("wrong_username", $username);
                            else
                                AppLogger::$CurLogger->AddParameter("data", $data);
                            break;
                        default:
                            self::Kill("[GET] Action '".$_GET["action"]."' not set!");
                            break;
                    }
                }
                break;
            case 'POST':
                $authKey = @$_POST["k"];
                $entityKey = @$_POST["mk"];
                if(isset($authKey))
                { //There are the actions that need the key given to the app.
                    if($_POST["action"])
                    {
                        switch($_POST["action"]) //Voy a hacer que los cases esten mejor escritas, dos palabras la primera en miuscula y la segunda en mayuscula
                        {
                            case "logout":
                                break;
                            case "rememberAuth":
                                //Solicitar mi clave a través de la instance_key, si now - creation_date > valid_for, entonces, hacer un logout y pedir un nuevo login (el cual será automático, si, is_remembered es true)
                                break;
                            /*case 'getOnlinePeople':
                                $data = $_POST['data'];
                                $t = 120;
                                if(isset($data) && is_numeric($data)) {$t = $data*60;}
                                echo count(UserUtils::getOnlinePeople($t));
                                break;*/
                            default:
                                self::Kill(self::StrFormat("[POST] Action '".@$_POST["action"]."' not registered with '{0}' authkey defined!", $authKey));
                                break;
                        }
                    }
                }
                else if(isset($entityKey))
                {
                    if(isset($_POST["action"]))
                    {
                        switch($_POST["action"]) //Voy a hacer que los cases esten mejor escritas, dos palabras la primera en miuscula y la segunda en mayuscula
                        {
                            case "regen-auth":
                                //La solicitación se hará a través de una OldKey, la cual se comprobará si ya existia en la base de datos, y si era asi se procederá a entregar un nuevo login
                                //Es como llamar a create-auth (pero comprobando la antigua key)
                                break;
                            case "remember-auth":
                                //Este será el metodo que se llamará desde el timer cada minuto, si el valid_until es menor a PHP_NOW, entonces se devolverá un false, y en .NET habrá que llamar con otro post al regen-auth
                                //Si la opcion de remember estaba activada, si no se devolverá al usuario al login para que vuelva a poner sus datos
                                break;
                            case "register-entity":
                                $val = EntityUtils::RegisterEntity($entityKey);
                                if(isset($val))
                                    AppLogger::$CurLogger->AddParameter("data", null);
                                break;
                            case "create-auth":
                                $entId = EntityUtils::RegisterEntity($entityKey);
                                if(isset($entId))
                                {
                                    $authSha = ClientUtils::NewGuid();
                                    if (AuthUtils::RegisterAuth($entId, $authSha))
                                        AppLogger::$CurLogger->AddParameter("data", array("auth_key", $authSha));
                                }
                                break;
                            case "register-app-activity":

                                break;
                        }
                    }
                }
                else
                { //This actions doesn't need any key, because they give it... At least login
                    if(isset($_POST["action"]))
                    {
                        switch ($_POST["action"]) //Voy a hacer que los cases esten mejor escritas, dos palabras la primera en miuscula y la segunda en mayuscula
                        {
                            case "login":
                                //$expireTime = @$_POST['duration'];
                                $username = mysqli_escape_string(Database::conn(), @$_POST['username']);
                                $password = @$_POST['password'];
                                if(!UserActions::AppLogin($username, $password))
                                    Debug::Test(); //Restar aquí un intento de login... Aunq esto se puede hacer antes... Aquino se lo q voy a hacer
                                break;
                            case "register":
                                $username = mysqli_escape_string(Database::conn(), @$_POST['username']);
                                $password = @$_POST['password'];
                                $email = mysqli_escape_string(Database::conn(), @$_POST['email']);
                                //$cpass = @$_POST['pass_confirm'];
                                if(!UserActions::AppRegister($username, $password, $email))
                                    AppLogger::$CurLogger->AddError("error_registering_account");
                                else
                                    AppLogger::$CurLogger->AddParameter("data", "SUCCESS");
                                break;
                            default:
                                self::Kill(self::StrFormat("Improper action used '{0}' with no keys defined!"), $_POST["action"]);
                                break;
                        }
                    }
                }
                break;
            default:
                self::Kill(self::StrFormat("Undefined REQUEST_METHOD '{0}' used!", $req));
                /*
                    header("Location: /");
                    exit;
                 * */
                break;
        }
    }
}

//Do action...
AppAjax::getAction();
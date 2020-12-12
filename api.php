<?php

class Api extends Rest
{
    protected $servName;

    public function __construct($ser)
    {
        parent::__construct($ser);
    }

    public function generateToken()
    {
        $username = $this->validateParam('email', $this->param['user_name'], STRING);
        $pass = $this->validateParam('pass', $this->param['user_pass'], STRING);
        try {
            $stmt = $this->dbConn->prepare('select * from users where user_name =:username and user_pass =:password');
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $pass);

            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASS, null, 'Email or password is incorrect');
            }

            $payload = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (15 * 60),
                'userId' => $user['user_id']
            ];
            $token = JWT::encode($payload, SECRETE_KEY);
            $data = ['token' => $token];
            $this->returnResponse(SUCCESS_RESPONSE, $data);

        } catch (Exception $e) {
            $this->returnResponse(JWT_PROCESSING_ERROR, null, $e->getMessage());
        }
    }

    public function getUsers()
    {
        try {
            $stmt = $this->dbConn->prepare('select * from users');
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->returnResponse(SUCCESS_RESPONSE, $users);
        } catch (Exception $e) {
            $this->returnResponse(JWT_PROCESSING_ERROR, null, $e->getMessage());
        }
    }

    public function getModels()
    {

        if (isset($this->param['searchKey']) && !empty($this->param['searchKey'])) {
            $searchKey = $this->validateParam('searchKey', $this->param['searchKey'], STRING, true);
            $sql = 'select * from ceramic where cer_model LIKE \'' . $searchKey . '\'';
        } else {
            $sql = 'select * from ceramic';
        }


        try {
            $stmt = $this->dbConn->prepare($sql);
            $stmt->execute();
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->returnResponse(SUCCESS_RESPONSE, $models);
        } catch (Exception $e) {
            $this->returnResponse(JWT_PROCESSING_ERROR, null, $e->getMessage());
        }

    }
    function validateUserToken(){
        parent::validateToken();
        $this->returnResponse(SUCCESS_RESPONSE, null,null);
    }

}

?>
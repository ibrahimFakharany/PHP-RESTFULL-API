<?php
require_once('Constants.php');
require_once('DbConnection.php');

class Rest
{

    protected $request;
    protected $serviceName;
    protected $param;
    protected $dbConn;
    protected $userId;
    protected $uri;

    public function __construct($servName)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->returnResponse(REQUEST_METHOD_NOT_VALID, null, "request method not supported");
        }


        $handler = fopen("php://input", "r");
        $this->request = stream_get_contents($handler);

        $this->serviceName = $servName;
        $this->validateRequest();

        $db = new  DbConnection();
        $this->dbConn = $db->connect();

        if ('generatetoken' != strtolower($this->serviceName)) {
            $this->validateToken();
        }
    }

    public function validateRequest()
    {
        if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $this->returnResponse(REQUEST_CONTENTTYPE_NOT_VALID, null, 'Request content type not valid');
        }
        $data = json_decode($this->request, true);

        if (!is_array($data['param'])) {
            $this->returnResponse(API_PARAM_REQUIRED, null, 'API PARAM is required');
        }
        $this->param = $data['param'];
    }

    public function processApi()
    {

        $api = new API($this->serviceName);
        if (!method_exists($api, $this->serviceName)) {
            $this->returnResponse(API_DOST_NOT_EXIST, null, "API does not exist.");
        }
        $rMethod = new reflectionMethod('API', $this->serviceName);

        $rMethod->invoke($api);


    }

    public function validateParam($fieldname, $value = null, $dataType, $required = true)
    {
        if ($required == true && empty($value) == true) {
            $this->returnResponse(VALIDATE_PARAMETER_REQUIRED, null, $fieldname . "parameter is required");
        }
        switch ($dataType) {
            case BOOLEAN:
                if (!is_bool($value)) {
                    if ($required)
                        $this->returnResponse(VALIDATE_PARAMETER_DATATYPE, null, "Datatype is not valid for " . $fieldname . " It should be boolean");
                }
                break;
            case INTEGER:

                if (!is_integer($value)) {
                    if ($required)
                        $this->returnResponse(VALIDATE_PARAMETER_DATATYPE, null, "Datatype is not valid for " . $fieldname . " It should be numeric");
                }
                break;
            case STRING:
                if (!is_string($value)) {
                    if ($required )
                        $this->returnResponse(VALIDATE_PARAMETER_DATATYPE, null, "Datatype is not valid for " . $fieldname . " It should be string");
                }
                break;
            default:
                if ($required)
                    $this->returnResponse(VALIDATE_PARAMETER_DATATYPE, null, "Datatype is not valid for " . $fieldname);
                break;

        }

        return $value;
    }

    public function throwError($code, $message)
    {
        header('content-type: application/json');
        $errorMsg = json_encode(['error' => ['status' => $code, 'message' => $message]]);
        echo $errorMsg;
        exit;
    }

    public function returnResponse($code, $data, $message = null)
    {
        header("content-type: application/json");
        $response = json_encode(['response' => ['status' => $code, 'result' => $data, 'message' => $message]]);
        echo $response;
        exit;
    }

    public function validateToken()
    {
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE user_id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($user)) {
                $this->returnResponse(INVALID_USER_PASS, null, "This user is not found in our database.");
            }

            $this->userId = $payload->userId;
        } catch (Exception $e) {
            $this->returnResponse(ACCESS_TOKEN_ERRORS, null, $e->getMessage());
        }
    }

    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->returnResponse(ATHORIZATION_HEADER_NOT_FOUND, null, 'Access Token Not found');
    }
}

?>
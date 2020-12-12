<?php
    require_once('lib/AltoRouter.php');
    require_once('functions.php');
//    $api = new Api;
//    $api->processApi();


    $router = new AltoRouter();

    // setup routes
    $router->map('POST','/ceramicRest/generateToken', function(){
        $api = new Api('generateToken');
        $api->processApi();
    });
    $router->map('POST','/ceramicRest/getUsers', function(){
            $api = new Api('getUsers');
            $api->processApi();
        });
    $router->map('POST','/ceramicRest/getModels', function(){
                $api = new Api('getModels');
                $api->processApi();
            });
    $router->map('POST','/ceramicRest/validateUserToken', function(){
                $api = new Api('validateUserToken');
                $api->processApi();
            });


    $match = $router->match();
    if( $match && is_callable( $match['target'] ) ) {

        call_user_func_array( $match['target'], $match['params'] );
    } else {
        // no route was matched
        header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    }
?>
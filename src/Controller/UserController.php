<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

class UserController extends AbstractController
{
	private function resjson($data){
		//Serializar datos con servicios serializer
		$json = $this->get('serializer')->serialize($data, 'json');

		//Response con httpfoundation
		$response = new Response();

		//Asignar contenido a la respuesta
		$response->setContent($json);

		//Indicar formato de respuesta
		$response->headers->set('Content-Type', 'aplication/json');

		//Devolver la respuesta
		return $response;
	}
    
    public function index(): Response
    {
    	$user_repo = $this->getDoctrine()->getRepository(User::class);
    	$video_repo = $this->getDoctrine()->getRepository(Video::class);
		$videos = $video_repo->findAll();
    	$users = $user_repo->findAll();
    	$user = $user_repo->find(1);
    	$data = ([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    	/*
    	foreach($users as $user){
    		echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

    		foreach($user->getVideos() as $video) {
    			echo"<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
    		}

    	}*/


    	//die();
    	


        return $this->json($videos);
    }
    public function create(Request $request){
    	//Recoger los datos por post
    	$json = $request->get('json', null);

    	//Decodificar el json 
    	$params = json_decode($json);

    	//Respuesta por defecto
    	$data = [
    		'status' =>'error',
    		'code' => 200,
    		'message' =>'El usuario no se ha creado.'
    	];

    	//comprobar y validar datos
    	if($json !=null){
    		$name =(!empty($params->name))? $params->name:null;
    		$surname =(!empty($params->surname))? $params->surname:null;
    		$email=(!empty($params->email))? $params->email:null;
    		$password =(!empty($params->password))? $params->password:null;

    		$validator = Validation::createValidator();
    		$validate_email =$validator->validate($email,[
    			new Email()
    		]);
    		if(!empty($email) && count($validate_email)==0 && !empty($password) && !empty($name) && !empty($surname)){
    			//Si la validacion es correcta, crear el objeto del usuario
    			
    			$user = new User();
    			$user->setName($name);
    			$user->setSurname($name);
    			$user->setEmail($email);
    			$user->setRole('ROLE_USER');
    			$user->setCreatedAt(new \Datetime('now'));
		    	//cifrar la contraseña
		    	$pwd = hash('sha256', $password);
		    	$user->setPassword($pwd);

		    	$data= $user;	

		    	//comprobar si el usuario existe(duplicados
		    	$doctrine = $this->getDoctrine();
		    	$em = $doctrine->getManager();
		    	$user_repo =$doctrine->getRepository(User::class);
		    	$isset_user = $user_repo->findBy(array(
		    		'email'=>$email
		    	));
		    	//si no existe, guardarlo en la bd
		    	if(count($isset_user)==0){
		    		//guardar el usuario
		    		$em->persist($user);
		    		//guardame los datos del usuario e la bse de datos definiticamente
		    		$em->flush();


		    		$data = [
			    		'status' =>'success',
			    		'code' => 200,
			    		'message' =>'usuario creado correctamente.',
			    		'user' =>$user
			    	];
		    	}else{
		    		$data = [
			    		'status' =>'error',
			    		'code' => 400,
			    		'message' =>'El usuario ya existe'
			    	];

		    	}
    		}


    	}


    	

   

    	//Hacer rrespuesta en json
    	return $this->resjson($data);
    }
    public function login(Request $request, JwtAuth $jwt_auth){
    	//Recibir los dats por post
    	$json = $request->get('json', null);
    	$params = json_decode($json);

    	//Array por defecto para devolver
    	$data=[
    		'status'=>'error',
    		'code' =>200,
    		'message'=>'El usuario o se ha podido identificar'
    	];

    	//comprobar y validar datos
    	if($json != null){
    		$email = (!empty($params->email)) ? $params->email:null;
    		$password = (!empty($params->password)) ? $params->password : null;
    		$gettoken = (!empty($params->gettoken)) ? $params->gettoken:null;
    		$validator = Validation::createValidator();
    		$validate_email= $validator->validate($email, [
    			new Email()
    		]);
    		if(!empty($email) && !empty($password) && count($validate_email) ==0){
    			//cifrar la contraseña
    			$pwd = hash('sha256', $password);

		    	//si todo es valido , llamaremos a un servicio para identificar el usuario (jwt, token o un objeto)
    			if($gettoken){
    				$signup = $jwt_auth->signup($email, $pwd,$gettoken);
    			}else{
    				$signup =$jwt_auth->signup($email, $pwd);

    			}
    		return new JsonResponse($signup);
    		}
    	}

    	//si nos devuelve bien los datos, respuesta
		return $this->resjson($data);    			

    }
    public function edit(Request $request, JwtAuth $jwt_auth){
    	//Recoger la cabecera de autentificacion
    	$token = $request->headers->get('Authorization');

    	//crear un metodo para comprobar si el tken es correco 
    	$autCheck = $jwt_auth->checkToken($token);

    	//Respuesta por defecto
    	$data = [
    		'status' =>'error',
    		'code' => 400, 
    		'message' => 'Usuario no actualizado'
    		
    	];

    	//si es correcto , hacer la actualizacion del usuario
    	if($autCheck){
    	//Actualizar al usuario	


    	//Conseguir el entity manager
    	$em = $this->getDoctrine()->getManager();
    	//Conseguir los datos del usuario identificado
    	$identity = $jwt_auth->checkToken($token, true);
    	//conseguir el usuario a actualizar completo
    	$user_repo = $this->getDoctrine()->getRepository(User::class);
    	$user = $user_repo->findOneBy([
    		'id' => $identity->sub
    	]);
    	//recoger datos por post
    	$json = $request->get('json', null);
    	$params = json_decode($json);
    	//comprobar y avlidar datos
    	if(!empty($json)){
    		$name =(!empty($params->name))? $params->name:null;
    		$surname =(!empty($params->surname))? $params->surname:null;
    		$email=(!empty($params->email))? $params->email:null;

    		$validator = Validation::createValidator();
    		$validate_email =$validator->validate($email,[
    			new Email()
    		]);
    		if(!empty($email) && count($validate_email)==0 && !empty($name) && !empty($surname)){
	    		//Asiganr nuevos datos al objeto del usuario
	    		$user->setEmail($email);
	    		$user->setName($name);
	    		$user->setSurname($surname);

		    	//Comprobar duplicados
		    	$isset_user = $user_repo->findBy([
		    		'email' =>$email
		    	]);
		    	if(count($isset_user) == 0 || $identity->email ==$email){
		    		// Guardar cambios en la base de datos
		    		$em->persist($user);
		    		$em->flush();
		    		$data = [
			    		'status' =>'success',
			    		'code' =>200, 
			    		'message' => 'Usuario actualizado',
			    		'user' =>$user
			    		
    				];

		    	}else{
		    		$data = [
			    		'status' =>'error',
			    		'code' => 400, 
			    		'message' => 'No puede usar ese email.',
			    		
			    	];

		    	}

    		}

    	}
    }
    		
    	return $this->resjson($data);
    }
}

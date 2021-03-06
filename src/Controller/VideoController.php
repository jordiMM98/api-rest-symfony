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
use Knp\Component\Pager\PaginatorInterface;
class VideoController extends AbstractController
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
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }
    public function create(Request $request, JwtAuth $jwt_auth, $id = null ){
    	$data = [
    		'status' =>'error',
    		'code' =>400,
    		'message' =>'El video no se ha podido guardar.'    	
    	];
    	
    	//Recoger el token 
    	$token = $request->headers->get('Authorization', null);

    	//comproar si es correcto
    	$autCheck = $jwt_auth->checkToken($token);
    	
    	if($autCheck){
    		//Recoger datos por post
    		$json = $request->get('json', null);
    		$params = json_decode($json);

	    	//Recoger el objeto del usuario identificado
	    	$identity = $jwt_auth->checkToken($token, true);

	    	//comprobar y validar datos
	    	if(!empty($json)){
	    		$user_id = ($identity->sub != null) ? $identity->sub :null;
	    		$title = (!empty($params->title)) ? $params->title: null;
	    		$description = (!empty($params->description)) ? $params->description: null;
	    		$url = (!empty($params->url)) ? $params->url: null;

	    		if(!empty($user_id) && !empty($title)){
	    			//guardar el nuevo video favorito en la bd
	    			$em = $this->getDoctrine()->getManager();
	    			$user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
	    				'id' =>$user_id
	    			]);

	    			if($id == null){

		    			
		    			//Crear y guardar objeto
		    			$video = new Video();
		    			$video->setUser($user);
		    			$video->setTitle($title);
		    			$video->setDescription($description);
		    			$video->setUrl($url);
		    			$video->setStatus('normal');
		    			$createdAt = new \Datetime('now');
		    			$updatedAt = new \Datetime('now');
		    			$video->setCreatedAt($createdAt);
		    			$video->setUpdatedAt($updatedAt);

		    			//guardar en bd
		    			$em->persist($video);
		    			$em->flush();
		    			$data = [
				    		'status' =>'success',
				    		'code' =>200,
				    		'message' =>'El video se ha guardado.',
				    		'video' =>$video    	
				    	];
			    	}else{
			    		$video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
			    			'id' =>$id,
			    			'user' => $identity->sub
			    		]);
			    		if($video && is_object($video)){
			    			$video->setTitle($title);
			    			$video->setDescription($description);
			    			$video->setUrl($url);
			    			
			    			$updatedAt = new \Datetime('now');
			    		
			    			$video->setUpdatedAt($updatedAt);
			    			$em->persist($video);
			    			$em->flush();
			    			$data = [
				    		'status' =>'success',
				    		'code' =>200,
				    		'message' =>'El video se ha actualizado.',
				    		'video' =>$video    	
				    	];

			    		}

			    	}

	    		}

	    	}

    	}

    	
    	//devolver respuesta
    	

    	return $this->resjson($data);
    }
    public function videos(Request $request , JwtAuth $jwt_auth, PaginatorInterface $paginator){
    	//Recoger la cabecera de auntetificacion
    	$token = $request->headers->get('Authorization');

    	//Comprobar el token 
    	$autCheck =$jwt_auth->checkToken($token);

    	//Si es valido , 
    	if($autCheck){
    	//Conseguir la identidad del usuario
    		$identity= $jwt_auth->checkToken($token,true);
    		$em = $this->getDoctrine()->getManager();

    	//hacer una consulta para paginar
    		$dql = "SELECT v FROM App\Entity\Video v Where v.user = {$identity->sub} ORDER BY v.id DESC";
    		$query = $em->createQuery($dql);

    	//Recoger el arametro page de la url
    		$page = $request->query->getInt('page', 1);
    		$items_por_page = 5;

    	//Invocar paginaci??n
    		$pagination = $paginator->paginate($query, $page, $items_por_page);
    		$total = $pagination->getTotalItemCount();
    	//Preparar array de datos para devolver
    		$data= array (
	    		'status' => 'success',
	    		'code' => 200,
	    		'Total_items_count' =>$total,
	    		'page_atual'=>$page,
	    		'items_por_page' =>$items_por_page,
	    		'total_pages' =>ceil($total / $items_por_page),
	    		'videos' =>$pagination,
	    		'user_id'=>$identity->sub
	    	); 

    	}else{
    		//Si falla devolver esto
	    	$data= array (
	    		'status' => 'error',
	    		'code' => 404,
	    		'message '=>'No se ha podido listar los videos en este momento.'
	    	);

    	}
    	
    	return $this->resjson($data);

    }
    public function video(Request $request, JwtAuth $jwt_auth, $id = null){
    	//Sacara el token y comprobar si es correcto
    	$token = $request->headers->get('Authorization');
    	$autCheck = $jwt_auth->checkToken($token); 
    	//Devolver respuesta
	    	$data= [
	    		'status' =>'error',
	    		'code' =>404,
	    		'message' =>'Video no encontrado'
	    		
	    	];
    	if($autCheck){
	    	//sacr la identidad del usuario
	    	$identity = $jwt_auth->checkToken($token, true);

	    	//sacar el objeto del vdeo en base al id
	    	$video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
	    		'id' => $id
	    	]);

	    	///Comprobar si el video existe y es propiedad del usuario identificado 
	    	if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){
	    		$data = [
	    			'status' =>'success',
	    			'code' =>200,
	    			'video' => $video
	    		];

	    	}
    	}
    	
    	return $this->resjson($data);
    }
    public function remove(Request $request, JwtAuth $jwt_auth , $id = null){

    	$token = $request->headers->get('Authorization');
    	$autCheck = $jwt_auth->checkToken($token);
    	//devolver respuesta
    	$data= [
	    		'status' =>'error',
	    		'code' =>404,
	    		'message' =>'Video no encontrado'
	    		
	    	];
	    	if($autCheck){
	    		$identity = $jwt_auth->checkToken($token, true);

	    		$doctrine = $this->getDoctrine();
	    		$em = $doctrine->getManager();
	    		$video = $doctrine->getRepository(Video::class)->findOneBy(['id'=>$id]);
	    		if($video && is_object($video) && $identity->sub ==$video->getUser()->getId()){
	    			$em->remove($video);
	    			$em->flush();
	    			$data= [
			    		'status' =>'success',
			    		'code' =>200,
			    		'video' =>$video
			    		
			    	];

	    		}

	    	}
	    	return $this->resjson($data);
    }
}
